<?php
declare(strict_types=1);

namespace ExamCore\Middleware;

use ExamCore\Core\Request;
use ExamCore\Core\Response;

interface MiddlewareInterface
{
    /** @param callable(Request): Response $next */
    public function process(Request $request, callable $next): Response;
}