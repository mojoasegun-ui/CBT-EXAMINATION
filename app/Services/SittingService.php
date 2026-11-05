<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Queue\Queue;
use ExamCore\Models\ExamSitting;
use ExamCore\Models\SchoolExam;

/**
 * The exam room engine.
 *
 * - Each student's paper is generated once, at start, and stored (question + option order),
 *   so a refresh, a power cut or a different computer always shows the same paper.
 * - The server clock is the only clock: the deadline is stored, the browser only displays it.
 * - Answers are saved in small batches; a late batch is accepted for a short grace period
 *   so answers typed just before the network dropped are not lost.
 * - Marking is one SQL statement; bank statistics are updated later by the queue.
 */
final class SittingService
{
    public const JOB_STATS = 'sitting.stats';
    /** Seconds after the deadline during which queued answers are still accepted. */
    public const GRACE_SECONDS = 120;

    public function __construct(
        private readonly Database $database,
        private readonly SchoolExam $exams,
        private readonly ExamSitting $sittings,
        private readonly ?Queue $queue = null,
    ) {
    }

    /** Exam a student may see (published, their class), or null. */
    public function examFor(int $schoolId, int $examId, ?int $classId): ?array
    {
        $exam = $this->exams->find($schoolId, $examId);
        if ($exam === null || $exam['status'] !== 'published' || $classId === null || !in_array($classId, $exam['class_ids'], true)) {
            return null;
        }
        return $exam;
    }

    /** upcoming | open | closed */
    public static function window(array $exam): string
    {
        $now = time();
        return match (true) {
            $now < strtotime($exam['starts_at'] . ' UTC') => 'upcoming',
            $now > strtotime($exam['ends_at'] . ' UTC') => 'closed',
            default => 'open',
        };
    }

    public static function remaining(array $sitting): int
    {
        return max(0, strtotime($sitting['deadline_at'] . ' UTC') - time());
    }

    /** Start (or return the existing) sitting. */
    public function start(int $schoolId, array $exam, int $studentId, string $accessCode, string $ip, string $userAgent): array
    {
        $existing = $this->sittings->forStudent((int) $exam['id'], $studentId);
        if ($existing !== null) {
            return $existing;
        }
        if (self::window($exam) !== 'open') {
            throw new HttpException(422, self::window($exam) === 'upcoming' ? 'This exam has not opened yet.' : 'This exam has closed.');
        }
        $code = trim((string) $exam['settings']['access_code']);
        if ($code !== '' && strcasecmp($code, trim($accessCode)) !== 0) {
            throw new HttpException(422, 'The access code is not correct. Ask your invigilator for it.');
        }
        $items = $this->buildPaper($schoolId, $exam);
        if ($items === []) {
            throw new HttpException(422, 'This exam has no questions yet. Please tell your teacher.');
        }
        $deadline = min(time() + (int) $exam['duration_minutes'] * 60, strtotime($exam['ends_at'] . ' UTC'));
        $id = $this->database->transaction(fn () => $this->sittings->create([
            'school_id' => $schoolId, 'exam_id' => (int) $exam['id'], 'student_id' => $studentId, 'status' => 'in_progress',
            'started_at' => gmdate('Y-m-d H:i:s'), 'deadline_at' => gmdate('Y-m-d H:i:s', $deadline), 'last_seen_at' => gmdate('Y-m-d H:i:s'),
            'question_count' => count($items), 'ip_address' => mb_substr($ip, 0, 45), 'user_agent' => mb_substr($userAgent, 0, 255),
        ], $items));
        return $id === null ? (array) $this->sittings->forStudent((int) $exam['id'], $studentId) : (array) $this->sittings->find($id);
    }

