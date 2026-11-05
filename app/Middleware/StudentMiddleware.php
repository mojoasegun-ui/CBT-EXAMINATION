<?php
declare(strict_types=1);

namespace ExamCore\Middleware;

use ExamCore\Core\Request;
use ExamCore\Core\Response;

final class StudentMiddleware extends AuthMiddleware
{
    public function process(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            return Response::json(['error' => 'Authentication required'], 401);
        }
        return $this->auth->hasRole('student') ? $next($request) : Response::json(['error' => 'Forbidden'], 403);
    }
}