<?php
declare(strict_types=1);

namespace ExamCore\Core\Queue;

use ExamCore\Core\Database;
use PDO;

/**
 * Minimal database queue.
 *
 * Reservation uses a single conditional UPDATE ... LIMIT 1 followed by a lookup
 * on a random token, which is atomic on MySQL and MariaDB alike and needs no
 * SKIP LOCKED support (unavailable on MariaDB < 10.6, the XAMPP default).
 */
final class Queue
{
    public function __construct(private readonly Database $database)
    {
    }

    /** @param array<string, mixed> $payload */
    public function push(string $type, array $payload, int $delaySeconds = 0, int $maxAttempts = 3): int
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table() . ' (type, payload, max_attempts, available_at, created_at) VALUES (?, ?, ?, ?, ?)');
        $now = time();
        $statement->execute([
            $type,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            max(1, $maxAttempts),
            gmdate('Y-m-d H:i:s', $now + max(0, $delaySeconds)),
            gmdate('Y-m-d H:i:s', $now),
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array{id: int, type: string, payload: array<string, mixed>, attempts: int, max_attempts: int}|null */
    public function reserve(): ?array
    {
        $reservation = bin2hex(random_bytes(16));
        $now = gmdate('Y-m-d H:i:s');
        $claim = $this->pdo()->prepare('UPDATE ' . $this->table() . '
            SET reserved_at = ?, reservation = ?, attempts = attempts + 1
            WHERE failed_at IS NULL AND reserved_at IS NULL AND available_at <= ?
            ORDER BY id LIMIT 1');
        $claim->execute([$now, $reservation, $now]);
        if ($claim->rowCount() === 0) {
            return null;
        }
        $find = $this->pdo()->prepare('SELECT id, type, payload, attempts, max_attempts FROM ' . $this->table() . ' WHERE reservation = ? LIMIT 1');
        $find->execute([$reservation]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        $payload = json_decode((string) $row['payload'], true);
        return [
            'id' => (int) $row['id'],
            'type' => (string) $row['type'],
            'payload' => is_array($payload) ? $payload : [],
            'attempts' => (int) $row['attempts'],
            'max_attempts' => (int) $row['max_attempts'],
        ];
    }

    public function complete(int $id): void
    {
        $this->pdo()->prepare('DELETE FROM ' . $this->table() . ' WHERE id = ?')->execute([$id]);
    }

    public function retry(int $id, int $delaySeconds, string $error): void
    {
        $this->pdo()->prepare('UPDATE ' . $this->table() . ' SET reserved_at = NULL, reservation = NULL, available_at = ?, last_error = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d H:i:s', time() + $delaySeconds), mb_substr($error, 0, 1000), $id]);
    }

    public function fail(int $id, string $error): void
    {
        $this->pdo()->prepare('UPDATE ' . $this->table() . ' SET failed_at = ?, reservation = NULL, last_error = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d H:i:s'), mb_substr($error, 0, 1000), $id]);
    }

    /** Put back jobs whose worker died mid-run (e.g. the PHP request was killed). */
    public function releaseStale(int $olderThanSeconds = 600): int
    {
        $statement = $this->pdo()->prepare('UPDATE ' . $this->table() . ' SET reserved_at = NULL, reservation = NULL WHERE failed_at IS NULL AND reserved_at IS NOT NULL AND reserved_at < ?');
        $statement->execute([gmdate('Y-m-d H:i:s', time() - $olderThanSeconds)]);
        return $statement->rowCount();
    }

    /** @return list<array<string, mixed>> most recent failures first */
    public function failed(int $limit = 20): array
    {
        return $this->pdo()->query('SELECT id, type, attempts, last_error, failed_at, created_at FROM ' . $this->table()
            . ' WHERE failed_at IS NOT NULL ORDER BY failed_at DESC LIMIT ' . max(1, min(100, $limit)))->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Give a failed job a fresh set of attempts. */
    public function retryFailed(int $id): bool
    {
        $statement = $this->pdo()->prepare('UPDATE ' . $this->table() . ' SET failed_at = NULL, reserved_at = NULL, reservation = NULL, attempts = 0, available_at = ? WHERE id = ? AND failed_at IS NOT NULL');
        $statement->execute([gmdate('Y-m-d H:i:s'), $id]);
        return $statement->rowCount() > 0;
    }

    public function deleteFailed(int $id): bool
    {
        $statement = $this->pdo()->prepare('DELETE FROM ' . $this->table() . ' WHERE id = ? AND failed_at IS NOT NULL');
        $statement->execute([$id]);
        return $statement->rowCount() > 0;
    }

    /** @return array{pending: int, reserved: int, failed: int} */
    public function stats(): array
    {
        $row = $this->pdo()->query('SELECT
                COALESCE(SUM(failed_at IS NULL AND reserved_at IS NULL), 0) AS pending,
                COALESCE(SUM(failed_at IS NULL AND reserved_at IS NOT NULL), 0) AS reserved,
                COALESCE(SUM(failed_at IS NOT NULL), 0) AS failed
            FROM ' . $this->table())->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['pending' => (int) ($row['pending'] ?? 0), 'reserved' => (int) ($row['reserved'] ?? 0), 'failed' => (int) ($row['failed'] ?? 0)];
    }

    private function pdo(): PDO
    {
        return $this->database->connection();
    }

    private function table(): string
    {
        return '`' . $this->database->tablePrefix() . 'jobs`';
    }
}
