<?php
declare(strict_types=1);

use ExamCore\Controllers\AccountController;
use ExamCore\Controllers\AuthController;
use ExamCore\Controllers\BankController;
use ExamCore\Controllers\Console\ConsoleController;
use ExamCore\Controllers\Console\PlanController;
use ExamCore\Controllers\Console\SchoolController;
use ExamCore\Controllers\School\AcademicController;
use ExamCore\Controllers\School\ClassController;
use ExamCore\Controllers\School\DashboardController;
use ExamCore\Controllers\School\ExamController;
use ExamCore\Controllers\School\SettingsController;
use ExamCore\Controllers\School\StudentController;
use ExamCore\Controllers\School\SubjectController;
use ExamCore\Controllers\School\TeacherController;
use ExamCore\Controllers\School\ResultController;
use ExamCore\Controllers\Student\ExamRoomController;
use ExamCore\Controllers\Student\ResultController as StudentResults;
use ExamCore\Controllers\VerifyController;
use ExamCore\Controllers\Student\PortalController as StudentPortal;
use ExamCore\Middleware\RequireRole;
use ExamCore\Controllers\SetupController;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Router;
use ExamCore\Middleware\Authenticate;
use ExamCore\Models\School;
use ExamCore\Models\User;
use ExamCore\Services\Auth\TwoFactorService;

