<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

final class GradingScale extends Model
{
    /** WAEC/NECO style default, editable per school. */
    public const DEFAULT = [
        ['A1', 75, 100, 'Excellent', 1], ['B2', 70, 74.99, 'Very good', 1], ['B3', 65, 69.99, 'Good', 1],
        ['C4', 60, 64.99, 'Credit', 1], ['C5', 55, 59.99, 'Credit', 1], ['C6', 50, 54.99, 'Credit', 1],
        ['D7', 45, 49.99, 'Pass', 1], ['E8', 40, 44.99, 'Pass', 1], ['F9', 0, 39.99, 'Fail', 0],
    ];

    /** @return list<array<string, mixed>> highest band first */
    public function all(int $schoolId): array
    {
        return $this->rows('SELECT * FROM ' . $this->table('grading_scales') . ' WHERE school_id = ? ORDER BY min_score DESC', [$schoolId]);
    }

    /** @param list<array{0: string, 1: float|int, 2: float|int, 3: string, 4: int}> $bands */
    public function replace(int $schoolId, array $bands): void
    {
        $this->run('DELETE FROM ' . $this->table('grading_scales') . ' WHERE school_id = ?', [$schoolId]);
        foreach ($bands as [$grade, $min, $max, $remark, $pass]) {
            $this->insertRow('grading_scales', ['school_id' => $schoolId, 'grade' => $grade, 'min_score' => $min, 'max_score' => $max, 'remark' => $remark, 'is_pass' => $pass ? 1 : 0]);
        }
    }

    /** @return array<string, mixed>|null the band containing a percentage */
    public function gradeFor(int $schoolId, float $percent): ?array
    {
        foreach ($this->all($schoolId) as $band) {
            if ($percent >= (float) $band['min_score'] && $percent <= (float) $band['max_score'] + 0.0001) {
                return $band;
            }
        }
        return null;
    }
}
