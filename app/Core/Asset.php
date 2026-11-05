<?php
declare(strict_types=1);

namespace ExamCore\Core;

/**
 * Builds cache-busted asset URLs (?v=<mtime>) so static files can be cached
 * "immutable" for a year and still refresh instantly after a deploy.
 */
final class Asset
{
    private static string $baseUrl = '';
    private static string $publicPath = '';
    /** @var array<string, string> */
    private static array $cache = [];

    public static function configure(string $baseUrl, string $publicPath): void
    {
        self::$baseUrl = rtrim($baseUrl, '/');
        self::$publicPath = rtrim($publicPath, '/\\');
        self::$cache = [];
    }

    public static function url(string $path): string
    {
        $path = ltrim($path, '/');
        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }
        $file = self::$publicPath . '/assets/' . $path;
        $version = is_file($file) ? (string) filemtime($file) : '0';
        $prefix = self::$baseUrl !== '' ? self::$baseUrl : '.';
        return self::$cache[$path] = $prefix . '/assets/' . $path . '?v=' . $version;
    }
}
