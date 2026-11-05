<?php
declare(strict_types=1);

namespace ExamCore\Controllers\School;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\SchoolSubject;
use ExamCore\Models\Teacher;
use ExamCore\Services\TeacherService;
use RuntimeException;

final class TeacherController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly Teacher $teachers,
        private readonly SchoolSubject $subjects,
        private readonly TeacherService $service,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function index(Request $request): Response
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        return $this->page($request, 'school/teachers/index', $this->base('Teachers & staff') + [
            'staff' => $this->teachers->all($this->schoolId(), $query),
            'query' => $query,
            'roles' => TeacherService::ROLES,
            'credentials' => $this->session->pull('credentials'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->page($request, 'school/teachers/form', $this->base('Add staff') + ['staffMember' => null, 'roles' => TeacherService::ROLES, 'titles' => TeacherService::TITLES]);
    }

    public function store(Request $request): Response
    {
        $check = $this->service->validate($this->schoolId(), $this->input($request));
        if ($check['errors'] !== []) {
            return $this->backWithErrors('/school/teachers/create', $check['errors'], $this->input($request));
        }
        $created = $this->service->create($this->schoolId(), $check['data']);
        $this->session->flash('credentials', ['name' => $check['data']['name'], 'email' => $check['data']['email'], 'password' => $created['password']]);
        return $this->redirectTo('/school/teachers', $check['data']['name'] . ' added. Share the sign-in details below.');
    }

    public function edit(Request $request, array $parameters): Response
    {
        $staff = $this->find($parameters);
        return $this->page($request, 'school/teachers/form', $this->base($staff['name']) + [
            'staffMember' => $staff,
            'roles' => TeacherService::ROLES,
            'titles' => TeacherService::TITLES,
            'assignments' => $this->subjects->forTeacher($this->schoolId(), (int) $staff['id']),
            'credentials' => $this->session->pull('credentials'),
        ]);
    }

    public function update(Request $request, array $parameters): Response
    {
        $staff = $this->find($parameters);
        $check = $this->service->validate($this->schoolId(), $this->input($request), (int) $staff['id']);
        if ($check['errors'] !== []) {
            return $this->backWithErrors('/school/teachers/' . $staff['id'], $check['errors'], $this->input($request));
        }
        try {
            $this->service->update($this->schoolId(), (int) $staff['id'], $check['data'], (int) $this->guard->id());
        } catch (RuntimeException $exception) {
            return $this->redirectTo('/school/teachers/' . $staff['id'], $exception->getMessage(), 'error');
        }
        return $this->redirectTo('/school/teachers/' . $staff['id'], 'Saved.');
    }

    public function resetPassword(Request $request, array $parameters): Response
    {
        $staff = $this->find($parameters);
        $password = $this->service->resetPassword($this->schoolId(), (int) $staff['id']);
        $this->session->flash('credentials', ['name' => $staff['name'], 'email' => $staff['email'], 'password' => $password]);
        return $this->redirectTo('/school/teachers/' . $staff['id'], 'New temporary password created.');
    }

    public function toggle(Request $request, array $parameters): Response
    {
        $staff = $this->find($parameters);
        try {
            $this->service->setActive($this->schoolId(), (int) $staff['id'], $staff['status'] !== 'active', (int) $this->guard->id());
        } catch (RuntimeException $exception) {
            return $this->redirectTo('/school/teachers/' . $staff['id'], $exception->getMessage(), 'error');
        }
        return $this->redirectTo('/school/teachers/' . $staff['id'], $staff['status'] === 'active' ? $staff['name'] . ' can no longer sign in.' : $staff['name'] . ' can sign in again.',
            $staff['status'] === 'active' ? 'warning' : 'success');
    }

    /** Teachers (any staff role) see their own classes and subjects. */
    public function mine(Request $request): Response
    {
        return $this->page($request, 'school/teachers/mine', $this->base('My classes') + [
            'assignments' => $this->subjects->forTeacher($this->schoolId(), (int) $this->guard->id()),
        ]);
    }

    /** @return array<string, mixed> */
    private function find(array $parameters): array
    {
        return $this->teachers->find($this->schoolId(), $this->idParam($parameters)) ?? throw HttpException::notFound('That staff member could not be found.');
    }
}
