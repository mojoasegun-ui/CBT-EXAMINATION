<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** A class arm such as "SS 2 A". Levels cover nursery to senior secondary. */
final class SchoolClass extends Model
{
    public const LEVELS = [
        'Nursery 1' => 1, 'Nursery 2' => 2,
        'Primary 1' => 3, 'Primary 2' => 4, 'Primary 3' => 5, 'Primary 4' => 6, 'Primary 5' => 7, 'Primary 6' => 8,
        'JSS 1' => 9, 'JSS 2' => 10, 'JSS 3' => 11,
        'SS 1' => 12, 'SS 2' => 13, 'SS 3' => 14,
    ];
    public const FINAL_LEVEL = 'SS 3';

    /** @return list<array<string, mixed>> ordered by level then arm, with student counts */
    public function all(int $schoolId, bool $activeOnly = false): array
    {
        return $this->rows('SELECT c.*, d.name AS department_name, t.name AS class_teacher_name,
                (SELECT COUNT(*) FROM ' . $this->table('student_profiles') . ' sp WHERE sp.class_id = c.id AND sp.status = \'active\') AS student_count,
                (SELECT COUNT(*) FROM ' . $this->table('class_subjects') . ' cs WHERE cs.class_id = c.id) AS subject_count
            FROM ' . $this->table('school_classes') . ' c
            LEFT JOIN ' . $this->table('departments') . ' d ON d.id = c.department_id
            LEFT JOIN ' . $this->table('users') . ' t ON t.id = c.class_teacher_id
            WHERE c.school_id = ?' . ($activeOnly ? ' AND c.is_active = 1' : '') . ' ORDER BY c.level_order, c.arm', [$schoolId]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $schoolId, int $id): ?array
    {
        return $this->row('SELECT c.*, d.name AS department_name, t.name AS class_teacher_name FROM ' . $this->table('school_classes') . ' c
            LEFT JOIN ' . $this->table('departments') . ' d ON d.id = c.department_id
            LEFT JOIN ' . $this->table('users') . ' t ON t.id = c.class_teacher_id
            WHERE c.id = ? AND c.school_id = ?', [$id, $schoolId]);
    }

    /** @return array<string, mixed>|null case-insensitive lookup by "SS 2 A", "ss2a", "SS2 A" (used by imports) */
    public function findByName(int $schoolId, string $name): ?array
    {
        $key = strtolower(preg_replace('/\s+/', '', $name) ?? '');
        foreach ($this->rows('SELECT id, name, level, arm, department_id FROM ' . $this->table('school_classes') . ' WHERE school_id = ?', [$schoolId]) as $class) {
            if (strtolower(str_replace(' ', '', (string) $class['name'])) === $key) {
                return $class;
            }
        }
        return null;
    }

    public function exists(int $schoolId, string $level, string $arm, int $exceptId = 0): bool
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('school_classes') . ' WHERE school_id = ? AND level = ? AND arm = ? AND id <> ?', [$schoolId, $level, $arm, $exceptId]) > 0;
    }

    public function create(int $schoolId, string $level, string $arm, ?int $departmentId, ?int $teacherId, ?int $capacity): int
    {
        $now = self::now();
        return $this->insertRow('school_classes', [
            'school_id' => $schoolId, 'level' => $level, 'level_order' => self::LEVELS[$level] ?? 99, 'arm' => $arm, 'name' => trim($level . ' ' . $arm),
            'department_id' => $departmentId, 'class_teacher_id' => $teacherId, 'capacity' => $capacity, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(int $schoolId, int $id, array $data): int
    {
        if (isset($data['level'], $data['arm'])) {
            $data['level_order'] = self::LEVELS[$data['level']] ?? 99;
            $data['name'] = trim($data['level'] . ' ' . $data['arm']);
        }
        return $this->updateScoped('school_classes', $schoolId, $id, $data + ['updated_at' => self::now()],
            ['level', 'level_order', 'arm', 'name', 'department_id', 'class_teacher_id', 'capacity', 'is_active', 'updated_at']);
    }

    public function studentCount(int $schoolId, int $classId): int
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('student_profiles') . ' WHERE school_id = ? AND class_id = ? AND status = \'active\'', [$schoolId, $classId]);
    }

    public function delete(int $schoolId, int $id): int
    {
        return $this->run('DELETE FROM ' . $this->table('school_classes') . ' WHERE id = ? AND school_id = ?', [$id, $schoolId]);
    }

    /** The class one level up with the same arm (JSS 1 A → JSS 2 A), if the school has it. @return array<string, mixed>|null */
    public function nextLevel(int $schoolId, array $class): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('school_classes') . ' WHERE school_id = ? AND level_order > ? AND arm = ? AND is_active = 1 ORDER BY level_order LIMIT 1',
            [$schoolId, (int) $class['level_order'], (string) $class['arm']]);
    }
}
