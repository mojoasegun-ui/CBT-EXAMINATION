<?php
declare(strict_types=1);

namespace ExamCore\Controllers\School;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\Spreadsheet\Spreadsheet;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\AcademicCalendar;
use ExamCore\Models\Department;
use ExamCore\Models\GradingScale;
use ExamCore\Services\AuditService;
use ExamCore\Services\SchoolSetupService;

/** Academic year & terms, departments, grading scale, and the one-click starter setup. */
final class AcademicController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly AcademicCalendar $calendar,
        private readonly Department $departments,
        private readonly GradingScale $grading,
        private readonly SchoolSetupService $setup,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function quickSetup(Request $request): Response
    {
        $created = $this->setup->seedDefaults($this->schoolId());
        $this->audit->record('school.setup', 'Ran quick setup', meta: $created, schoolId: $this->schoolId());
        return $this->redirectTo('/school', sprintf('Ready! Created %d classes, %d subjects and %d departments. Adjust anything you like.', $created['classes'], $created['subjects'], $created['departments']));
    }

    // ---- Academic year ---------------------------------------------------------------

    public function calendar(Request $request): Response
    {
        return $this->page($request, 'school/academic/calendar', $this->base('Academic year') + [
            'sessions' => $this->calendar->sessions($this->schoolId()),
            'current' => $this->calendar->current($this->schoolId()),
        ]);
    }

    public function createSession(Request $request): Response
    {
        $year = (int) $request->input('start_year', 0);
        if ($year < 2000 || $year > 2100) {
            return $this->backWithErrors('/school/academic', ['start_year' => 'Choose a valid starting year.']);
        }
        if ($this->calendar->sessionExists($this->schoolId(), $year . '/' . ($year + 1))) {
            return $this->backWithErrors('/school/academic', ['start_year' => 'That session already exists.']);
        }
        $this->calendar->createSession($this->schoolId(), $year);
        $this->audit->record('calendar.session', 'Created the ' . $year . '/' . ($year + 1) . ' session', schoolId: $this->schoolId());
        return $this->redirectTo('/school/academic', $year . '/' . ($year + 1) . ' session created with three terms.');
    }

    public function setCurrentTerm(Request $request, array $parameters): Response
    {
        $before = $this->calendar->current($this->schoolId())['term']['id'] ?? null;
        $ok = $this->calendar->setCurrentTerm($this->schoolId(), $this->idParam($parameters));
        if ($ok) {
            if ($before !== null && (int) $before !== $this->idParam($parameters)) {
                $this->container->get(\ExamCore\Services\ResultService::class)->freezeTerm($this->schoolId(), (int) $before); // the term just left keeps its rules
            }
            $this->container->get(\ExamCore\Models\Enrolment::class)->sync($this->schoolId());
            $this->audit->record('calendar.current_term', 'Changed the current term', 'term', $this->idParam($parameters), schoolId: $this->schoolId());
        }
        return $this->redirectTo('/school/academic', $ok ? 'Current term updated. New exams and results will use it.' : 'Term not found.', $ok ? 'success' : 'error');
    }

    public function updateTerm(Request $request, array $parameters): Response
    {
        $start = Spreadsheet::toDate((string) $request->input('starts_on', ''));
        $end = Spreadsheet::toDate((string) $request->input('ends_on', ''));
        if ($start !== null && $end !== null && $end < $start) {
            return $this->redirectTo('/school/academic', 'The term cannot end before it starts.', 'error');
        }
        $this->calendar->updateTermDates($this->schoolId(), $this->idParam($parameters), $start, $end);
        return $this->redirectTo('/school/academic', 'Term dates saved.');
    }

    // ---- Departments -------------------------------------------------------------------

    public function departments(Request $request): Response
    {
        return $this->page($request, 'school/academic/departments', $this->base('Departments') + [
            'departments' => $this->departments->all($this->schoolId()),
        ]);
    }

    public function storeDepartment(Request $request): Response
    {
        [$name, $code, $description, $errors] = $this->departmentInput($request);
        if ($errors === [] && $this->departments->nameTaken($this->schoolId(), $name)) {
            $errors['name'] = 'A department with this name already exists.';
        }
        if ($errors !== []) {
            return $this->backWithErrors('/school/departments', $errors, $this->input($request));
        }
        $this->departments->create($this->schoolId(), $name, $code, $description);
        $this->audit->record('department.created', 'Created the ' . $name . ' department', schoolId: $this->schoolId());
        return $this->redirectTo('/school/departments', $name . ' department added.');
    }

    public function updateDepartment(Request $request, array $parameters): Response
    {
        $id = $this->idParam($parameters);
        [$name, $code, $description, $errors] = $this->departmentInput($request);
        if ($errors === [] && $this->departments->nameTaken($this->schoolId(), $name, $id)) {
            $errors['name'] = 'A department with this name already exists.';
        }
        if ($errors !== []) {
            return $this->redirectTo('/school/departments', reset($errors), 'error');
        }
        $this->departments->update($this->schoolId(), $id, ['name' => $name, 'code' => $code, 'description' => $description, 'is_active' => $request->input('is_active') === '1' ? 1 : 0]);
        return $this->redirectTo('/school/departments', 'Department saved.');
    }

    public function deleteDepartment(Request $request, array $parameters): Response
    {
        $department = $this->departments->find($this->schoolId(), $this->idParam($parameters));
        if ($department === null) {
            return $this->redirectTo('/school/departments', 'Department not found.', 'error');
        }
        $this->departments->delete($this->schoolId(), (int) $department['id']);
        $this->audit->record('department.deleted', 'Deleted the ' . $department['name'] . ' department', schoolId: $this->schoolId());
        return $this->redirectTo('/school/departments', $department['name'] . ' removed. Its classes, subjects and students now have no department.', 'warning');
    }

    // ---- Grading -----------------------------------------------------------------------------

    public function grading(Request $request): Response
    {
        return $this->page($request, 'school/academic/grading', $this->base('Grading scale') + ['bands' => $this->grading->all($this->schoolId())]);
    }

    public function saveGrading(Request $request): Response
    {
        $list = static fn (mixed $value): array => is_array($value) ? array_values($value) : [];
        $grades = $list($request->input('grade'));
        $mins = $list($request->input('min'));
        $maxes = $list($request->input('max'));
        $remarks = $list($request->input('remark'));
        $passes = is_array($request->input('pass')) ? $request->input('pass') : [];
        $bands = [];
        foreach ($grades as $i => $grade) {
            $grade = strtoupper(trim((string) $grade));
            if ($grade === '') {
                continue;
            }
            $min = (float) ($mins[$i] ?? 0);
            $max = (float) ($maxes[$i] ?? 0);
            $remark = trim((string) ($remarks[$i] ?? ''));
            if (!preg_match('/^[A-Z][A-Z0-9+-]{0,3}$/', $grade) || $min < 0 || $max > 100 || $min > $max || $remark === '') {
                return $this->redirectTo('/school/grading', 'Row ' . ($i + 1) . ': use a short grade (e.g. B2), scores between 0 and 100 (min ≤ max) and a remark.', 'error');
            }
            $bands[] = [$grade, $min, $max, mb_substr($remark, 0, 40), isset($passes[$i]) ? 1 : 0];
        }
        usort($bands, static fn (array $a, array $b): int => $b[1] <=> $a[1]);
        for ($i = 1; $i < count($bands); $i++) {
            if ($bands[$i][2] >= $bands[$i - 1][1]) {
                return $this->redirectTo('/school/grading', 'Grades ' . $bands[$i - 1][0] . ' and ' . $bands[$i][0] . ' overlap. Each score must fall in exactly one band.', 'error');
            }
        }
        if (count(array_unique(array_column($bands, 0))) !== count($bands) || $bands === []) {
            return $this->redirectTo('/school/grading', 'Each grade must be unique, and you need at least one.', 'error');
        }
        $this->grading->replace($this->schoolId(), $bands);
        $this->audit->record('grading.updated', 'Updated the grading scale', schoolId: $this->schoolId());
        return $this->redirectTo('/school/grading', 'Grading scale saved.');
    }

    public function resetGrading(Request $request): Response
    {
        $this->grading->replace($this->schoolId(), GradingScale::DEFAULT);
        return $this->redirectTo('/school/grading', 'Restored the standard WAEC-style scale (A1–F9).');
    }

    /** @return array{0: string, 1: ?string, 2: ?string, 3: array<string, string>} */
    private function departmentInput(Request $request): array
    {
        $name = trim((string) $request->input('name', ''));
        $code = strtoupper(trim((string) $request->input('code', '')));
        $description = trim((string) $request->input('description', ''));
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
            $errors['name'] = 'Enter a department name (2–60 characters).';
        }
        if ($code !== '' && !preg_match('/^[A-Z0-9]{1,12}$/', $code)) {
            $errors['code'] = 'Use up to 12 letters or numbers.';
        }
        return [$name, $code === '' ? null : $code, $description === '' ? null : mb_substr($description, 0, 255), $errors];
    }
}