/** Owner Console (/console), school portal (/school) and sign-in screens. */
return static function (Router $router, Container $container): void {
    $config = $container->get('config');
    $guard = $container->get(Guard::class);

    // ---- Sign-in -------------------------------------------------------------------
    $router->get('/console/login', [AuthController::class, 'consoleForm'], name: 'console.login');
    $router->post('/console/login', [AuthController::class, 'consoleLogin']);
    $router->get('/login', [AuthController::class, 'schoolForm'], name: 'login');
    $router->post('/login', [AuthController::class, 'schoolLogin']);
    $router->get('/login/choose', [AuthController::class, 'chooseForm']);
    $router->post('/login/choose', [AuthController::class, 'choose']);
    $router->get('/login/two-factor', [AuthController::class, 'twoFactorForm']);
    $router->post('/login/two-factor', [AuthController::class, 'twoFactor']);
    $router->post('/logout', [AuthController::class, 'logout'], name: 'logout');
    $router->get('/setup', [SetupController::class, 'form']);
    $router->post('/setup', [SetupController::class, 'store']);

    // ---- Owner Console ---------------------------------------------------------------
    $owner = new Authenticate(
        $guard,
        [User::ROLE_OWNER],
        '/console/login',
        $container->get(TwoFactorService::class),
        (bool) ($config['app']['console_require_2fa'] ?? true),
    );
    // Question bank: identical pages for the school bank and the platform bank.
    $bankRoutes = static function (Router $router): void {
        $router->get('/bank', [BankController::class, 'index']);
        $router->get('/bank/create', [BankController::class, 'create']);
        $router->post('/bank', [BankController::class, 'store']);
        $router->get('/bank/import', [BankController::class, 'importForm']);
        $router->post('/bank/import', [BankController::class, 'import']);
        $router->get('/bank/import/template', [BankController::class, 'template']);
        $router->get('/bank/passages', [BankController::class, 'passages']);
        $router->post('/bank/passages', [BankController::class, 'savePassage']);
        $router->get('/bank/{id}', [BankController::class, 'edit']);
        $router->post('/bank/{id}', [BankController::class, 'update']);
        $router->post('/bank/{id}/status', [BankController::class, 'transition']);
        $router->post('/bank/{id}/delete', [BankController::class, 'delete']);
    };
    $router->group(['prefix' => '/console', 'middleware' => [$owner]], static function (Router $router) use ($bankRoutes): void {
        $bankRoutes($router);
        $router->get('/', [ConsoleController::class, 'dashboard'], name: 'console.dashboard');
        $router->get('/schools', [SchoolController::class, 'index'], name: 'console.schools');
        $router->get('/schools/create', [SchoolController::class, 'create']);
        $router->post('/schools', [SchoolController::class, 'store']);
        $router->get('/schools/{id}', [SchoolController::class, 'show']);
        $router->post('/schools/{id}', [SchoolController::class, 'update']);
        $router->post('/schools/{id}/logo', [SchoolController::class, 'logo']);
        $router->post('/schools/{id}/status', [SchoolController::class, 'status']);
        $router->post('/schools/{id}/reset-admin-password', [SchoolController::class, 'resetAdminPassword']);
        $router->post('/schools/{id}/impersonate', [SchoolController::class, 'impersonate']);
        $router->get('/plans', [PlanController::class, 'index']);
        $router->post('/plans/{id}', [PlanController::class, 'update']);
        $router->get('/audit', [ConsoleController::class, 'audit']);
        $router->get('/system', [ConsoleController::class, 'system']);
        $router->post('/system/backup', [ConsoleController::class, 'backup']);
        $router->get('/system/backups/{file}', [ConsoleController::class, 'downloadBackup']);
        $router->post('/system/migrate', [ConsoleController::class, 'migrate']);
        $router->post('/system/jobs/{id}/retry', [ConsoleController::class, 'retryJob']);
        $router->post('/system/jobs/{id}/delete', [ConsoleController::class, 'deleteJob']);
        $router->get('/account/password', [AccountController::class, 'passwordForm']);
        $router->post('/account/password', [AccountController::class, 'password']);
        $router->get('/account/two-factor', [AccountController::class, 'twoFactorForm']);
        $router->post('/account/two-factor', [AccountController::class, 'enableTwoFactor']);
        $router->post('/account/two-factor/disable', [AccountController::class, 'disableTwoFactor']);
    });

    // ---- School portal -----------------------------------------------------------------
    $staff = new Authenticate($guard, User::STAFF_ROLES, '/login', schools: $container->get(School::class), area: 'school');
    $adminOnly = new RequireRole($guard, [User::ROLE_SCHOOL_ADMIN]);
    $router->group(['prefix' => '/school', 'middleware' => [$staff]], static function (Router $router) use ($adminOnly, $bankRoutes): void {
        $router->get('/', [DashboardController::class, 'index'], name: 'school.dashboard');
        $router->post('/stop-impersonating', [DashboardController::class, 'stopImpersonating']);
        $router->get('/my-classes', [TeacherController::class, 'mine']);
        $router->get('/exams', [ExamController::class, 'index']);
        $router->get('/exams/create', [ExamController::class, 'create']);
        $router->post('/exams', [ExamController::class, 'store']);
        $router->get('/exams/{id}', [ExamController::class, 'show']);
        $router->get('/exams/{id}/edit', [ExamController::class, 'edit']);
        $router->post('/exams/{id}', [ExamController::class, 'update']);
        $router->post('/exams/{id}/action', [ExamController::class, 'action']);
        $router->get('/exams/{id}/results', [ResultController::class, 'exam']);
        $router->get('/exams/{id}/results/export', [ResultController::class, 'examExport']);
        $router->get('/exams/{id}/slips', [ResultController::class, 'slips']);
        $router->get('/exams/{id}/monitor', [ExamController::class, 'monitor']);
        $router->get('/exams/{id}/monitor/data', [ExamController::class, 'monitorData']);
        $router->post('/exams/{id}/sittings/{sitting}', [ExamController::class, 'sitting']);
        $router->get('/exams/{id}/sittings/{sitting}', [ExamController::class, 'report']);
        $router->get('/exams/{id}/sittings/{sitting}/snapshots/{snap}', [ExamController::class, 'snapshotImage']);
        $router->get('/exams/{id}/papers/{paper}/questions', [ExamController::class, 'pick']);
        $router->post('/exams/{id}/papers/{paper}/questions', [ExamController::class, 'savePick']);
        // Fixed paths first: "/bank/platform" must not be captured by "/bank/{id}".
        $router->get('/bank/platform', [BankController::class, 'platform']);
        $router->post('/bank/platform/copy', [BankController::class, 'copy']);
        $bankRoutes($router);
        $router->get('/search', [SettingsController::class, 'search']);
        $router->get('/account/password', [AccountController::class, 'passwordForm']);
        $router->post('/account/password', [AccountController::class, 'password']);
        $router->get('/account/two-factor', [AccountController::class, 'twoFactorForm']);
        $router->post('/account/two-factor', [AccountController::class, 'enableTwoFactor']);
        $router->post('/account/two-factor/disable', [AccountController::class, 'disableTwoFactor']);

        // Administration: school admins only.
        $router->group(['middleware' => [$adminOnly]], static function (Router $router): void {
            $router->post('/quick-setup', [AcademicController::class, 'quickSetup']);
            $router->get('/academic', [AcademicController::class, 'calendar']);
            $router->post('/academic/sessions', [AcademicController::class, 'createSession']);
            $router->post('/academic/terms/{id}/current', [AcademicController::class, 'setCurrentTerm']);
            $router->post('/academic/terms/{id}', [AcademicController::class, 'updateTerm']);
            $router->get('/departments', [AcademicController::class, 'departments']);
            $router->post('/departments', [AcademicController::class, 'storeDepartment']);
            $router->post('/departments/{id}', [AcademicController::class, 'updateDepartment']);
            $router->post('/departments/{id}/delete', [AcademicController::class, 'deleteDepartment']);
            $router->get('/grading', [AcademicController::class, 'grading']);
            $router->post('/grading', [AcademicController::class, 'saveGrading']);
            $router->post('/grading/reset', [AcademicController::class, 'resetGrading']);

            $router->get('/classes', [ClassController::class, 'index']);
            $router->post('/classes', [ClassController::class, 'store']);
            $router->get('/classes/promote', [ClassController::class, 'promoteForm']);
            $router->post('/classes/promote', [ClassController::class, 'promote']);
            $router->get('/classes/{id}', [ClassController::class, 'show']);
            $router->post('/classes/{id}', [ClassController::class, 'update']);
            $router->post('/classes/{id}/subjects', [ClassController::class, 'subjects']);
            $router->post('/classes/{id}/delete', [ClassController::class, 'delete']);

            $router->get('/subjects', [SubjectController::class, 'index']);
            $router->post('/subjects', [SubjectController::class, 'store']);
            $router->post('/subjects/{id}', [SubjectController::class, 'update']);

            $router->get('/teachers', [TeacherController::class, 'index']);
            $router->get('/teachers/create', [TeacherController::class, 'create']);
            $router->post('/teachers', [TeacherController::class, 'store']);
            $router->get('/teachers/{id}', [TeacherController::class, 'edit']);
            $router->post('/teachers/{id}', [TeacherController::class, 'update']);
            $router->post('/teachers/{id}/reset-password', [TeacherController::class, 'resetPassword']);
            $router->post('/teachers/{id}/toggle', [TeacherController::class, 'toggle']);

            $router->get('/students', [StudentController::class, 'index']);
            $router->get('/students/create', [StudentController::class, 'create']);
            $router->post('/students', [StudentController::class, 'store']);
            $router->get('/students/export', [StudentController::class, 'export']);
            $router->get('/students/slips', [StudentController::class, 'slips']);
            $router->get('/students/import', [StudentController::class, 'importForm']);
            $router->get('/students/import/template', [StudentController::class, 'template']);
            $router->post('/students/import', [StudentController::class, 'analyse']);
            $router->post('/students/import/{id}/process', [StudentController::class, 'process']);
            $router->get('/students/import/{id}', [StudentController::class, 'importResult']);
            $router->get('/students/import/{id}/credentials', [StudentController::class, 'importCredentials']);
            $router->get('/students/{id}', [StudentController::class, 'show']);
            $router->post('/students/{id}', [StudentController::class, 'update']);
            $router->post('/students/{id}/photo', [StudentController::class, 'photo']);
            $router->post('/students/{id}/reset-password', [StudentController::class, 'resetPassword']);

            $router->get('/results', [ResultController::class, 'index']);
            $router->get('/results/export', [ResultController::class, 'export']);
            $router->get('/results/report-cards', [ResultController::class, 'reportCards']);
            $router->post('/results/settings', [ResultController::class, 'settings']);
            $router->get('/results/students/{student}', [ResultController::class, 'reportCard']);
            $router->post('/results/students/{student}/remarks', [ResultController::class, 'remarks']);
            $router->post('/results/students/{student}/code', [ResultController::class, 'resultCode']);

            $router->get('/settings', [SettingsController::class, 'edit']);
            $router->post('/settings', [SettingsController::class, 'update']);
            $router->post('/settings/logo', [SettingsController::class, 'logo']);
            $router->get('/activity', [SettingsController::class, 'activity']);
        });
    });

    // ---- Public: result verification (QR) and parent result checker ---------------------------
    $router->get('/verify/{token}', [VerifyController::class, 'verify']);
    $router->get('/check-result', [VerifyController::class, 'checkForm']);
    $router->post('/check-result', [VerifyController::class, 'check']);

    // ---- Student portal ------------------------------------------------------------------
    $router->get('/s/{slug}', [StudentPortal::class, 'schoolLink']);
    $router->get('/student/login', [StudentPortal::class, 'loginForm'], name: 'student.login');
    $router->post('/student/login', [StudentPortal::class, 'login']);
    $student = new Authenticate($guard, [User::ROLE_STUDENT], '/student/login', schools: $container->get(School::class), area: 'student');
    $router->group(['prefix' => '/student', 'middleware' => [$student]], static function (Router $router): void {
        $router->get('/', [StudentPortal::class, 'dashboard'], name: 'student.dashboard');
        $router->get('/exams/{id}', [ExamRoomController::class, 'lobby']);
        $router->post('/exams/{id}/start', [ExamRoomController::class, 'start']);
        $router->get('/exams/{id}/room', [ExamRoomController::class, 'room']);
        $router->post('/exams/{id}/sync', [ExamRoomController::class, 'sync']);
        $router->post('/exams/{id}/verify', [ExamRoomController::class, 'verify']);
        $router->post('/exams/{id}/approve', [ExamRoomController::class, 'approve']);
        $router->post('/exams/{id}/snapshot', [ExamRoomController::class, 'snapshot']);
        $router->post('/exams/{id}/submit', [ExamRoomController::class, 'submit']);
        $router->get('/exams/{id}/done', [ExamRoomController::class, 'done']);
        $router->get('/results', [StudentResults::class, 'index']);
        $router->get('/results/report', [StudentResults::class, 'report']);
        $router->get('/account/password', [AccountController::class, 'passwordForm']);
        $router->post('/account/password', [AccountController::class, 'password']);
    });
};
