<?php
declare(strict_types=1);

namespace ExamCore\Core;

use Throwable;

/**
 * Runs periodic maintenance tasks.
 *
 * Triggered by `php cbt schedule:run` from cron where available, and also by
 * ordinary web traffic (after the response is sent) so hosts without cron,
 * such as InfinityFree, still auto-submit expired exams and send queued email.
 * A non-blocking file lock guarantees only one run at a time.
 */
final class Scheduler
{
    /** @var array<string, array{every: int, task: callable(bool): string}> */
    private array $tasks = [];

    public function __construct(private readonly string $stateFile, private readonly Logger $logger)
    {
    }

    /** @param callable(bool $fromWeb): string $task Returns a short summary for the CLI. */
    public function every(int $seconds, string $name, callable $task): void
    {
        $this->tasks[$name] = ['every' => max(1, $seconds), 'task' => $task];
    }

    /**
     * Cheap entry point for web requests: a single stat() call when nothing is due.
     */
    public function tick(int $minimumGapSeconds = 60): void
    {
        clearstatcache(true, $this->stateFile);
        $lastRun = @filemtime($this->stateFile);
        if ($lastRun !== false && time() - $lastRun < $minimumGapSeconds) {
            return;
        }
        $this->run(fromWeb: true);
    }

    /** @return array<string, string> task name => summary, for tasks that ran */
    public function run(bool $fromWeb = false, bool $force = false): array
    {
        $directory = dirname($this->stateFile);
        if (!is_dir($directory) || !is_writable($directory)) {
            return [];
        }
        $lock = fopen($this->stateFile . '.lock', 'c');
        if ($lock === false) {
            return [];
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return [];
        }

        $results = [];
        try {
            $state = $this->readState();
            $now = time();
            foreach ($this->tasks as $name => $definition) {
                if (!$force && $now - (int) ($state[$name] ?? 0) < $definition['every']) {
                    continue;
                }
                $state[$name] = $now;
                try {
                    $results[$name] = ($definition['task'])($fromWeb);
                } catch (Throwable $exception) {
                    $results[$name] = 'failed: ' . $exception->getMessage();
                    $this->logger->log('error', 'Scheduled task failed.', [
                        'task' => $name,
                        'exception' => $exception::class,
                        'message' => $exception->getMessage(),
                        'file' => basename($exception->getFile()) . ':' . $exception->getLine(),
                    ]);
                }
            }
            // Written even when nothing was due, so web ticks stay a single stat() until the next gap.
            file_put_contents($this->stateFile, json_encode($state, JSON_THROW_ON_ERROR), LOCK_EX);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        return $results;
    }

    /** @return array<string, int> */
    private function readState(): array
    {
        $raw = @file_get_contents($this->stateFile);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }
}
