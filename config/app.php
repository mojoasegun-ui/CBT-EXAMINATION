<?php
declare(strict_types=1);

return [
    'name' => env('APP_NAME') ?: 'ExamCore CBT',
    'version' => env('APP_VERSION') ?: '1.0.0',
    'environment' => env('APP_ENV') ?: 'production',
    'debug' => filter_var(env('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
    'base_url' => rtrim((string) (env('APP_BASE_URL') ?: ''), '/'),
    'timezone' => env('APP_TIMEZONE') ?: 'UTC',
    'root' => dirname(__DIR__),
    'pass_mark_percent' => (float) (env('PASS_MARK_PERCENT') ?: 50),
    // Owners must use two-step verification. Defaults to on in production, optional elsewhere.
    'console_require_2fa' => filter_var(env('CONSOLE_REQUIRE_2FA') ?: ((env('APP_ENV') ?: 'production') === 'production' ? 'true' : 'false'), FILTER_VALIDATE_BOOL),
    // Run maintenance (auto-submit, email queue) on web traffic. Keep true on hosts without
    // cron (e.g. InfinityFree); set false once `php cbt schedule:run` runs from cron every minute.
    'scheduler_web_trigger' => filter_var(env('SCHEDULER_WEB_TRIGGER') ?: 'true', FILTER_VALIDATE_BOOL),
];
