<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;
use PDO;

final class User extends Model
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_SCHOOL_ADMIN = 'school_admin';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_REVIEWER = 'reviewer';
    public const ROLE_STUDENT = 'student';
    public const STAFF_ROLES = [self::ROLE_SCHOOL_ADMIN, self::ROLE_TEACHER, self::ROLE_REVIEWER];

    private const COLUMNS = 'u.id, u.school_id, u.role, u.name, u.email, u.username, u.phone, u.password_hash, u.status, u.must_change_password,
        u.two_factor_secret, u.two_factor_confirmed_at, u.two_factor_recovery_codes, u.two_factor_last_step, u.failed_logins, u.locked_until,
        u.last_login_at, u.last_login_ip, u.created_at, u.updated_at';

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->connection()->prepare('SELECT ' . self::COLUMNS . ' FROM ' . $this->table('users') . ' u WHERE u.id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    public function findOwnerByEmail(string $email): ?array
    {
        $statement = $this->connection()->prepare('SELECT ' . self::COLUMNS . ' FROM ' . $this->table('users') . ' u WHERE u.school_id IS NULL AND u.role = ? AND u.email = ? LIMIT 1');
        $statement->execute([self::ROLE_OWNER, self::normalizeEmail($email)]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * Staff accounts across all schools sharing this email (a teacher may work at two schools).
     *
     * @return list<array<string, mixed>>
     */
    public function findStaffByEmail(string $email): array
    {
        $placeholders = implode(',', array_fill(0, count(self::STAFF_ROLES), '?'));
        $statement = $this->connection()->prepare('SELECT ' . self::COLUMNS . ', s.name AS school_name, s.status AS school_status
            FROM ' . $this->table('users') . ' u INNER JOIN ' . $this->table('schools') . ' s ON s.id = u.school_id
            WHERE u.email = ? AND u.role IN (' . $placeholders . ') ORDER BY s.name');
        $statement->execute([self::normalizeEmail($email), ...self::STAFF_ROLES]);
        return $statement->fetchAll();
    }

    /** @param array<string, mixed> $data @return int new id */
    public function create(array $data): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $statement = $this->connection()->prepare('INSERT INTO ' . $this->table('users') . '
            (school_id, role, name, email, username, phone, password_hash, status, must_change_password, created_at, updated_at)
            VALUES (:school_id, :role, :name, :email, :username, :phone, :password_hash, :status, :must_change_password, :created_at, :updated_at)');
        $statement->execute([
            'school_id' => $data['school_id'] ?? null,
            'role' => $data['role'],
            'name' => $data['name'],
            'email' => isset($data['email']) ? self::normalizeEmail((string) $data['email']) : null,
            'username' => $data['username'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password_hash' => $data['password_hash'],
            'status' => $data['status'] ?? 'active',
            'must_change_password' => (int) ($data['must_change_password'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->connection()->lastInsertId();
    }

    /** @param array<string, mixed> $fields Only whitelisted columns are written. */
    public function update(int $id, array $fields): void
    {
        $allowed = ['name', 'email', 'phone', 'status', 'password_hash', 'must_change_password', 'two_factor_secret', 'two_factor_confirmed_at',
            'two_factor_recovery_codes', 'two_factor_last_step', 'failed_logins', 'locked_until', 'last_login_at', 'last_login_ip'];
        $sets = [];
        $values = [];
        foreach ($fields as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                throw new \InvalidArgumentException('Column cannot be updated: ' . $column);
            }
            $sets[] = '`' . $column . '` = ?';
            $values[] = $column === 'email' && is_string($value) ? self::normalizeEmail($value) : $value;
        }
        if ($sets === []) {
            return;
        }
        $sets[] = 'updated_at = ?';
        $values[] = gmdate('Y-m-d H:i:s');
        $values[] = $id;
        $this->connection()->prepare('UPDATE ' . $this->table('users') . ' SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($values);
    }

    /** Change a login name (e.g. a student's admission number) inside one school only. */
    public function setUsername(int $schoolId, int $userId, string $username): void
    {
        $this->connection()->prepare('UPDATE ' . $this->table('users') . ' SET username = ?, updated_at = ? WHERE id = ? AND school_id = ?')
            ->execute([$username, gmdate('Y-m-d H:i:s'), $userId, $schoolId]);
    }

    /** Switch between staff roles inside one school. Never grants owner or student roles. */
    public function setRole(int $schoolId, int $userId, string $role): void
    {
        if (!in_array($role, self::STAFF_ROLES, true)) {
            throw new \InvalidArgumentException('Only staff roles can be assigned here.');
        }
        $this->connection()->prepare('UPDATE ' . $this->table('users') . ' SET role = ?, updated_at = ? WHERE id = ? AND school_id = ? AND role IN (\'school_admin\', \'teacher\', \'reviewer\')')
            ->execute([$role, gmdate('Y-m-d H:i:s'), $userId, $schoolId]);
    }

    /** @return array<string, mixed>|null a student account by school + admission number */
    public function findStudentLogin(int $schoolId, string $username): ?array
    {
        $statement = $this->connection()->prepare('SELECT ' . self::COLUMNS . ', sp.status AS profile_status FROM ' . $this->table('users') . ' u
            INNER JOIN ' . $this->table('student_profiles') . ' sp ON sp.user_id = u.id
            WHERE u.school_id = ? AND u.role = ? AND u.username = ? LIMIT 1');
        $statement->execute([$schoolId, self::ROLE_STUDENT, strtoupper(trim($username))]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function recordFailedLogin(int $id, int $lockAfter, int $lockSeconds): void
    {
        $this->connection()->prepare('UPDATE ' . $this->table('users') . '
            SET failed_logins = failed_logins + 1,
                locked_until = IF(failed_logins >= ?, ?, locked_until)
            WHERE id = ?')->execute([$lockAfter, gmdate('Y-m-d H:i:s', time() + $lockSeconds), $id]);
    }

    /** @return array<string, mixed>|null the school admin with the lowest id (the founding admin) */
    public function primaryAdmin(int $schoolId): ?array
    {
        $statement = $this->connection()->prepare('SELECT ' . self::COLUMNS . ' FROM ' . $this->table('users') . ' u
            WHERE u.school_id = ? AND u.role = ? ORDER BY u.id LIMIT 1');
        $statement->execute([$schoolId, self::ROLE_SCHOOL_ADMIN]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function countOwners(): int
    {
        return (int) $this->connection()->query('SELECT COUNT(*) FROM ' . $this->table('users') . " WHERE school_id IS NULL AND role = 'owner'")->fetchColumn();
    }

    /** @return array<string, int> role => count, across the platform */
    public function countByRole(): array
    {
        $rows = $this->connection()->query('SELECT role, COUNT(*) AS n FROM ' . $this->table('users') . ' WHERE school_id IS NOT NULL GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_map('intval', $rows);
    }

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
