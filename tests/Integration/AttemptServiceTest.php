<?php
declare(strict_types=1);

namespace ExamCore\Tests\Integration;

use ExamCore\Core\Database;
use ExamCore\Core\Database\Migrator;
use ExamCore\Core\Environment;
use ExamCore\Core\Queue\Queue;
use ExamCore\Models\Attempt;
use ExamCore\Models\AttemptAnswer;
use ExamCore\Models\Exam;
use ExamCore\Models\Question;
use ExamCore\Services\AttemptService;
use ExamCore\Services\Exceptions\AttemptException;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Runs the real attempt lifecycle against a throwaway database (DB_TEST_DATABASE,
 * default "<DB_DATABASE>_test"), built from the migrations. Never touches live data.
 */
final class AttemptServiceTest extends TestCase
{
    private static ?Database $database = null;
    private AttemptService $service;
    private Queue $queue;
    private string $session;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 2);
        Environment::load($root . '/.env');
        $base = require $root . '/config/database.php';
        $name = env('DB_TEST_DATABASE') ?: ($base['database'] !== '' ? $base['database'] . '_test' : '');
        if ($name === '' || !preg_match('/^[A-Za-z0-9_]+_test$/', $name)) {
            return; // Refuse to run against anything not clearly a test database.
        }
        try {
            $server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $base['host'], $base['port']), $base['username'], $base['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $server->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        } catch (Throwable) {
            return;
        }
        self::$database = new Database(['database' => $name] + $base);
        (new Migrator(self::$database, $root . '/database/migrations'))->migrate();
    }

    protected function setUp(): void
    {
        if (self::$database === null) {
            self::markTestSkipped('Test database is not reachable.');
        }
        $pdo = self::$database->connection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['attempt_answers', 'attempts', 'questions', 'exams', 'subjects', 'exam_types', 'jobs'] as $table) {
            $pdo->exec('TRUNCATE TABLE `cbt_' . $table . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $pdo->exec("INSERT INTO cbt_exam_types (id, slug, name) VALUES (1, 'jamb', 'JAMB')");
        $pdo->exec("INSERT INTO cbt_subjects (id, slug, name) VALUES (1, 'english', 'English'), (2, 'physics', 'Physics')");
        $pdo->exec("INSERT INTO cbt_exams (id, exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active) VALUES
            (1, 1, 1, 2024, 'JAMB English 2024', 30, 3, 1),
            (2, 1, 2, 2024, 'JAMB Physics 2024', 20, 2, 1),
            (3, 1, 2, 2023, 'JAMB Physics 2023 (retired)', 20, 2, 0)");
        $question = $pdo->prepare('INSERT INTO cbt_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, sort_order) VALUES (?, ?, \'a\', \'b\', \'c\', \'d\', ?, \'why\', ?, ?)');
        foreach ([[1, 'E1', 'A', 1, 1], [1, 'E2', 'B', 1, 2], [1, 'E3', 'C', 2, 3], [2, 'P1', 'D', 1, 1], [2, 'P2', 'A', 1, 2], [3, 'R1', 'A', 1, 1], [3, 'R2', 'A', 1, 2]] as $row) {
            $question->execute($row);
        }

        $this->queue = new Queue(self::$database);
        $this->service = new AttemptService(
            self::$database,
            new Exam(self::$database),
            new Question(self::$database),
            new Attempt(self::$database),
            new AttemptAnswer(self::$database),
            $this->queue,
        );
        $this->session = bin2hex(random_bytes(32));
    }

    public function testRunnerNeverExposesAnswers(): void
    {
        $attempt = $this->service->startAttempt(1, $this->session, 'Ada', 'ada@example.com');
        $runner = $this->service->getRunnerData($attempt['attempt_id'], $this->session);

        self::assertCount(3, $runner['questions']);
        foreach ($runner['questions'] as $question) {
            self::assertArrayNotHasKey('correct_option', $question);
            self::assertArrayNotHasKey('explanation', $question);
        }
        self::assertGreaterThan(29 * 60, $runner['remaining_seconds']);
    }

    public function testGradingUsesMarksAndIsIdempotent(): void
    {
        $id = $this->service->startAttempt(1, $this->session, 'Ada', 'ada@example.com')['attempt_id'];
        $ids = $this->questionIdsByText();
        $this->service->saveAnswer($id, $ids['E1'], 'A', $this->session); // correct, 1 mark
        $this->service->saveAnswer($id, $ids['E2'], 'D', $this->session); // wrong
        $this->service->saveAnswer($id, $ids['E3'], 'B', $this->session);
        $this->service->saveAnswer($id, $ids['E3'], 'C', $this->session); // changed to correct, 2 marks

        $first = $this->service->submitAttempt($id, $this->session);
        $second = $this->service->submitAttempt($id, $this->session);

        self::assertSame('submitted', $first['status']);
        self::assertEquals(3.0, (float) $first['score']);
        self::assertEquals(4.0, (float) $first['total']);
        self::assertSame($first['submitted_at'], $second['submitted_at'], 'A second submit must not regrade.');
        self::assertSame(1, $this->queue->stats()['pending'], 'Exactly one result email is queued.');

        $review = $this->service->getReview($id, $this->session);
        self::assertCount(3, $review['questions']);
        self::assertSame('A', $review['questions'][array_search('E1', array_column($review['questions'], 'question_text'), true)]['correct_option']);
    }

    public function testMultiSubjectSittingCombinesPapers(): void
    {
        $attempt = $this->service->startAttempt(1, $this->session, 'Ada', 'ada@example.com', null, [2]);
        $runner = $this->service->getRunnerData($attempt['attempt_id'], $this->session);

        self::assertSame([1, 2], $attempt['exam_ids']);
        self::assertSame(5, $attempt['question_count']);
        self::assertSame(50, $attempt['duration_minutes'], 'Durations add up across papers.');
        // Papers stay grouped: the first three questions are all English.
        self::assertSame([1, 1, 1, 2, 2], array_map(static fn (array $q): int => (int) $q['exam_id'], $runner['questions']));

        $ids = $this->questionIdsByText();
        $this->service->saveAnswer($attempt['attempt_id'], $ids['P1'], 'D', $this->session);
        self::assertEquals(1.0, (float) $this->service->submitAttempt($attempt['attempt_id'], $this->session)['score']);
    }

    public function testRejectsQuestionsFromOtherPapers(): void
    {
        $id = $this->service->startAttempt(1, $this->session, 'Ada', 'ada@example.com')['attempt_id'];
        $this->expectExceptionObject(AttemptException::invalidAnswer());
        $this->service->saveAnswer($id, $this->questionIdsByText()['P1'], 'A', $this->session);
    }

    public function testAnotherSessionCannotTouchTheAttempt(): void
    {
        $id = $this->service->startAttempt(1, $this->session, 'Ada', 'ada@example.com')['attempt_id'];
        $this->expectException(AttemptException::class);
        $this->service->getRunnerData($id, bin2hex(random_bytes(32)));
    }

    public function testRetiredExamIsUnavailable(): void
    {
        try {
            $this->service->startAttempt(3, $this->session, 'Ada', 'ada@example.com');
            self::fail('A retired exam must not start.');
        } catch (AttemptException $exception) {
            self::assertSame(AttemptException::UNAVAILABLE, $exception->reason());
        }
    }

    public function testTooManyPapersIsRejected(): void
    {
        $this->expectExceptionObject(AttemptException::tooManyPapers(AttemptService::MAX_PAPERS_PER_SITTING));
        $this->service->startAttempt(1, $this->session, 'Ada', 'ada@example.com', null, [2, 4, 5, 6]);
    }

    public function testSchedulerAutoSubmitsAbandonedAttempts(): void
    {
        $id = $this->service->startAttempt(1, $this->session, 'Ada', 'ada@example.com')['attempt_id'];
        $this->service->saveAnswer($id, $this->questionIdsByText()['E1'], 'A', $this->session);
        // Pretend the student started 31 minutes ago and closed the tab.
        self::$database->connection()->prepare('UPDATE cbt_attempts SET started_at = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d H:i:s', time() - 31 * 60 - 130), $id]);

        self::assertSame(1, $this->service->expireOverdue(120));
        self::assertSame(0, $this->service->expireOverdue(120), 'Already finalized attempts are left alone.');

        $review = $this->service->getReview($id, $this->session);
        self::assertSame('timed_out', $review['attempt']['status']);
        self::assertEquals(1.0, (float) $review['attempt']['score'], 'Answers saved before the timeout still count.');
    }

    public function testSavingAfterTimeUpReportsExpiry(): void
    {
        $id = $this->service->startAttempt(1, $this->session, 'Ada', 'ada@example.com')['attempt_id'];
        self::$database->connection()->prepare('UPDATE cbt_attempts SET started_at = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d H:i:s', time() - 31 * 60), $id]);

        $result = $this->service->saveAnswer($id, $this->questionIdsByText()['E1'], 'A', $this->session);
        self::assertSame(['saved' => false, 'expired' => true], $result);
    }

    /** @return array<string, int> */
    private function questionIdsByText(): array
    {
        $rows = self::$database->connection()->query('SELECT question_text, id FROM cbt_questions')->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_map('intval', $rows);
    }
}
