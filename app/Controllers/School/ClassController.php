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
use ExamCore\Models\Department;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\SchoolSubject;
use ExamCore\Models\Teacher;
use ExamCore\Services\AuditService;
use ExamCore\Services\StudentService;

final class ClassController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly SchoolClass $classes,
        private readonly Department $departments,
        private readonly SchoolSubject $subjects,
        private readonly Teacher $teachers,
        private readonly StudentService $students,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function index(Request $request): Response
    {
        $classes = $this->classes->all($this->schoolId());
        $byLevel = [];
        foreach ($classes as $class) {
            $byLevel[$class['level']][] = $class;
        }
        return $this->page($request, 'school/classes/index', $this->base('Classes') + [
            'byLevel' => $byLevel,
            'total' => count($classes),
            'students' => array_sum(array_column($classes, 'student_count')),
            'levels' => array_keys(SchoolClass::LEVELS),
            'departments' => $this->departments->all($this->schoolId(), true),
            'teachers' => $this->teachers->all($this->schoolId(), activeOnly: true),
        ]);
    }

    public function store(Request $request): Response
    {
        [$data, $errors] = $this->validated($request);
        $arms = array_values(array_unique(array_filter(array_map(static fn ($a): string => strtoupper(trim($a)), explode(',', (string) $request->input('arm', 'A'))))));
        if ($arms === []) {
            $errors['arm'] = 'Enter at least one arm, e.g. A or A, B, C.';
        }
        foreach ($arms as $arm) {
            if (!preg_match('/^[A-Z0-9 ]{1,20}$/', $arm)) {
                $errors['arm'] = 'Arms are short labels like A, B, Gold or Science.';
            } elseif ($errors === [] && $this->classes->exists($this->schoolId(), $data['level'], $arm)) {
                $errors['arm'] = $data['level'] . ' ' . $arm . ' already exists.';
            }
        }
        if ($errors !== []) {
            return $this->backWithErrors('/school/classes', $errors, $this->input($request), reset($errors));
        }
        foreach ($arms as $arm) {
            $this->classes->create($this->schoolId(), $data['level'], $arm, $data['department_id'], $data['class_teacher_id'], $data['capacity']);
        }
        $names = implode(', ', array_map(static fn (string $arm): string => $data['level'] . ' ' . $arm, $arms));
        $this->audit->record('class.created', 'Created ' . $names, schoolId: $this->schoolId());
        return $this->redirectTo('/school/classes', 'Created ' . $names . '.');
    }

    public function show(Request $request, array $parameters): Response
    {
        $class = $this->find($parameters);
        $assigned = $this->subjects->forClass($this->schoolId(), (int) $class['id']);
        return $this->page($request, 'school/classes/show', $this->base($class['name']) + [
            'class' => $class,
            'assigned' => array_column($assigned, null, 'id'),
            'subjects' => $this->subjects->all($this->schoolId(), true),
            'teachers' => $this->teachers->all($this->schoolId(), activeOnly: true),
            'departments' => $this->departments->all($this->schoolId(), true),
            'levels' => array_keys(SchoolClass::LEVELS),
            'studentCount' => $this->classes->studentCount($this->schoolId(), (int) $class['id']),
        ]);
    }

    public function update(Request $request, array $parameters): Response
    {
        $class = $this->find($parameters);
        [$data, $errors] = $this->validated($request);
        $arm = strtoupper(trim((string) $request->input('arm', '')));
        if (!preg_match('/^[A-Z0-9 ]{1,20}$/', $arm)) {
            $errors['arm'] = 'Arms are short labels like A, B, Gold or Science.';
        } elseif ($this->classes->exists($this->schoolId(), $data['level'], $arm, (int) $class['id'])) {
            $errors['arm'] = $data['level'] . ' ' . $arm . ' already exists.';
        }
        if ($errors !== []) {
            return $this->redirectTo('/school/classes/' . $class['id'], reset($errors), 'error');
        }
        $this->classes->update($this->schoolId(), (int) $class['id'], $data + ['arm' => $arm]);
        return $this->redirectTo('/school/classes/' . $class['id'], 'Class saved.');
    }

    public function subjects(Request $request, array $parameters): Response
    {
        $class = $this->find($parameters);
        $chosen = is_array($request->input('subjects')) ? $request->input('subjects') : [];
        $teacherInput = is_array($request->input('teacher')) ? $request->input('teacher') : [];
        $plan = [];
        foreach ($chosen as $subjectId) {
            $teacher = (int) ($teacherInput[$subjectId] ?? 0);
            $plan[(int) $subjectId] = $teacher > 0 ? $teacher : null;
        }
        $this->subjects->syncClass($this->schoolId(), (int) $class['id'], $plan,
            array_map(static fn (array $s): int => (int) $s['id'], $this->subjects->all($this->schoolId())), $this->teachers->activeIds($this->schoolId()));
        $this->audit->record('class.subjects', 'Updated subjects and teachers for ' . $class['name'], 'class', (int) $class['id'], ['subjects' => count($plan)], $this->schoolId());
        return $this->redirectTo('/school/classes/' . $class['id'], count($plan) . ' subject(s) offered in ' . $class['name'] . '.');
    }

    public function delete(Request $request, array $parameters): Response
    {
        $class = $this->find($parameters);
        if ($this->classes->studentCount($this->schoolId(), (int) $class['id']) > 0) {
            return $this->redirectTo('/school/classes/' . $class['id'], 'Move or promote the students in ' . $class['name'] . ' before deleting it.', 'error');
        }
        $this->classes->delete($this->schoolId(), (int) $class['id']);
        $this->audit->record('class.deleted', 'Deleted ' . $class['name'], schoolId: $this->schoolId());
        return $this->redirectTo('/school/classes', $class['name'] . ' deleted.', 'warning');
    }

    // ---- Promotion -----------------------------------------------------------------------

    public function promoteForm(Request $request): Response
    {
        $classes = $this->classes->all($this->schoolId(), true);
        $suggestions = [];
        foreach ($classes as $class) {
            $next = $this->classes->nextLevel($this->schoolId(), $class);
            $suggestions[(int) $class['id']] = $class['level'] === SchoolClass::FINAL_LEVEL || $next === null ? 'graduate' : (int) $next['id'];
        }
        return $this->page($request, 'school/classes/promote', $this->base('Promote students') + ['classes' => $classes, 'suggestions' => $suggestions,
            'current' => $this->container->get(\ExamCore\Models\AcademicCalendar::class)->current($this->schoolId())]);
    }

    public function promote(Request $request): Response
    {
        $plan = [];
        foreach ((is_array($request->input('to')) ? $request->input('to') : []) as $from => $to) {
            if ($to === 'graduate' || (int) $to > 0) {
                $plan[(int) $from] = $to === 'graduate' ? 'graduate' : (int) $to;
            }
        }
        if ($plan === []) {
            return $this->redirectTo('/school/classes/promote', 'Choose where at least one class should move.', 'error');
        }
        $currentTerm = $this->container->get(\ExamCore\Models\AcademicCalendar::class)->current($this->schoolId())['term']['id'] ?? null;
        if ($currentTerm !== null) {
            $this->container->get(\ExamCore\Services\ResultService::class)->freezeTerm($this->schoolId(), (int) $currentTerm); // the closing term keeps its rules
        }
        $result = $this->students->promote($this->schoolId(), $plan);
        $note = '';
        if ($request->input('start_next') === '1') {
            $note = ' ' . $this->startNextSession();
        }
        return $this->redirectTo('/school/classes', sprintf('Done: %d student(s) moved up and %d graduated.', $result['moved'], $result['graduated']) . $note);
    }

    /** Create the next academic session (if missing), make its first term current and open everyone's new-session record. */
    private function startNextSession(): string
    {
        $calendar = $this->container->get(\ExamCore\Models\AcademicCalendar::class);
        $current = $calendar->current($this->schoolId());
        $startYear = $current['session'] !== null ? (int) substr((string) $current['session']['name'], 0, 4) + 1 : (int) gmdate('Y');
        $name = $startYear . '/' . ($startYear + 1);
        if (!$calendar->sessionExists($this->schoolId(), $name)) {
            $calendar->createSession($this->schoolId(), $startYear);
        }
        foreach ($calendar->sessions($this->schoolId()) as $session) {
            if ($session['name'] === $name && ($session['terms'][0]['id'] ?? null) !== null) {
                $calendar->setCurrentTerm($this->schoolId(), (int) $session['terms'][0]['id']);
                $this->container->get(\ExamCore\Models\Enrolment::class)->sync($this->schoolId());
                return 'The ' . $name . ' session has started; its First Term is now current.';
            }
        }
        return 'Create the next session under Academic year.';
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function validated(Request $request): array
    {
        $errors = [];
        $level = (string) $request->input('level', '');
        if (!array_key_exists($level, SchoolClass::LEVELS)) {
            $errors['level'] = 'Choose a level.';
        }
        $departmentId = (int) $request->input('department_id', 0);
        if ($departmentId > 0 && $this->departments->find($this->schoolId(), $departmentId) === null) {
            $errors['department_id'] = 'Choose a department from the list.';
        }
        $teacherId = (int) $request->input('class_teacher_id', 0);
        if ($teacherId > 0 && !in_array($teacherId, $this->teachers->activeIds($this->schoolId()), true)) {
            $errors['class_teacher_id'] = 'Choose a teacher from the list.';
        }
        $capacity = trim((string) $request->input('capacity', ''));
        return [[
            'level' => $level,
            'department_id' => $departmentId > 0 ? $departmentId : null,
            'class_teacher_id' => $teacherId > 0 ? $teacherId : null,
            'capacity' => $capacity === '' ? null : max(1, min(999, (int) $capacity)),
        ], $errors];
    }

    /** @return array<string, mixed> */
    private function find(array $parameters): array
    {
        return $this->classes->find($this->schoolId(), $this->idParam($parameters)) ?? throw HttpException::notFound('That class could not be found.');
    }
}
