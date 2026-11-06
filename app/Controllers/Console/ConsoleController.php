<?php
declare(strict_types=1);

namespace ExamCore\Controllers\Console;

use ExamCore\Controllers\PortalController;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Database;
use ExamCore\Core\Database\Migrator;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Queue\Queue;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\View;
use ExamCore\Models\Attempt;
use ExamCore\Models\AuditLog;
use ExamCore\Models\School;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;
use ExamCore\Services\BackupService;
use Throwable;

/** Owner Console: dashboard, audit trail and system health. */
final class ConsoleController extends PortalController
{
    public function __construct(
        Container $container,
        View $view,
        Guard $guard,
        Session $session,
        Csrf $csrf,
        private readonly School $schools,
        private readonly User $users,
        private readonly Attempt $attempts,
        private readonly AuditLog $auditLogs,
        private readonly Queue $queue,
        private readonly Migrator $migrator,
        private readonly Database $database,
        private readonly AuditService $audit,
        private readonly BackupService $backups,
        private readonly \ExamCore\Models\DashboardStats $stats,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function dashboard(Request $request): Response
    {
        $byStatus = $this->schools->countByStatus();
        $byRole = $this->users->countByRole();
        $series = $this->attempts->dailyCounts(14);
        $backups = $this->backups->list();

        return $this->page($request, 'console/dashboard', [
            'area' => 'console',
            'title' => 'Dashboard',
            'schoolCounts' => $byStatus,
            'totalSchools' => array_sum($byStatus),
            'students' => $byRole['student'] ?? 0,
            'staff' => ($byRole['school_admin'] ?? 0) + ($byRole['teacher'] ?? 0) + ($byRole['reviewer'] ?? 0),
            'attemptSeries' => $series,
            'sittingSeries' => $this->stats->sittingsDaily(14),
            'enrolledSeries' => $this->stats->enrolledSeries(14),
            'live' => $this->stats->liveSittings(6),
            'liveCount' => $this->stats->liveCount(),
            'bankCount' => $this->stats->bankCount(),
            'attemptsToday' => $series[count($series) - 1]['count'] ?? 0,
            'attemptsTotal14' => array_sum(array_column($series, 'count')),
            'queue' => $this->queue->stats(),
            'recentSchools' => $this->schools->recent(5),
            'activity' => $this->auditLogs->search(null, '', 1, 8)['rows'],
            'lastBackup' => $backups[0] ?? null,
            'pendingMigrations' => count($this->migrator->pending()),
        ]);
    }

    public function audit(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $action = preg_replace('/[^a-z0-9_.]/', '', strtolower((string) $request->query('action', ''))) ?? '';
        $schoolId = filter_var($request->query('school'), FILTER_VALIDATE_INT) ?: null;
        $result = $this->auditLogs->search($schoolId, $action, $page, 30);
        return $this->page($request, 'console/audit', [
            'area' => 'console',
            'title' => 'Audit log',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'pageNumber' => $page,
            'perPage' => 30,
            'action' => $action,
            'schoolFilter' => $schoolId === null ? null : $this->schools->find($schoolId),
        ]);
    }

    public function system(Request $request): Response
    {
        $config = $this->service('config');
        try {
            $dbVersion = (string) $this->database->connection()->query('SELECT VERSION()')->fetchColumn();
        } catch (Throwable) {
            $dbVersion = 'unavailable';
        }
        $root = (string) $this->service('root');
        $scheduler = json_decode((string) @file_get_contents($root . '/storage/cache/scheduler.json'), true);

        return $this->page($request, 'console/system', [
            'area' => 'console',
            'title' => 'System',
            'checks' => [
                ['label' => 'PHP version', 'value' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.1.0', '>=')],
                ['label' => 'Database', 'value' => $dbVersion, 'ok' => $dbVersion !== 'unavailable'],
                ['label' => 'Environment', 'value' => (string) $config['app']['environment'] . ((bool) $config['app']['debug'] ? ' (debug on)' : ''), 'ok' => !((bool) $config['app']['debug'] && $config['app']['environment'] === 'production')],
                ['label' => 'HTTPS', 'value' => $this->isHttps() ? 'Enabled' : 'Not detected', 'ok' => $this->isHttps() || $config['app']['environment'] !== 'production'],
                ['label' => 'Email', 'value' => (bool) $config['mail']['enabled'] ? strtoupper((string) $config['mail']['transport']) . ' · ' . ($config['mail']['host'] ?: 'PHP mail()') : 'Disabled', 'ok' => true],
                ['label' => 'Storage writable', 'value' => is_writable($root . '/storage') ? 'Yes' : 'No', 'ok' => is_writable($root . '/storage')],
                ['label' => 'Uploads writable', 'value' => is_writable($root . '/public/uploads') ? 'Yes' : 'No', 'ok' => is_writable($root . '/public/uploads')],
                ['label' => 'Scheduler', 'value' => is_array($scheduler) && $scheduler !== [] ? 'Last run ' . time_ago(gmdate('Y-m-d H:i:s', (int) max($scheduler))) : 'Never run', 'ok' => is_array($scheduler) && time() - (int) max($scheduler ?: [0]) < 900],
                ['label' => 'Free disk space', 'value' => $this->formatBytes((float) @disk_free_space($root)), 'ok' => (float) @disk_free_space($root) > 500 * 1024 * 1024],
            ],
            'migrations' => $this->migrator->status(),
            'queue' => $this->queue->stats(),
            'failedJobs' => $this->queue->failed(15),
            'backups' => array_slice($this->backups->list(), 0, 10),
        ]);
    }

    public function backup(Request $request): Response
    {
        $path = $this->backups->create();
        $this->audit->record('system.backup', 'Created database backup ' . basename($path));
        return $this->redirectTo('/console/system', 'Backup created: ' . basename($path));
    }

    public function downloadBackup(Request $request, array $parameters): Response
    {
        $path = $this->backups->path((string) ($parameters['file'] ?? ''));
        if ($path === null) {
            throw HttpException::notFound('That backup does not exist.');
        }
        $this->audit->record('system.backup_download', 'Downloaded backup ' . basename($path));
        return new Response((string) file_get_contents($path), 200, [
            'Content-Type' => str_ends_with($path, '.gz') ? 'application/gzip' : 'application/sql',
            'Content-Disposition' => 'attachment; filename="' . basename($path) . '"',
        ]);
    }

    /** Lets owners on hosts without SSH apply updates. A backup is taken first, automatically. */
    public function migrate(Request $request): Response
    {
        if ($this->migrator->pending() === []) {
            return $this->redirectTo('/console/system', 'The database is already up to date.');
        }
        $backup = $this->backups->create();
        $ran = $this->migrator->migrate();
        $this->audit->record('system.migrate', 'Applied ' . count($ran) . ' database update(s)', meta: ['migrations' => $ran, 'backup' => basename($backup)]);
        return $this->redirectTo('/console/system', 'Applied ' . count($ran) . ' update(s). A backup was saved first: ' . basename($backup));
    }

    public function retryJob(Request $request, array $parameters): Response
    {
        $ok = $this->queue->retryFailed((int) ($parameters['id'] ?? 0));
        return $this->redirectTo('/console/system', $ok ? 'Job queued to run again.' : 'That job could not be retried.', $ok ? 'success' : 'error');
    }

    public function deleteJob(Request $request, array $parameters): Response
    {
        $ok = $this->queue->deleteFailed((int) ($parameters['id'] ?? 0));
        return $this->redirectTo('/console/system', $ok ? 'Failed job removed.' : 'That job could not be removed.', $ok ? 'success' : 'error');
    }

    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    private function formatBytes(float $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
            if ($bytes < 1024) {
                return number_format($bytes, $unit === 'B' ? 0 : 1) . ' ' . $unit;
            }
            $bytes /= 1024;
        }
        return number_format($bytes, 1) . ' PB';
    }
}
