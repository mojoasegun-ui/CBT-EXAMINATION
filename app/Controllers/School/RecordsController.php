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
use ExamCore\Core\Spreadsheet\Spreadsheet;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\Enrolment;
use ExamCore\Models\Student;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;

/**
 * Records & history: look up any student in any past or present session. A school administrator sees every student;
 * a teacher sees the students who are in classes they teach today (their whole history, not only this year).
 */
final class RecordsController extends SchoolController
{
    private const PER_PAGE = 25;

    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly Enrolment $enrolments,
        private readonly Student $students,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    /** @return list<int>|null null = no restriction (administrator) */
    private function scope(): ?array
    {
        return $this->guard->role() === User::ROLE_SCHOOL_ADMIN ? null : $this->enrolments->teacherClassIds($this->schoolId(), (int) $this->guard->id());
    }

    public function index(Request $request): Response
    {
        $sessions = $this->enrolments->sessions($this->schoolId());
        $ids = array_map(static fn (array $s): int => (int) $s['id'], $sessions);
        $asked = (int) $request->query('session', 0);
        $current = 0;
        foreach ($sessions as $s) {
            if ($s['is_current']) {
                $current = (int) $s['id'];
            }
        }
        $sessionId = in_array($asked, $ids, true) ? $asked : ($current ?: ($ids[0] ?? 0));
        $filters = [
            'class' => (int) $request->query('class', 0),
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 80),
            'outcome' => (string) $request->query('outcome', ''),
        ];
        $page = max(1, (int) $request->query('page', 1));
        $scope = $this->scope();
        $result = $sessionId > 0 ? $this->enrolments->inSession($this->schoolId(), $sessionId, $filters, $page, self::PER_PAGE, $scope) : ['rows' => [], 'total' => 0];
        return $this->page($request, 'school/records/index', $this->base('Records & history') + [
            'sessions' => $sessions,
            'sessionId' => $sessionId,
            'classes' => $sessionId > 0 ? $this->enrolments->classesIn($this->schoolId(), $sessionId, $scope) : [],
            'filters' => $filters,
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'isAdmin' => $scope === null,
        ]);
    }

    public function show(Request $request, array $parameters): Response
    {
        return $this->page($request, 'school/records/show', $this->record($parameters, 'Student record'));
    }

    public function print(Request $request, array $parameters): Response
    {
        $data = $this->record($parameters, 'Student record');
        $data['back'] = url('/school/records/' . (int) $data['student']['id']);
        return $this->page($request, 'school/records/print', $data, 'layouts/print');
    }

    public function export(Request $request, array $parameters): Response
    {
        $data = $this->record($parameters, 'Student record');
        $rows = array_map(static fn (array $r): array => [
            $r['session_name'] ?? '', $r['term_name'] ?? '', $r['title'], ucfirst((string) $r['type']), $r['score'] ?? '', $r['percent'] === null ? '' : (string) round((float) $r['percent'], 1), $r['question_count'] ?? '',
            $r['submitted_at'] ? local_date((string) $r['submitted_at'], 'Y-m-d H:i') : '',
        ], $data['results']);
        $this->audit->record('student.record_exported', 'Exported the record of ' . $data['student']['name'], 'student', (int) $data['student']['id'], schoolId: $this->schoolId());
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $data['student']['admission_no']) ?: 'student';
        return new Response(Spreadsheet::csv(['Session', 'Term', 'Exam', 'Type', 'Score', 'Percent', 'Questions', 'Submitted'], $rows), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="record-' . $name . '.csv"',
        ]);
    }

    /** @return array<string, mixed> */
    private function record(array $parameters, string $title): array
    {
        $studentId = $this->idParam($parameters);
        $student = $this->students->find($this->schoolId(), $studentId) ?? throw HttpException::notFound('That student could not be found.');
        $scope = $this->scope();
        if ($scope !== null && !$this->enrolments->inClasses($this->schoolId(), $studentId, $scope)) {
            throw HttpException::notFound('That student could not be found.'); // same answer as "no such student": nothing is revealed
        }
        $this->audit->record('student.record_viewed', 'Opened the record of ' . $student['name'], 'student', $studentId, schoolId: $this->schoolId());
        $isAdmin = $scope === null;
        if (!$isAdmin) {
            // Teachers get the academic story only, not family contact details.
            foreach (['guardian_name', 'guardian_phone', 'guardian_email', 'address', 'date_of_birth', 'email', 'phone'] as $private) {
                $student[$private] = null;
            }
        }
        $results = $this->enrolments->results($this->schoolId(), $studentId);
        $bySession = [];
        foreach ($results as $r) {
            $bySession[$r['session_name'] ?? 'Not filed under a term'][$r['term_name'] ?? '—'][] = $r;
        }
        $percents = array_values(array_filter(array_map(static fn (array $r): ?float => $r['percent'] === null ? null : (float) $r['percent'], $results), static fn ($v): bool => $v !== null));
        return [
            'title' => $title . ' · ' . $student['name'],
            'student' => $student,
            'timeline' => $this->enrolments->timeline($this->schoolId(), $studentId),
            'results' => $results,
            'bySession' => $bySession,
            'summary' => ['exams' => count($results), 'avg' => $percents === [] ? null : round(array_sum($percents) / count($percents), 1), 'best' => $percents === [] ? null : max($percents)],
            'isAdmin' => $isAdmin,
        ];
    }
}
