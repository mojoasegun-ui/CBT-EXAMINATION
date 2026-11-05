<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;
use PDOException;

/** A student's sitting of a school exam: their own paper, answers, clock and score. */
final class ExamSitting extends Model
{
    public function find(int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('exam_sittings') . ' WHERE id = ?', [$id]);
    }

    public function forStudent(int $examId, int $studentId): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('exam_sittings') . ' WHERE exam_id = ? AND student_id = ?', [$examId, $studentId]);
    }

    /** Lock the row for the rest of the current transaction (submit, add time). */
    public function lock(int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('exam_sittings') . ' WHERE id = ? FOR UPDATE', [$id]);
    }

    /**
     * Create the sitting and its items. Returns null when another request created it first
     * (double-click, two tabs): the caller then loads the existing one.
     *
     * @param array<string, mixed> $sitting
     * @param list<array{paper_id:int, question_id:int, option_order:string}> $items
     */
    public function create(array $sitting, array $items): ?int
    {
        try {
            $id = $this->insertRow('exam_sittings', $sitting + ['created_at' => self::now(), 'updated_at' => self::now()]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                return null;
            }
            throw $e;
        }
        foreach (array_chunk($items, 100, true) as $chunk) {
            $values = [];
            $params = [];
            foreach ($chunk as $i => $item) {
                $values[] = '(?, ?, ?, ?, ?)';
                array_push($params, $id, $i + 1, $item['paper_id'], $item['question_id'], $item['option_order']);
            }
            $this->run('INSERT INTO ' . $this->table('exam_sitting_items') . ' (sitting_id, position, paper_id, question_id, option_order) VALUES ' . implode(', ', $values), $params);
        }
        return $id;
    }

    /** Items with question content, in sitting order. Correct answers are NOT selected. @return list<array<string, mixed>> */
    public function paper(int $sittingId): array
    {
        return $this->rows('SELECT i.position, i.paper_id, i.option_order, i.answer, i.flagged, p.subject,
                q.id AS question_id, q.body, q.image_path, q.option_a, q.option_b, q.option_c, q.option_d, q.option_e, q.passage_id,
                ps.title AS passage_title, ps.body AS passage_body
            FROM ' . $this->table('exam_sitting_items') . ' i
            JOIN ' . $this->table('school_exam_papers') . ' p ON p.id = i.paper_id
            JOIN ' . $this->table('bank_questions') . ' q ON q.id = i.question_id
            LEFT JOIN ' . $this->table('bank_passages') . ' ps ON ps.id = q.passage_id
            WHERE i.sitting_id = ? ORDER BY i.position', [$sittingId]);
    }

    /** @return array<int, string> position => allowed letters */
    public function optionOrders(int $sittingId): array
    {
        $out = [];
        foreach ($this->rows('SELECT position, option_order FROM ' . $this->table('exam_sitting_items') . ' WHERE sitting_id = ?', [$sittingId]) as $row) {
            $out[(int) $row['position']] = (string) $row['option_order'];
        }
        return $out;
    }

    public function saveAnswer(int $sittingId, int $position, ?string $answer, bool $flagged): void
    {
        $this->run('UPDATE ' . $this->table('exam_sitting_items') . ' SET answer = ?, flagged = ?, answered_at = ? WHERE sitting_id = ? AND position = ?',
            [$answer, $flagged ? 1 : 0, self::now(), $sittingId, $position]);
    }

    public function touch(int $sittingId, int $position, int $newWarnings): void
    {
        $this->run('UPDATE ' . $this->table('exam_sittings') . ' s SET last_seen_at = ?, updated_at = ?, current_position = ?, warnings = warnings + ?,
                answered_count = (SELECT COUNT(*) FROM ' . $this->table('exam_sitting_items') . ' i WHERE i.sitting_id = s.id AND i.answer IS NOT NULL)
            WHERE id = ?', [self::now(), self::now(), $position, $newWarnings, $sittingId]);
    }

    /** @param list<array{type:string, detail:?string}> $events */
    public function logEvents(int $sittingId, array $events): void
    {
        foreach ($events as $event) {
            $this->run('INSERT INTO ' . $this->table('exam_sitting_events') . ' (sitting_id, type, detail, created_at) VALUES (?, ?, ?, ?)',
                [$sittingId, $event['type'], $event['detail'], self::now()]);
        }
    }

    /** Per-paper marks computed in the database. @return list<array{paper_id:int, subject:string, total:int, correct:int, answered:int}> */
    public function grade(int $sittingId): array
    {
        return array_map(static fn (array $r): array => [
            'paper_id' => (int) $r['paper_id'], 'subject' => (string) $r['subject'],
            'total' => (int) $r['total'], 'correct' => (int) $r['correct'], 'answered' => (int) $r['answered'],
        ], $this->rows('SELECT i.paper_id, p.subject, COUNT(*) AS total, SUM(i.answer IS NOT NULL) AS answered,
                SUM(CASE WHEN i.answer = q.correct_option THEN 1 ELSE 0 END) AS correct
            FROM ' . $this->table('exam_sitting_items') . ' i
            JOIN ' . $this->table('school_exam_papers') . ' p ON p.id = i.paper_id
            JOIN ' . $this->table('bank_questions') . ' q ON q.id = i.question_id
            WHERE i.sitting_id = ? GROUP BY i.paper_id, p.subject, p.position ORDER BY p.position', [$sittingId]));
    }

    /** @param array<string, mixed> $data */
    public function finish(int $sittingId, array $data): void
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = $column . ' = ?';
        }
        $this->run('UPDATE ' . $this->table('exam_sittings') . ' SET ' . implode(', ', $sets) . ', updated_at = ? WHERE id = ?', [...array_values($data), self::now(), $sittingId]);
    }

    /** Bank statistics (difficulty insight) updated in one statement per sitting. */
    public function recordQuestionStats(int $sittingId): void
    {
        $this->run('UPDATE ' . $this->table('bank_questions') . ' q JOIN ' . $this->table('exam_sitting_items') . ' i ON i.question_id = q.id AND i.sitting_id = ?
            SET q.times_answered = q.times_answered + 1, q.times_correct = q.times_correct + (i.answer = q.correct_option)
            WHERE i.answer IS NOT NULL', [$sittingId]);
    }

    public function extend(int $sittingId, int $minutes): void
    {
        $this->run('UPDATE ' . $this->table('exam_sittings') . ' SET deadline_at = DATE_ADD(deadline_at, INTERVAL ? MINUTE), extra_minutes = extra_minutes + ?, updated_at = ? WHERE id = ?',
            [$minutes, $minutes, self::now(), $sittingId]);
    }

    public function delete(int $sittingId): void
    {
        $this->run('DELETE FROM ' . $this->table('exam_sittings') . ' WHERE id = ?', [$sittingId]);
    }

    /** @return list<int> in-progress sittings whose time ran out more than $grace seconds ago */
    public function overdue(int $graceSeconds, int $limit): array
    {
        return array_map('intval', array_column($this->rows('SELECT id FROM ' . $this->table('exam_sittings') . ' WHERE status = \'in_progress\' AND deadline_at < ? ORDER BY deadline_at LIMIT ' . max(1, $limit),
            [gmdate('Y-m-d H:i:s', time() - $graceSeconds)]), 'id'));
    }

    /** Every student in the exam's classes with their sitting (if any). @return list<array<string, mixed>> */
    public function roster(int $schoolId, int $examId): array
    {
        return $this->rows('SELECT u.id AS student_id, u.name, sp.admission_no, c.name AS class_name,
                s.id AS sitting_id, s.status, s.started_at, s.deadline_at, s.submitted_at, s.last_seen_at, s.answered_count, s.question_count,
                s.score, s.percent, s.warnings, s.extra_minutes, s.face_status, s.flags, s.seat_no, (s.device_hash IS NOT NULL) AS device_locked
            FROM ' . $this->table('school_exam_classes') . ' ec
            JOIN ' . $this->table('student_profiles') . ' sp ON sp.class_id = ec.class_id AND sp.school_id = ? AND sp.status = \'active\'
            JOIN ' . $this->table('users') . ' u ON u.id = sp.user_id
            JOIN ' . $this->table('school_classes') . ' c ON c.id = sp.class_id
            LEFT JOIN ' . $this->table('exam_sittings') . ' s ON s.exam_id = ec.exam_id AND s.student_id = u.id
            WHERE ec.exam_id = ? ORDER BY c.level_order, c.name, u.name', [$schoolId, $examId]);
    }

    /** @return list<array<string, mixed>> */
    public function events(int $sittingId, int $limit = 50): array
    {
        return $this->rows('SELECT type, detail, created_at FROM ' . $this->table('exam_sitting_events') . ' WHERE sitting_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit), [$sittingId]);
    }

    public function addSnapshot(int $sittingId, string $kind, string $path, ?int $faces, ?float $score, ?string $flag): void
    {
        $this->run('INSERT INTO ' . $this->table('exam_snapshots') . ' (sitting_id, kind, path, faces, match_score, flag, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$sittingId, $kind, $path, $faces, $score, $flag, self::now()]);
    }

    public function bumpFlags(int $sittingId): void
    {
        $this->run('UPDATE ' . $this->table('exam_sittings') . ' SET flags = flags + 1, warnings = warnings + 1, updated_at = ? WHERE id = ?', [self::now(), $sittingId]);
    }

    public function lastSnapshotAt(int $sittingId): ?string
    {
        $v = $this->scalar('SELECT MAX(created_at) FROM ' . $this->table('exam_snapshots') . ' WHERE sitting_id = ? AND kind = \'random\'', [$sittingId]);
        return $v === null ? null : (string) $v;
    }

    /** @return list<array<string, mixed>> */
    public function snapshots(int $sittingId): array
    {
        return $this->rows('SELECT id, kind, faces, match_score, flag, created_at FROM ' . $this->table('exam_snapshots') . ' WHERE sitting_id = ? ORDER BY id LIMIT 500', [$sittingId]);
    }

    public function snapshot(int $sittingId, int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('exam_snapshots') . ' WHERE id = ? AND sitting_id = ?', [$id, $sittingId]);
    }

    /** @return list<array{id:int|string, path:string}> */
    public function oldSnapshots(string $before, int $limit): array
    {
        return $this->rows('SELECT id, path FROM ' . $this->table('exam_snapshots') . ' WHERE created_at < ? ORDER BY id LIMIT ' . max(1, $limit), [$before]);
    }

    public function deleteSnapshot(int $id): void
    {
        $this->run('DELETE FROM ' . $this->table('exam_snapshots') . ' WHERE id = ?', [$id]);
    }

    public function setDevice(int $sittingId, ?string $hash): void
    {
        $this->run('UPDATE ' . $this->table('exam_sittings') . ' SET device_hash = ?, updated_at = ? WHERE id = ?', [$hash, self::now(), $sittingId]);
    }

    /** @return array<int, array<string, mixed>> exam id => sitting summary, for one student */
    public function summariesForStudent(int $schoolId, int $studentId): array
    {
        $out = [];
        foreach ($this->rows('SELECT id, exam_id, status, deadline_at, answered_count, question_count, score, percent FROM ' . $this->table('exam_sittings') . ' WHERE school_id = ? AND student_id = ?', [$schoolId, $studentId]) as $row) {
            $out[(int) $row['exam_id']] = $row;
        }
        return $out;
    }
}
