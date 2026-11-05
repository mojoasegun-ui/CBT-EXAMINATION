<?php
declare(strict_types=1);

namespace ExamCore\Core;

final class Response
{
    private const REASONS = [
        200 => 'OK', 201 => 'Created', 204 => 'No Content',
        301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
        400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed',
        409 => 'Conflict', 419 => 'Page Expired', 422 => 'Unprocessable Content', 429 => 'Too Many Requests',
        500 => 'Internal Server Error', 503 => 'Service Unavailable',
    ];

    /** @param array<string, string> $headers */
    public function __construct(
        private readonly string $content = '',
        private readonly int $status = 200,
        private readonly array $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
    ) {
    }

    /** @param array<string, string> $headers */
    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self((string) json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), $status, $headers + ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        if (!in_array($status, [301, 302, 303, 307, 308], true) || preg_match('/[\r\n]/', $location)) {
            throw new \InvalidArgumentException('Invalid redirect response.');
        }
        return new self('', $status, ['Location' => $location]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            // An explicit reason phrase is required: Apache rewrites codes it does not know
            // (such as 419) to "500 Internal Server Error" when PHP sends only the number.
            $protocol = (string) ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1');
            header(sprintf('%s %d %s', preg_match('#^HTTP/[0-9.]+$#', $protocol) ? $protocol : 'HTTP/1.1', $this->status, self::REASONS[$this->status] ?? 'Status'), true, $this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
            // A known length lets the browser finish the page while post-response work runs.
            if (!isset($this->headers['Content-Length'])) {
                header('Content-Length: ' . strlen($this->content));
            }
        }
        echo $this->content;
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }
}