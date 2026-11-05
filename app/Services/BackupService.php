<?php
declare(strict_types=1);

namespace ExamCore\Services;

use ExamCore\Core\Database;
use PDO;
use RuntimeException;

/**
 * Pure-PHP SQL dump of the platform's own (prefixed) tables.
 * No shell access or mysqldump needed, so it works on shared hosting.
 */
final class BackupService
{
    private const BATCH = 250;

    public function __construct(private readonly Database $database, private readonly string $directory, private readonly int $keep = 10)
    {
    }

    /** @return string path of the written backup */
    public function create(): string
    {
        if (!is_dir($this->directory) || !is_writable($this->directory)) {
            throw new RuntimeException('Backup directory is not writable: ' . $this->directory);
        }
        $pdo = $this->database->connection();
        $prefix = $this->database->tablePrefix();
        $gzip = function_exists('gzopen');
        $path = $this->directory . '/backup-' . gmdate('Ymd-His') . '.sql' . ($gzip ? '.gz' : '');
        $handle = $gzip ? gzopen($path, 'wb6') : fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Backup file could not be created.');
        }
        $write = $gzip ? static fn (string $data) => gzwrite($handle, $data) : static fn (string $data) => fwrite($handle, $data);

        try {
            $write("-- ExamCore backup " . gmdate(DATE_ATOM) . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
            $tables = $pdo->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = \'BASE TABLE\' AND TABLE_NAME LIKE ? ORDER BY TABLE_NAME');
            $tables->execute([addcslashes($prefix, '_%\\') . '%']);
            foreach ($tables->fetchAll(PDO::FETCH_COLUMN) as $table) {
                $quoted = '`' . str_replace('`', '``', (string) $table) . '`';
                $create = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch(PDO::FETCH_NUM);
                $write("DROP TABLE IF EXISTS {$quoted};\n" . $create[1] . ";\n\n");

                $offset = 0;
                do {
                    $rows = $pdo->query('SELECT * FROM ' . $quoted . ' LIMIT ' . self::BATCH . ' OFFSET ' . $offset)->fetchAll(PDO::FETCH_ASSOC);
                    if ($rows !== []) {
                        $columns = '(`' . implode('`,`', array_keys($rows[0])) . '`)';
                        $values = array_map(fn (array $row): string => '(' . implode(',', array_map(fn ($v): string => $this->literal($pdo, $v), $row)) . ')', $rows);
                        $write("INSERT INTO {$quoted} {$columns} VALUES\n" . implode(",\n", $values) . ";\n");
                    }
                    $offset += self::BATCH;
                } while (count($rows) === self::BATCH);
                $write("\n");
            }
            $write("SET FOREIGN_KEY_CHECKS = 1;\n");
        } finally {
            $gzip ? gzclose($handle) : fclose($handle);
        }
        $this->prune();
        return $path;
    }

    /** @return list<array{name: string, size: int, time: int}> newest first */
    public function list(): array
    {
        $files = [];
        foreach (glob($this->directory . '/*.sql*') ?: [] as $path) {
            $files[] = ['name' => basename($path), 'size' => (int) filesize($path), 'time' => (int) filemtime($path)];
        }
        usort($files, static fn (array $a, array $b): int => $b['time'] <=> $a['time']);
        return $files;
    }

    /** Absolute path of a backup by exact file name, or null. Never accepts paths. */
    public function path(string $name): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_.-]+\.sql(\.gz)?$/', $name) || str_contains($name, '..')) {
            return null;
        }
        $path = $this->directory . '/' . $name;
        return is_file($path) ? $path : null;
    }

    private function literal(PDO $pdo, mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_int($value), is_float($value) => (string) $value,
            default => $pdo->quote((string) $value),
        };
    }

    private function prune(): void
    {
        $files = glob($this->directory . '/backup-*.sql*') ?: [];
        rsort($files, SORT_STRING); // Names are timestamped, so this is newest first.
        foreach (array_slice($files, $this->keep) as $old) {
            @unlink($old);
        }
    }
}
