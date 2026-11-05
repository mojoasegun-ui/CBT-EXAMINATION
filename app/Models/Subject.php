<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;
use PDO;

final class Subject extends Model
{
    /** @return list<array<string, mixed>> */
    public function listForExamType(int $examTypeId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT s.id, s.slug, s.name, s.icon, COUNT(e.id) AS available_exam_count
             FROM ' . $this->table('subjects') . ' s
             LEFT JOIN ' . $this->table('exams') . ' e
                 ON e.subject_id = s.id
                AND e.is_active = 1
                AND e.exam_type_id = :exam_type_id
                AND (
                    SELECT COUNT(*)
                    FROM ' . $this->table('questions') . ' q
                    WHERE q.exam_id = e.id
                ) = e.total_questions
             WHERE s.is_active = 1
             GROUP BY s.id, s.slug, s.name, s.icon
             HAVING COUNT(e.id) > 0
             ORDER BY s.name'
        );
        $statement->execute(['exam_type_id' => $examTypeId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findActiveBySlug(string $slug): ?array
    {
        $statement = $this->connection()->prepare('SELECT id, slug, name, icon FROM ' . $this->table('subjects') . ' WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
}