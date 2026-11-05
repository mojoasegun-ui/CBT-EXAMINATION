<?php
declare(strict_types=1);

namespace ExamCore\Controllers\Console;

use ExamCore\Controllers\PortalController;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\View;
use ExamCore\Models\AuditLog;
use ExamCore\Models\Plan;
use ExamCore\Models\School;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;
use ExamCore\Services\SchoolService;

final class SchoolController extends PortalController
{
    public function __construct(
        Container $container,
        View $view,
        Guard $guard,
        Session $session,
        Csrf $csrf,
        private readonly School $schools,
        private readonly Plan $plans,
        private readonly User $users,
        private readonly AuditLog $auditLogs,
        private readonly SchoolService $service,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function index(Request $request): Response
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->schools->search($query, $status === '' ? null : $status, $page, 20);
        return $this->page($request, 'console/schools/index', [
            'area' => 'console',
            'title' => 'Schools',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'pageNumber' => $page,
            'perPage' => 20,
            'query' => $query,
            'status' => $status,
            'counts' => $this->schools->countByStatus(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->page($request, 'console/schools/create', [
            'area' => 'console',
            'title' => 'Add a school',
            'plans' => $this->plans->all(true),
            'states' => SchoolService::NIGERIAN_STATES,
        ]);
    }

    public function store(Request $request): Response
    {
        $input = $this->input($request);
        $errors = $this->service->validate($input);
        if ($errors !== []) {
            return $this->backWithErrors('/console/schools/create', $errors, $input);
        }
        $created = $this->service->create($input);
        // Shown exactly once on the next page; never stored in plain text anywhere.
        $this->session->flash('credentials', ['email' => $created['admin_email'], 'password' => $created['temporary_password']]);
        return $this->redirectTo('/console/schools/' . $created['school_id'], 'School created. Share the sign-in details below with the administrator.');
    }

    public function show(Request $request, array $parameters): Response
    {
        $school = $this->findOrFail($parameters);
        return $this->page($request, 'console/schools/show', [
            'area' => 'console',
            'title' => $school['name'],
            'school' => $school,
            'admin' => $this->users->primaryAdmin((int) $school['id']),
            'plans' => $this->plans->all(),
            'states' => SchoolService::NIGERIAN_STATES,
            'credentials' => $this->session->pull('credentials'),
            'activity' => $this->auditLogs->search((int) $school['id'], '', 1, 10)['rows'],
            'tab' => in_array($request->query('tab'), ['details', 'branding', 'plan', 'danger'], true) ? (string) $request->query('tab') : 'details',
        ]);
    }

    public function update(Request $request, array $parameters): Response
    {
        $school = $this->findOrFail($parameters);
        // Each tab posts only its own fields; everything else keeps its current value.
        $current = array_map(static fn ($value) => $value ?? '', $school);
        $input = $this->input($request) + $current;
        $tab = (string) ($input['tab'] ?? 'details');
        $errors = $this->service->validate($input, (int) $school['id'], withAdmin: false);
        if ($errors !== []) {
            return $this->backWithErrors('/console/schools/' . $school['id'] . '?tab=' . rawurlencode($tab), $errors, $input);
        }
        $this->service->update((int) $school['id'], $input);
        return $this->redirectTo('/console/schools/' . $school['id'] . '?tab=' . rawurlencode($tab), 'School details saved.');
    }

    public function logo(Request $request, array $parameters): Response
    {
        $school = $this->findOrFail($parameters);
        $file = $request->file('logo');
        $error = $file === null ? 'Choose an image to upload.' : $this->service->uploadLogo((int) $school['id'], $file);
        return $this->redirectTo('/console/schools/' . $school['id'] . '?tab=branding', $error ?? 'Logo updated.', $error === null ? 'success' : 'error');
    }

    public function status(Request $request, array $parameters): Response
    {
        $school = $this->findOrFail($parameters);
        $status = (string) $request->input('status', '');
        if (!in_array($status, School::STATUSES, true)) {
            return $this->redirectTo('/console/schools/' . $school['id'] . '?tab=danger', 'Choose a valid status.', 'error');
        }
        $this->service->changeStatus((int) $school['id'], $status, mb_substr(trim((string) $request->input('reason', '')), 0, 200));
        $labels = ['trial' => 'moved to trial', 'active' => 'activated', 'suspended' => 'suspended', 'archived' => 'archived'];
        return $this->redirectTo('/console/schools/' . $school['id'] . '?tab=danger', 'School ' . $labels[$status] . '.', $status === 'active' ? 'success' : 'warning');
    }

    public function resetAdminPassword(Request $request, array $parameters): Response
    {
        $school = $this->findOrFail($parameters);
        $reset = $this->service->resetAdminPassword((int) $school['id']);
        $this->session->flash('credentials', ['email' => $reset['email'], 'password' => $reset['temporary_password']]);
        return $this->redirectTo('/console/schools/' . $school['id'], 'Password reset. The administrator must choose a new one at next sign-in.');
    }

    /** Sign in as the school's administrator to help with support. Fully audited. */
    public function impersonate(Request $request, array $parameters): Response
    {
        $school = $this->findOrFail($parameters);
        $admin = $this->users->primaryAdmin((int) $school['id']);
        if ($admin === null || $admin['status'] !== 'active') {
            return $this->redirectTo('/console/schools/' . $school['id'], 'This school has no active administrator to sign in as.', 'error');
        }
        $this->audit->record('school.impersonate', 'Signed in as ' . $admin['name'] . ' for support', 'user', (int) $admin['id'], [], (int) $school['id']);
        $this->guard->impersonate($admin);
        return $this->redirectTo('/school', 'You are viewing ' . $school['name'] . ' as its administrator.', 'info');
    }

    /** @return array<string, mixed> */
    private function findOrFail(array $parameters): array
    {
        $id = filter_var($parameters['id'] ?? null, FILTER_VALIDATE_INT);
        $school = $id === false ? null : $this->schools->find($id);
        return $school ?? throw HttpException::notFound('That school could not be found.');
    }
}
