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
use ExamCore\Models\ExamSitting;
use ExamCore\Models\SchoolExam;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;
use ExamCore\Services\PaperService;
use ExamCore\Services\ProctorService;
use ExamCore\Services\ResultService;

/** Paper exams: question papers, answer keys, shading sheets, phone scanning and theory marks. */
final class PaperController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly SchoolExam $exams,
        private readonly ExamSitting $sittings,
        private readonly PaperService $paper,
        private readonly ProctorService $proctor,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function hub(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        $versions = $this->paper->versions($this->schoolId(), $exam);
        $roster = $this->roster($exam);
        $scanned = array_filter($roster, static fn ($r) => $r['mode'] === 'paper');
        return $this->page($request, 'school/paper/hub', [
            'title' => 'Paper exam · ' . $exam['title'], 'exam' => $exam, 'versions' => $versions,
            'roster' => $roster, 'scanned' => count($scanned), 'locked' => $this->paper->paperSittingCount((int) $exam['id']) > 0,
            'theory' => PaperService::theoryQuestions($exam), 'classes' => $this->classNames($exam),
        ]);
    }

    public function regenerate(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        try {
            $this->paper->versions($this->schoolId(), $exam, true);
        } catch (HttpException $e) {
            return $this->redirectTo('/school/exams/' . $exam['id'] . '/paper', $e->getMessage(), 'error');
        }
        return $this->redirectTo('/school/exams/' . $exam['id'] . '/paper', 'New question order created. Reprint the papers and answer sheets.');
    }

    /** The question paper, one version (or all) on A4. */
    public function printPaper(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        $versions = $this->paper->versions($this->schoolId(), $exam);
        $want = strtoupper((string) $request->query('version', 'A'));
        $selected = $want === 'ALL' ? $versions : array_intersect_key($versions, [$want => true]);
        $papers = [];
        foreach ($selected ?: [array_key_first($versions) => reset($versions)] as $v => $items) {
            $papers[$v] = $this->paper->questions($items);
        }
        $this->audit->record('paper.printed', 'Printed question paper ' . implode(', ', array_keys($papers)) . ' of “' . $exam['title'] . '”', 'exam', (int) $exam['id'], schoolId: $this->schoolId());
        return $this->page($request, 'school/paper/print', [
            'title' => 'Question paper · ' . $exam['title'], 'exam' => $exam, 'papers' => $papers,
            'theory' => PaperService::theoryQuestions($exam), 'back' => url('/school/exams/' . $exam['id'] . '/paper'),
            'classes' => $this->classNames($exam),
        ], 'layouts/print');
    }

    public function key(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        $keys = [];
        foreach ($this->paper->versions($this->schoolId(), $exam) as $v => $items) {
            $keys[$v] = array_map(static fn ($q) => ['n' => $q['n'], 'answer' => $q['answer'], 'topic' => $q['topic']], $this->paper->questions($items));
        }
        return $this->page($request, 'school/paper/key', ['title' => 'Marking scheme · ' . $exam['title'], 'exam' => $exam, 'keys' => $keys, 'back' => url('/school/exams/' . $exam['id'] . '/paper')], 'layouts/print');
    }

    /** Shading sheets: one per student, pre-printed with name and a signed QR code. */
    public function sheets(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        $versions = $this->paper->versions($this->schoolId(), $exam);
        $count = count($versions);
        $classFilter = (int) $request->query('class', 0);
        $only = (int) $request->query('student', 0);
        $sheets = [];
        $indexByClass = [];
        foreach ($this->roster($exam) as $r) {
            $i = $indexByClass[$r['class_id']] = ($indexByClass[$r['class_id']] ?? -1) + 1;
            if (($classFilter && (int) $r['class_id'] !== $classFilter) || ($only && (int) $r['student_id'] !== $only)) {
                continue;
            }
            $v = PaperService::versionFor($i, $count);
            $code = PaperService::sheetCode((int) $exam['id'], (int) $r['student_id'], $v);
            $sheets[] = ['version' => $v, 'qr' => ResultService::qr($code), 'layout' => PaperService::layout($versions[$v], (int) $exam['settings']['theory_max'])] + $r;
        }
        $blank = $request->query('blank') === '1';
        return $this->page($request, 'school/paper/sheets', [
            'title' => 'Answer sheets · ' . $exam['title'], 'exam' => $exam, 'sheets' => $sheets, 'blank' => $blank,
            'back' => url('/school/exams/' . $exam['id'] . '/paper'),
        ], 'layouts/print');
    }

    public function scanner(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        $versions = $this->paper->versions($this->schoolId(), $exam);
        $layouts = [];
        foreach ($versions as $v => $items) {
            $layouts[$v] = PaperService::layout($items, (int) $exam['settings']['theory_max']);
        }
        $names = [];
        foreach ($this->roster($exam) as $r) {
            $names[$r['student_id']] = ['name' => $r['name'], 'adm' => $r['admission_no'], 'scanned' => $r['mode'] === 'paper', 'class' => $r['class_name']];
        }
        return $this->page($request, 'school/paper/scan', [
            'title' => 'Scan sheets · ' . $exam['title'], 'exam' => $exam,
            'config' => ['exam' => (int) $exam['id'], 'layouts' => $layouts, 'students' => $names, 'theoryMax' => (int) $exam['settings']['theory_max'],
                'saveUrl' => url('/school/exams/' . $exam['id'] . '/paper/scan'), 'jsqr' => asset('vendor/jsqr/jsQR.js')],
        ]);
    }

    /** JSON: one confirmed sheet from the phone scanner. */
    public function saveScan(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        $body = $request->json();
        $code = PaperService::readSheetCode((string) ($body['code'] ?? ''));
        if ($code === null || $code['exam'] !== (int) $exam['id']) {
            return Response::json(['error' => 'This sheet does not belong to this exam (or its QR code is damaged).'], 422);
        }
        $student = array_values(array_filter($this->roster($exam), static fn ($r) => (int) $r['student_id'] === $code['student']))[0] ?? null;
        if ($student === null) {
            return Response::json(['error' => 'This student is not in the classes for this exam.'], 422);
        }
        $answers = [];
        foreach ((array) ($body['answers'] ?? []) as $n => $letter) {
            if ((int) $n >= 1 && $n <= 100) {
                $answers[(int) $n] = is_string($letter) && preg_match('/^[A-E]$/', $letter) ? $letter : null;
            }
        }
        $theory = isset($body['theory']) && is_numeric($body['theory']) ? (float) $body['theory'] : null;
        $path = null;
        if (is_string($body['image'] ?? null) && str_starts_with($body['image'], 'data:image/jpeg;base64,')) {
            $bytes = base64_decode(substr($body['image'], 23), true);
            $path = $bytes !== false ? $this->proctor->storeImage($this->schoolId(), (int) $exam['id'], $bytes) : null;
        }
        try {
            $sitting = $this->paper->recordScan($this->schoolId(), $exam, $code['student'], $code['version'], $answers, $theory, $path, (int) $this->guard->id(), !empty($body['replace']));
        } catch (HttpException $e) {
            return Response::json(['error' => $e->getMessage(), 'already' => $e->status() === 409], $e->status());
        }
        return Response::json([
            'saved' => true, 'student' => $student['name'], 'admission_no' => $student['admission_no'],
            'score' => (int) $sitting['score'], 'out_of' => (int) $sitting['question_count'], 'percent' => (float) $sitting['percent'],
        ]);
    }

    public function theoryForm(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        return $this->page($request, 'school/paper/theory', [
            'title' => 'Theory scores · ' . $exam['title'], 'exam' => $exam, 'roster' => $this->roster($exam),
            'max' => (int) $exam['settings']['theory_max'],
        ]);
    }

    public function saveTheory(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        $max = (int) $exam['settings']['theory_max'];
        if ($max <= 0) {
            return $this->redirectTo('/school/exams/' . $exam['id'] . '/paper', 'This exam has no theory section.', 'error');
        }
        $scores = (array) $request->input('theory', []);
        $saved = 0;
        $bad = [];
        foreach ($this->roster($exam) as $r) {
            if ($r['sitting_id'] === null || !array_key_exists((string) $r['student_id'], $scores)) {
                continue;
            }
            $raw = trim((string) $scores[(string) $r['student_id']]);
            if ($raw === '' && $r['theory_score'] === null) {
                continue;
            }
            if ($raw !== '' && (!is_numeric($raw) || (float) $raw < 0 || (float) $raw > $max)) {
                $bad[] = $r['name'];
                continue;
            }
            $value = $raw === '' ? null : round((float) $raw, 1);
            if ($value !== ($r['theory_score'] === null ? null : (float) $r['theory_score'])) {
                $this->paper->saveTheory((int) $r['sitting_id'], $value, $max);
                $saved++;
            }
        }
        $msg = $saved . ' theory score' . ($saved === 1 ? '' : 's') . ' saved.' . ($bad ? ' Check: ' . implode(', ', array_slice($bad, 0, 5)) . " (must be 0–{$max})." : '');
        return $this->redirectTo('/school/exams/' . $exam['id'] . '/paper/theory', $msg, $bad ? 'warning' : 'success');
    }

    public function scanImage(Request $request, array $parameters): Response
    {
        $exam = $this->exam($parameters);
        $sitting = $this->sittings->find((int) ($parameters['sitting'] ?? 0));
        if ($sitting === null || (int) $sitting['exam_id'] !== (int) $exam['id'] || empty($sitting['scan_path'])) {
            throw HttpException::notFound();
        }
        $file = $this->proctor->path((string) $sitting['scan_path']) ?? throw HttpException::notFound();
        return new Response((string) file_get_contents($file), 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=3600']);
    }

    // ---- Helpers -----------------------------------------------------------------------------

    /** Students in the exam's classes with their sitting (if any). @return list<array<string, mixed>> */
    private function roster(array $exam): array
    {
        $pdo = $this->service(\ExamCore\Core\Database::class)->connection();
        $px = $this->service(\ExamCore\Core\Database::class)->tablePrefix();
        $st = $pdo->prepare("SELECT u.id AS student_id, u.name, sp.admission_no, sp.class_id, c.name AS class_name,
                s.id AS sitting_id, s.mode, s.version, s.score, s.percent, s.question_count, s.theory_score, s.scan_path
            FROM {$px}school_exam_classes ec
            JOIN {$px}student_profiles sp ON sp.class_id = ec.class_id AND sp.school_id = ? AND sp.status = 'active'
            JOIN {$px}users u ON u.id = sp.user_id
            JOIN {$px}school_classes c ON c.id = sp.class_id
            LEFT JOIN {$px}exam_sittings s ON s.exam_id = ec.exam_id AND s.student_id = u.id
            WHERE ec.exam_id = ? ORDER BY c.level_order, c.name, u.name");
        $st->execute([$this->schoolId(), (int) $exam['id']]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function classNames(array $exam): string
    {
        return implode(', ', array_unique(array_column($this->roster($exam), 'class_name')));
    }

    private function exam(array $parameters): array
    {
        $exam = $this->exams->find($this->schoolId(), $this->idParam($parameters)) ?? throw HttpException::notFound('That exam could not be found.');
        if ($this->guard->role() !== User::ROLE_SCHOOL_ADMIN && (int) $exam['created_by'] !== (int) $this->guard->id()) {
            throw HttpException::forbidden('Only the teacher who created this exam or an administrator can manage it.');
        }
        if (!SchoolExam::isPaper($exam)) {
            throw new HttpException(404, 'This exam is written on computer. Change “Delivery” to paper to use these pages.');
        }
        return $exam;
    }
}
