<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;
use PDO;

final class Plan extends Model
{
    /** Feature switches a plan can grant. Labels are shown in the Owner Console. */
    public const FEATURES = [
        'school_question_bank' => 'School question bank',
        'result_pdf' => 'PDF result slips with QR verification',
        'analytics' => 'Analytics and broadsheets',
        'global_question_bank' => 'Curated JAMB/WAEC/NECO question bank',
        'proctoring' => 'Anti-cheating proctoring',
        'face_verification' => 'Face verification at exam start',
        'offline_packs' => 'Offline exam packs (PWA)',
        'white_label' => 'White-label branding',
        'lan_mode' => 'School LAN mode',
        'api_access' => 'API access',
    ];

    /** @return list<array<string, mixed>> */
    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT p.*, (SELECT COUNT(*) FROM ' . $this->table('schools') . ' s WHERE s.plan_id = p.id) AS school_count
            FROM ' . $this->table('plans') . ' p' . ($activeOnly ? ' WHERE p.is_active = 1' : '') . ' ORDER BY p.sort_order, p.id';
        return array_map([$this, 'hydrate'], $this->connection()->query($sql)->fetchAll());
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->connection()->prepare('SELECT p.*, 0 AS school_count FROM ' . $this->table('plans') . ' p WHERE p.id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrate($row);
    }

    /** @return array<string, mixed>|null */
    public function findByCode(string $code): ?array
    {
        $statement = $this->connection()->prepare('SELECT p.*, 0 AS school_count FROM ' . $this->table('plans') . ' p WHERE p.code = ? LIMIT 1');
        $statement->execute([$code]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrate($row);
    }

    /** @param list<string> $features */
    public function update(int $id, string $name, string $description, int $pricePerStudentKobo, ?int $maxStudents, array $features, bool $active): void
    {
        $features = array_values(array_intersect(array_keys(self::FEATURES), $features));
        $this->connection()->prepare('UPDATE ' . $this->table('plans') . '
            SET name = ?, description = ?, price_per_student_kobo = ?, max_students = ?, features = ?, is_active = ?, updated_at = ? WHERE id = ?')
            ->execute([$name, $description, max(0, $pricePerStudentKobo), $maxStudents, json_encode($features), $active ? 1 : 0, gmdate('Y-m-d H:i:s'), $id]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): array
    {
        $features = json_decode((string) ($row['features'] ?? '[]'), true);
        $row['features'] = is_array($features) ? array_values(array_filter($features, 'is_string')) : [];
        return $row;
    }
}
