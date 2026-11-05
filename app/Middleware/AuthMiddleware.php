<?php
declare(strict_types=1);

namespace ExamCore\Middleware;

use ExamCore\Core\Auth;
use ExamCore\Core\Request;
use ExamCore\Core\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(protected readonly Auth $auth)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        return $this->auth->check() ? $next($request) : Response::json(['error' => 'Authentication required'], 401);
    }
}