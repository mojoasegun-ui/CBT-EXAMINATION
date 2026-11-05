<?php
declare(strict_types=1);

namespace ExamCore\Controllers\School;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\AcademicCalendar;
use ExamCore\Models\AuditLog;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\SchoolSubject;
use ExamCore\Models\Student;
use ExamCore\Models\Teacher;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;
use ExamCore\Services\SchoolSetupService;
use ExamCore\Services\StudentService;

final class DashboardController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly AuditService $audit,
        private readonly Student $students,
        private readonly Teacher $teachers,
        private readonly SchoolClass $classes,
        private readonly SchoolSubject $subjects,
        private readonly AcademicCalendar $calendar,
        private readonly AuditLog $auditLogs,
        private readonly SchoolSetupService $setup,
        private readonly StudentService $studentService,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function index(Request $request): Response
    {
        $schoolId = $this->schoolId();
        $classes = $this->classes->all($schoolId);
        $staff = $this->teachers->countByRole($schoolId);
        $activeStudents = $this->students->countActive($schoolId);
        $current = $this->calendar->current($schoolId);
        $subjectCount = count($this->subjects->all($schoolId, true));
        $teacherCount = ($staff['teacher'] ?? 0);

        return $this->page($request, 'school/dashboard', $this->base('Dashboard') + [
            'isAdmin' => $this->guard->role() === User::ROLE_SCHOOL_ADMIN,
            'features' => $this->tenant->features(),
            'needsSetup' => $this->setup->needsSetup($schoolId),
            'stats' => [
                'students' => $activeStudents,
                'teachers' => $teacherCount,
                'classes' => count($classes),
                'subjects' => $subjectCount,
            ],
            'gender' => $this->students->genderSplit($schoolId),
            'classSeries' => array_map(static fn (array $c): array => [
                'label' => (string) $c['name'],
                'short' => str_replace(['Primary ', 'Nursery ', 'JSS ', 'SS '], ['P', 'N', 'J', 'S'], (string) $c['name']),
                'count' => (int) $c['student_count'],
            ], $classes),
            'current' => $current,
            'remaining' => $this->studentService->remainingCapacity($schoolId),
            'checklist' => [
                ['Academic year & current term', $current['term'] !== null, '/school/academic'],
                ['Classes created', $classes !== [], '/school/classes'],
                ['Subjects offered', $subjectCount > 0, '/school/subjects'],
                ['Teachers added', $teacherCount > 0, '/school/teachers/create'],
                ['Students enrolled', $activeStudents > 0, '/school/students/import'],
                ['School logo uploaded', !empty($this->tenant->school()['logo_path']), '/school/settings'],
            ],
            'activity' => $this->auditLogs->search($schoolId, '', 1, 6)['rows'],
        ]);
    }

    public function stopImpersonating(Request $request): Response
    {
        $schoolId = $this->guard->schoolId();
        $owner = $this->guard->stopImpersonating();
        if ($owner === null) {
            return $this->redirectTo('/console/login', 'Please sign in again.', 'error');
        }
        $this->audit->record('school.impersonate_end', 'Returned to the Owner Console', 'school', $schoolId, [], $schoolId);
        return $this->redirectTo($schoolId === null ? '/console' : '/console/schools/' . $schoolId, 'Back in the Owner Console.');
    }
}
