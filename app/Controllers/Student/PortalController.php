<?php
declare(strict_types=1);

namespace ExamCore\Controllers\Student;

use ExamCore\Controllers\PortalController as BasePortal;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\Url;
use ExamCore\Core\View;
use ExamCore\Models\AcademicCalendar;
use ExamCore\Models\SchoolSubject;
use ExamCore\Models\Student;
use ExamCore\Models\User;
use ExamCore\Services\Auth\AuthService;

/** Student sign-in (school code + admission number) and the student home. */
final class PortalController extends BasePortal
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf,
        private readonly AuthService $auth,
        private readonly TenantContext $tenant,
        private readonly Student $students,
        private readonly SchoolSubject $subjects,
        private readonly AcademicCalendar $calendar,
        private readonly \ExamCore\Models\SchoolExam $exams,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function loginForm(Request $request): Response
    {
        if ($this->guard->role() === User::ROLE_STUDENT) {
            return $this->redirectTo('/student');
        }
        $slug = strtolower(trim((string) $request->query('school', '')));
        return $this->page($request, 'student/login', [
            'area' => 'student',
            'title' => 'Student sign-in',
            'schoolCode' => preg_match('/^[a-z0-9-]{1,60}$/', $slug) ? $slug : '',
        ], 'layouts/auth');
    }

    /** Short link printed on slips: /s/{school} → sign-in with the school pre-filled. */
    public function schoolLink(Request $request, array $parameters): Response
    {
        $slug = strtolower((string) ($parameters['slug'] ?? ''));
        return Response::redirect(Url::to('/student/login') . (preg_match('/^[a-z0-9-]{1,60}$/', $slug) ? '?school=' . rawurlencode($slug) : ''), 302);
    }

    public function login(Request $request): Response
    {
        $school = strtolower(trim((string) $request->input('school', '')));
        $admission = trim((string) $request->input('admission_no', ''));
        $result = $this->auth->attemptStudent($school, $admission, (string) $request->input('password', ''), $request->ip());
        $back = '/student/login?school=' . rawurlencode($school);
        return match ($result['status']) {
            'ok' => $this->redirectTo('/student'),
            'locked' => $this->backWithErrors($back, ['admission_no' => ' '], ['admission_no' => $admission], sprintf('Too many wrong attempts. Wait %d minute(s) or ask your teacher to reset your password.', $result['minutes'] ?? 15)),
            'school_inactive' => $this->backWithErrors($back, [], ['admission_no' => $admission], 'Your school’s portal is not active right now. Please speak to your school.'),
            default => $this->backWithErrors($back, ['admission_no' => ' ', 'password' => ' '], ['admission_no' => $admission], 'The school code, admission number or password is not correct.'),
        };
    }

    public function dashboard(Request $request): Response
    {
        $student = $this->students->find($this->tenant->id(), (int) $this->guard->id());
        return $this->page($request, 'student/dashboard', [
            'area' => 'student',
            'title' => 'My dashboard',
            'student' => $student,
            'subjects' => $student !== null && $student['class_id'] ? $this->subjects->forClass($this->tenant->id(), (int) $student['class_id']) : [],
            'current' => $this->calendar->current($this->tenant->id()),
            'sittings' => $student !== null ? $this->container->get(\ExamCore\Models\ExamSitting::class)->summariesForStudent($this->tenant->id(), (int) $student['id']) : [],
            'exams' => $student !== null && $student['class_id'] ? $this->exams->forClass($this->tenant->id(), (int) $student['class_id']) : [],
        ]);
    }
}
