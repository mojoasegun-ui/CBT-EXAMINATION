<?php
declare(strict_types=1);

namespace ExamCore\Models;

use ExamCore\Core\Model;

/** A validated spreadsheet waiting to be (or being) imported in small chunks. */
final class ImportBatch extends Model
{
    /** @param list<array<string, mixed>> $rows */
    public function create(int $schoolId, int $userId, string $kind, string $fileName, array $rows): int
    {
        $now = self::now();
        return $this->insertRow('import_batches', [
            'school_id' => $schoolId, 'created_by' => $userId, 'kind' => $kind, 'status' => 'ready', 'file_name' => mb_substr($fileName, 0, 190),
            'total_rows' => count($rows), 'payload' => json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    /** @return array<string, mixed>|null */
    public function find(int $schoolId, int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('import_batches') . ' WHERE id = ? AND school_id = ?', [$id, $schoolId]);
    }

    /** Row lock so two browser tabs can never process the same chunk twice. @return array<string, mixed>|null */
    public function lock(int $schoolId, int $id): ?array
    {
        return $this->row('SELECT * FROM ' . $this->table('import_batches') . ' WHERE id = ? AND school_id = ? FOR UPDATE', [$id, $schoolId]);
    }

    /** @param array<string, mixed> $data */
    public function update(int $schoolId, int $id, array $data): void
    {
        $this->updateScoped('import_batches', $schoolId, $id, $data + ['updated_at' => self::now()],
            ['status', 'processed_rows', 'created_rows', 'skipped_rows', 'credentials', 'payload', 'updated_at']);
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $schoolId, int $limit = 5): array
    {
        return $this->rows('SELECT id, kind, status, file_name, total_rows, processed_rows, created_rows, skipped_rows, created_at, credentials IS NOT NULL AS has_credentials
            FROM ' . $this->table('import_batches') . ' WHERE school_id = ? ORDER BY id DESC LIMIT ' . max(1, min(20, $limit)), [$schoolId]);
    }

    /** Scheduler: wipe temporary credentials and payloads after a day. */
    public function purgeOld(int $hours = 24): int
    {
        return $this->run('UPDATE ' . $this->table('import_batches') . ' SET credentials = NULL, payload = \'[]\' WHERE created_at < ? AND (credentials IS NOT NULL OR payload <> \'[]\')',
            [gmdate('Y-m-d H:i:s', time() - $hours * 3600)]);
    }
}
