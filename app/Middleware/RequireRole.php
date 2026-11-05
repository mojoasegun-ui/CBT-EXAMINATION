<?php
declare(strict_types=1);

namespace ExamCore\Middleware;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;

/** Narrows an already-authenticated area to certain roles (e.g. school admin-only pages). */
final class RequireRole implements MiddlewareInterface
{
    /** @param list<string> $roles */
    public function __construct(private readonly Guard $guard, private readonly array $roles, private readonly string $message = '')
    {
    }

    public function process(Request $request, callable $next): Response
    {
        if (!in_array($this->guard->role(), $this->roles, true)) {
            throw HttpException::forbidden($this->message !== '' ? $this->message : 'Only your school administrator can open this page.');
        }
        return $next($request);
    }
}
