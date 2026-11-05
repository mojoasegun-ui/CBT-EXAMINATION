<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

final class Question extends Model
{
    private const PUBLIC_COLUMNS = 'id, exam_id, question_text, option_a, option_b, option_c, option_d, marks, sort_order';
    private const GRADING_COLUMNS = 'id, exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, sort_order';

    /** @return list<array<string, mixed>> Public projection: never includes correct_option or explanation. */
    public function listForExam(int $examId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM ' . $this->table('questions') . ' WHERE exam_id = :exam_id ORDER BY sort_order'
        );
        $statement->execute(['exam_id' => $examId]);
        return $statement->fetchAll();
    }

    /**
     * Public projection for an explicit set of ids, returned in the given order.
     *
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function listByIds(array $ids): array
    {
        return $this->fetchOrdered(self::PUBLIC_COLUMNS, $ids);
    }

    /**
     * Includes answers and explanations. Server-side grading and post-submission review only.
     *
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    public function listForGradingByIds(array $ids): array
    {
        return $this->fetchOrdered(self::GRADING_COLUMNS, $ids);
    }

    public function countForExam(int $examId): int
    {
        $statement = $this->connection()->prepare('SELECT COUNT(*) FROM ' . $this->table('questions') . ' WHERE exam_id = :exam_id');
        $statement->execute(['exam_id' => $examId]);
        return (int) $statement->fetchColumn();
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    private function fetchOrdered(string $columns, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->connection()->prepare('SELECT ' . $columns . ' FROM ' . $this->table('questions') . ' WHERE id IN (' . $placeholders . ')');
        $statement->execute($ids);
        $byId = [];
        foreach ($statement->fetchAll() as $row) {
            $byId[(int) $row['id']] = $row;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    }
}
