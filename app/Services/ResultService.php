<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use ExamCore\Models\GradingScale;
use PDO;

/**
 * Results: exam result lists with grades and positions, item analysis, term report cards and
 * the class broadsheet. Term results combine every exam of the term per subject:
 *   CA     = average of class tests / CA / mock papers in that subject
 *   Exam   = average of terminal exam papers in that subject
 *   Total  = CA × ca_weight% + Exam × exam_weight%  (or whichever exists)
 */
final class ResultService
{
    public const DEFAULT_SETTINGS = ['ca_weight' => 40, 'exam_weight' => 60, 'principal_name' => '', 'next_term_begins' => '', 'show_position' => true];
    /** @var array<int, list<array<string, mixed>>> */
    private array $bands = [];

    public function __construct(private readonly Database $database, private readonly GradingScale $grading)
    {
    }

    private function pdo(): PDO
    {
        return $this->database->connection();
    }

    private function t(string $table): string
    {
        return $this->database->tablePrefix() . $table;
    }

    public function settings(array $school): array
    {
        $saved = json_decode((string) ($school['report_settings'] ?? ''), true);
        return (is_array($saved) ? $saved : []) + self::DEFAULT_SETTINGS;
    }

    public function saveSettings(int $schoolId, array $settings): void
    {
        $this->pdo()->prepare('UPDATE ' . $this->t('schools') . ' SET report_settings = ?, updated_at = ? WHERE id = ?')
            ->execute([json_encode($settings), gmdate('Y-m-d H:i:s'), $schoolId]);
    }

    /** @var array<int, array{bands: list<array<string,mixed>>, settings: array<string,mixed>}|null> */
    private array $frozenCache = [];

    /**
     * The grading bands and report settings a term was issued with, or null when the term still follows the school's
     * live rules (it is the current term, or nobody has frozen it yet).
     *
     * @return array{bands: list<array<string,mixed>>, settings: array<string,mixed>}|null
     */
    public function frozenRules(?int $termId): ?array
    {
        if ($termId === null || $termId < 1) {
            return null;
        }
        if (!array_key_exists($termId, $this->frozenCache)) {
            $st = $this->pdo()->prepare('SELECT snapshot FROM ' . $this->t('terms') . ' WHERE id = ? AND snapshot IS NOT NULL');
            $st->execute([$termId]);
            $decoded = json_decode((string) $st->fetchColumn(), true);
            $this->frozenCache[$termId] = is_array($decoded) && isset($decoded['bands'], $decoded['settings']) ? $decoded : null;
        }
        return $this->frozenCache[$termId];
    }

    /** Copy today's grading bands and report settings into the term. Does nothing if it is already frozen. */
    public function freezeTerm(int $schoolId, int $termId): bool
    {
        $st = $this->pdo()->prepare('SELECT report_settings FROM ' . $this->t('schools') . ' WHERE id = ?');
        $st->execute([$schoolId]);
        $settings = $this->settings(['report_settings' => $st->fetchColumn() ?: '']);
        $bands = array_map(static fn (array $b): array => ['grade' => $b['grade'], 'min_score' => $b['min_score'], 'max_score' => $b['max_score'], 'remark' => $b['remark'], 'is_pass' => $b['is_pass']], $this->grading->all($schoolId));
        $snapshot = json_encode(['bands' => $bands, 'settings' => array_intersect_key($settings, array_flip(['ca_weight', 'exam_weight', 'principal_name', 'next_term_begins', 'show_position']))], JSON_THROW_ON_ERROR);
        $up = $this->pdo()->prepare('UPDATE ' . $this->t('terms') . ' SET snapshot = ?, frozen_at = ? WHERE id = ? AND school_id = ? AND snapshot IS NULL');
        $up->execute([$snapshot, gmdate('Y-m-d H:i:s'), $termId, $schoolId]);
        unset($this->frozenCache[$termId]);
        return $up->rowCount() > 0;
    }

