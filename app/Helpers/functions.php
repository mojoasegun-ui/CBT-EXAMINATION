<?php
declare(strict_types=1);

if (!function_exists('e')) {
    /** Escape for HTML text and attribute context. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /** Configuration value from the host environment or .env (thread-safe; never uses putenv). */
    function env(string $name): string|false
    {
        return \ExamCore\Core\Environment::get($name);
    }
}

if (!function_exists('asset')) {
    /** Cache-busted URL for a file under public/assets, e.g. asset('css/app.css'). */
    function asset(string $path): string
    {
        return \ExamCore\Core\Asset::url($path);
    }
}

if (!function_exists('url')) {
    /** Site URL for a path, e.g. url('/console/schools'). */
    function url(string $path = '/'): string
    {
        return \ExamCore\Core\Url::to($path);
    }
}

if (!function_exists('naira')) {
    /** Format kobo as Naira, e.g. 30000 → "₦300". */
    function naira(int $kobo): string
    {
        $amount = $kobo / 100;
        return '₦' . number_format($amount, fmod($amount, 1.0) === 0.0 ? 0 : 2);
    }
}

if (!function_exists('time_ago')) {
    /** Human-friendly relative time for a UTC datetime string. */
    function time_ago(?string $utc): string
    {
        if ($utc === null || $utc === '') {
            return 'never';
        }
        $seconds = time() - (int) strtotime($utc . ' UTC');
        return match (true) {
            $seconds < 60 => 'just now',
            $seconds < 3600 => intdiv($seconds, 60) . ' min ago',
            $seconds < 86400 => intdiv($seconds, 3600) . ' h ago',
            $seconds < 604800 => intdiv($seconds, 86400) . ' d ago',
            default => local_date($utc, 'j M Y'),
        };
    }
}

if (!function_exists('local_date')) {
    /** Format a UTC datetime in the app timezone (Africa/Lagos by default). */
    function local_date(?string $utc, string $format = 'j M Y, g:i a'): string
    {
        if ($utc === null || $utc === '') {
            return '—';
        }
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format($format);
    }
}

if (!function_exists('time_until')) {
    /** "in 3 days" style label for a future UTC timestamp. */
    function time_until(int $timestamp): string
    {
        $s = $timestamp - time();
        return match (true) {
            $s <= 0 => 'now',
            $s < 3600 => 'in ' . max(1, intdiv($s, 60)) . ' min',
            $s < 86400 => 'in ' . intdiv($s, 3600) . ' h ' . intdiv($s % 3600, 60) . ' min',
            default => 'in ' . intdiv($s, 86400) . ' day' . (intdiv($s, 86400) === 1 ? '' : 's'),
        };
    }
}
