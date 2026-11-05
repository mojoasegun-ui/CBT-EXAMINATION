<?php
declare(strict_types=1);

namespace ExamCore\Tests\Integration;

use ExamCore\Core\Container;
use ExamCore\Core\Crypto;
use ExamCore\Core\Database;
use ExamCore\Core\Database\Migrator;
use ExamCore\Core\Environment;
use ExamCore\Core\ImageUpload;
use ExamCore\Core\Logger;
use ExamCore\Core\Session;
use ExamCore\Core\Spreadsheet\Spreadsheet;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\Student;
use ExamCore\Services\Auth\AuthService;
use ExamCore\Services\SchoolService;
use ExamCore\Services\SchoolSetupService;
use ExamCore\Services\StudentService;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/** School setup, students, imports and — most importantly — isolation between schools. */
final class SchoolStructureTest extends TestCase
{
    private static ?Database $database = null;
    private static string $sessionDir = '';
    private Container $c;
    private int $schoolA;
    private int $schoolB;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 2);
        Environment::load($root . '/.env');
        $base = require $root . '/config/database.php';
        $name = env('DB_TEST_DATABASE') ?: ($base['database'] !== '' ? $base['database'] . '_test' : '');
        if (!preg_match('/^[A-Za-z0-9_]+_test$/', $name)) {
            return;
        }
        try {
            $server = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $base['host'], $base['port']), $base['username'], $base['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $server->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            self::$database = new Database(['database' => $name] + $base);
            (new Migrator(self::$database, $root . '/database/migrations'))->migrate();
        } catch (Throwable) {
            self::$database = null;
        }
        self::$sessionDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'examcore-it-' . bin2hex(random_bytes(4));
        @mkdir(self::$sessionDir, 0700, true);
    }

    protected function setUp(): void
    {
        if (self::$database === null) {
            self::markTestSkipped('Test database is not reachable.');
        }
        $pdo = self::$database->connection();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['import_batches', 'grading_scales', 'staff_profiles', 'student_profiles', 'class_subjects', 'school_subjects', 'school_classes', 'departments', 'terms', 'academic_sessions', 'audit_logs', 'users', 'schools'] as $table) {
            $pdo->exec('TRUNCATE TABLE `cbt_' . $table . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $c = new Container();
        $c->instance(Database::class, self::$database);
        $c->instance(Logger::class, new Logger(self::$sessionDir . '/test.log'));
        $c->instance(Session::class, new Session(['session' => ['name' => 'examcore_it', 'lifetime' => 600]], self::$sessionDir));
        $c->set(Crypto::class, static fn (): Crypto => new Crypto(Crypto::generateKey()));
        $c->set(ImageUpload::class, static fn (): ImageUpload => new ImageUpload(self::$sessionDir));
        $c->set(\ExamCore\Services\Auth\TwoFactorService::class, static fn (Container $s) => new \ExamCore\Services\Auth\TwoFactorService(
            $s->get(\ExamCore\Models\User::class), $s->get(Session::class), $s->get(Crypto::class), 'ExamCore', 'test-key'));
        $c->set(SchoolService::class, static fn (Container $s): SchoolService => new SchoolService(
            $s->get(Database::class), $s->get(\ExamCore\Models\School::class), $s->get(\ExamCore\Models\Plan::class), $s->get(\ExamCore\Models\User::class),
            $s->get(\ExamCore\Services\AuditService::class), self::$sessionDir, $s->get(SchoolSetupService::class)));
        $this->c = $c;

        $plan = (int) $pdo->query("SELECT id FROM cbt_plans WHERE code = 'basic'")->fetchColumn();
        $this->schoolA = $c->get(SchoolService::class)->create(['name' => 'Alpha College', 'slug' => 'alpha-college', 'plan_id' => $plan, 'admin_name' => 'Ada Admin', 'admin_email' => 'ada@alpha.test', 'short_name' => 'ALC'])['school_id'];
        $this->schoolB = $c->get(SchoolService::class)->create(['name' => 'Beta Academy', 'slug' => 'beta-academy', 'plan_id' => $plan, 'admin_name' => 'Bola Admin', 'admin_email' => 'bola@beta.test'])['school_id'];
    }

    public static function tearDownAfterClass(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        foreach (glob(self::$sessionDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir(self::$sessionDir);
    }

    public function testNewSchoolsGetAWorkingStructureOnDayOne(): void
    {
        $classes = $this->c->get(SchoolClass::class)->all($this->schoolA);
        self::assertSame(['JSS 1 A', 'JSS 2 A', 'JSS 3 A', 'SS 1 A', 'SS 2 A', 'SS 3 A'], array_column($classes, 'name'));
        self::assertGreaterThan(25, count($this->c->get(\ExamCore\Models\SchoolSubject::class)->all($this->schoolA)));
        self::assertNotNull($this->c->get(\ExamCore\Models\AcademicCalendar::class)->current($this->schoolA)['term'], 'A current term is set.');
        self::assertCount(9, $this->c->get(\ExamCore\Models\GradingScale::class)->all($this->schoolA));
        self::assertFalse($this->c->get(SchoolSetupService::class)->needsSetup($this->schoolA));
        $again = $this->c->get(SchoolSetupService::class)->seedDefaults($this->schoolA);
        self::assertSame(0, $again['classes'] + $again['subjects'] + $again['departments'], 'Running setup twice changes nothing.');
    }

    public function testStudentsAreInvisibleToOtherSchools(): void
    {
        $service = $this->c->get(StudentService::class);
        $classA = (int) $this->c->get(SchoolClass::class)->all($this->schoolA)[0]['id'];
        $created = $service->create($this->schoolA, $service->validate($this->schoolA, ['name' => 'Adaeze Okafor', 'class_id' => $classA])['data']);

        $students = $this->c->get(Student::class);
        self::assertNotNull($students->find($this->schoolA, $created['user_id']));
        self::assertNull($students->find($this->schoolB, $created['user_id']), 'School B cannot read School A’s student.');
        self::assertSame(0, $students->updateProfile($this->schoolB, $created['user_id'], ['guardian_name' => 'Hacker']), 'School B cannot change it either.');
        self::assertSame(0, $students->search($this->schoolB, ['q' => 'Adaeze'], 1, 50)['total']);
        self::assertSame(1, $students->search($this->schoolA, ['q' => 'Adaeze'], 1, 50)['total'], 'Prefix search on the first word.');
        self::assertSame(1, $students->search($this->schoolA, ['q' => 'Okafor'], 1, 50)['total'], 'Falls back to full-text for a later word.');
        self::assertSame(1, $students->search($this->schoolA, ['q' => 'okafor adaeze'], 1, 50)['total'], 'Several words, any order.');
        self::assertSame(1, $students->search($this->schoolA, ['q' => strtolower($created['admission_no'])], 1, 50)['total'], 'Admission number, any case.');

        $foreignClass = (int) $this->c->get(SchoolClass::class)->all($this->schoolB)[0]['id'];
        self::assertArrayHasKey('class_id', $service->validate($this->schoolA, ['name' => 'Test Pupil', 'class_id' => $foreignClass])['errors'], 'Cannot place a student in another school’s class.');
    }

    public function testAdmissionNumbersAreGeneratedAndUnique(): void
    {
        $service = $this->c->get(StudentService::class);
        $first = $service->create($this->schoolA, $service->validate($this->schoolA, ['name' => 'First Pupil'])['data']);
        $second = $service->create($this->schoolA, $service->validate($this->schoolA, ['name' => 'Second Pupil'])['data']);
        self::assertSame('ALC/' . gmdate('Y') . '/0001', $first['admission_no']);
        self::assertSame('ALC/' . gmdate('Y') . '/0002', $second['admission_no']);
        self::assertArrayHasKey('admission_no', $service->validate($this->schoolA, ['name' => 'Copy Cat', 'admission_no' => $first['admission_no']])['errors']);
        self::assertSame([], $service->validate($this->schoolB, ['name' => 'Copy Cat', 'admission_no' => $first['admission_no']])['errors'], 'Another school may reuse the number.');
    }

    public function testSpreadsheetImportValidatesThenImportsInChunks(): void
    {
        $service = $this->c->get(StudentService::class);
        $rows = [['Grace Bello', 'jss 1 a', 'F', '08031234567'], ['Musa Ibrahim', 'SS 2 A', 'Male', '8069876543'], ['', '', '', ''], ['Tunde Ade', 'SS 9 Z', 'M', ''], ['Uche Eze', 'JSS 2 A', 'Unknown', '']];
        for ($i = 1; $i <= 60; $i++) {
            $rows[] = ['Bulk Student ' . $i, 'JSS 3 A', $i % 2 ? 'F' : 'M', ''];
        }
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        file_put_contents($path, Spreadsheet::xlsx(['Full Name', 'Class', 'Sex', 'Parent Phone'], $rows));
        $analysis = $service->analyseImport($this->schoolA, $path, 'list.xlsx');
        @unlink($path);

        self::assertCount(62, $analysis['valid']);
        self::assertSame([5, 6], array_column($analysis['errors'], 'row'), 'Unknown class (row 5) and gender (row 6) are reported with spreadsheet row numbers.');
        self::assertSame('08069876543', $analysis['valid'][1]['guardian_phone'], 'Lost leading zero restored.');

        $batch = $service->queueImport($this->schoolA, 1, 'list.xlsx', $analysis['valid']);
        $steps = 0;
        do {
            $progress = $service->processImportChunk($this->schoolA, $batch);
            $steps++;
        } while ($progress['status'] !== 'completed' && $steps < 10);
        self::assertSame(3, $steps, '62 rows in chunks of 25.');
        self::assertSame(62, $progress['created']);
        self::assertSame('completed', $service->processImportChunk($this->schoolA, $batch)['status'], 'Re-running a finished import is harmless.');
        self::assertCount(62, $service->importCredentials($this->schoolA, $batch));
        self::assertSame([], $service->importCredentials($this->schoolB, $batch), 'Another school cannot read the passwords.');
    }

    public function testPlanLimitIsEnforced(): void
    {
        self::$database->connection()->exec('UPDATE cbt_schools SET max_students_override = 1 WHERE id = ' . $this->schoolA);
        $service = $this->c->get(StudentService::class);
        $service->create($this->schoolA, $service->validate($this->schoolA, ['name' => 'Only Seat'])['data']);
        $this->expectException(RuntimeException::class);
        $service->create($this->schoolA, $service->validate($this->schoolA, ['name' => 'One Too Many'])['data']);
    }

    public function testPromotionMovesUpAndGraduatesTheFinalYear(): void
    {
        $service = $this->c->get(StudentService::class);
        $classes = array_column($this->c->get(SchoolClass::class)->all($this->schoolA), 'id', 'name');
        $junior = $service->create($this->schoolA, $service->validate($this->schoolA, ['name' => 'Junior Pupil', 'class_id' => $classes['SS 2 A']])['data']);
        $senior = $service->create($this->schoolA, $service->validate($this->schoolA, ['name' => 'Senior Pupil', 'class_id' => $classes['SS 3 A']])['data']);

        $result = $service->promote($this->schoolA, [(int) $classes['SS 2 A'] => (int) $classes['SS 3 A'], (int) $classes['SS 3 A'] => 'graduate']);
        self::assertSame(['moved' => 1, 'graduated' => 1], $result, 'SS 3 graduates before SS 2 moves in, so nobody is double-moved.');
        $students = $this->c->get(Student::class);
        self::assertSame('SS 3 A', $students->find($this->schoolA, $junior['user_id'])['class_name']);
        self::assertSame('graduated', $students->find($this->schoolA, $senior['user_id'])['status']);
    }

    public function testStudentSignInNeedsTheRightSchoolCode(): void
    {
        $service = $this->c->get(StudentService::class);
        $created = $service->create($this->schoolA, $service->validate($this->schoolA, ['name' => 'Login Pupil'])['data']);
        $auth = $this->c->get(AuthService::class);

        self::assertSame('invalid', $auth->attemptStudent('beta-academy', $created['admission_no'], $created['password'], '127.0.0.1')['status'], 'Right number, wrong school.');
        self::assertSame('invalid', $auth->attemptStudent('alpha-college', $created['admission_no'], 'wrong-pass-9', '127.0.0.1')['status']);
        self::assertSame('ok', $auth->attemptStudent('alpha-college', strtolower($created['admission_no']), $created['password'], '127.0.0.1')['status'], 'Admission number is case-insensitive.');

        $this->c->get(Student::class)->updateProfile($this->schoolA, $created['user_id'], ['status' => 'withdrawn']);
        self::assertSame('invalid', $auth->attemptStudent('alpha-college', $created['admission_no'], $created['password'], '127.0.0.1')['status'], 'Withdrawn students cannot sign in.');
    }
}
