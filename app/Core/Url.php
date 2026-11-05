<?php
declare(strict_types=1);

namespace ExamCore\Core;

/** Absolute-path URLs under the configured base (handles installs in a subfolder). */
final class Url
{
    private static string $base = '';

    public static function configure(string $baseUrl): void
    {
        self::$base = rtrim($baseUrl, '/');
    }

    public static function to(string $path = '/'): string
    {
        return self::$base . '/' . ltrim($path, '/');
    }

    /**
     * Only allow redirects back into this site ("?next=" must never send users elsewhere).
     */
    public static function safeNext(mixed $next, string $fallback): string
    {
        if (!is_string($next) || !preg_match('#^/[A-Za-z0-9/_\-]*$#', $next) || str_starts_with($next, '//')) {
            return self::to($fallback);
        }
        return self::to($next);
    }
}
