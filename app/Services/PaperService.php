<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Models\ExamSitting;
use ExamCore\Models\SchoolExam;
use PDO;

/**
 * Paper exams.
 *
 * - Versions (Paper A, B …) are generated once and frozen, so printed papers, answer keys and
 *   scanned sheets always agree. All versions share the same questions; B, C, D change the
 *   order of questions (within each subject) and, if enabled, of options.
 * - Each student gets a pre-printed shading sheet whose QR code carries a signed code
 *   (exam, student, version). The phone scanner reads the bubbles in the browser and posts
 *   the answers; they are stored as a normal sitting, so results and report cards just work.
 * - Sheet geometry (millimetres on A4) is defined once here and used both to print the sheet
 *   and, as JSON, by the scanner.
 */
final class PaperService
{
    public const LETTERS = ['A', 'B', 'C', 'D'];
    // Corner markers (10 mm solid squares); centres are what the scanner looks for.
    public const MARKER_SIZE = 10.0;
    public const MARKERS = [[15.0, 15.0], [195.0, 15.0], [15.0, 282.0], [195.0, 282.0]];
    private const GRID_TOP = 70.0;
    private const ROW_H = 7.2;
    private const ROWS = 25;
    private const COL_W = 45.0;
    private const COL_X = 17.0;
    private const BUBBLE_DX = 6.4;
    private const BUBBLE_R = 2.25;

    public function __construct(
        private readonly Database $database,
        private readonly SchoolExam $exams,
        private readonly ExamSitting $sittings,
        private readonly SittingService $sittingService,
    ) {
    }

    private function pdo(): PDO
    {
        return $this->database->connection();
    }

    private function t(string $table): string
    {
        return $this->database->tablePrefix() . $table;
    }

    // ---- Versions ------------------------------------------------------------------------

