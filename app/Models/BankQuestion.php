<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/**
 * Bank questions and passages. $schoolId null = the platform bank (Owner Console).
 * Every query is scoped with "school_id <=> ?" so a school never sees another's questions.
 */
final class BankQuestion extends Model
{
    public const STATUSES = ['draft' => 'Draft', 'review' => 'In review', 'published' => 'Published', 'archived' => 'Archived'];
    public const DIFFICULTIES = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'];
    public const SOURCES = ['teacher' => 'Teacher-written', 'practice' => 'Practice set', 'past_licensed' => 'Past question (licensed)', 'ai_reviewed' => 'AI-drafted, reviewed'];
    public const LEVELS = ['any' => 'Any level', 'primary' => 'Primary', 'jss' => 'Junior secondary', 'ss' => 'Senior secondary'];
    public const BODIES = ['' => 'None', 'WAEC' => 'WAEC', 'NECO' => 'NECO', 'JAMB' => 'JAMB', 'BECE' => 'BECE', 'SCHOOL' => 'School exam'];
    private const FIELDS = ['subject', 'topic', 'level', 'difficulty', 'exam_body', 'year', 'source', 'passage_id', 'body', 'image_path', 'option_a', 'option_b', 'option_c',
        'option_d', 'option_e', 'correct_option', 'explanation', 'marks', 'status', 'review_note', 'fingerprint', 'reviewed_by', 'reviewed_at'];

