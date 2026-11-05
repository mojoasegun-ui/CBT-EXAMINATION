<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** School staff (teachers and reviewers) with optional staff profile details. */
final class Teacher extends Model
{
    /** @return list<array<string, mixed>> */
    public function all(int $schoolId, string $query = '', bool $activeOnly = false): array
    {
        $params = [$schoolId];
        $where = 'u.school_id = ? AND u.role IN (\'teacher\', \'reviewer\', \'school_admin\')';
        if ($query !== '') {
            $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR sf.staff_no LIKE ?)';
            array_push($params, self::like($query), self::like($query), self::like($query));
        }
        if ($activeOnly) {
            $where .= ' AND u.status = \'active\'';
        }
        return $this->rows('SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.last_login_at, u.must_change_password, u.created_at,
                sf.title, sf.staff_no, sf.qualification,
                (SELECT COUNT(*) FROM ' . $this->table('class_subjects') . ' cs WHERE cs.teacher_id = u.id) AS assignment_count,
                (SELECT COUNT(*) FROM ' . $this->table('school_classes') . ' c WHERE c.class_teacher_id = u.id) AS form_class_count
            FROM ' . $this->table('users') . ' u LEFT JOIN ' . $this->table('staff_profiles') . ' sf ON sf.user_id = u.id
            WHERE ' . $where . ' ORDER BY FIELD(u.role, \'school_admin\', \'teacher\', \'reviewer\'), u.name', $params);
    }

    /** @return array<string, mixed>|null */
    public function find(int $schoolId, int $userId): ?array
    {
        return $this->row('SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.last_login_at, u.must_change_password, u.created_at,
                sf.title, sf.staff_no, sf.qualification
            FROM ' . $this->table('users') . ' u LEFT JOIN ' . $this->table('staff_profiles') . ' sf ON sf.user_id = u.id
            WHERE u.id = ? AND u.school_id = ? AND u.role IN (\'teacher\', \'reviewer\', \'school_admin\')', [$userId, $schoolId]);
    }

    /** @return list<int> ids of active teaching staff, for validating assignments */
    public function activeIds(int $schoolId): array
    {
        return array_map('intval', array_column($this->rows('SELECT id FROM ' . $this->table('users') . ' WHERE school_id = ? AND role IN (\'teacher\', \'school_admin\') AND status = \'active\'', [$schoolId]), 'id'));
    }

    public function saveProfile(int $schoolId, int $userId, ?string $title, ?string $staffNo, ?string $qualification): void
    {
        $now = self::now();
        $this->run('INSERT INTO ' . $this->table('staff_profiles') . ' (user_id, school_id, title, staff_no, qualification, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE title = VALUES(title), staff_no = VALUES(staff_no), qualification = VALUES(qualification), updated_at = VALUES(updated_at)',
            [$userId, $schoolId, $title, $staffNo, $qualification, $now, $now]);
    }

    public function staffNoTaken(int $schoolId, string $staffNo, int $exceptUserId = 0): bool
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('staff_profiles') . ' WHERE school_id = ? AND staff_no = ? AND user_id <> ?', [$schoolId, $staffNo, $exceptUserId]) > 0;
    }

    public function emailTaken(int $schoolId, string $email, int $exceptUserId = 0): bool
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('users') . ' WHERE school_id = ? AND email = ? AND id <> ?', [$schoolId, strtolower($email), $exceptUserId]) > 0;
    }

    /** @return array<string, int> role => count */
    public function countByRole(int $schoolId): array
    {
        $rows = $this->rows('SELECT role, COUNT(*) AS n FROM ' . $this->table('users') . ' WHERE school_id = ? AND role <> \'student\' AND status = \'active\' GROUP BY role', [$schoolId]);
        return array_map('intval', array_column($rows, 'n', 'role'));
    }
}
