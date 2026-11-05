<?php
declare(strict_types=1);

use ExamCore\Core\Request;

require dirname(__DIR__) . '/bootstrap/autoload.php';

/** @var \ExamCore\Core\Application $application */
$application = require dirname(__DIR__) . '/bootstrap/app.php';
$application->run(Request::fromGlobals())->send();
$application->terminate();
