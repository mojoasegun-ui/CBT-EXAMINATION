<?php
declare(strict_types=1);

namespace ExamCore\Core;

use PDO;

abstract class Model
{
    public function __construct(protected readonly Database $database)
    {
    }

    protected function connection(): PDO
    {
        return $this->database->connection();
    }

    protected function table(string $table): string
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException('Invalid table name.');
        }
        return '`' . $this->database->tablePrefix() . $table . '`';
    }

    /** @param list<mixed>|array<string, mixed> $params @return list<array<string, mixed>> */
    protected function rows(string $sql, array $params = []): array
    {
        $statement = $this->connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param list<mixed>|array<string, mixed> $params @return array<string, mixed>|null */
    protected function row(string $sql, array $params = []): ?array
    {
        $statement = $this->connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** @param list<mixed>|array<string, mixed> $params */
    protected function scalar(string $sql, array $params = []): mixed
    {
        $statement = $this->connection()->prepare($sql);
        $statement->execute($params);
        $value = $statement->fetchColumn();
        return $value === false ? null : $value;
    }

    /** @param list<mixed>|array<string, mixed> $params @return int affected rows */
    protected function run(string $sql, array $params = []): int
    {
        $statement = $this->connection()->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    /**
     * Insert one row. Column names are whitelisted by the caller (never user-supplied keys).
     *
     * @param array<string, mixed> $data
     */
    protected function insertRow(string $table, array $data): int
    {
        foreach (array_keys($data) as $column) {
            if (!preg_match('/^[a-z_]+$/', $column)) {
                throw new \InvalidArgumentException('Invalid column name.');
            }
        }
        $columns = array_keys($data);
        $this->run('INSERT INTO ' . $this->table($table) . ' (`' . implode('`, `', $columns) . '`) VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
        return (int) $this->connection()->lastInsertId();
    }

    /**
     * Update columns in a row that belongs to the given school. The school condition is not
     * optional: a forged id from another school simply matches nothing.
     *
     * @param array<string, mixed> $data
     * @param list<string> $allowed
     */
    protected function updateScoped(string $table, int $schoolId, int $id, array $data, array $allowed, string $key = 'id'): int
    {
        $sets = [];
        $values = [];
        foreach ($data as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                throw new \InvalidArgumentException('Column cannot be updated: ' . $column);
            }
            $sets[] = '`' . $column . '` = ?';
            $values[] = $value;
        }
        if ($sets === []) {
            return 0;
        }
        return $this->run('UPDATE ' . $this->table($table) . ' SET ' . implode(', ', $sets) . ' WHERE `' . $key . '` = ? AND school_id = ?', [...$values, $id, $schoolId]);
    }

    protected static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    /** Escape a user search term for LIKE '%term%'. */
    protected static function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }
}
