<?php
declare(strict_types=1);

namespace ExamCore\Core\Database;

use PDO;

/**
 * Thin, portable DDL helper handed to migrations.
 *
 * Existence checks go through information_schema rather than
 * "ADD COLUMN IF NOT EXISTS", which MariaDB supports but MySQL 8 does not.
 */
final class Schema
{
    public function __construct(private readonly PDO $pdo, private readonly string $prefix)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** The table prefix (e.g. "cbt_"), for naming keys and constraints. */
    public function prefix(): string
    {
        return $this->prefix;
    }

    /** Prefixed, quoted table name, e.g. `cbt_attempts`. */
    public function table(string $name): string
    {
        return '`' . $this->name($name) . '`';
    }

    /** Prefixed, unquoted table name for identifiers such as foreign keys. */
    public function name(string $name): string
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException('Invalid table name.');
        }
        return $this->prefix . $name;
    }

    public function exec(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    public function tableExists(string $table): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $statement->execute([$this->name($table)]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function columnExists(string $table, string $column): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $statement->execute([$this->name($table), $column]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function indexExists(string $table, string $index): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $statement->execute([$this->name($table), $index]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (!$this->columnExists($table, $column)) {
            $this->exec('ALTER TABLE ' . $this->table($table) . ' ADD COLUMN `' . $column . '` ' . $definition);
        }
    }

    public function addIndexIfMissing(string $table, string $index, string $columns): void
    {
        if (!$this->indexExists($table, $index)) {
            $this->exec('ALTER TABLE ' . $this->table($table) . ' ADD INDEX `' . $index . '` (' . $columns . ')');
        }
    }
}
