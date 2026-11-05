<?php
declare(strict_types=1);

namespace ExamCore\Console;

use ExamCore\Core\Container;
use ExamCore\Core\Crypto;
use ExamCore\Models\User;
use ExamCore\Services\Auth\PasswordPolicy;
use ExamCore\Core\Database;
use ExamCore\Core\Database\Migrator;
use ExamCore\Core\Queue\Queue;
use ExamCore\Core\Queue\Worker;
use ExamCore\Core\Scheduler;
use ExamCore\Services\AttemptService;
use ExamCore\Services\BackupService;
use Throwable;

/** `php cbt <command>`: maintenance commands for local use, cron and deploys. */
final class Kernel
{
    /** @var array<string, array{0: string, 1: callable(array<string, string>): int}> */
    private array $commands;

    public function __construct(private readonly Container $container)
    {
        $this->commands = [
            'migrate' => ['Run pending database migrations', fn (array $o): int => $this->migrate()],
            'migrate:status' => ['List migrations and whether they have run', fn (array $o): int => $this->migrateStatus()],
            'migrate:rollback' => ['Roll back the last batch of migrations', fn (array $o): int => $this->rollback()],
            'schedule:run' => ['Run due maintenance tasks (add to cron: * * * * *). --force runs all', fn (array $o): int => $this->schedule(isset($o['force']))],
            'queue:work' => ['Process queued jobs. --max-jobs=50 --max-time=50', fn (array $o): int => $this->work((int) ($o['max-jobs'] ?? 50), (float) ($o['max-time'] ?? 50))],
            'queue:status' => ['Show pending, running and failed job counts', fn (array $o): int => $this->queueStatus()],
            'attempts:expire' => ['Auto-submit attempts whose time has run out', fn (array $o): int => $this->expire()],
            'db:backup' => ['Write a compressed SQL backup to storage/backups', fn (array $o): int => $this->backup()],
            'key:generate' => ['Create APP_KEY and SETUP_TOKEN in .env if missing (never overwrites)', fn (array $o): int => $this->generateKeys()],
            'bank:seed-starter' => ['Load the original starter questions into the platform bank and public practice sets', fn (array $o): int => $this->seedStarter()],
            'bank:seed-syllabus' => ['Load the syllabus-based practice questions (database/seeds/syllabus) into the platform bank; safe to re-run', fn (array $o): int => $this->seedSyllabus()],
            'bank:build-sets' => ['Build year-named public practice sets (2005-2026) from the bank. --board=jamb|waec|neco --subject="Biology" --from=2005 --to=2026', fn (array $o): int => $this->buildSets($o)],
            'owner:create' => ['Create an Owner Console account. --name="Ada Obi" --email=you@x.ng [--password=...]', fn (array $o): int => $this->createOwner($o)],
        ];
    }

