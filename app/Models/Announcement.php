<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** Notices posted by the platform, a school admin or a teacher. Every read is limited to the viewer's own school. */
final class Announcement extends Model
{
    public const AUDIENCES = ['students' => 'Students', 'teachers' => 'Teachers and staff', 'everyone' => 'Everyone'];

    /**
     * WHERE fragment + params for "announcements addressed to this viewer".
     *
     * @return array{0: string, 1: list<mixed>}
     */
    private function addressedTo(string $role, ?int $schoolId, int $userId): array
    {
        if ($role === User::ROLE_OWNER) {
            return ['a.school_id IS NULL', []];
        }
        if ($schoolId === null) {
            return ['1 = 0', []];
        }
        if ($role === User::ROLE_STUDENT) {
            $classId = (int) $this->scalar('SELECT class_id FROM ' . $this->table('student_profiles') . ' WHERE user_id = ? AND school_id = ?', [$userId, $schoolId]);
            return ["(a.school_id IS NULL OR a.school_id = ?) AND a.audience IN ('students','everyone') AND (a.class_id IS NULL OR a.class_id = ?)", [$schoolId, $classId]];
        }
        return ["(a.school_id IS NULL OR a.school_id = ?) AND a.audience IN ('teachers','everyone')", [$schoolId]];
    }

    /** @return list<array<string, mixed>> newest first, pinned on top, with is_read for this viewer */
    public function feed(string $role, ?int $schoolId, int $userId, int $limit = 60): array
    {
        [$where, $params] = $this->addressedTo($role, $schoolId, $userId);
        return $this->rows('SELECT a.*, (r.user_id IS NOT NULL) AS is_read, c.name AS class_name FROM ' . $this->table('announcements') . ' a
            LEFT JOIN ' . $this->table('announcement_reads') . ' r ON r.announcement_id = a.id AND r.user_id = ?
            LEFT JOIN ' . $this->table('school_classes') . ' c ON c.id = a.class_id
            WHERE ' . $where . ' ORDER BY a.pinned DESC, a.id DESC LIMIT ' . max(1, min(200, $limit)), [$userId, ...$params]);
    }

    public function unreadFor(?string $role, ?int $schoolId, ?int $userId): int
    {
        if ($role === null || $userId === null) {
            return 0;
        }
        [$where, $params] = $this->addressedTo($role, $schoolId, $userId);
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('announcements') . ' a
            LEFT JOIN ' . $this->table('announcement_reads') . ' r ON r.announcement_id = a.id AND r.user_id = ?
            WHERE r.user_id IS NULL AND ' . $where, [$userId, ...$params]);
    }

    /** @param list<int> $ids */
    public function markRead(int $userId, array $ids): void
    {
        foreach ($ids as $id) {
            $this->run('INSERT IGNORE INTO ' . $this->table('announcement_reads') . ' (announcement_id, user_id, read_at) VALUES (?, ?, ?)', [$id, $userId, gmdate('Y-m-d H:i:s')]);
        }
    }

    /** Posts the viewer is allowed to manage: a school's own (admin) or one's own (teacher); the owner's platform posts. */
    public function sent(?int $schoolId, ?int $onlyAuthorId, int $limit = 40): array
    {
        $where = $schoolId === null ? 'a.school_id IS NULL' : 'a.school_id = ?';
        $params = $schoolId === null ? [] : [$schoolId];
        if ($onlyAuthorId !== null) {
            $where .= ' AND a.author_id = ?';
            $params[] = $onlyAuthorId;
        }
        return $this->rows('SELECT a.*, c.name AS class_name FROM ' . $this->table('announcements') . ' a LEFT JOIN ' . $this->table('school_classes') . ' c ON c.id = a.class_id
            WHERE ' . $where . ' ORDER BY a.id DESC LIMIT ' . max(1, min(100, $limit)), $params);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->insertRow('announcements', $data + ['created_at' => gmdate('Y-m-d H:i:s')]);
    }

    public function find(int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('announcements') . ' WHERE id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->run('DELETE FROM ' . $this->table('announcement_reads') . ' WHERE announcement_id = ?', [$id]);
        $this->run('DELETE FROM ' . $this->table('announcements') . ' WHERE id = ?', [$id]);
    }

    /**
     * Everyone an announcement is addressed to (so each can be notified on their phone).
     *
     * @return list<int>
     */
    public function recipientIds(?int $schoolId, string $audience, ?int $classId): array
    {
        $ids = [];
        if ($audience === 'students' || $audience === 'everyone') {
            $sql = 'SELECT user_id FROM ' . $this->table('student_profiles') . ' WHERE status = \'active\'';
            $params = [];
            if ($schoolId !== null) {
                $sql .= ' AND school_id = ?';
                $params[] = $schoolId;
            }
            if ($classId !== null) {
                $sql .= ' AND class_id = ?';
                $params[] = $classId;
            }
            $ids = array_merge($ids, array_column($this->rows($sql, $params), 'user_id'));
        }
        if ($audience === 'teachers' || $audience === 'everyone') {
            $sql = 'SELECT id FROM ' . $this->table('users') . ' WHERE status = \'active\' AND role IN (\'school_admin\',\'teacher\',\'reviewer\')';
            $params = [];
            if ($schoolId !== null) {
                $sql .= ' AND school_id = ?';
                $params[] = $schoolId;
            }
            $ids = array_merge($ids, array_column($this->rows($sql, $params), 'id'));
        }
        return array_map('intval', $ids);
    }

    /** @return list<array<string, mixed>> the classes a teacher is class teacher of */
    public function classesOfTeacher(int $schoolId, int $teacherId): array
    {
        return $this->rows('SELECT id, name FROM ' . $this->table('school_classes') . ' WHERE school_id = ? AND class_teacher_id = ? AND is_active = 1 ORDER BY level_order, arm', [$schoolId, $teacherId]);
    }
}
