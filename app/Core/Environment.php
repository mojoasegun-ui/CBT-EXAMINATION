<?php
declare(strict_types=1);

namespace ExamCore\Core;

/**
 * Reads .env into request-local memory.
 *
 * Deliberately never calls putenv(): the process environment is shared by every thread of
 * a threaded server (Apache on Windows, some LiteSpeed setups) and is not thread-safe.
 * Under load, one request writing it while another reads it produced an empty DB_DATABASE.
 * Variables set by the host (cPanel, Apache SetEnv, Docker) still win over the .env file.
 */
final class Environment
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name === '' || !preg_match('/^[A-Z][A-Z0-9_]*$/i', $name)) {
                continue;
            }
            if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                $value = substr($value, 1, -1);
            } else {
                $value = preg_replace('/\s+#.*$/', '', $value) ?? $value;
            }
            self::$values[$name] = $value;
            $_ENV[$name] ??= $value;
        }
    }

    /** Same contract as getenv(): the string value, or false when unset. */
    public static function get(string $name): string|false
    {
        // Host-provided variables are only ever read here, never written, so this is safe.
        $host = $_SERVER[$name] ?? null;
        if (is_string($host)) {
            return $host;
        }
        $real = getenv($name);
        if ($real !== false) {
            return $real;
        }
        return self::$values[$name] ?? false;
    }
}