    /** @param list<string> $argv */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'help';
        $options = [];
        foreach (array_slice($argv, 2) as $argument) {
            if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $argument, $match)) {
                $options[$match[1]] = $match[2] ?? '1';
            }
        }
        if (!isset($this->commands[$name])) {
            $this->help($name === 'help' ? null : $name);
            return $name === 'help' ? 0 : 1;
        }
        try {
            return ($this->commands[$name][1])($options);
        } catch (Throwable $exception) {
            $this->line("\033[31mError:\033[0m " . $exception->getMessage());
            $previous = $exception->getPrevious();
            if ($previous !== null) {
                $this->line('  Caused by: ' . $previous->getMessage());
            }
            return 1;
        }
    }

    private function migrate(): int
    {
        $ran = $this->container->get(Migrator::class)->migrate(fn (string $name) => $this->line("  Migrating: {$name}"));
        $this->line($ran === [] ? 'Nothing to migrate. Database is up to date.' : "\033[32mMigrated " . count($ran) . " migration(s).\033[0m");
        return 0;
    }

    private function migrateStatus(): int
    {
        foreach ($this->container->get(Migrator::class)->status() as $row) {
            $state = $row['batch'] === null ? "\033[33mPending\033[0m" : "\033[32mRan\033[0m (batch {$row['batch']})";
            $this->line(sprintf('  %-60s %s', $row['migration'], $state));
        }
        return 0;
    }

    private function rollback(): int
    {
        $done = $this->container->get(Migrator::class)->rollback(fn (string $name) => $this->line("  Rolling back: {$name}"));
        $this->line($done === [] ? 'Nothing to roll back.' : 'Rolled back ' . count($done) . ' migration(s).');
        return 0;
    }

    private function schedule(bool $force): int
    {
        $results = $this->container->get(Scheduler::class)->run(fromWeb: false, force: $force);
        if ($results === []) {
            $this->line('No tasks were due (or another run is in progress).');
        }
        foreach ($results as $task => $summary) {
            $this->line(sprintf('  %-22s %s', $task, $summary));
        }
        return 0;
    }

    private function work(int $maxJobs, float $maxSeconds): int
    {
        $processed = $this->container->get(Worker::class)->work(max(1, $maxJobs), max(1.0, $maxSeconds));
        $this->line("Processed {$processed} job(s).");
        return 0;
    }

    private function queueStatus(): int
    {
        $stats = $this->container->get(Queue::class)->stats();
        $this->line("Pending: {$stats['pending']}  Running: {$stats['reserved']}  Failed: {$stats['failed']}");
        return 0;
    }

    private function expire(): int
    {
        $count = $this->container->get(AttemptService::class)->expireOverdue(120, 500);
        $this->line("Auto-submitted {$count} overdue attempt(s).");
        return 0;
    }

    private function backup(): int
    {
        $service = new BackupService($this->container->get(Database::class), $this->container->get('root') . '/storage/backups');
        $path = $service->create();
        $this->line("\033[32mBackup written:\033[0m {$path} (" . number_format((float) filesize($path) / 1024, 1) . ' KB)');
        return 0;
    }

    private function generateKeys(): int
    {
        $env = $this->container->get('root') . '/.env';
        $contents = is_file($env) ? (string) file_get_contents($env) : '';
        $added = [];
        foreach (['APP_KEY' => Crypto::generateKey(), 'SETUP_TOKEN' => bin2hex(random_bytes(24))] as $name => $value) {
            if (preg_match('/^' . $name . '=\S+/m', $contents)) {
                continue; // Never overwrite: changing APP_KEY would make encrypted 2FA secrets unreadable.
            }
            $contents = preg_replace('/^' . $name . '=.*$/m', '', $contents) ?? $contents;
            $contents = rtrim($contents) . PHP_EOL . $name . '=' . $value . PHP_EOL;
            $added[] = $name;
        }
        if ($added === []) {
            $this->line('APP_KEY and SETUP_TOKEN already exist. Nothing changed.');
            return 0;
        }
        file_put_contents($env, $contents, LOCK_EX);
        $this->line("\033[32mAdded to .env:\033[0m " . implode(', ', $added) . '. Back up your .env: APP_KEY cannot be recovered if lost.');
        return 0;
    }

    /** @param array<string, string> $options */
    private function createOwner(array $options): int
    {
        $name = trim($options['name'] ?? '');
        $email = trim($options['email'] ?? '');
        $password = $options['password'] ?? PasswordPolicy::temporary() . PasswordPolicy::temporary();
        if (mb_strlen($name) < 2 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->line('Usage: php cbt owner:create --name="Your Name" --email=you@example.com [--password=...]');
            return 1;
        }
        if (($error = PasswordPolicy::validate($password, 10, $name, $email)) !== null) {
            $this->line("\033[31mPassword rejected:\033[0m " . $error);
            return 1;
        }
        $users = $this->container->get(User::class);
        if ($users->findOwnerByEmail($email) !== null) {
            $this->line('An owner with that email already exists.');
            return 1;
        }
        $users->create(['role' => User::ROLE_OWNER, 'name' => $name, 'email' => $email, 'password_hash' => PasswordPolicy::hash($password)]);
        $this->line("\033[32mOwner created:\033[0m {$email}");
        if (!isset($options['password'])) {
            $this->line("Generated password (shown once): {$password}");
        }
        $this->line('Sign in at /console/login');
        return 0;
    }

    /** Platform-bank questions written against the official syllabus topics. Duplicates are skipped by fingerprint. */
    private function seedSyllabus(): int
    {
        $dir = $this->container->get('root') . '/database/seeds/syllabus';
        $bank = $this->container->get(\ExamCore\Models\BankQuestion::class);
        $pdo = $this->container->get(Database::class)->connection();
        $owner = (int) $pdo->query("SELECT id FROM cbt_users WHERE role = 'owner' ORDER BY id LIMIT 1")->fetchColumn() ?: null;
        $files = glob($dir . '/[0-9]*.php') ?: [];
        sort($files);
        $total = 0;
        $skipped = 0;
        foreach ($files as $file) {
            foreach ((require $file) as $subject => $questions) {
                $added = 0;
                foreach ($questions as [$topic, $difficulty, $body, $a, $b, $c, $d, $answer, $explanation]) {
                    $options = [$a, $b, $c, $d];
                    if (count(array_unique($options)) !== 4 || !in_array($answer, ['A', 'B', 'C', 'D'], true) || mb_strlen($body) < 8) {
                        $this->line("  skipped an invalid question in {$subject}: " . mb_substr($body, 0, 60));
                        $skipped++;
                        continue;
                    }
                    $fingerprint = \ExamCore\Services\QuestionBankService::fingerprint($body . '|' . implode('|', $options));
                    if ($bank->duplicateOf(null, $fingerprint) !== null) {
                        continue;
                    }
                    $bank->create(null, (int) $owner, ['subject' => $subject, 'topic' => $topic, 'difficulty' => $difficulty, 'level' => 'ss', 'source' => 'practice', 'body' => $body,
                        'option_a' => $a, 'option_b' => $b, 'option_c' => $c, 'option_d' => $d, 'correct_option' => $answer, 'explanation' => $explanation, 'marks' => 1,
                        'status' => 'published', 'fingerprint' => $fingerprint]);
                    $added++;
                }
                $total += $added;
                $this->line(sprintf('  %-24s +%d  (%s)', $subject, $added, basename($file)));
            }
        }
        $this->line("Added {$total} question(s)" . ($skipped ? ", skipped {$skipped} invalid" : '') . '.');
        return 0;
    }

    /**
     * Public practice series for the free home page: one set per board × subject × year label. The questions are
     * ORIGINAL syllabus-based bank questions (never real past papers); the year is only the series label.
     * Stable and idempotent: the same board/subject/year always picks the same questions, existing sets are kept.
     *
     * @param array<string,string> $o
     */
    private function buildSets(array $o): int
    {
        $pdo = $this->container->get(Database::class)->connection();
        $boards = ['jamb' => ['JAMB', 40, 0.75], 'waec' => ['WAEC', 50, 1.2], 'neco' => ['NECO', 50, 1.2]];
        $only = strtolower($o['board'] ?? '');
        $from = max(2005, (int) ($o['from'] ?? 2005));
        $to = min(2026, (int) ($o['to'] ?? 2026));
        $typeIds = $pdo->query('SELECT slug, id FROM cbt_exam_types')->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Retire the first-generation starter sets (their year was just the creation year); delete when nobody has attempted them.
        $pdo->exec("UPDATE cbt_exams SET is_active = 0 WHERE title LIKE 'WAEC-style % Practice Set 1'");
        $pdo->exec("DELETE q FROM cbt_questions q JOIN cbt_exams e ON e.id = q.exam_id WHERE e.title LIKE 'WAEC-style % Practice Set 1' AND NOT EXISTS (SELECT 1 FROM cbt_attempts a WHERE a.exam_id = e.id)");
        $pdo->exec("DELETE e FROM cbt_exams e WHERE e.title LIKE 'WAEC-style % Practice Set 1' AND NOT EXISTS (SELECT 1 FROM cbt_attempts a WHERE a.exam_id = e.id)");

        $subjects = $pdo->query("SELECT DISTINCT subject FROM cbt_bank_questions WHERE school_id IS NULL AND status = 'published' AND passage_id IS NULL AND image_path IS NULL")->fetchAll(\PDO::FETCH_COLUMN);
        $findSubject = $pdo->prepare('SELECT id FROM cbt_subjects WHERE LOWER(name) = LOWER(?) LIMIT 1');
        $newSubject = $pdo->prepare('INSERT INTO cbt_subjects (slug, name, icon, is_active) VALUES (?, ?, ?, 1)');
        $exists = $pdo->prepare('SELECT 1 FROM cbt_exams WHERE exam_type_id = ? AND subject_id = ? AND year = ? LIMIT 1');
        $addExam = $pdo->prepare('INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $addQuestion = $pdo->prepare('INSERT INTO cbt_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
        $made = 0;

        foreach ($subjects as $subject) {
            $pool = $pdo->prepare("SELECT id, topic, body, option_a, option_b, option_c, option_d, correct_option, explanation FROM cbt_bank_questions WHERE school_id IS NULL AND status = 'published' AND passage_id IS NULL AND image_path IS NULL AND subject = ? ORDER BY id");
            $pool->execute([$subject]);
            $rows = $pool->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) < 15) {
                continue;
            }
            $findSubject->execute([$subject]);
            $subjectId = (int) $findSubject->fetchColumn();
            if ($subjectId === 0) {
                $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($subject)), '-');
                $newSubject->execute([$slug, $subject, 'chart']);
                $subjectId = (int) $pdo->lastInsertId();
            }
            foreach ($boards as $key => [$label, $base, $perQuestion]) {
                if ($only !== '' && $only !== $key) {
                    continue;
                }
                $size = (int) min($base, max(15, floor(count($rows) * 0.6)));
                $minutes = max(10, (int) ceil($size * $perQuestion));
                for ($year = $from; $year <= $to; $year++) {
                    $title = "{$label}-style {$subject} Practice Series {$year}";
                    $exists->execute([$typeIds[$key], $subjectId, $year]);
                    if ($exists->fetchColumn()) {
                        continue;
                    }
                    $picked = self::pickSet($rows, $size, crc32("{$key}|{$subject}|{$year}"));
                    $pdo->beginTransaction();
                    $addExam->execute([$typeIds[$key], $subjectId, $year, $title, $minutes, count($picked)]);
                    $examId = (int) $pdo->lastInsertId();
                    foreach ($picked as $n => $q) {
                        $addQuestion->execute([$examId, $q['body'], $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['correct_option'], $q['explanation'], $n + 1]);
                    }
                    $pdo->commit();
                    $made++;
                }
                $this->line(sprintf('  %-5s %-26s %d questions per set', $label, $subject, $size));
            }
        }
        $this->line("Built {$made} new practice set(s).");
        return 0;
    }

    /**
     * Topic-balanced, seeded pick: shuffle each topic and the topic order, then deal round-robin.
     *
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private static function pickSet(array $rows, int $size, int $seed): array
    {
        mt_srand($seed);
        $byTopic = [];
        foreach ($rows as $r) {
            $byTopic[(string) $r['topic']][] = $r;
        }
        ksort($byTopic);
        foreach ($byTopic as &$list) {
            for ($i = count($list) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                [$list[$i], $list[$j]] = [$list[$j], $list[$i]];
            }
        }
        unset($list);
        $topics = array_keys($byTopic);
        for ($i = count($topics) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$topics[$i], $topics[$j]] = [$topics[$j], $topics[$i]];
        }
        $out = [];
        while (count($out) < $size) {
            $progress = false;
            foreach ($topics as $t) {
                if ($byTopic[$t] !== [] && count($out) < $size) {
                    $out[] = array_pop($byTopic[$t]);
                    $progress = true;
                }
            }
            if (!$progress) {
                break;
            }
        }
        for ($i = count($out) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$out[$i], $out[$j]] = [$out[$j], $out[$i]];
        }
        return $out;
    }

    private function seedStarter(): int
    {
        $sets = require $this->container->get('root') . '/database/seeds/starter_questions.php';
        $bank = $this->container->get(\ExamCore\Models\BankQuestion::class);
        $pdo = $this->container->get(Database::class)->connection();
        $waec = (int) $pdo->query("SELECT id FROM cbt_exam_types WHERE slug = 'waec'")->fetchColumn();
        $owner = (int) $pdo->query("SELECT id FROM cbt_users WHERE role = 'owner' ORDER BY id LIMIT 1")->fetchColumn();
        foreach ($sets as $subject => $questions) {
            $added = 0;
            foreach ($questions as [$topic, $difficulty, $body, $a, $b, $c, $d, $answer, $explanation]) {
                $fingerprint = \ExamCore\Services\QuestionBankService::fingerprint($body);
                if ($bank->duplicateOf(null, $fingerprint) === null) {
                    $bank->create(null, $owner, ['subject' => $subject, 'topic' => $topic, 'difficulty' => $difficulty, 'level' => 'ss', 'source' => 'practice', 'body' => $body,
                        'option_a' => $a, 'option_b' => $b, 'option_c' => $c, 'option_d' => $d, 'correct_option' => $answer, 'explanation' => $explanation, 'marks' => 1,
                        'status' => 'published', 'fingerprint' => $fingerprint]);
                    $added++;
                }
            }
            // Public practice site (legacy simulator tables) until the practice centre uses the bank directly.
            $subjectId = $pdo->prepare('SELECT id FROM cbt_subjects WHERE LOWER(name) = LOWER(?) LIMIT 1');
            $subjectId->execute([$subject]);
            $subjectId = (int) $subjectId->fetchColumn();
            $title = 'WAEC-style ' . $subject . ' Practice Set 1';
            $exists = $pdo->prepare('SELECT COUNT(*) FROM cbt_exams WHERE title = ?');
            $exists->execute([$title]);
            $practice = 'skipped';
            if ($waec > 0 && $subjectId > 0 && (int) $exists->fetchColumn() === 0) {
                $pdo->prepare('INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)')
                    ->execute([$waec, $subjectId, (int) gmdate('Y'), $title, 20, count($questions)]);
                $examId = (int) $pdo->lastInsertId();
                $insert = $pdo->prepare('INSERT INTO cbt_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)');
                foreach ($questions as $i => [, , $body, $a, $b, $c, $d, $answer, $explanation]) {
                    $insert->execute([$examId, $body, $a, $b, $c, $d, $answer, $explanation, $i + 1]);
                }
                $practice = 'created';
            }
            $this->line(sprintf('  %-18s bank +%d · practice set %s', $subject, $added, $practice));
        }
        return 0;
    }

    private function help(?string $unknown): void
    {
        if ($unknown !== null) {
            $this->line("\033[31mUnknown command:\033[0m {$unknown}\n");
        }
        $this->line("ExamCore CBT console\n\nUsage: php cbt <command> [--options]\n\nCommands:");
        foreach ($this->commands as $name => [$description]) {
            $this->line(sprintf('  %-20s %s', $name, $description));
        }
    }

    private function line(string $text): void
    {
        $plain = env('NO_COLOR') !== false || !stream_isatty(STDOUT);
        fwrite(STDOUT, ($plain ? preg_replace('/\033\[[0-9;]*m/', '', $text) : $text) . PHP_EOL);
    }
}
