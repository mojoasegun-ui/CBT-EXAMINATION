<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

final class SchoolExam extends Model
{
    public const TYPES = ['test' => 'Class test', 'ca' => 'Continuous assessment', 'exam' => 'Terminal exam', 'mock' => 'Mock (WAEC/NECO/JAMB)', 'practice' => 'Practice'];
    public const DEFAULT_SETTINGS = [
        'shuffle_questions' => true, 'shuffle_options' => true, 'allow_back' => true, 'calculator' => false,
        'result_release' => 'after_window', 'show_review' => false, 'pass_mark' => 50, 'access_code' => '',
        // Exam security (M7)
        'proctoring' => 'off', 'snapshot_minutes' => 5, 'require_fullscreen' => false, 'single_device' => true,
        'ask_seat' => false, 'max_warnings' => 0, 'invigilator_pin' => '',
    ];
    public const PROCTORING = [
        'off' => 'Off — no camera',
        'presence' => 'Camera: check a face is present (light, ~0.3 MB once)',
        'match' => 'Camera: match the face to the school photo (~8 MB once)',
    ];

    /** @return list<array<string, mixed>> */
    public function list(int $schoolId, string $when, ?int $authorId = null): array
    {
        $now = self::now();
        $where = match ($when) {
            'upcoming' => "e.status = 'published' AND e.starts_at > ?",
            'live' => "e.status = 'published' AND e.starts_at <= ? AND e.ends_at >= ?",
            'past' => "(e.status = 'cancelled' OR (e.status = 'published' AND e.ends_at < ?))",
            default => "e.status = 'draft'",
        };
        $params = match ($when) { 'upcoming', 'past' => [$now], 'live' => [$now, $now], default => [] };
        if ($authorId !== null) {
            $where .= ' AND e.created_by = ?';
            $params[] = $authorId;
        }
        return $this->rows('SELECT e.*, u.name AS author_name,
                (SELECT GROUP_CONCAT(c.name ORDER BY c.level_order, c.arm SEPARATOR \', \') FROM ' . $this->table('school_exam_classes') . ' ec JOIN ' . $this->table('school_classes') . ' c ON c.id = ec.class_id WHERE ec.exam_id = e.id) AS class_names,
                (SELECT GROUP_CONCAT(p.subject ORDER BY p.position SEPARATOR \', \') FROM ' . $this->table('school_exam_papers') . ' p WHERE p.exam_id = e.id) AS subjects
            FROM ' . $this->table('school_exams') . ' e LEFT JOIN ' . $this->table('users') . ' u ON u.id = e.created_by
            WHERE e.school_id = ? AND ' . $where . ' ORDER BY ' . ($when === 'past' ? 'e.ends_at DESC' : 'e.starts_at') . ' LIMIT 200', [$schoolId, ...$params]);
    }

    /** @return array<string, mixed>|null with decoded settings, papers and class ids */
    public function find(int $schoolId, int $id): ?array
    {
        $exam = $this->row('SELECT e.*, u.name AS author_name FROM ' . $this->table('school_exams') . ' e LEFT JOIN ' . $this->table('users') . ' u ON u.id = e.created_by WHERE e.id = ? AND e.school_id = ?', [$id, $schoolId]);
        if ($exam === null) {
            return null;
        }
        $exam['settings'] = (json_decode((string) $exam['settings'], true) ?: []) + self::DEFAULT_SETTINGS;
        $exam['papers'] = $this->rows('SELECT p.*, (SELECT COUNT(*) FROM ' . $this->table('school_exam_questions') . ' q WHERE q.paper_id = p.id) AS fixed_count
            FROM ' . $this->table('school_exam_papers') . ' p WHERE p.exam_id = ? ORDER BY p.position', [$id]);
        foreach ($exam['papers'] as &$paper) {
            $paper['topics'] = json_decode((string) ($paper['topics'] ?? '[]'), true) ?: [];
        }
        $exam['class_ids'] = array_map('intval', array_column($this->rows('SELECT class_id FROM ' . $this->table('school_exam_classes') . ' WHERE exam_id = ?', [$id]), 'class_id'));
        return $exam;
    }

    /**
     * Save exam + papers + classes. Papers are matched by subject so fixed question picks survive edits.
     *
     * @param array<string, mixed> $exam
     * @param list<array<string, mixed>> $papers
     * @param list<int> $classIds
     */
    public function save(int $schoolId, ?int $id, int $userId, array $exam, array $papers, array $classIds): int
    {
        $now = self::now();
        $row = ['term_id' => $exam['term_id'], 'title' => $exam['title'], 'type' => $exam['type'], 'starts_at' => $exam['starts_at'], 'ends_at' => $exam['ends_at'],
            'duration_minutes' => $exam['duration_minutes'], 'instructions' => $exam['instructions'], 'settings' => json_encode($exam['settings']), 'updated_at' => $now];
        if ($id === null) {
            $id = $this->insertRow('school_exams', $row + ['school_id' => $schoolId, 'created_by' => $userId, 'status' => 'draft', 'created_at' => $now]);
        } else {
            $this->updateScoped('school_exams', $schoolId, $id, $row, array_keys($row));
        }
        $keep = [];
        foreach (array_values($papers) as $i => $paper) {
            $existing = $this->row('SELECT id FROM ' . $this->table('school_exam_papers') . ' WHERE exam_id = ? AND subject = ?', [$id, $paper['subject']]);
            $data = ['mode' => $paper['mode'], 'question_count' => $paper['question_count'], 'topics' => json_encode($paper['topics']), 'difficulty' => $paper['difficulty'] ?: null, 'position' => $i + 1];
            if ($existing !== null) {
                $this->run('UPDATE ' . $this->table('school_exam_papers') . ' SET mode = ?, question_count = ?, topics = ?, difficulty = ?, position = ? WHERE id = ?', [...array_values($data), $existing['id']]);
                $keep[] = (int) $existing['id'];
            } else {
                $keep[] = $this->insertRow('school_exam_papers', $data + ['exam_id' => $id, 'subject' => $paper['subject']]);
            }
        }
        $this->run('DELETE FROM ' . $this->table('school_exam_papers') . ' WHERE exam_id = ?' . ($keep ? ' AND id NOT IN (' . implode(',', $keep) . ')' : ''), [$id]);
        $this->run('DELETE FROM ' . $this->table('school_exam_classes') . ' WHERE exam_id = ?', [$id]);
        foreach (array_unique($classIds) as $classId) {
            $this->insertRow('school_exam_classes', ['exam_id' => $id, 'class_id' => $classId]);
        }
        return $id;
    }

    public function setStatus(int $schoolId, int $id, string $status): void
    {
        $this->updateScoped('school_exams', $schoolId, $id, ['status' => $status, 'published_at' => $status === 'published' ? self::now() : null, 'updated_at' => self::now()], ['status', 'published_at', 'updated_at']);
    }

    /** @param list<int> $questionIds */
    public function setFixedQuestions(int $paperId, array $questionIds): void
    {
        $this->run('DELETE FROM ' . $this->table('school_exam_questions') . ' WHERE paper_id = ?', [$paperId]);
        foreach (array_values(array_unique($questionIds)) as $i => $qid) {
            $this->insertRow('school_exam_questions', ['paper_id' => $paperId, 'question_id' => $qid, 'position' => $i + 1]);
        }
    }

    /** @return list<int> */
    public function fixedQuestionIds(int $paperId): array
    {
        return array_map('intval', array_column($this->rows('SELECT question_id FROM ' . $this->table('school_exam_questions') . ' WHERE paper_id = ? ORDER BY position', [$paperId]), 'question_id'));
    }

    /** Ids of published questions matching a random paper's filters. @param list<string> $topics @return list<int> */
    public function questionPool(int $schoolId, string $subject, array $topics, ?string $difficulty): array
    {
        $sql = 'SELECT id FROM ' . $this->table('bank_questions') . ' WHERE school_id = ? AND subject = ? AND status = \'published\'';
        $params = [$schoolId, $subject];
        if ($topics !== []) {
            $sql .= ' AND topic IN (' . implode(',', array_fill(0, count($topics), '?')) . ')';
            array_push($params, ...$topics);
        }
        if ($difficulty) {
            $sql .= ' AND difficulty = ?';
            $params[] = $difficulty;
        }
        return array_map('intval', array_column($this->rows($sql . ' LIMIT 5000', $params), 'id'));
    }

    /** Which option letters each question actually has. @param list<int> $ids @return array<int, string> id => "ABCD" */
    public function optionLetters(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $out = [];
        foreach ($this->rows('SELECT id, option_c, option_d, option_e FROM ' . $this->table('bank_questions') . ' WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids) as $row) {
            $letters = 'AB';
            foreach (['c', 'd', 'e'] as $l) {
                if (trim((string) $row['option_' . $l]) !== '') {
                    $letters .= strtoupper($l);
                }
            }
            $out[(int) $row['id']] = $letters;
        }
        return $out;
    }

    /** @param list<int> $classIds @return array{0:int, 1:int} [active students, of whom without a photo] */
    public function photoGaps(int $schoolId, array $classIds): array
    {
        $row = $this->row('SELECT COUNT(*) AS total, SUM(photo_path IS NULL OR photo_path = \'\') AS missing FROM ' . $this->table('student_profiles')
            . ' WHERE school_id = ? AND status = \'active\' AND class_id IN (' . implode(',', array_fill(0, count($classIds), '?')) . ')', [$schoolId, ...$classIds]);
        return [(int) ($row['total'] ?? 0), (int) ($row['missing'] ?? 0)];
    }

    public function releaseResults(int $schoolId, int $id): void
    {
        $this->run('UPDATE ' . $this->table('school_exams') . ' SET results_released_at = ?, updated_at = ? WHERE id = ? AND school_id = ?', [self::now(), self::now(), $id, $schoolId]);
    }

    /** Published questions available for a random paper. */
    public function availableCount(int $schoolId, string $subject, array $topics, ?string $difficulty): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->table('bank_questions') . ' WHERE school_id = ? AND subject = ? AND status = \'published\'';
        $params = [$schoolId, $subject];
        if ($topics !== []) {
            $sql .= ' AND topic IN (' . implode(',', array_fill(0, count($topics), '?')) . ')';
            array_push($params, ...$topics);
        }
        if ($difficulty) {
            $sql .= ' AND difficulty = ?';
            $params[] = $difficulty;
        }
        return (int) $this->scalar($sql, $params);
    }

    /** Exams a student can see: published, for their class, not yet past + recent past. @return list<array<string, mixed>> */
    public function forClass(int $schoolId, int $classId): array
    {
        return $this->rows('SELECT e.id, e.title, e.type, e.starts_at, e.ends_at, e.duration_minutes, e.instructions,
                (SELECT GROUP_CONCAT(p.subject ORDER BY p.position SEPARATOR \', \') FROM ' . $this->table('school_exam_papers') . ' p WHERE p.exam_id = e.id) AS subjects,
                (SELECT SUM(p.question_count) FROM ' . $this->table('school_exam_papers') . ' p WHERE p.exam_id = e.id) AS question_total
            FROM ' . $this->table('school_exams') . ' e JOIN ' . $this->table('school_exam_classes') . ' ec ON ec.exam_id = e.id AND ec.class_id = ?
            WHERE e.school_id = ? AND e.status = \'published\' AND e.ends_at >= ? ORDER BY e.starts_at LIMIT 50', [$classId, $schoolId, gmdate('Y-m-d H:i:s', time() - 7 * 86400)]);
    }
}
