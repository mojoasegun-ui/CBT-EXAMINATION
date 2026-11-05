<?php
declare(strict_types=1);

return [
    'admin' => ['users.manage', 'settings.manage', 'exams.manage'],
    'student' => ['exams.access', 'results.view-own'],
    'invigilator' => ['exams.supervise'],
];