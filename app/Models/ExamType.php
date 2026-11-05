<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;
use PDO;

final class ExamType extends Model
{
    /** @return list<array<string, mixed>> */
    public function listActive(): array
    {
        $statement = $this->connection()->query('SELECT id, slug, name, description FROM ' . $this->table('exam_types') . ' WHERE is_active = 1 ORDER BY name');
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findActiveBySlug(string $slug): ?array
    {
        $statement = $this->connection()->prepare('SELECT id, slug, name, description FROM ' . $this->table('exam_types') . ' WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
}