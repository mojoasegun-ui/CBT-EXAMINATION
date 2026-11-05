<?php
declare(strict_types=1);

return [
    // Master switch. Off by default so local installs never try to send.
    'enabled' => filter_var(env('MAIL_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL),
    // "smtp" (recommended: Brevo, Mailgun, Zoho, your host's SMTP) or "mail" (PHP mail(); many hosts block it).
    'transport' => env('MAIL_TRANSPORT') ?: (env('MAIL_HOST') ? 'smtp' : 'mail'),
    'host' => env('MAIL_HOST') ?: '',
    'port' => (int) (env('MAIL_PORT') ?: 587),
    'username' => env('MAIL_USERNAME') ?: '',
    'password' => env('MAIL_PASSWORD') ?: '',
    // "tls" (STARTTLS, port 587), "ssl" (SMTPS, port 465) or "" for none.
    'encryption' => env('MAIL_ENCRYPTION') !== false ? (string) env('MAIL_ENCRYPTION') : 'tls',
    'timeout' => (int) (env('MAIL_TIMEOUT') ?: 15),
    'from_address' => env('MAIL_FROM_ADDRESS') ?: (env('MAILER_FROM') ?: 'noreply@examcore.local'),
    'from_name' => env('MAIL_FROM_NAME') ?: (env('APP_NAME') ?: 'ExamCore CBT'),
];