    /** @return array<string, list<array{paper_id:int, question_id:int, option_order:string}>> version => items */
    public function versions(int $schoolId, array $exam, bool $regenerate = false): array
    {
        $count = max(1, min(4, (int) ($exam['settings']['versions'] ?? 2)));
        $letters = array_slice(self::LETTERS, 0, $count);
        $st = $this->pdo()->prepare('SELECT version, items FROM ' . $this->t('school_exam_versions') . ' WHERE exam_id = ? ORDER BY version');
        $st->execute([(int) $exam['id']]);
        $saved = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $saved[$row['version']] = json_decode((string) $row['items'], true) ?: [];
        }
        if (!$regenerate && $saved !== [] && array_keys($saved) === $letters) {
            return $saved;
        }
        if ($regenerate && $this->paperSittingCount((int) $exam['id']) > 0) {
            throw new HttpException(422, 'Sheets have already been scanned for this exam, so its papers can no longer change.');
        }
        // Same question set for every version.
        $base = [];
        foreach ($exam['papers'] as $paper) {
            $ids = $paper['mode'] === 'fixed'
                ? $this->exams->fixedQuestionIds((int) $paper['id'])
                : $this->exams->questionPool($schoolId, (string) $paper['subject'], $paper['topics'], $paper['difficulty'] ?: null);
            if ($paper['mode'] !== 'fixed') {
                shuffle($ids);
            }
            $ids = array_slice($ids, 0, (int) $paper['question_count']);
            $optionLetters = $this->exams->optionLetters($ids);
            $base[] = ['paper_id' => (int) $paper['id'], 'ids' => $ids, 'letters' => $optionLetters];
        }
        $shuffleOptions = !empty($exam['settings']['shuffle_options']);
        $versions = [];
        foreach ($letters as $i => $v) {
            $items = [];
            foreach ($base as $paper) {
                $ids = $paper['ids'];
                if ($i > 0) {
                    shuffle($ids);
                }
                foreach ($ids as $qid) {
                    $order = str_split($paper['letters'][$qid] ?? 'AB');
                    if ($i > 0 && $shuffleOptions) {
                        shuffle($order);
                    }
                    $items[] = ['paper_id' => $paper['paper_id'], 'question_id' => $qid, 'option_order' => implode('', $order)];
                }
            }
            $versions[$v] = $items;
        }
        $this->database->transaction(function () use ($exam, $versions): void {
            $this->pdo()->prepare('DELETE FROM ' . $this->t('school_exam_versions') . ' WHERE exam_id = ?')->execute([(int) $exam['id']]);
            $ins = $this->pdo()->prepare('INSERT INTO ' . $this->t('school_exam_versions') . ' (exam_id, version, items, created_at) VALUES (?, ?, ?, ?)');
            foreach ($versions as $v => $items) {
                $ins->execute([(int) $exam['id'], $v, json_encode($items), gmdate('Y-m-d H:i:s')]);
            }
        });
        return $versions;
    }

    public function paperSittingCount(int $examId): int
    {
        $st = $this->pdo()->prepare('SELECT COUNT(*) FROM ' . $this->t('exam_sittings') . ' WHERE exam_id = ? AND mode = \'paper\'');
        $st->execute([$examId]);
        return (int) $st->fetchColumn();
    }

    /** Printable questions for one version, in paper order, with options in printed order. @return list<array<string, mixed>> */
    public function questions(array $items): array
    {
        $ids = array_values(array_unique(array_column($items, 'question_id')));
        if ($ids === []) {
            return [];
        }
        $st = $this->pdo()->prepare('SELECT q.*, p.title AS passage_title, p.body AS passage_body FROM ' . $this->t('bank_questions') . ' q
            LEFT JOIN ' . $this->t('bank_passages') . ' p ON p.id = q.passage_id WHERE q.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $st->execute($ids);
        $rows = array_column($st->fetchAll(PDO::FETCH_ASSOC), null, 'id');
        $out = [];
        foreach ($items as $n => $item) {
            $q = $rows[$item['question_id']] ?? null;
            if ($q === null) {
                continue;
            }
            $options = [];
            $answer = null;
            foreach (str_split($item['option_order']) as $i => $key) {
                $options[] = ['letter' => self::LETTERS[$i] ?? chr(65 + $i), 'text' => (string) $q['option_' . strtolower($key)]];
                if ($key === $q['correct_option']) {
                    $answer = chr(65 + $i);
                }
            }
            $out[] = ['n' => $n + 1, 'paper_id' => $item['paper_id'], 'body' => (string) $q['body'], 'image' => $q['image_path'], 'options' => $options,
                'answer' => $answer, 'passage_id' => $q['passage_id'], 'passage_title' => $q['passage_title'], 'passage_body' => $q['passage_body'], 'topic' => $q['topic']];
        }
        return $out;
    }

    /** Theory questions typed by the teacher, split on blank lines. @return list<string> */
    public static function theoryQuestions(array $exam): array
    {
        $text = trim((string) ($exam['settings']['theory'] ?? ''));
        return $text === '' ? [] : array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $text) ?: [])));
    }

    // ---- Sheet code (QR) ---------------------------------------------------------------------

    public static function sheetCode(int $examId, int $studentId, string $version): string
    {
        $payload = "EC1-{$examId}-{$studentId}-{$version}";
        return $payload . '-' . substr(hash_hmac('sha256', $payload, self::key()), 0, 10);
    }

    /** @return array{exam:int, student:int, version:string}|null */
    public static function readSheetCode(string $code): ?array
    {
        if (!preg_match('/^EC1-(\d+)-(\d+)-([A-D])-([a-f0-9]{10})$/', trim($code), $m)) {
            return null;
        }
        $payload = "EC1-{$m[1]}-{$m[2]}-{$m[3]}";
        if (!hash_equals(substr(hash_hmac('sha256', $payload, self::key()), 0, 10), $m[4])) {
            return null;
        }
        return ['exam' => (int) $m[1], 'student' => (int) $m[2], 'version' => $m[3]];
    }

    private static function key(): string
    {
        return 'examcore-omr|' . (string) (env('APP_KEY') ?: 'local');
    }

    /** Which version each student gets: alternate down the class list so neighbours differ. */
    public static function versionFor(int $index, int $versions): string
    {
        return self::LETTERS[$index % max(1, $versions)];
    }

    // ---- Sheet geometry --------------------------------------------------------------------

    /**
     * Bubble positions in millimetres for a version. Shared by the printed sheet and scanner.
     *
     * @return array{markers: list<array{0: float, 1: float}>, markerSize: float, r: float, questions: list<array{n:int, x:float, y:float, letters: list<array{l:string, x:float, y:float}>}>, theory: list<array{row:string, d:int, x:float, y:float}>}
     */
    public static function layout(array $items, int $theoryMax): array
    {
        $questions = [];
        foreach (array_values($items) as $i => $item) {
            $col = intdiv($i, self::ROWS);
            $row = $i % self::ROWS;
            $x0 = self::COL_X + $col * self::COL_W;
            $y = self::GRID_TOP + $row * self::ROW_H;
            $letters = [];
            foreach (str_split((string) $item['option_order']) as $k => $unused) {
                $letters[] = ['l' => chr(65 + $k), 'x' => round($x0 + 11.5 + $k * self::BUBBLE_DX, 2), 'y' => $y];
            }
            $questions[] = ['n' => $i + 1, 'x' => $x0, 'y' => $y, 'letters' => $letters];
        }
        $theory = [];
        if ($theoryMax > 0) {
            $rows = $theoryMax >= 10 ? ['t' => 258.5, 'u' => 266.0] : ['u' => 262.0];
            foreach ($rows as $name => $y) {
                for ($d = 0; $d <= 9; $d++) {
                    $theory[] = ['row' => $name, 'd' => $d, 'x' => 70.0 + $d * 7.5, 'y' => $y];
                }
            }
        }
        return ['markers' => self::MARKERS, 'markerSize' => self::MARKER_SIZE, 'r' => self::BUBBLE_R, 'questions' => $questions, 'theory' => $theory];
    }

    // ---- Recording a scanned sheet --------------------------------------------------------------

    /**
     * Store one scanned sheet as a paper sitting and mark it.
     *
     * @param array<int|string, ?string> $answers question number (1-based) => printed letter or null
     */
    public function recordScan(int $schoolId, array $exam, int $studentId, string $version, array $answers, ?float $theory, ?string $scanPath, int $userId, bool $replace): array
    {
        $versions = $this->versions($schoolId, $exam);
        $items = $versions[$version] ?? throw new HttpException(422, 'This sheet is for a paper version that no longer exists.');
        $existing = $this->sittings->forStudent((int) $exam['id'], $studentId);
        if ($existing !== null) {
            if (($existing['mode'] ?? 'cbt') !== 'paper') {
                throw new HttpException(409, 'This student already wrote this exam on computer.');
            }
            if (!$replace) {
                throw new HttpException(409, 'This sheet was already scanned. Scan again with "Replace" to overwrite it.');
            }
            $this->sittings->delete((int) $existing['id']);
        }
        $theoryMax = (int) ($exam['settings']['theory_max'] ?? 0);
        $now = gmdate('Y-m-d H:i:s');
        $id = $this->database->transaction(function () use ($schoolId, $exam, $studentId, $version, $items, $answers, $theory, $theoryMax, $scanPath, $userId, $now): int {
            $id = $this->sittings->create([
                'school_id' => $schoolId, 'exam_id' => (int) $exam['id'], 'student_id' => $studentId, 'status' => 'in_progress',
                'mode' => 'paper', 'version' => $version, 'started_at' => $now, 'deadline_at' => $now, 'last_seen_at' => $now,
                'question_count' => count($items), 'theory_max' => $theoryMax > 0 ? $theoryMax : null,
                'theory_score' => $theoryMax > 0 && $theory !== null ? max(0, min($theoryMax, $theory)) : null,
                'scan_path' => $scanPath, 'scanned_by' => $userId,
            ], $items) ?? throw new HttpException(409, 'This sheet is being saved by someone else. Try again.');
            foreach ($items as $i => $item) {
                $letter = $answers[$i + 1] ?? $answers[(string) ($i + 1)] ?? null;
                $index = is_string($letter) && strlen($letter) === 1 ? ord(strtoupper($letter)) - 65 : -1;
                $key = $index >= 0 ? (str_split($item['option_order'])[$index] ?? null) : null;
                if ($key !== null) {
                    $this->sittings->saveAnswer($id, $i + 1, $key, false);
                }
            }
            return $id;
        });
        return $this->sittingService->submit($id);
    }

    public function saveTheory(int $sittingId, ?float $score, int $max): array
    {
        $this->sittings->finish($sittingId, ['theory_score' => $score === null ? null : max(0, min($max, $score)), 'theory_max' => $max]);
        return $this->sittingService->regrade($sittingId);
    }
}
