<?php
declare(strict_types=1);

return [
    'app_key' => env('APP_KEY') ?: '',
    'session' => [
        'name' => env('SESSION_NAME') ?: 'examcore_session',
        'lifetime' => max(300, (int) (env('SESSION_LIFETIME') ?: 7200)),
        'same_site' => env('SESSION_SAME_SITE') ?: 'Lax',
        'path' => env('SESSION_COOKIE_PATH') ?: '/',
    ],
    'rate_limit' => [
        // Counted per browser session (falls back to IP). First matching bucket wins.
        'buckets' => [
            // A student answering and flagging quickly must never be throttled mid-exam.
            ['name' => 'exam-autosave', 'pattern' => '#^/exam/[^/]+/(answer|flag)$#', 'methods' => ['POST'], 'limit' => 300, 'window' => 60],
            // Creating attempts is the expensive write; keep it tight to stop scripted abuse.
            ['name' => 'attempt-start', 'pattern' => '#^/(start|attempts/[^/]+/retake)$#', 'methods' => ['POST'], 'limit' => 20, 'window' => 60, 'ip_limit' => 400],
            // Password guessing: tight per client, on top of per-account lockout after 5 failures.
            ['name' => 'sign-in', 'pattern' => '#^/(console/login|login|login/two-factor|login/choose|student/login|setup)$#', 'methods' => ['POST'], 'limit' => 10, 'window' => 60, 'ip_limit' => 300],
            ['name' => 'result-check', 'pattern' => '#^/check-result$#', 'methods' => ['POST'], 'limit' => 8, 'window' => 60, 'ip_limit' => 120],
            ['name' => 'practice-start', 'pattern' => '#^/practice/start$#', 'methods' => ['POST'], 'limit' => 30, 'window' => 60, 'ip_limit' => 600],
            ['name' => 'practice-save', 'pattern' => '#^/practice/s/[a-f0-9]+/(sync|submit)$#', 'methods' => ['POST'], 'limit' => 240, 'window' => 60],
            ['name' => 'api', 'pattern' => '#^/api/#', 'limit' => 120, 'window' => 60],
        ],
        'default' => ['limit' => 180, 'window' => 60],
    ],
];
