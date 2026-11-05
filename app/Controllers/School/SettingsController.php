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
use ExamCore\Models\AuditLog;
use ExamCore\Models\School;
use ExamCore\Models\Student;
use ExamCore\Models\Teacher;
use ExamCore\Services\AuditService;
use ExamCore\Services\SchoolService;
use ExamCore\Services\StudentService;

/** The school's own profile & branding, its activity log, and quick search. */
final class SettingsController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly School $schools,
        private readonly SchoolService $schoolService,
        private readonly AuditLog $auditLogs,
        private readonly AuditService $audit,
        private readonly Student $students,
        private readonly Teacher $teachers,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function edit(Request $request): Response
    {
        return $this->page($request, 'school/settings', $this->base('School profile') + ['states' => SchoolService::NIGERIAN_STATES]);
    }

    public function update(Request $request): Response
    {
        $school = $this->tenant->school();
        $errors = [];
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email or leave it blank.';
        }
        $phone = trim((string) $request->input('phone', ''));
        if ($phone !== '' && StudentService::normalizePhone($phone) === null) {
            $errors['phone'] = 'Enter a Nigerian number like 0803 123 4567.';
        }
        $color = strtoupper((string) $request->input('primary_color', $school['primary_color']));
        if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
            $errors['primary_color'] = 'Choose a colour in #RRGGBB format.';
        }
        $state = (string) $request->input('state', '');
        if ($state !== '' && !in_array($state, SchoolService::NIGERIAN_STATES, true)) {
            $errors['state'] = 'Choose a state from the list.';
        }
        if ($errors !== []) {
            return $this->backWithErrors('/school/settings', $errors, $this->input($request));
        }
        $text = static fn (string $key, int $max) => ($v = trim((string) $request->input($key, ''))) === '' ? null : mb_substr($v, 0, $max);
        // Name, web address, plan and status stay with the platform owner.
        $this->schools->update((int) $school['id'], [
            'short_name' => $text('short_name', 40), 'motto' => $text('motto', 160), 'email' => $email === '' ? null : $email,
            'phone' => $phone === '' ? null : StudentService::normalizePhone($phone), 'address' => $text('address', 255),
            'state' => $state === '' ? null : $state, 'primary_color' => $color,
        ]);
        $this->audit->record('school.profile', 'Updated the school profile', 'school', (int) $school['id'], schoolId: (int) $school['id']);
        return $this->redirectTo('/school/settings', 'School profile saved.');
    }

    public function logo(Request $request): Response
    {
        $file = $request->file('logo');
        $error = $file === null ? 'Choose an image to upload.' : $this->schoolService->uploadLogo($this->schoolId(), $file);
        return $this->redirectTo('/school/settings', $error ?? 'Logo updated across your portal and result slips.', $error === null ? 'success' : 'error');
    }

    public function activity(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->auditLogs->search($this->schoolId(), '', $page, 30);
        return $this->page($request, 'school/activity', $this->base('Activity log') + ['rows' => $result['rows'], 'total' => $result['total'], 'pageNumber' => $page, 'perPage' => 30]);
    }

    public function search(Request $request): Response
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        $students = $q === '' ? ['rows' => [], 'total' => 0] : $this->students->search($this->schoolId(), ['q' => $q, 'status' => 'all'], 1, 20);
        $staff = $q === '' ? [] : array_slice($this->teachers->all($this->schoolId(), $q), 0, 10);
        if ($request->wantsJson()) {
            return Response::json([
                'students' => array_map(static fn (array $s): array => ['name' => $s['name'], 'meta' => $s['admission_no'] . ' · ' . ($s['class_name'] ?? 'No class'), 'url' => url('/school/students/' . $s['id'])], $students['rows']),
                'staff' => array_map(static fn (array $t): array => ['name' => $t['name'], 'meta' => $t['email'], 'url' => url('/school/teachers/' . $t['id'])], $staff),
            ]);
        }
        return $this->page($request, 'school/search', $this->base('Search') + ['q' => $q, 'students' => $students, 'staff' => $staff]);
    }
}
