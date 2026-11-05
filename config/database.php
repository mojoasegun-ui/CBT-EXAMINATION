<?php
declare(strict_types=1);

return [
    'connection' => env('DB_CONNECTION') ?: 'mysql',
    'host' => env('DB_HOST') ?: '127.0.0.1',
    'port' => (int) (env('DB_PORT') ?: 3306),
    'database' => env('DB_DATABASE') ?: '',
    'username' => env('DB_USERNAME') ?: '',
    'password' => env('DB_PASSWORD') ?: '',
    'charset' => env('DB_CHARSET') ?: 'utf8mb4',
    'prefix' => env('DB_PREFIX') ?: 'cbt_',
];