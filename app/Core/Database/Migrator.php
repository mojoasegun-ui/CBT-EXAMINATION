<?php
declare(strict_types=1);

namespace ExamCore\Core\Database;

use ExamCore\Core\Database;
use RuntimeException;

final class Migrator
{
    public function __construct(private readonly Database $database, private readonly string $directory)
    {
    }

    /** @return list<array{migration: string, batch: ?int, ran_at: ?string}> */
    public function status(): array
    {
        $ran = $this->ran();
        $rows = [];
        foreach ($this->files() as $name => $file) {
            $rows[] = ['migration' => $name, 'batch' => $ran[$name]['batch'] ?? null, 'ran_at' => $ran[$name]['ran_at'] ?? null];
        }
        return $rows;
    }

    /** @return list<string> */
    public function pending(): array
    {
        $ran = $this->ran();
        return array_values(array_filter(array_keys($this->files()), static fn (string $name): bool => !isset($ran[$name])));
    }

    /**
     * Run every pending migration in filename order as one batch.
     *
     * @param callable(string): void|null $progress
     * @return list<string> Names that ran.
     */
    public function migrate(?callable $progress = null): array
    {
        $pending = $this->pending();
        if ($pending === []) {
            return [];
        }
        $files = $this->files();
        $batch = $this->nextBatch();
        $schema = $this->schema();
        $done = [];
        foreach ($pending as $name) {
            $progress && $progress($name);
            $this->load($files[$name])->up($schema);
            $insert = $this->database->connection()->prepare('INSERT INTO ' . $this->table() . ' (migration, batch, ran_at) VALUES (?, ?, ?)');
            $insert->execute([$name, $batch, gmdate('Y-m-d H:i:s')]);
            $done[] = $name;
        }
        return $done;
    }

    /** @return list<string> Names that were rolled back. */
    public function rollback(?callable $progress = null): array
    {
        $this->ensureTable();
        $pdo = $this->database->connection();
        $batch = (int) $pdo->query('SELECT COALESCE(MAX(batch), 0) FROM ' . $this->table())->fetchColumn();
        if ($batch === 0) {
            return [];
        }
        $statement = $pdo->prepare('SELECT migration FROM ' . $this->table() . ' WHERE batch = ? ORDER BY id DESC');
        $statement->execute([$batch]);
        $files = $this->files();
        $schema = $this->schema();
        $done = [];
        foreach ($statement->fetchAll(\PDO::FETCH_COLUMN) as $name) {
            if (!isset($files[$name])) {
                throw new RuntimeException('Migration file is missing: ' . $name);
            }
            $progress && $progress($name);
            $this->load($files[$name])->down($schema);
            $pdo->prepare('DELETE FROM ' . $this->table() . ' WHERE migration = ?')->execute([$name]);
            $done[] = $name;
        }
        return $done;
    }

    private function schema(): Schema
    {
        return new Schema($this->database->connection(), $this->database->tablePrefix());
    }

    /** @return array<string, string> name => path, sorted. */
    private function files(): array
    {
        $files = [];
        foreach (glob($this->directory . '/*.php') ?: [] as $path) {
            $files[basename($path, '.php')] = $path;
        }
        ksort($files, SORT_STRING);
        return $files;
    }

    private function load(string $path): Migration
    {
        $migration = require $path;
        if (!$migration instanceof Migration) {
            throw new RuntimeException('Migration must return a Migration instance: ' . basename($path));
        }
        return $migration;
    }

    /** @return array<string, array{batch: int, ran_at: string}> */
    private function ran(): array
    {
        $this->ensureTable();
        $rows = [];
        foreach ($this->database->connection()->query('SELECT migration, batch, ran_at FROM ' . $this->table() . ' ORDER BY id')->fetchAll() as $row) {
            $rows[(string) $row['migration']] = ['batch' => (int) $row['batch'], 'ran_at' => (string) $row['ran_at']];
        }
        return $rows;
    }

    private function nextBatch(): int
    {
        return (int) $this->database->connection()->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM ' . $this->table())->fetchColumn();
    }

    private function ensureTable(): void
    {
        $this->database->connection()->exec('CREATE TABLE IF NOT EXISTS ' . $this->table() . ' (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            migration VARCHAR(190) NOT NULL,
            batch INT UNSIGNED NOT NULL,
            ran_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private function table(): string
    {
        return '`' . $this->database->tablePrefix() . 'migrations`';
    }
}
