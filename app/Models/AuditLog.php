<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

final class AuditLog extends Model
{
    /** @param array<string, mixed> $entry */
    public function insert(array $entry): void
    {
        $this->connection()->prepare('INSERT INTO ' . $this->table('audit_logs') . '
            (school_id, actor_id, actor_name, impersonator_id, action, target_type, target_id, summary, meta, ip, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
            $entry['school_id'] ?? null,
            $entry['actor_id'] ?? null,
            $entry['actor_name'] ?? null,
            $entry['impersonator_id'] ?? null,
            $entry['action'],
            $entry['target_type'] ?? null,
            $entry['target_id'] ?? null,
            mb_substr((string) $entry['summary'], 0, 255),
            isset($entry['meta']) && $entry['meta'] !== [] ? json_encode($entry['meta'], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : null,
            $entry['ip'] ?? null,
            isset($entry['user_agent']) ? mb_substr((string) $entry['user_agent'], 0, 255) : null,
            gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function search(?int $schoolId, string $action, int $page, int $perPage, bool $platformOnly = false): array
    {
        $where = ['1 = 1'];
        $values = [];
        if ($platformOnly) {
            $where[] = 'a.school_id IS NULL';
        } elseif ($schoolId !== null) {
            $where[] = 'a.school_id = ?';
            $values[] = $schoolId;
        }
        if ($action !== '') {
            $where[] = 'a.action LIKE ?';
            $values[] = addcslashes($action, '%_\\') . '%';
        }
        $condition = implode(' AND ', $where);
        $count = $this->connection()->prepare('SELECT COUNT(*) FROM ' . $this->table('audit_logs') . ' a WHERE ' . $condition);
        $count->execute($values);
        $perPage = max(1, min(100, $perPage));
        $statement = $this->connection()->prepare('SELECT a.*, s.name AS school_name FROM ' . $this->table('audit_logs') . ' a
            LEFT JOIN ' . $this->table('schools') . ' s ON s.id = a.school_id
            WHERE ' . $condition . ' ORDER BY a.id DESC LIMIT ' . $perPage . ' OFFSET ' . max(0, ($page - 1) * $perPage));
        $statement->execute($values);
        return ['rows' => $statement->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }
}
