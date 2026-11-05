<?php
declare(strict_types=1);

namespace ExamCore\Core;

use ExamCore\Core\Exceptions\DatabaseException;
use PDO;
use Throwable;

final class Database
{
    private ?PDO $pdo = null;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function connection(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $driver = (string) ($this->config['connection'] ?? 'mysql');
        $database = (string) ($this->config['database'] ?? '');
        if ($driver !== 'mysql' || $database === '') {
            throw new DatabaseException('Database configuration is incomplete or unsupported.');
        }

        $host = (string) ($this->config['host'] ?? '127.0.0.1');
        $port = (int) ($this->config['port'] ?? 3306);
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $host) || !preg_match('/^[a-zA-Z0-9_]+$/', $database) || $port < 1 || $port > 65535) {
            throw new DatabaseException('Database configuration is invalid.');
        }
        $charset = (string) ($this->config['charset'] ?? 'utf8mb4');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $charset)) {
            throw new DatabaseException('Database configuration is invalid.');
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
        try {
            $this->pdo = new PDO($dsn, (string) ($this->config['username'] ?? ''), (string) ($this->config['password'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable) {
            throw new DatabaseException('Unable to connect to the configured database.');
        }

        return $this->pdo;
    }

    public function tablePrefix(): string
    {
        $prefix = (string) ($this->config['prefix'] ?? 'cbt_');
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $prefix)) {
            throw new DatabaseException('Database table prefix is invalid.');
        }
        return $prefix;
    }

    /** @template T @param callable(PDO): T $operation @return T */
    public function transaction(callable $operation): mixed
    {
        $connection = $this->connection();
        // Re-entrant: a service that needs a transaction can be called from inside another one.
        if ($connection->inTransaction()) {
            return $operation($connection);
        }
        $connection->beginTransaction();
        try {
            $result = $operation($connection);
            $connection->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }
}