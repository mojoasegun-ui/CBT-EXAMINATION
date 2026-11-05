<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

final class Department extends Model
{
    /** @return list<array<string, mixed>> with class and student counts */
    public function all(int $schoolId, bool $activeOnly = false): array
    {
        return $this->rows('SELECT d.*,
                (SELECT COUNT(*) FROM ' . $this->table('school_classes') . ' c WHERE c.department_id = d.id) AS class_count,
                (SELECT COUNT(*) FROM ' . $this->table('student_profiles') . ' sp WHERE sp.department_id = d.id AND sp.status = \'active\') AS student_count,
                (SELECT COUNT(*) FROM ' . $this->table('school_subjects') . ' ss WHERE ss.department_id = d.id) AS subject_count
            FROM ' . $this->table('departments') . ' d WHERE d.school_id = ?' . ($activeOnly ? ' AND d.is_active = 1' : '') . ' ORDER BY d.name', [$schoolId]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $schoolId, int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('departments') . ' WHERE id = ? AND school_id = ?', [$id, $schoolId]);
    }

    public function nameTaken(int $schoolId, string $name, int $exceptId = 0): bool
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('departments') . ' WHERE school_id = ? AND name = ? AND id <> ?', [$schoolId, $name, $exceptId]) > 0;
    }

    public function create(int $schoolId, string $name, ?string $code, ?string $description): int
    {
        $now = self::now();
        return $this->insertRow('departments', ['school_id' => $schoolId, 'name' => $name, 'code' => $code, 'description' => $description, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now]);
    }

    /** @param array<string, mixed> $data */
    public function update(int $schoolId, int $id, array $data): int
    {
        return $this->updateScoped('departments', $schoolId, $id, $data + ['updated_at' => self::now()], ['name', 'code', 'description', 'is_active', 'updated_at']);
    }

    public function delete(int $schoolId, int $id): int
    {
        return $this->run('DELETE FROM ' . $this->table('departments') . ' WHERE id = ? AND school_id = ?', [$id, $schoolId]);
    }
}
