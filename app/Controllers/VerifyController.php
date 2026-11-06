<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\View;
use ExamCore\Models\ExamSitting;
use ExamCore\Models\School;
use ExamCore\Models\SchoolExam;
use ExamCore\Models\Student;
use ExamCore\Services\ResultService;
use ExamCore\Services\SittingService;

/**
 * Public pages: QR verification of slips and report cards, and the parent result checker
 * (school + admission number + the student's private result code).
 */
final class VerifyController extends PortalController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf,
        private readonly ResultService $results,
        private readonly ExamSitting $sittings,
        private readonly SchoolExam $exams,
        private readonly School $schools,
        private readonly Student $students,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function verify(Request $request, array $parameters): Response
    {
        $parsed = ResultService::readToken((string) ($parameters['token'] ?? ''));
        $data = ['title' => 'Verify a result', 'area' => 'school', 'valid' => false];
        if ($parsed !== null && $parsed['kind'] === 's') {
            $sitting = $this->sittings->find($parsed['ids'][0]);
            $exam = $sitting ? $this->exams->find((int) $sitting['school_id'], (int) $sitting['exam_id']) : null;
            if ($sitting && $exam && $sitting['status'] !== 'in_progress' && SittingService::resultsVisible($exam, $sitting)) {
                $school = $this->schools->find((int) $sitting['school_id']);
                $student = $this->students->find((int) $sitting['school_id'], (int) $sitting['student_id']);
                $data = ['valid' => true, 'kind' => 'slip', 'school' => $school, 'student' => $student, 'exam' => $exam, 'sitting' => $sitting,
                    'grade' => $this->results->grade((int) $sitting['school_id'], (float) $sitting['percent'])] + $data;
            }
        } elseif ($parsed !== null && $parsed['kind'] === 'r' && count($parsed['ids']) === 2) {
            [$studentId, $termId] = $parsed['ids'];
            $profile = $this->studentAnywhere($studentId);
            $term = $profile ? $this->results->term((int) $profile['school_id'], $termId) : null;
            if ($profile && $term) {
                $school = (array) $this->schools->find((int) $profile['school_id']);
                $card = $this->results->card($school, $profile, $term, true);
                $data = ['valid' => true, 'kind' => 'report', 'school' => $school, 'student' => $profile, 'term' => $term, 'card' => $card] + $data;
            }
        }
        return $this->page($request, 'public/verify', $data, 'layouts/auth', $data['valid'] ? 200 : 404);
    }

    public function checkForm(Request $request): Response
    {
        return $this->page($request, 'public/check', ['title' => 'Check a result', 'area' => 'school', 'slug' => (string) $request->query('school', '')], 'layouts/signin');
    }

    public function check(Request $request): Response
    {
        $slug = strtolower(trim((string) $request->input('school', '')));
        $admission = strtoupper(trim((string) $request->input('admission_no', '')));
        $code = strtoupper(preg_replace('/\s+/', '', (string) $request->input('code', '')) ?? '');
        $school = $slug !== '' ? $this->schools->findBySlug($slug) : null;
        $student = $school ? $this->students->findByAdmission((int) $school['id'], $admission) : null;
        $realCode = $student ? (string) ($student['result_code'] ?? '') : '';
        if ($student === null || $realCode === '' || !hash_equals($realCode, $code) || in_array($school['status'], ['suspended', 'archived'], true)) {
            usleep(random_int(200_000, 400_000)); // slow down guessing; the route is also rate limited
            return $this->backWithErrors('/check-result', ['code' => 'We could not find a result with those details. Check the admission number and the result code on the report card.'], ['school' => $slug, 'admission_no' => $admission]);
        }
        $rows = array_values(array_filter($this->results->studentResults((int) $school['id'], (int) $student['id']), static fn ($r) => $r['visible']));
        $terms = array_values(array_unique(array_filter(array_map(static fn ($r) => $r['term_id'] !== null ? (int) $r['term_id'] : null, $rows))));
        $cards = [];
        foreach (array_slice($terms, 0, 3) as $termId) {
            if ($term = $this->results->term((int) $school['id'], $termId)) {
                $cards[] = ['term' => $term, 'url' => url('/verify/' . ResultService::token('r', (int) $student['id'], $termId))];
            }
        }
        return $this->page($request, 'public/check-result', ['title' => 'Result · ' . $student['name'], 'area' => 'school', 'school' => $school, 'student' => $student, 'rows' => $rows, 'cards' => $cards], 'layouts/auth');
    }

    private function studentAnywhere(int $studentId): ?array
    {
        $schoolId = $this->students->schoolOf($studentId);
        return $schoolId === null ? null : $this->students->find($schoolId, $studentId) + ['school_id' => $schoolId];
    }
}
