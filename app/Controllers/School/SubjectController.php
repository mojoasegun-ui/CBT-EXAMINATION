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
use ExamCore\Models\Department;
use ExamCore\Models\SchoolSubject;
use ExamCore\Services\AuditService;

final class SubjectController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly SchoolSubject $subjects,
        private readonly Department $departments,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function index(Request $request): Response
    {
        $subjects = $this->subjects->all($this->schoolId());
        $grouped = [];
        foreach ($subjects as $subject) {
            $grouped[$subject['category']][] = $subject;
        }
        return $this->page($request, 'school/subjects', $this->base('Subjects') + [
            'grouped' => $grouped,
            'total' => count($subjects),
            'categories' => SchoolSubject::CATEGORIES,
            'departments' => $this->departments->all($this->schoolId(), true),
        ]);
    }

    public function store(Request $request): Response
    {
        [$data, $errors] = $this->validated($request);
        if ($errors !== []) {
            return $this->backWithErrors('/school/subjects', $errors, $this->input($request), reset($errors));
        }
        $this->subjects->create($this->schoolId(), $data['name'], $data['code'], $data['category'], $data['department_id']);
        $this->audit->record('subject.created', 'Added the subject ' . $data['name'], schoolId: $this->schoolId());
        return $this->redirectTo('/school/subjects', $data['name'] . ' added.');
    }

    public function update(Request $request, array $parameters): Response
    {
        $id = $this->idParam($parameters);
        if ($this->subjects->find($this->schoolId(), $id) === null) {
            return $this->redirectTo('/school/subjects', 'Subject not found.', 'error');
        }
        [$data, $errors] = $this->validated($request, $id);
        if ($errors !== []) {
            return $this->redirectTo('/school/subjects', reset($errors), 'error');
        }
        $this->subjects->update($this->schoolId(), $id, $data + ['is_active' => $request->input('is_active') === '1' ? 1 : 0]);
        return $this->redirectTo('/school/subjects', $data['name'] . ' saved.');
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function validated(Request $request, int $exceptId = 0): array
    {
        $errors = [];
        $name = trim(preg_replace('/\s+/', ' ', (string) $request->input('name', '')) ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            $errors['name'] = 'Enter a subject name (2–80 characters).';
        } elseif ($this->subjects->nameTaken($this->schoolId(), $name, $exceptId)) {
            $errors['name'] = $name . ' already exists.';
        }
        $code = strtoupper(trim((string) $request->input('code', '')));
        if ($code !== '' && !preg_match('/^[A-Z0-9]{1,12}$/', $code)) {
            $errors['code'] = 'Use up to 12 letters or numbers.';
        }
        $category = (string) $request->input('category', 'general');
        $departmentId = (int) $request->input('department_id', 0);
        if ($departmentId > 0 && $this->departments->find($this->schoolId(), $departmentId) === null) {
            $errors['department_id'] = 'Choose a department from the list.';
        }
        return [[
            'name' => $name,
            'code' => $code === '' ? null : $code,
            'category' => array_key_exists($category, SchoolSubject::CATEGORIES) ? $category : 'general',
            'department_id' => $departmentId > 0 ? $departmentId : null,
        ], $errors];
    }
}