    /**
     * Freeze terms that are over: every past term, and the current term once 30 days have passed since it ended.
     * Safe to run any time; frozen terms are never touched again.
     */
    public function freezeEndedTerms(): int
    {
        $today = gmdate('Y-m-d');
        $st = $this->pdo()->prepare('SELECT id, school_id FROM ' . $this->t('terms') . ' WHERE snapshot IS NULL AND ends_on IS NOT NULL
            AND ((is_current = 0 AND ends_on < ?) OR (is_current = 1 AND ends_on < ?)) LIMIT 500');
        $st->execute([$today, gmdate('Y-m-d', time() - 30 * 86400)]);
        $n = 0;
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $term) {
            $n += $this->freezeTerm((int) $term['school_id'], (int) $term['id']) ? 1 : 0;
        }
        return $n;
    }

    /** @return array{grade:string, remark:string, pass:bool} */
    public function grade(int $schoolId, ?float $percent, ?array $bands = null): array
    {
        if ($percent === null) {
            return ['grade' => '—', 'remark' => '', 'pass' => false];
        }
        $bands ??= ($this->bands[$schoolId] ??= $this->grading->all($schoolId));
        foreach ($bands as $band) {
            if ($percent >= (float) $band['min_score'] - 0.0001) {
                return ['grade' => (string) $band['grade'], 'remark' => (string) $band['remark'], 'pass' => (bool) $band['is_pass']];
            }
        }
        return ['grade' => '—', 'remark' => '', 'pass' => false];
    }

    /** "1st", "2nd", "3rd", "11th" … */
    public static function ordinal(?int $n): string
    {
        if ($n === null) {
            return '—';
        }
        $suffix = in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
        return $n . $suffix;
    }

    /** Competition ranking (1, 2, 2, 4). @param array<int|string, ?float> $scores @return array<int|string, ?int> */
    public static function rank(array $scores): array
    {
        $valid = array_filter($scores, static fn ($v) => $v !== null);
        arsort($valid);
        $ranks = array_fill_keys(array_keys($scores), null);
        $pos = 0;
        $prev = null;
        $i = 0;
        foreach ($valid as $key => $score) {
            $i++;
            if ($prev === null || abs($score - $prev) > 0.0001) {
                $pos = $i;
                $prev = $score;
            }
            $ranks[$key] = $pos;
        }
        return $ranks;
    }