    /** @return list<array{paper_id:int, question_id:int, option_order:string}> */
    private function buildPaper(int $schoolId, array $exam): array
    {
        $s = $exam['settings'];
        $items = [];
        foreach ($exam['papers'] as $paper) {
            if ($paper['mode'] === 'fixed') {
                $ids = $this->exams->fixedQuestionIds((int) $paper['id']);
                if (!empty($s['shuffle_questions'])) {
                    shuffle($ids);
                }
            } else {
                $ids = $this->exams->questionPool($schoolId, (string) $paper['subject'], $paper['topics'], $paper['difficulty'] ?: null);
                shuffle($ids); // random papers are always a random draw
            }
            $ids = array_slice($ids, 0, (int) $paper['question_count']);
            if (empty($s['shuffle_questions']) && $paper['mode'] !== 'fixed') {
                sort($ids); // same draw, but in bank order
            }
            $letters = $this->exams->optionLetters($ids);
            foreach ($ids as $qid) {
                $order = str_split($letters[$qid] ?? 'AB');
                if (!empty($s['shuffle_options'])) {
                    shuffle($order);
                }
                $items[] = ['paper_id' => (int) $paper['id'], 'question_id' => $qid, 'option_order' => implode('', $order)];
            }
        }
        return $items;
    }

    /** Everything the exam room needs, in one payload (works offline after it loads). */
    public function payload(array $exam, array $sitting): array
    {
        $questions = [];
        $passages = [];
        foreach ($this->sittings->paper((int) $sitting['id']) as $row) {
            $options = [];
            foreach (str_split((string) $row['option_order']) as $letter) {
                $options[] = ['key' => $letter, 'html' => rich_text((string) $row['option_' . strtolower($letter)])];
            }
            if ($row['passage_id'] !== null && !isset($passages[$row['passage_id']])) {
                $passages[(int) $row['passage_id']] = ['title' => (string) $row['passage_title'], 'html' => rich_text((string) $row['passage_body'])];
            }
            $questions[] = [
                'n' => (int) $row['position'], 'paper' => (int) $row['paper_id'], 'subject' => (string) $row['subject'],
                'html' => rich_text((string) $row['body']),
                'image' => $row['image_path'] ? url('/' . ltrim((string) $row['image_path'], '/')) : null,
                'passage' => $row['passage_id'] !== null ? (int) $row['passage_id'] : null,
                'options' => $options, 'answer' => $row['answer'], 'flagged' => (bool) $row['flagged'],
            ];
        }
        $s = $exam['settings'];
        return [
            'sitting' => (int) $sitting['id'], 'exam' => (int) $exam['id'], 'title' => (string) $exam['title'],
            'deadline' => strtotime($sitting['deadline_at'] . ' UTC'), 'serverNow' => time(),
            'position' => (int) $sitting['current_position'],
            'settings' => ['allowBack' => !empty($s['allow_back']), 'calculator' => !empty($s['calculator'])],
            'questions' => $questions, 'passages' => (object) $passages,
        ];
    }

