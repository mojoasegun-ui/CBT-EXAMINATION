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
use ExamCore\Models\AcademicCalendar;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\SchoolExam;
use ExamCore\Models\Student;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;
use ExamCore\Services\ResultService;

/** Exam results, result slips, class broadsheet and term report cards. */
final class ResultController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly ResultService $results,
        private readonly SchoolExam $exams,
        private readonly SchoolClass $classes,
        private readonly Student $students,
        private readonly AcademicCalendar $calendar,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    // ---- One exam -----------------------------------------------------------------------

    public function exam(Request $request, array $parameters): Response
    {
        $exam = $this->findExam($parameters);
        return $this->page($request, 'school/exams/results', [
            'title' => 'Results · ' . $exam['title'], 'exam' => $exam,
            'result' => $this->results->examResults($this->schoolId(), $exam),
            'items' => $this->results->itemAnalysis((int) $exam['id']),
        ]);
    }

    public function examExport(Request $request, array $parameters): Response
    {
        $exam = $this->findExam($parameters);
        $r = $this->results->examResults($this->schoolId(), $exam);
        $multi = count($r['subjects']) > 1;
        $header = ['Position', 'Admission No', 'Name', 'Class', ...($multi ? array_map(static fn ($s) => $s . ' %', $r['subjects']) : []), 'Score', 'Out of', 'Percent', 'Grade', 'Remark', 'Integrity'];
        $rows = array_map(static fn ($x) => [
            ResultService::ordinal($x['position']), $x['admission_no'], $x['name'], (string) $x['class_name'],
            ...($multi ? array_map(static fn ($s) => (string) ($x['papers'][$s] ?? ''), $r['subjects']) : []),
            (string) $x['score'], (string) $x['question_count'], (string) $x['percent'], $x['grade']['grade'], $x['grade']['remark'], ucfirst($x['trust']),
        ], $r['rows']);
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', (string) $exam['title']) . '-results';
        return $request->query('format') === 'csv'
            ? new Response(Spreadsheet::csv($header, $rows), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="' . $name . '.csv"'])
            : new Response(Spreadsheet::xlsx($header, $rows, 'Results'), 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $name . '.xlsx"']);
    }

    /** Printable result slips with a QR code that proves each slip is genuine. */
    public function slips(Request $request, array $parameters): Response
    {
        $exam = $this->findExam($parameters);
        $r = $this->results->examResults($this->schoolId(), $exam);
        $only = (int) $request->query('sitting', 0);
        $rows = $only > 0 ? array_values(array_filter($r['rows'], static fn ($x) => (int) $x['id'] === $only)) : $r['rows'];
        foreach ($rows as &$row) {
            $row['verify'] = url('/verify/' . ResultService::token('s', (int) $row['id']));
            $row['qr'] = ResultService::qr($row['verify']);
        }
        unset($row);
        return $this->page($request, 'school/results/slips', ['title' => 'Result slips · ' . $exam['title'], 'exam' => $exam, 'rows' => $rows, 'size' => $r['stats']['count']], 'layouts/print');
    }

    // ---- Term results (administrators) --------------------------------------------------------

    public function index(Request $request): Response
    {
        [$term, $terms] = $this->term($request);
        $classes = $this->classes->all($this->schoolId(), true);
        $classId = (int) $request->query('class', $classes[0]['id'] ?? 0);
        $settings = $this->results->settings($this->tenant->school());
        $sheet = $term && $classId ? $this->results->classSheet($this->schoolId(), $classId, (int) $term['id'], $settings, false) : null;
        return $this->page($request, 'school/results/index', [
            'title' => 'Results', 'terms' => $terms, 'term' => $term, 'classes' => $classes, 'classId' => $classId,
            'sheet' => $sheet, 'settings' => $settings,
        ]);
    }

    public function export(Request $request): Response
    {
        [$term] = $this->term($request);
        $classId = (int) $request->query('class', 0);
        $class = $this->classes->find($this->schoolId(), $classId) ?? throw HttpException::notFound();
        $sheet = $this->results->classSheet($this->schoolId(), $classId, (int) ($term['id'] ?? 0), $this->results->settings($this->tenant->school()), false);
        $header = ['Position', 'Admission No', 'Name'];
        foreach ($sheet['subjects'] as $sub) {
            array_push($header, $sub . ' CA', $sub . ' Exam', $sub . ' Total', $sub . ' Grade');
        }
        array_push($header, 'Subjects', 'Total', 'Average', 'Grade');
        $rows = [];
        foreach ($sheet['students'] as $s) {
            $row = [ResultService::ordinal($s['position']), $s['admission_no'], $s['name']];
            foreach ($sheet['subjects'] as $sub) {
                $m = $s['subjects'][$sub];
                array_push($row, (string) ($m['ca'] ?? ''), (string) ($m['exam'] ?? ''), (string) ($m['total'] ?? ''), $m['total'] !== null ? $m['grade'] : '');
            }
            array_push($row, (string) $s['count'], (string) $s['sum'], (string) ($s['average'] ?? ''), $s['average'] !== null ? $s['overall']['grade'] : '');
            $rows[] = $row;
        }
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', $class['name'] . ' ' . ($term['name'] ?? '')) . '-broadsheet.xlsx';
        return new Response(Spreadsheet::xlsx($header, $rows, 'Broadsheet'), 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $name . '"']);
    }

    public function reportCard(Request $request, array $parameters): Response
    {
        [$term] = $this->term($request);
        $student = $this->students->find($this->schoolId(), $this->idParam($parameters, 'student')) ?? throw HttpException::notFound('Student not found.');
        $card = $term ? $this->card($student, $term, false) : null;
        return $this->page($request, 'school/results/report', ['title' => 'Report card · ' . $student['name'], 'cards' => $card ? [$card] : [], 'editable' => true, 'term' => $term], 'layouts/print');
    }

    /** Every report card of a class, one per A4 page. */
    public function reportCards(Request $request): Response
    {
        [$term] = $this->term($request);
        $classId = (int) $request->query('class', 0);
        $this->classes->find($this->schoolId(), $classId) ?? throw HttpException::notFound();
        $cards = [];
        if ($term) {
            $settings = $this->results->settings($this->tenant->school());
            $sheet = $this->results->classSheet($this->schoolId(), $classId, (int) $term['id'], $settings, false);
            foreach ($sheet['students'] as $row) {
                $student = $this->students->find($this->schoolId(), (int) $row['id']);
                if ($student !== null) {
                    $cards[] = $this->card($student, $term, false, $sheet);
                }
            }
        }
        return $this->page($request, 'school/results/report', ['title' => 'Report cards', 'cards' => $cards, 'editable' => false, 'term' => $term], 'layouts/print');
    }

    public function remarks(Request $request, array $parameters): Response
    {
        [$term] = $this->term($request);
        $studentId = $this->idParam($parameters, 'student');
        $this->students->find($this->schoolId(), $studentId) ?? throw HttpException::notFound();
        if ($term === null) {
            throw HttpException::notFound();
        }
        $clean = static fn ($v): ?string => ($t = mb_substr(trim((string) $v), 0, 400)) === '' ? null : $t;
        $this->results->saveRemarks($this->schoolId(), (int) $term['id'], $studentId, $clean($request->input('teacher_comment')), $clean($request->input('principal_comment')), (int) $this->guard->id());
        return $this->redirectTo('/school/results/students/' . $studentId . '?term=' . $term['id'], 'Comments saved.');
    }

    public function settings(Request $request): Response
    {
        $ca = max(0, min(100, (int) $request->input('ca_weight', 40)));
        $this->results->saveSettings($this->schoolId(), [
            'ca_weight' => $ca, 'exam_weight' => 100 - $ca,
            'principal_name' => mb_substr(trim((string) $request->input('principal_name', '')), 0, 80),
            'next_term_begins' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->input('next_term_begins')) ? (string) $request->input('next_term_begins') : '',
            'show_position' => $request->input('show_position') === '1',
        ]);
        $this->audit->record('results.settings', 'Updated report card settings', 'school', $this->schoolId(), schoolId: $this->schoolId());
        return $this->redirectTo('/school/results?' . http_build_query(['term' => $request->input('term'), 'class' => $request->input('class')]), 'Report card settings saved.');
    }

    public function resultCode(Request $request, array $parameters): Response
    {
        $studentId = $this->idParam($parameters, 'student');
        $this->students->find($this->schoolId(), $studentId) ?? throw HttpException::notFound();
        $this->results->resultCode($this->schoolId(), $studentId, true);
        $this->audit->record('results.code_reset', 'Issued a new parent result code', 'student', $studentId, schoolId: $this->schoolId());
        return $this->redirectTo('/school/students/' . $studentId, 'New parent result code issued. The old one no longer works.');
    }

    // ---- Helpers -----------------------------------------------------------------------------

    private function card(array $student, array $term, bool $releasedOnly, ?array $sheet = null): array
    {
        return $this->results->card($this->tenant->school(), $student, $term, $releasedOnly, $sheet);
    }

    /** @return array{0: ?array, 1: list<array>} selected term (default: current) and all terms */
    private function term(Request $request): array
    {
        $terms = [];
        foreach ($this->calendar->sessions($this->schoolId()) as $session) {
            foreach ($session['terms'] as $t) {
                $terms[] = $t + ['session_name' => $session['name']];
            }
        }
        $want = (int) $request->query('term', $request->input('term', 0));
        $current = $this->calendar->current($this->schoolId())['term'];
        foreach ($terms as $t) {
            if ($want > 0 ? (int) $t['id'] === $want : $current !== null && (int) $t['id'] === (int) $current['id']) {
                return [$t, $terms];
            }
        }
        return [$terms[0] ?? null, $terms];
    }

    private function findExam(array $parameters): array
    {
        $exam = $this->exams->find($this->schoolId(), $this->idParam($parameters)) ?? throw HttpException::notFound('That exam could not be found.');
        if ($this->guard->role() !== User::ROLE_SCHOOL_ADMIN && (int) $exam['created_by'] !== (int) $this->guard->id()) {
            throw HttpException::forbidden('Only the teacher who created this exam or an administrator can see its results.');
        }
        return $exam;
    }
}
