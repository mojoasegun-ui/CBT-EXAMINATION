<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;
use PDO;

final class Exam extends Model
{
    /** @return list<array<string, mixed>> */
    public function listAvailable(int $examTypeId, int $subjectId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT e.id, e.year, e.title, e.duration_minutes, e.total_questions,
                    s.name AS subject_name, s.slug AS subject_slug, t.name AS exam_type_name, t.slug AS exam_type_slug
             FROM ' . $this->table('exams') . ' e
             INNER JOIN ' . $this->table('exam_types') . ' t ON t.id = e.exam_type_id AND t.is_active = 1
             INNER JOIN ' . $this->table('subjects') . ' s ON s.id = e.subject_id AND s.is_active = 1
             WHERE e.exam_type_id = :exam_type_id AND e.subject_id = :subject_id AND e.is_active = 1
             AND (
                 SELECT COUNT(*)
                 FROM ' . $this->table('questions') . ' q
                 WHERE q.exam_id = e.id
             ) = e.total_questions
             ORDER BY e.year DESC, e.id DESC'
        );
        $statement->execute(['exam_type_id' => $examTypeId, 'subject_id' => $subjectId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findAvailable(int $id): ?array
    {
        $statement = $this->connection()->prepare(
            'SELECT e.id, e.exam_type_id, e.subject_id, e.year, e.title, e.duration_minutes, e.total_questions,
                    s.name AS subject_name, s.slug AS subject_slug, t.name AS exam_type_name, t.slug AS exam_type_slug
             FROM ' . $this->table('exams') . ' e
             INNER JOIN ' . $this->table('exam_types') . ' t ON t.id = e.exam_type_id AND t.is_active = 1
             INNER JOIN ' . $this->table('subjects') . ' s ON s.id = e.subject_id AND s.is_active = 1
             WHERE e.id = :id AND e.is_active = 1
             AND (
                 SELECT COUNT(*)
                 FROM ' . $this->table('questions') . ' q
                 WHERE q.exam_id = e.id
             ) = e.total_questions
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** @return list<array<string, mixed>> */
    public function listRecentForSession(string $sessionToken, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $statement = $this->connection()->prepare(
            'SELECT a.id AS attempt_id, a.status, a.score, a.total, a.started_at, a.student_name, a.student_email, e.id AS exam_id, e.title, e.exam_type_id, e.subject_id
             FROM ' . $this->table('attempts') . ' a
             INNER JOIN ' . $this->table('exams') . ' e ON e.id = a.exam_id
             WHERE a.session_token = :session_token ORDER BY a.started_at DESC LIMIT ' . $limit
        );
        $statement->execute(['session_token' => $sessionToken]);
        return $statement->fetchAll();
    }
}