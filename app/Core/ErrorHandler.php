<?php
declare(strict_types=1);

namespace ExamCore\Core;

use ErrorException;
use ExamCore\Core\Exceptions\HttpException;
use Throwable;

final class ErrorHandler
{
    private const TITLES = [
        400 => 'Bad request',
        401 => 'Sign in required',
        403 => 'Access denied',
        404 => 'Page not found',
        405 => 'Not allowed',
        419 => 'Session expired',
        422 => 'Check your details',
        429 => 'Slow down',
        500 => 'Something went wrong',
        503 => 'Back shortly',
    ];

    private ?View $view = null;
    private string $baseUrl = '';

    public function __construct(private readonly Logger $logger, private readonly bool $debug)
    {
    }

    public function register(): void
    {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            // Deprecations must never take the platform down after a PHP upgrade on shared hosting.
            if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
                $this->safeLog('warning', 'PHP deprecation.', ['message' => $message, 'file' => $this->relative($file), 'line' => $line]);
                return true;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler(function (Throwable $exception): void {
            $this->report($exception, null, 'unhandled');
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=UTF-8');
            }
            echo $this->debug
                ? e($exception::class . ': ' . $exception->getMessage())
                : 'An unexpected error occurred.';
        });
    }

    /** Late-bound so error pages can share the application's view layer and base URL. */
    public function useView(View $view, string $baseUrl): void
    {
        $this->view = $view;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function response(Throwable $exception, ?Request $request = null, string $requestId = ''): Response
    {
        $status = $exception instanceof HttpException ? $exception->status() : 500;
        $headers = $exception instanceof HttpException ? $exception->headers() : [];
        $message = $exception instanceof HttpException ? $exception->getMessage() : HttpException::defaultMessage(500);

        if ($status >= 500) {
            $this->report($exception, $request, $requestId);
        } elseif (in_array($status, [419, 429], true)) {
            $this->safeLog('info', 'Request rejected.', ['status' => $status, 'request_id' => $requestId] + $this->requestContext($request));
        }

        if ($request === null || $request->wantsJson()) {
            $payload = ['error' => $message];
            if ($requestId !== '') {
                $payload['request_id'] = $requestId;
            }
            if ($this->debug && $status >= 500) {
                $payload['debug'] = $this->debugDetails($exception);
            }
            return Response::json($payload, $status, $headers);
        }

        return new Response($this->renderPage($status, $message, $requestId, $exception), $status, $headers + ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function report(Throwable $exception, ?Request $request = null, string $requestId = ''): void
    {
        $this->safeLog('error', 'Request failed.', [
            'request_id' => $requestId,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $this->relative($exception->getFile()),
            'line' => $exception->getLine(),
            'trace' => $this->trace($exception),
            'previous' => $exception->getPrevious() === null ? null : [
                'exception' => $exception->getPrevious()::class,
                'message' => $exception->getPrevious()->getMessage(),
                'file' => $this->relative($exception->getPrevious()->getFile()),
                'line' => $exception->getPrevious()->getLine(),
            ],
        ] + $this->requestContext($request));
    }

    private function renderPage(int $status, string $message, string $requestId, Throwable $exception): string
    {
        $data = [
            'status' => $status,
            'title' => self::TITLES[$status] ?? self::TITLES[500],
            'message' => $message,
            'requestId' => $requestId,
            'baseUrl' => $this->baseUrl,
            'debug' => $this->debug && $status >= 500 ? $this->debugDetails($exception) : null,
        ];
        try {
            if ($this->view !== null) {
                return $this->view->render('errors/error', $data);
            }
        } catch (Throwable $renderFailure) {
            $this->safeLog('critical', 'Error page could not be rendered.', ['exception' => $renderFailure::class, 'message' => $renderFailure->getMessage()]);
        }
        return '<!doctype html><meta charset="utf-8"><title>' . e($data['title']) . '</title><h1>' . e($data['title']) . '</h1><p>' . e($message) . '</p>';
    }

    /** @return array{exception: string, message: string, location: string} */
    private function debugDetails(Throwable $exception): array
    {
        return [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'location' => $this->relative($exception->getFile()) . ':' . $exception->getLine(),
        ];
    }

    /** Frames only: arguments are never logged because they can contain passwords or tokens. */
    private function trace(Throwable $exception): array
    {
        $frames = [];
        foreach (array_slice($exception->getTrace(), 0, 15) as $frame) {
            $function = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $location = isset($frame['file']) ? $this->relative((string) $frame['file']) . ':' . ($frame['line'] ?? 0) : '[internal]';
            $frames[] = $location . ' ' . $function;
        }
        return $frames;
    }

    /** @return array<string, string> */
    private function requestContext(?Request $request): array
    {
        if ($request === null) {
            return [];
        }
        return ['method' => $request->method(), 'path' => $request->path()];
    }

    private function relative(string $file): string
    {
        $root = dirname(__DIR__, 2);
        $normalized = str_replace('\\', '/', $file);
        $rootNormalized = str_replace('\\', '/', $root);
        return str_starts_with($normalized, $rootNormalized . '/') ? substr($normalized, strlen($rootNormalized) + 1) : basename($normalized);
    }

    /** @param array<string, mixed> $context */
    private function safeLog(string $level, string $message, array $context): void
    {
        try {
            $this->logger->log($level, $message, $context);
        } catch (Throwable) {
            // Logging must never turn one failure into two.
        }
    }
}