    /**
     * @param array{q?: string, subject?: string, status?: string, difficulty?: string, author?: int, topic?: string} $f
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function search(?int $schoolId, array $f, int $page, int $perPage = 25): array
    {
        $where = ['q.school_id <=> ?'];
        $params = [$schoolId];
        foreach (['subject', 'status', 'difficulty', 'topic'] as $key) {
            if (($f[$key] ?? '') !== '') {
                $where[] = 'q.' . $key . ' = ?';
                $params[] = $f[$key];
            }
        }
        if (!empty($f['author'])) {
            $where[] = 'q.created_by = ?';
            $params[] = (int) $f['author'];
        }
        $text = trim((string) ($f['q'] ?? ''));
        if ($text !== '') {
            $words = array_filter(preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [], static fn ($w) => mb_strlen($w) >= 3);
            if ($words !== []) {
                $where[] = 'MATCH(q.body) AGAINST (? IN BOOLEAN MODE)';
                $params[] = implode(' ', array_map(static fn ($w) => '+' . $w . '*', array_slice($words, 0, 6)));
            } else {
                $where[] = 'q.body LIKE ?';
                $params[] = self::like($text);
            }
        }
        $sql = ' FROM ' . $this->table('bank_questions') . ' q WHERE ' . implode(' AND ', $where);
        $perPage = max(1, min(100, $perPage));
        $rows = $this->rows('SELECT q.*, u.name AS author_name' . str_replace(' WHERE ', ' LEFT JOIN ' . $this->table('users') . ' u ON u.id = q.created_by WHERE ', $sql)
            . ' ORDER BY q.updated_at DESC, q.id DESC LIMIT ' . $perPage . ' OFFSET ' . max(0, ($page - 1) * $perPage), $params);
        return ['rows' => $rows, 'total' => (int) $this->scalar('SELECT COUNT(*)' . $sql, $params)];
    }

    /** @return array<string, mixed>|null */
    public function find(?int $schoolId, int $id): ?array
    {
        return $this->row('SELECT q.*, u.name AS author_name, r.name AS reviewer_name, p.title AS passage_title, p.body AS passage_body FROM ' . $this->table('bank_questions') . ' q
            LEFT JOIN ' . $this->table('users') . ' u ON u.id = q.created_by LEFT JOIN ' . $this->table('users') . ' r ON r.id = q.reviewed_by
            LEFT JOIN ' . $this->table('bank_passages') . ' p ON p.id = q.passage_id WHERE q.id = ? AND q.school_id <=> ?', [$id, $schoolId]);
    }

    /** @param array<string, mixed> $data */
    public function create(?int $schoolId, int $userId, array $data): int
    {
        $now = self::now();
        return $this->insertRow('bank_questions', array_intersect_key($data, array_flip(self::FIELDS)) + ['school_id' => $schoolId, 'created_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
    }

    /** @param array<string, mixed> $data */
    public function update(?int $schoolId, int $id, array $data): int
    {
        $data = array_intersect_key($data, array_flip(self::FIELDS)) + ['updated_at' => self::now()];
        $sets = implode(', ', array_map(static fn ($c) => '`' . $c . '` = ?', array_keys($data)));
        return $this->run('UPDATE ' . $this->table('bank_questions') . ' SET ' . $sets . ' WHERE id = ? AND school_id <=> ?', [...array_values($data), $id, $schoolId]);
    }

    public function delete(?int $schoolId, int $id): int
    {
        return $this->run('DELETE FROM ' . $this->table('bank_questions') . ' WHERE id = ? AND school_id <=> ?', [$id, $schoolId]);
    }

    /** @return array<string, mixed>|null an existing question with the same normalised text */
    public function duplicateOf(?int $schoolId, string $fingerprint, int $exceptId = 0): ?array
    {
        return $this->row('SELECT id, subject, status FROM ' . $this->table('bank_questions') . ' WHERE school_id <=> ? AND fingerprint = ? AND id <> ? LIMIT 1', [$schoolId, $fingerprint, $exceptId]);
    }

    /** @return list<array{subject: string, total: int, published: int, review: int, draft: int}> */
    public function health(?int $schoolId): array
    {
        return array_map(static fn (array $r): array => ['subject' => (string) $r['subject']] + array_map('intval', array_diff_key($r, ['subject' => 1])),
            $this->rows('SELECT subject, COUNT(*) AS total, SUM(status = \'published\') AS published, SUM(status = \'review\') AS review, SUM(status = \'draft\') AS draft
                FROM ' . $this->table('bank_questions') . ' WHERE school_id <=> ? AND status <> \'archived\' GROUP BY subject ORDER BY subject', [$schoolId]));
    }

    /** @return list<string> subjects used in this bank */
    public function subjects(?int $schoolId): array
    {
        return array_column($this->rows('SELECT DISTINCT subject FROM ' . $this->table('bank_questions') . ' WHERE school_id <=> ? ORDER BY subject', [$schoolId]), 'subject');
    }

    /** @return list<string> */
    public function topics(?int $schoolId, string $subject): array
    {
        return array_column($this->rows('SELECT DISTINCT topic FROM ' . $this->table('bank_questions') . ' WHERE school_id <=> ? AND subject = ? AND topic IS NOT NULL ORDER BY topic', [$schoolId, $subject]), 'topic');
    }

    public function countByStatus(?int $schoolId, string $status): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('bank_questions') . ' WHERE school_id <=> ? AND status = ?', [$schoolId, $status]);
    }

    // ---- Passages -------------------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public function passages(?int $schoolId): array
    {
        return $this->rows('SELECT p.*, (SELECT COUNT(*) FROM ' . $this->table('bank_questions') . ' q WHERE q.passage_id = p.id) AS question_count
            FROM ' . $this->table('bank_passages') . ' p WHERE p.school_id <=> ? ORDER BY p.subject, p.title', [$schoolId]);
    }

    /** @return array<string, mixed>|null */
    public function passage(?int $schoolId, int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('bank_passages') . ' WHERE id = ? AND school_id <=> ?', [$id, $schoolId]);
    }

    public function savePassage(?int $schoolId, int $userId, ?int $id, string $subject, string $title, string $body): int
    {
        $now = self::now();
        if ($id !== null) {
            $this->run('UPDATE ' . $this->table('bank_passages') . ' SET subject = ?, title = ?, body = ?, updated_at = ? WHERE id = ? AND school_id <=> ?', [$subject, $title, $body, $now, $id, $schoolId]);
            return $id;
        }
        return $this->insertRow('bank_passages', ['school_id' => $schoolId, 'subject' => $subject, 'title' => $title, 'body' => $body, 'created_by' => $userId, 'created_at' => $now, 'updated_at' => $now]);
    }
}
