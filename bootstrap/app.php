<?php
declare(strict_types=1);

use ExamCore\Core\Application;
use ExamCore\Core\Container;
use ExamCore\Core\Environment;

$root = dirname(__DIR__);
Environment::load($root . '/.env');
$config = [
    'app' => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
    'security' => require $root . '/config/security.php',
    'integration' => require $root . '/config/integration.php',
    'permissions' => require $root . '/config/permissions.php',
    'mail' => require $root . '/config/mail.php',
];
date_default_timezone_set((string) $config['app']['timezone']);

$container = new Container();
return Application::boot($root, $config, $container);