    /**
     * Save a batch of changes from the exam room.
     *
     * @param array<int|string, array{a?:?string, f?:bool}> $answers position => change
     * @param list<array{type?:string, detail?:string}> $events
     * @return array{status:string, deadline:int, serverNow:int, saved:int}
     */
    public function sync(array $sitting, array $answers, array $events, int $position, int $maxWarnings = 0): array
    {
        $id = (int) $sitting['id'];
        $accepting = $sitting['status'] === 'in_progress' && time() <= strtotime($sitting['deadline_at'] . ' UTC') + self::GRACE_SECONDS;
        $saved = 0;
        if ($accepting) {
            $orders = $this->sittings->optionOrders($id);
            $clean = [];
            foreach (array_slice($answers, 0, 250, true) as $pos => $change) {
                $pos = (int) $pos;
                if (!isset($orders[$pos]) || !is_array($change)) {
                    continue;
                }
                $answer = isset($change['a']) && is_string($change['a']) ? strtoupper($change['a']) : null;
                if ($answer !== null && (strlen($answer) !== 1 || !str_contains($orders[$pos], $answer))) {
                    continue;
                }
                $clean[$pos] = [$answer, !empty($change['f'])];
            }
            $allowed = ['focus_lost', 'fullscreen_exit', 'copy_attempt', 'print_attempt', 'resumed', 'offline', 'devtools', 'camera_off'];
            $log = [];
            foreach (array_slice($events, 0, 20) as $event) {
                $type = (string) ($event['type'] ?? '');
                if (in_array($type, $allowed, true)) {
                    $log[] = ['type' => $type, 'detail' => mb_substr((string) ($event['detail'] ?? ''), 0, 255) ?: null];
                }
            }
            $warnings = count(array_filter($log, static fn ($e) => in_array($e['type'], ['focus_lost', 'fullscreen_exit', 'copy_attempt', 'print_attempt', 'devtools', 'camera_off'], true)));
            $this->database->transaction(function () use ($id, $clean, $log, $position, $warnings, $orders): void {
                foreach ($clean as $pos => [$answer, $flagged]) {
                    $this->sittings->saveAnswer($id, $pos, $answer, $flagged);
                }
                $this->sittings->logEvents($id, $log);
                $this->sittings->touch($id, max(1, min(count($orders), $position)), $warnings);
            });
            $saved = count($clean);
        }
        $fresh = (array) $this->sittings->find($id);
        if ($maxWarnings > 0 && $fresh['status'] === 'in_progress' && (int) $fresh['warnings'] >= $maxWarnings) {
            $this->sittings->logEvents($id, [['type' => 'auto_submitted', 'detail' => 'Warning limit reached']]);
            $fresh = $this->submit($id, true);
        }
        return ['warnings' => (int) $fresh['warnings'], 'status' => (string) $fresh['status'], 'deadline' => strtotime($fresh['deadline_at'] . ' UTC'), 'serverNow' => time(), 'saved' => $saved];
    }

    /** Mark and close a sitting. Safe to call twice (the second call changes nothing). */
    public function submit(int $sittingId, bool $auto = false): array
    {
        $result = $this->database->transaction(function () use ($sittingId, $auto): array {
            $sitting = $this->sittings->lock($sittingId);
            if ($sitting === null) {
                throw new HttpException(404, 'Sitting not found.');
            }
            if ($sitting['status'] !== 'in_progress') {
                return [$sitting, false];
            }
            $papers = $this->sittings->grade($sittingId);
            $total = array_sum(array_column($papers, 'total'));
            $correct = array_sum(array_column($papers, 'correct'));
            $data = [
                'status' => $auto ? 'auto_submitted' : 'submitted', 'submitted_at' => gmdate('Y-m-d H:i:s'),
                'score' => $correct, 'percent' => $total > 0 ? round($correct * 100 / $total, 2) : 0,
                'answered_count' => array_sum(array_column($papers, 'answered')), 'breakdown' => json_encode($papers),
            ];
            $this->sittings->finish($sittingId, $data);
            return [array_merge($sitting, $data), true];
        });
        [$sitting, $changed] = $result;
        if ($changed) {
            $this->queue !== null ? $this->queue->push(self::JOB_STATS, ['sitting_id' => $sittingId]) : $this->sittings->recordQuestionStats($sittingId);
        }
        return $sitting;
    }

    public function expireOverdue(int $limit): int
    {
        $n = 0;
        foreach ($this->sittings->overdue(self::GRACE_SECONDS, $limit) as $id) {
            $this->submit($id, true);
            $n++;
        }
        return $n;
    }

    /** Can this student see their score now? */
    public static function resultsVisible(array $exam, array $sitting): bool
    {
        if ($sitting['status'] === 'in_progress') {
            return false;
        }
        return match ($exam['settings']['result_release'] ?? 'after_window') {
            'instant' => true,
            'after_window' => time() > strtotime($exam['ends_at'] . ' UTC') || !empty($exam['results_released_at']),
            default => !empty($exam['results_released_at']),
        };
    }
}
