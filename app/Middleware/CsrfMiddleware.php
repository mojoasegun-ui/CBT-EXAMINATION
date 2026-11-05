<?php
declare(strict_types=1);

namespace ExamCore\Middleware;

use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;

/**
 * Synchroniser-token CSRF protection.
 *
 * The token is stable for the lifetime of the session (it is rotated on
 * login/logout via Session::regenerate). Rotating on every request broke
 * students who had the exam open in more than one tab or pressed Back.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Csrf $csrf)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }
        $json = $request->json();
        $token = $request->header('x-csrf-token') ?? ($request->input('_csrf_token') ?? ($json['_csrf_token'] ?? null));
        if (!$this->csrf->verify(is_string($token) ? $token : null)) {
            throw new HttpException(419, 'Your session expired. Refresh the page and try again.', ['X-CSRF-Token' => $this->csrf->token()]);
        }
        $response = $next($request);
        $headers = $response->headers();
        $headers['X-CSRF-Token'] = $this->csrf->token();
        return new Response($response->content(), $response->status(), $headers);
    }
}
