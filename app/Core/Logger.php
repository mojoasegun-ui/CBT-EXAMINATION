<?php
declare(strict_types=1);

namespace ExamCore\Core;

use RuntimeException;

final class Logger
{
    private const SENSITIVE_KEYS = ['password', 'token', 'secret', 'authorization', 'cookie', 'credential', 'app_key'];

    public function __construct(private readonly string $file, private readonly string $minimumLevel = 'info')
    {
    }

    /** @param array<string, mixed> $context */
    public function log(string $level, string $message, array $context = []): void
    {
        $levels = ['debug' => 100, 'info' => 200, 'warning' => 300, 'error' => 400, 'critical' => 500];
        if (!isset($levels[$level], $levels[$this->minimumLevel]) || $levels[$level] < $levels[$this->minimumLevel]) {
            return;
        }
        if (!is_dir(dirname($this->file)) || !is_writable(dirname($this->file))) {
            throw new RuntimeException('Log storage is unavailable.');
        }
        $entry = [
            'time' => gmdate(DATE_ATOM),
            'level' => $level,
            'message' => $this->clean($message),
            'context' => $this->sanitize($context),
        ];
        file_put_contents($this->file, json_encode($entry, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** @param array<string, mixed> $context */
    private function sanitize(array $context): array
    {
        $safe = [];
        foreach ($context as $key => $value) {
            $safe[$key] = $this->isSensitive((string) $key) ? '[REDACTED]' : $this->sanitizeValue($value);
        }
        return $safe;
    }

    private function sanitizeValue(mixed $value, int $depth = 0): mixed
    {
        if (is_string($value)) {
            return $this->clean($value);
        }
        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }
        if (!is_array($value) || $depth >= 4) {
            return get_debug_type($value);
        }

        $safe = [];
        foreach (array_slice($value, 0, 100, true) as $key => $item) {
            $safe[$key] = $this->isSensitive((string) $key) ? '[REDACTED]' : $this->sanitizeValue($item, $depth + 1);
        }
        return $safe;
    }

    private function isSensitive(string $key): bool
    {
        $normalized = strtolower($key);
        foreach (self::SENSITIVE_KEYS as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }
        return false;
    }

    private function clean(string $value): string
    {
        return substr(preg_replace('/[\r\n\x00-\x1F]+/', ' ', $value) ?? '', 0, 1000);
    }
}