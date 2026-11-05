<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;
use PDO;

final class School extends Model
{
    public const STATUSES = ['trial', 'active', 'suspended', 'archived'];

    private const COLUMNS = 's.id, s.slug, s.name, s.short_name, s.motto, s.status, s.plan_id, s.max_students_override, s.trial_ends_at,
        s.subscription_ends_at, s.email, s.phone, s.address, s.state, s.country, s.logo_path, s.primary_color, s.report_settings, s.custom_domain, s.notes,
        s.created_at, s.updated_at, p.code AS plan_code, p.name AS plan_name, p.features AS plan_features,
        COALESCE(s.max_students_override, p.max_students) AS max_students';

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->connection()->prepare('SELECT ' . self::COLUMNS . ' FROM ' . $this->table('schools') . ' s
            INNER JOIN ' . $this->table('plans') . ' p ON p.id = s.plan_id WHERE s.id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        $statement = $this->connection()->prepare('SELECT ' . self::COLUMNS . ' FROM ' . $this->table('schools') . ' s
            INNER JOIN ' . $this->table('plans') . ' p ON p.id = s.plan_id WHERE s.slug = ? LIMIT 1');
        $statement->execute([$slug]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $statement = $this->connection()->prepare('SELECT COUNT(*) FROM ' . $this->table('schools') . ' WHERE slug = ? AND id <> ?');
        $statement->execute([$slug, $exceptId ?? 0]);
        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function search(string $query, ?string $status, int $page, int $perPage): array
    {
        $where = ['1 = 1'];
        $values = [];
        if ($query !== '') {
            $where[] = '(s.name LIKE ? OR s.slug LIKE ? OR s.email LIKE ? OR s.state LIKE ?)';
            $like = '%' . addcslashes($query, '%_\\') . '%';
            array_push($values, $like, $like, $like, $like);
        }
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $where[] = 's.status = ?';
            $values[] = $status;
        }
        $condition = implode(' AND ', $where);
        $count = $this->connection()->prepare('SELECT COUNT(*) FROM ' . $this->table('schools') . ' s WHERE ' . $condition);
        $count->execute($values);
        $perPage = max(1, min(100, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $statement = $this->connection()->prepare('SELECT ' . self::COLUMNS . ',
                (SELECT COUNT(*) FROM ' . $this->table('users') . ' u WHERE u.school_id = s.id AND u.role = \'student\') AS student_count,
                (SELECT COUNT(*) FROM ' . $this->table('users') . ' u WHERE u.school_id = s.id AND u.role <> \'student\') AS staff_count
            FROM ' . $this->table('schools') . ' s INNER JOIN ' . $this->table('plans') . ' p ON p.id = s.plan_id
            WHERE ' . $condition . ' ORDER BY s.created_at DESC, s.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
        $statement->execute($values);
        return ['rows' => $statement->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $columns = array_keys($data);
        $statement = $this->connection()->prepare('INSERT INTO ' . $this->table('schools') . ' (`' . implode('`, `', $columns) . '`, created_at, updated_at)
            VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ', ?, ?)');
        $statement->execute([...array_values($data), $now, $now]);
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string, mixed> $fields */
    public function update(int $id, array $fields): void
    {
        $allowed = ['name', 'short_name', 'motto', 'slug', 'status', 'plan_id', 'max_students_override', 'trial_ends_at', 'subscription_ends_at',
            'email', 'phone', 'address', 'state', 'country', 'logo_path', 'primary_color', 'custom_domain', 'notes'];
        $sets = [];
        $values = [];
        foreach ($fields as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                throw new \InvalidArgumentException('Column cannot be updated: ' . $column);
            }
            $sets[] = '`' . $column . '` = ?';
            $values[] = $value;
        }
        if ($sets === []) {
            return;
        }
        $sets[] = 'updated_at = ?';
        $values[] = gmdate('Y-m-d H:i:s');
        $values[] = $id;
        $this->connection()->prepare('UPDATE ' . $this->table('schools') . ' SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($values);
    }

    /** @return array<string, int> status => count */
    public function countByStatus(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($this->connection()->query('SELECT status, COUNT(*) FROM ' . $this->table('schools') . ' GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR) as $status => $n) {
            $counts[(string) $status] = (int) $n;
        }
        return $counts;
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 5): array
    {
        $statement = $this->connection()->query('SELECT ' . self::COLUMNS . ' FROM ' . $this->table('schools') . ' s
            INNER JOIN ' . $this->table('plans') . ' p ON p.id = s.plan_id ORDER BY s.created_at DESC, s.id DESC LIMIT ' . max(1, min(20, $limit)));
        return $statement->fetchAll();
    }

    /** Trials whose end date has passed and are still marked trial. @return list<int> */
    public function expiredTrialIds(string $today): array
    {
        $statement = $this->connection()->prepare('SELECT id FROM ' . $this->table('schools') . ' WHERE status = \'trial\' AND trial_ends_at IS NOT NULL AND trial_ends_at < ?');
        $statement->execute([$today]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }
}