    /** Results of one exam: every submitted sitting with grade and position, plus statistics. */
    public function examResults(int $schoolId, array $exam): array
    {
        $st = $this->pdo()->prepare('SELECT s.id, s.student_id, s.score, s.percent, s.question_count, s.answered_count, s.breakdown, s.status, s.submitted_at, s.started_at,
                s.warnings, s.flags, s.face_status, u.name, sp.admission_no, c.name AS class_name
            FROM ' . $this->t('exam_sittings') . ' s
            JOIN ' . $this->t('users') . ' u ON u.id = s.student_id
            JOIN ' . $this->t('student_profiles') . ' sp ON sp.user_id = s.student_id
            LEFT JOIN ' . $this->t('school_classes') . ' c ON c.id = sp.class_id
            WHERE s.exam_id = ? AND s.school_id = ? AND s.status <> \'in_progress\' ORDER BY s.percent DESC, u.name');
        $st->execute([(int) $exam['id'], $schoolId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $frozenBands = $this->frozenRules((int) ($exam['term_id'] ?? 0))['bands'] ?? null;
        $ranks = self::rank(array_map(static fn ($r) => (float) $r['percent'], array_column($rows, null, 'id')));
        $passMark = (float) ($exam['settings']['pass_mark'] ?? 50);
        $subjects = array_column($exam['papers'], 'subject');
        $dist = [];
        foreach ($rows as &$r) {
            $r['position'] = $ranks[$r['id']];
            $r['grade'] = $this->grade($schoolId, (float) $r['percent'], $frozenBands);
            $r['passed'] = (float) $r['percent'] >= $passMark;
            $r['papers'] = [];
            foreach (json_decode((string) $r['breakdown'], true) ?: [] as $b) {
                $r['papers'][$b['subject']] = $b['total'] > 0 ? round($b['correct'] * 100 / $b['total'], 1) : 0.0;
            }
            $r['trust'] = ProctorService::trust($r)['level'];
            $dist[$r['grade']['grade']] = ($dist[$r['grade']['grade']] ?? 0) + 1;
        }
        unset($r);
        $percents = array_map(static fn ($r) => (float) $r['percent'], $rows);
        $order = array_column($frozenBands ?? ($this->bands[$schoolId] ?? $this->grading->all($schoolId)), 'grade');
        $distribution = [];
        foreach ($order as $g) {
            $distribution[] = ['label' => $g, 'count' => $dist[$g] ?? 0];
        }
        $subjectAvg = [];
        foreach ($subjects as $sub) {
            $vals = array_values(array_filter(array_map(static fn ($r) => $r['papers'][$sub] ?? null, $rows), static fn ($v) => $v !== null));
            $subjectAvg[$sub] = $vals ? round(array_sum($vals) / count($vals), 1) : null;
        }
        return [
            'rows' => $rows, 'subjects' => $subjects, 'distribution' => $distribution, 'subjectAvg' => $subjectAvg,
            'stats' => [
                'count' => count($rows),
                'average' => $percents ? round(array_sum($percents) / count($percents), 1) : null,
                'highest' => $percents ? max($percents) : null,
                'lowest' => $percents ? min($percents) : null,
                'passRate' => $rows ? round(count(array_filter($rows, static fn ($r) => $r['passed'])) * 100 / count($rows)) : null,
            ],
        ];
    }

    /** Questions students found hardest (lowest % correct). @return list<array<string, mixed>> */
    public function itemAnalysis(int $examId, int $limit = 15): array
    {
        $st = $this->pdo()->prepare('SELECT q.id, q.body, q.topic, q.correct_option, p.subject, COUNT(*) AS attempts,
                SUM(i.answer = q.correct_option) AS correct, SUM(i.answer IS NULL) AS skipped,
                SUM(i.answer = \'A\') AS a, SUM(i.answer = \'B\') AS b, SUM(i.answer = \'C\') AS c, SUM(i.answer = \'D\') AS d, SUM(i.answer = \'E\') AS e
            FROM ' . $this->t('exam_sitting_items') . ' i
            JOIN ' . $this->t('exam_sittings') . ' s ON s.id = i.sitting_id AND s.status <> \'in_progress\'
            JOIN ' . $this->t('bank_questions') . ' q ON q.id = i.question_id
            JOIN ' . $this->t('school_exam_papers') . ' p ON p.id = i.paper_id
            WHERE s.exam_id = ? GROUP BY q.id, q.body, q.topic, q.correct_option, p.subject
            ORDER BY (SUM(i.answer = q.correct_option) / COUNT(*)) ASC, COUNT(*) DESC LIMIT ' . max(1, $limit));
        $st->execute([$examId]);
        return array_map(static function (array $r): array {
            $r['rate'] = $r['attempts'] > 0 ? round($r['correct'] * 100 / $r['attempts']) : 0;
            $wrong = array_filter(['A' => (int) $r['a'], 'B' => (int) $r['b'], 'C' => (int) $r['c'], 'D' => (int) $r['d'], 'E' => (int) $r['e']], static fn ($n, $k) => $k !== $r['correct_option'] && $n > 0, ARRAY_FILTER_USE_BOTH);
            arsort($wrong);
            $r['common_wrong'] = $wrong ? array_key_first($wrong) : null;
            return $r;
        }, $st->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Term results for a whole class (the broadsheet). Report cards are read from it so
     * positions are always consistent.
     *
     * @return array{subjects: list<string>, students: list<array<string, mixed>>, size: int}
     */
    public function classSheet(int $schoolId, int $classId, int $termId, array $settings, bool $releasedOnly): array
    {
        // A past term is computed with the rules it was issued with, not today's.
        $frozen = $this->frozenRules($termId);
        if ($frozen !== null) {
            $settings = $frozen['settings'] + $settings;
        }
        $frozenBands = $frozen['bands'] ?? null;
        $st = $this->pdo()->prepare('SELECT u.id, u.name, sp.admission_no, sp.gender, sp.photo_path, sp.result_code FROM ' . $this->t('student_profiles') . ' sp
            JOIN ' . $this->t('users') . ' u ON u.id = sp.user_id WHERE sp.school_id = ? AND sp.class_id = ? AND sp.status = \'active\' ORDER BY u.name');
        $st->execute([$schoolId, $classId]);
        $students = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $students[(int) $s['id']] = $s + ['marks' => []];
        }
        if ($students === []) {
            return ['subjects' => [], 'students' => [], 'size' => 0];
        }
        $ids = array_keys($students);
        $st = $this->pdo()->prepare('SELECT s.student_id, s.breakdown, e.type, e.settings, e.ends_at, e.results_released_at
            FROM ' . $this->t('exam_sittings') . ' s JOIN ' . $this->t('school_exams') . ' e ON e.id = s.exam_id
            WHERE e.school_id = ? AND e.term_id = ? AND e.status = \'published\' AND e.type <> \'practice\' AND s.status <> \'in_progress\'
              AND s.student_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $st->execute([$schoolId, $termId, ...$ids]);
        $subjects = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ($releasedOnly && !self::released($row)) {
                continue;
            }
            $bucket = $row['type'] === 'exam' ? 'exam' : 'ca';
            foreach (json_decode((string) $row['breakdown'], true) ?: [] as $b) {
                if ((int) $b['total'] <= 0) {
                    continue;
                }
                $subjects[$b['subject']] = true;
                $students[(int) $row['student_id']]['marks'][$b['subject']][$bucket][] = $b['correct'] * 100 / $b['total'];
            }
        }
        $subjects = array_keys($subjects);
        sort($subjects);
        $caW = max(0, min(100, (int) $settings['ca_weight']));
        $exW = 100 - $caW;
        $totals = [];
        foreach ($students as $id => &$s) {
            $s['subjects'] = [];
            foreach ($subjects as $sub) {
                $m = $s['marks'][$sub] ?? [];
                $ca = isset($m['ca']) ? array_sum($m['ca']) / count($m['ca']) : null;
                $ex = isset($m['exam']) ? array_sum($m['exam']) / count($m['exam']) : null;
                $total = match (true) {
                    $ca !== null && $ex !== null => $ca * $caW / 100 + $ex * $exW / 100,
                    default => $ca ?? $ex,
                };
                $s['subjects'][$sub] = ['ca' => $ca !== null ? round($ca, 1) : null, 'exam' => $ex !== null ? round($ex, 1) : null, 'total' => $total !== null ? round($total, 1) : null]
                    + $this->grade($schoolId, $total !== null ? round($total, 1) : null, $frozenBands);
                $totals[$sub][$id] = $total !== null ? round($total, 1) : null;
            }
            $taken = array_values(array_filter(array_column($s['subjects'], 'total'), static fn ($v) => $v !== null));
            $s['count'] = count($taken);
            $s['sum'] = round(array_sum($taken), 1);
            $s['average'] = $taken ? round(array_sum($taken) / count($taken), 1) : null;
            $s['overall'] = $this->grade($schoolId, $s['average'], $frozenBands);
            unset($s['marks']);
        }
        unset($s);
        foreach ($subjects as $sub) {
            foreach (self::rank($totals[$sub] ?? []) as $id => $pos) {
                $students[$id]['subjects'][$sub]['position'] = $pos;
            }
        }
        $overall = self::rank(array_map(static fn ($s) => $s['average'], $students));
        foreach ($overall as $id => $pos) {
            $students[$id]['position'] = $pos;
        }
        $list = array_values($students);
        usort($list, static fn ($a, $b) => [$a['position'] ?? PHP_INT_MAX, $a['name']] <=> [$b['position'] ?? PHP_INT_MAX, $b['name']]);
        $classAvg = [];
        foreach ($subjects as $sub) {
            $vals = array_values(array_filter($totals[$sub] ?? [], static fn ($v) => $v !== null));
            $classAvg[$sub] = $vals ? round(array_sum($vals) / count($vals), 1) : null;
        }
        return ['subjects' => $subjects, 'students' => $list, 'size' => count(array_filter($list, static fn ($s) => $s['count'] > 0)), 'classAvg' => $classAvg];
    }

    private static function released(array $examRow): bool
    {
        $settings = json_decode((string) $examRow['settings'], true) ?: [];
        return match ($settings['result_release'] ?? 'after_window') {
            'instant' => true,
            'after_window' => time() > strtotime($examRow['ends_at'] . ' UTC') || !empty($examRow['results_released_at']),
            default => !empty($examRow['results_released_at']),
        };
    }

    /** Everything a report card shows for one student and term. */
    public function card(array $school, array $student, array $term, bool $releasedOnly, ?array $sheet = null): array
    {
        $schoolId = (int) $school['id'];
        $frozen = $this->frozenRules((int) $term['id']);
        $settings = ($frozen['settings'] ?? []) + $this->settings($school);
        $sheet ??= $student['class_id'] ? $this->classSheet($schoolId, (int) $student['class_id'], (int) $term['id'], $settings, $releasedOnly) : ['subjects' => [], 'students' => [], 'size' => 0, 'classAvg' => []];
        $row = array_values(array_filter($sheet['students'], static fn ($s) => (int) $s['id'] === (int) $student['id']))[0] ?? null;
        $token = self::token('r', (int) $student['id'], (int) $term['id']);
        return [
            'school' => $school, 'student' => $student, 'term' => $term, 'settings' => $settings, 'sheet' => $sheet, 'row' => $row,
            'remarks' => $this->remarks((int) $term['id'], (int) $student['id']), 'code' => $this->resultCode($schoolId, (int) $student['id']),
            'verify' => url('/verify/' . $token), 'qr' => self::qr(url('/verify/' . $token)),
            'bands' => $frozen['bands'] ?? ($this->bands[$schoolId] ??= $this->grading->all($schoolId)),
            'frozen' => $frozen !== null,
        ];
    }

    /** Term with its session name. */
    public function term(int $schoolId, int $termId): ?array
    {
        $st = $this->pdo()->prepare('SELECT t.*, s.name AS session_name FROM ' . $this->t('terms') . ' t JOIN ' . $this->t('academic_sessions') . ' s ON s.id = t.session_id WHERE t.id = ? AND t.school_id = ?');
        $st->execute([$termId, $schoolId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Released exam results of one student, newest first. @return list<array<string, mixed>> */
    public function studentResults(int $schoolId, int $studentId): array
    {
        $st = $this->pdo()->prepare('SELECT s.id, s.exam_id, s.score, s.percent, s.question_count, s.answered_count, s.submitted_at, s.breakdown, s.status,
                e.title, e.type, e.settings, e.ends_at, e.results_released_at, e.term_id
            FROM ' . $this->t('exam_sittings') . ' s JOIN ' . $this->t('school_exams') . ' e ON e.id = s.exam_id
            WHERE s.school_id = ? AND s.student_id = ? AND s.status <> \'in_progress\' AND e.status = \'published\' ORDER BY s.submitted_at DESC LIMIT 200');
        $st->execute([$schoolId, $studentId]);
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $r['visible'] = self::released($r);
            $r['grade'] = $r['visible'] ? $this->grade($schoolId, (float) $r['percent'], $this->frozenRules((int) ($r['term_id'] ?? 0))['bands'] ?? null) : null;
            $out[] = $r;
        }
        return $out;
    }

    /** @return array<int, ?int> exam id => student's position (released exams only) */
    public function positions(array $examIds, int $studentId): array
    {
        $out = [];
        foreach (array_unique($examIds) as $examId) {
            $st = $this->pdo()->prepare('SELECT student_id, percent FROM ' . $this->t('exam_sittings') . ' WHERE exam_id = ? AND status <> \'in_progress\'');
            $st->execute([$examId]);
            $ranks = self::rank(array_map('floatval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'percent', 'student_id')));
            $out[(int) $examId] = $ranks[$studentId] ?? null;
            $out[(int) $examId . ':of'] = count($ranks);
        }
        return $out;
    }
    /** @return array{teacher_comment:?string, principal_comment:?string} */
    public function remarks(int $termId, int $studentId): array
    {
        $st = $this->pdo()->prepare('SELECT teacher_comment, principal_comment FROM ' . $this->t('report_remarks') . ' WHERE term_id = ? AND student_id = ?');
        $st->execute([$termId, $studentId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: ['teacher_comment' => null, 'principal_comment' => null];
    }

    public function saveRemarks(int $schoolId, int $termId, int $studentId, ?string $teacher, ?string $principal, int $userId): void
    {
        $this->pdo()->prepare('INSERT INTO ' . $this->t('report_remarks') . ' (school_id, term_id, student_id, teacher_comment, principal_comment, updated_by, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE teacher_comment = VALUES(teacher_comment), principal_comment = VALUES(principal_comment), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)')
            ->execute([$schoolId, $termId, $studentId, $teacher, $principal, $userId, gmdate('Y-m-d H:i:s')]);
    }

    public static function autoComment(?float $average): string
    {
        return match (true) {
            $average === null => 'No results recorded this term.',
            $average >= 75 => 'An excellent result. Keep up the good work.',
            $average >= 65 => 'A very good performance. Aim even higher next term.',
            $average >= 50 => 'A fair result. More effort will bring better grades.',
            $average >= 40 => 'Below expectation. Needs to study harder and seek help early.',
            default => 'A poor result. Close attention and extra support are needed.',
        };
    }

    /** Parent result code (created on first use). */
    public function resultCode(int $schoolId, int $studentId, bool $regenerate = false): string
    {
        $st = $this->pdo()->prepare('SELECT result_code FROM ' . $this->t('student_profiles') . ' WHERE school_id = ? AND user_id = ?');
        $st->execute([$schoolId, $studentId]);
        $code = (string) $st->fetchColumn();
        if ($code === '' || $regenerate) {
            $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $this->pdo()->prepare('UPDATE ' . $this->t('student_profiles') . ' SET result_code = ? WHERE school_id = ? AND user_id = ?')->execute([$code, $schoolId, $studentId]);
        }
        return $code;
    }

    /** Signed, unguessable verification token for a slip ("s") or report card ("r"). */
    public static function token(string $kind, int ...$ids): string
    {
        $payload = $kind . '-' . implode('-', $ids);
        return $payload . '-' . substr(hash_hmac('sha256', $payload, self::key()), 0, 12);
    }

    /** @return array{kind:string, ids:list<int>}|null */
    public static function readToken(string $token): ?array
    {
        if (!preg_match('/^([sr])-((?:\d+-){1,3})([a-f0-9]{12})$/', $token, $m)) {
            return null;
        }
        $payload = $m[1] . '-' . rtrim($m[2], '-');
        if (!hash_equals(substr(hash_hmac('sha256', $payload, self::key()), 0, 12), $m[3])) {
            return null;
        }
        return ['kind' => $m[1], 'ids' => array_map('intval', explode('-', rtrim($m[2], '-')))];
    }

    private static function key(): string
    {
        return 'examcore-results|' . (string) (env('APP_KEY') ?: 'local');
    }

    public static function qr(string $url): string
    {
        return (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\Output\QROutputInterface::MARKUP_SVG, 'outputBase64' => true,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M, 'addQuietzone' => true, 'svgUseFillAttributes' => true,
        ])))->render($url);
    }
}
