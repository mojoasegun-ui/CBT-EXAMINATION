<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

final class SchoolSubject extends Model
{
    public const CATEGORIES = [
        'core' => 'Core (compulsory)',
        'science' => 'Science',
        'commercial' => 'Commercial',
        'arts' => 'Arts & Humanities',
        'language' => 'Languages',
        'technical' => 'Technical & Vocational',
        'general' => 'General',
    ];

    /** @return list<array<string, mixed>> */
    public function all(int $schoolId, bool $activeOnly = false): array
    {
        return $this->rows('SELECT s.*, d.name AS department_name,
                (SELECT COUNT(*) FROM ' . $this->table('class_subjects') . ' cs WHERE cs.subject_id = s.id) AS class_count
            FROM ' . $this->table('school_subjects') . ' s LEFT JOIN ' . $this->table('departments') . ' d ON d.id = s.department_id
            WHERE s.school_id = ?' . ($activeOnly ? ' AND s.is_active = 1' : '') . ' ORDER BY FIELD(s.category, \'core\', \'science\', \'commercial\', \'arts\', \'language\', \'technical\', \'general\'), s.name', [$schoolId]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $schoolId, int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('school_subjects') . ' WHERE id = ? AND school_id = ?', [$id, $schoolId]);
    }

    public function nameTaken(int $schoolId, string $name, int $exceptId = 0): bool
    {
        return (int) $this->scalar('SELECT COUNT(*) FROM ' . $this->table('school_subjects') . ' WHERE school_id = ? AND name = ? AND id <> ?', [$schoolId, $name, $exceptId]) > 0;
    }

    public function create(int $schoolId, string $name, ?string $code, string $category, ?int $departmentId): int
    {
        $now = self::now();
        return $this->insertRow('school_subjects', [
            'school_id' => $schoolId, 'name' => $name, 'code' => $code, 'category' => array_key_exists($category, self::CATEGORIES) ? $category : 'general',
            'department_id' => $departmentId, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(int $schoolId, int $id, array $data): int
    {
        return $this->updateScoped('school_subjects', $schoolId, $id, $data + ['updated_at' => self::now()], ['name', 'code', 'category', 'department_id', 'is_active', 'updated_at']);
    }

    // ---- Class ↔ subject ↔ teacher assignments -------------------------------------

    /** @return list<array<string, mixed>> subjects offered in a class, with their teacher */
    public function forClass(int $schoolId, int $classId): array
    {
        return $this->rows('SELECT cs.id AS assignment_id, cs.teacher_id, s.id, s.name, s.code, s.category, t.name AS teacher_name
            FROM ' . $this->table('class_subjects') . ' cs
            INNER JOIN ' . $this->table('school_subjects') . ' s ON s.id = cs.subject_id
            LEFT JOIN ' . $this->table('users') . ' t ON t.id = cs.teacher_id
            WHERE cs.school_id = ? AND cs.class_id = ? ORDER BY s.name', [$schoolId, $classId]);
    }

    /**
     * Replace a class's subject list. Teachers are only kept if they belong to the same school.
     *
     * @param array<int, int|null> $subjectTeachers subject id => teacher id (or null)
     */
    public function syncClass(int $schoolId, int $classId, array $subjectTeachers, array $validSubjectIds, array $validTeacherIds): void
    {
        $this->run('DELETE FROM ' . $this->table('class_subjects') . ' WHERE school_id = ? AND class_id = ?', [$schoolId, $classId]);
        foreach ($subjectTeachers as $subjectId => $teacherId) {
            if (!in_array((int) $subjectId, $validSubjectIds, true)) {
                continue;
            }
            $this->insertRow('class_subjects', [
                'school_id' => $schoolId, 'class_id' => $classId, 'subject_id' => (int) $subjectId,
                'teacher_id' => $teacherId !== null && in_array((int) $teacherId, $validTeacherIds, true) ? (int) $teacherId : null,
            ]);
        }
    }

    /** @return list<array<string, mixed>> a teacher's class/subject pairs */
    public function forTeacher(int $schoolId, int $teacherId): array
    {
        return $this->rows('SELECT c.id AS class_id, c.name AS class_name, s.id AS subject_id, s.name AS subject_name
            FROM ' . $this->table('class_subjects') . ' cs
            INNER JOIN ' . $this->table('school_classes') . ' c ON c.id = cs.class_id
            INNER JOIN ' . $this->table('school_subjects') . ' s ON s.id = cs.subject_id
            WHERE cs.school_id = ? AND cs.teacher_id = ? ORDER BY c.level_order, c.arm, s.name', [$schoolId, $teacherId]);
    }
}
