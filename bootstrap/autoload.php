<?php
declare(strict_types=1);

$composerAutoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($composerAutoloader)) {
    require_once $composerAutoloader;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'ExamCore\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once dirname(__DIR__) . '/app/Helpers/functions.php';
require_once dirname(__DIR__) . '/app/Helpers/icons.php';