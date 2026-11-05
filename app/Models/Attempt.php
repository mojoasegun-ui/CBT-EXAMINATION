<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;
use PDO;

final class Attempt extends Model
{
    public function create(
        int $examId,
        string $sessionToken,
        string $startedAt,
        float $total,
        ?string $studentName = null,
        ?string $studentEmail = null,
        ?string $accessToken = null,
        ?string $multiExamJson = null,
        ?string $questionOrderJson = null,
        ?int $durationMinutes = null,
    ): int {
        $statement = $this->connection()->prepare(
            'INSERT INTO ' . $this->table('attempts') . ' (exam_id, session_token, student_name, student_email, access_token, multi_exam, question_order, started_at, total, duration_minutes, status)
             VALUES (:exam_id, :session_token, :student_name, :student_email, :access_token, :multi_exam, :question_order, :started_at, :total, :duration_minutes, \'in_progress\')'
        );
        $statement->execute([
            'exam_id' => $examId,
            'session_token' => $sessionToken,
            'student_name' => $studentName,
            'student_email' => $studentEmail,
            'access_token' => $accessToken,
            'multi_exam' => $multiExamJson,
            'question_order' => $questionOrderJson,
            'started_at' => $startedAt,
            'total' => $total,
            'duration_minutes' => $durationMinutes,
        ]);
        return (int) $this->connection()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findOwned(int $attemptId, string $sessionToken, bool $forUpdate = false): ?array
    {
        return $this->findWhere('a.id = :id AND a.session_token = :session_token', ['id' => $attemptId, 'session_token' => $sessionToken], $forUpdate);
    }

    /** For trusted server-side work only (scheduler, queued jobs): skips the ownership check. @return array<string, mixed>|null */
    public function findById(int $attemptId, bool $forUpdate = false): ?array
    {
        return $this->findWhere('a.id = :id', ['id' => $attemptId], $forUpdate);
    }

    /** @return array<string, mixed>|null */
    public function findByAccessToken(string $accessToken): ?array
    {
        return $this->findWhere('a.access_token = :access_token', ['access_token' => $accessToken]);
    }

    /**
     * In-progress attempts whose time limit (plus grace) has passed.
     *
     * @return list<int>
     */
    public function listOverdueIds(int $graceSeconds, int $limit): array
    {
        $limit = max(1, min(500, $limit));
        $statement = $this->connection()->prepare(
            'SELECT a.id FROM ' . $this->table('attempts') . ' a
             INNER JOIN ' . $this->table('exams') . ' e ON e.id = a.exam_id
             WHERE a.status = \'in_progress\'
               AND a.started_at < :cutoff_floor
               AND TIMESTAMPADD(SECOND, COALESCE(a.duration_minutes, e.duration_minutes) * 60 + :grace, a.started_at) < :now
             ORDER BY a.started_at
             LIMIT ' . $limit
        );
        $now = gmdate('Y-m-d H:i:s');
        // Cheap pre-filter on the (status, started_at) index: nothing that started in the last minute can be overdue.
        $statement->execute(['cutoff_floor' => gmdate('Y-m-d H:i:s', time() - 60), 'grace' => max(0, $graceSeconds), 'now' => $now]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Attempts started per day (UTC) for the last N days, oldest first, zero-filled.
     *
     * @return list<array{date: string, count: int}>
     */
    public function dailyCounts(int $days): array
    {
        $days = max(1, min(90, $days));
        $since = gmdate('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
        $statement = $this->connection()->prepare('SELECT DATE(started_at) AS d, COUNT(*) AS n FROM ' . $this->table('attempts') . ' WHERE started_at >= ? GROUP BY DATE(started_at)');
        $statement->execute([$since]);
        $counts = $statement->fetchAll(PDO::FETCH_KEY_PAIR);
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = gmdate('Y-m-d', strtotime('-' . $i . ' days'));
            $series[] = ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
        }
        return $series;
    }

    public function submit(int $attemptId, float $score, float $total, string $submittedAt, string $status): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE ' . $this->table('attempts') . ' SET score = :score, total = :total, submitted_at = :submitted_at, status = :status
             WHERE id = :id AND status = \'in_progress\''
        );
        $statement->execute(['score' => $score, 'total' => $total, 'submitted_at' => $submittedAt, 'status' => $status, 'id' => $attemptId]);
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>|null
     */
    private function findWhere(string $condition, array $parameters, bool $forUpdate = false): ?array
    {
        $sql = 'SELECT a.id, a.exam_id, a.session_token, a.student_name, a.student_email, a.access_token, a.multi_exam, a.question_order,
                       a.started_at, a.submitted_at, a.score, a.total, a.status,
                       COALESCE(a.duration_minutes, e.duration_minutes) AS duration_minutes,
                       e.title, e.total_questions, t.name AS exam_type_name, t.slug AS exam_type_slug,
                       s.name AS subject_name, s.slug AS subject_slug, e.year
                FROM ' . $this->table('attempts') . ' a
                INNER JOIN ' . $this->table('exams') . ' e ON e.id = a.exam_id
                INNER JOIN ' . $this->table('exam_types') . ' t ON t.id = e.exam_type_id
                INNER JOIN ' . $this->table('subjects') . ' s ON s.id = e.subject_id
                WHERE ' . $condition . ' LIMIT 1';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->connection()->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
}
