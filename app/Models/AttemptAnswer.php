<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

final class AttemptAnswer extends Model
{
    public function save(int $attemptId, int $questionId, string $option): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO ' . $this->table('attempt_answers') . ' (attempt_id, question_id, selected_option)
             VALUES (:attempt_id, :question_id, :selected_option)
             ON DUPLICATE KEY UPDATE selected_option = VALUES(selected_option), is_correct = NULL'
        );
        $statement->execute(['attempt_id' => $attemptId, 'question_id' => $questionId, 'selected_option' => $option]);
    }

    public function setFlagged(int $attemptId, int $questionId, bool $flagged): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO ' . $this->table('attempt_answers') . ' (attempt_id, question_id, is_flagged)
             VALUES (:attempt_id, :question_id, :is_flagged)
             ON DUPLICATE KEY UPDATE is_flagged = VALUES(is_flagged)'
        );
        $statement->execute(['attempt_id' => $attemptId, 'question_id' => $questionId, 'is_flagged' => $flagged ? 1 : 0]);
    }

    /** @return array<int, string> question id => selected option */
    public function selectedOptions(int $attemptId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT question_id, selected_option FROM ' . $this->table('attempt_answers') . ' WHERE attempt_id = :attempt_id AND selected_option IS NOT NULL'
        );
        $statement->execute(['attempt_id' => $attemptId]);
        $answers = [];
        foreach ($statement->fetchAll() as $row) {
            $answers[(int) $row['question_id']] = (string) $row['selected_option'];
        }
        return $answers;
    }

    /** @return list<int> */
    public function flaggedQuestions(int $attemptId): array
    {
        $statement = $this->connection()->prepare(
            'SELECT question_id FROM ' . $this->table('attempt_answers') . ' WHERE attempt_id = :attempt_id AND is_flagged = 1'
        );
        $statement->execute(['attempt_id' => $attemptId]);
        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Record correctness for every question in one statement (one round trip instead of
     * one per question, which matters on a 180-question JAMB mock).
     *
     * @param array<int, bool> $results question id => correct
     */
    public function markGradedBulk(int $attemptId, array $results): void
    {
        if ($results === []) {
            return;
        }
        $rows = [];
        $values = [];
        foreach ($results as $questionId => $correct) {
            $rows[] = '(?, ?, NULL, ?)';
            array_push($values, $attemptId, (int) $questionId, $correct ? 1 : 0);
        }
        $statement = $this->connection()->prepare(
            'INSERT INTO ' . $this->table('attempt_answers') . ' (attempt_id, question_id, selected_option, is_correct)
             VALUES ' . implode(',', $rows) . '
             ON DUPLICATE KEY UPDATE is_correct = VALUES(is_correct)'
        );
        $statement->execute($values);
    }

    /**
     * Graded review rows for the given questions, in the given order.
     *
     * @param list<array<string, mixed>> $questions grading projection from Question::listForGradingByIds
     * @return list<array<string, mixed>>
     */
    public function review(int $attemptId, array $questions): array
    {
        $statement = $this->connection()->prepare(
            'SELECT question_id, selected_option, is_correct, is_flagged FROM ' . $this->table('attempt_answers') . ' WHERE attempt_id = :attempt_id'
        );
        $statement->execute(['attempt_id' => $attemptId]);
        $answers = [];
        foreach ($statement->fetchAll() as $row) {
            $answers[(int) $row['question_id']] = $row;
        }
        $review = [];
        foreach ($questions as $question) {
            $answer = $answers[(int) $question['id']] ?? null;
            $review[] = [
                'question_id' => (int) $question['id'],
                'exam_id' => (int) $question['exam_id'],
                'question_text' => $question['question_text'],
                'option_a' => $question['option_a'],
                'option_b' => $question['option_b'],
                'option_c' => $question['option_c'],
                'option_d' => $question['option_d'],
                'correct_option' => $question['correct_option'],
                'explanation' => $question['explanation'],
                'marks' => $question['marks'],
                'sort_order' => $question['sort_order'],
                'selected_option' => $answer['selected_option'] ?? null,
                'is_correct' => $answer['is_correct'] ?? null,
                'is_flagged' => (int) ($answer['is_flagged'] ?? 0),
            ];
        }
        return $review;
    }
}
