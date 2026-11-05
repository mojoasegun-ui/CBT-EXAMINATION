<?php
declare(strict_types=1);

namespace ExamCore\Core;

final class Request
{
    /** @param array<string, mixed> $query @param array<string, mixed> $form @param array<string, string> $headers */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $form = [],
        private readonly array $headers = [],
        private readonly string $body = '',
        private readonly array $files = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDirectory = str_replace('\\', '/', dirname($scriptName));
        $basePath = basename($scriptDirectory) === 'public' ? dirname($scriptDirectory) : $scriptDirectory;
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/.');
        if ($basePath !== '' && $basePath !== '/' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }
        $path = '/' . trim($path, '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (!is_array($headers)) {
            $headers = [];
        }
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with((string) $key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr((string) $key, 5)))));
                $headers[$name] ??= (string) $value;
            }
        }
        // Under FastCGI / PHP-FPM (most shared hosts, e.g. InfinityFree) these two arrive
        // without the HTTP_ prefix and getallheaders() may not exist.
        foreach (['CONTENT_TYPE' => 'Content-Type', 'CONTENT_LENGTH' => 'Content-Length'] as $key => $name) {
            if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
                $headers[$name] ??= (string) $_SERVER[$key];
            }
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $body = (string) file_get_contents('php://input');
        $form = $_POST;
        if ($form === [] && in_array($method, ['PUT', 'PATCH', 'DELETE'], true)
            && str_contains(strtolower((string) ($headers['Content-Type'] ?? $headers['content-type'] ?? '')), 'application/x-www-form-urlencoded')) {
            parse_str($body, $form);
        }

        return new self(
            $method,
            $path,
            $_GET,
            $form,
            array_change_key_case($headers, CASE_LOWER),
            $body,
            $_FILES,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->form[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function allInput(): array
    {
        return $this->form;
    }

    /** @return array<string, mixed> */
    public function json(): array
    {
        $type = strtolower($this->header('content-type') ?? '');
        $looksJson = $type === '' && preg_match('/^\s*[\[{]/', $this->body) === 1; // header lost by a proxy
        if (!str_contains($type, 'application/json') && !$looksJson) {
            return [];
        }
        $decoded = json_decode($this->body, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** True for API and fetch() calls that expect JSON instead of an HTML page. */
    public function wantsJson(): bool
    {
        if (str_starts_with($this->path, '/api/')) {
            return true;
        }
        $accept = strtolower($this->header('accept') ?? '');
        $contentType = strtolower($this->header('content-type') ?? '');
        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || strtolower($this->header('x-requested-with') ?? '') === 'xmlhttprequest';
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    public function cookie(string $name): ?string
    {
        $value = $_COOKIE[$name] ?? null;
        return is_string($value) ? $value : null;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * A single successfully uploaded file, or null.
     *
     * @return array{name: string, type: string, tmp_name: string, error: int, size: int}|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || !is_string($file['tmp_name'] ?? null) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        return $file;
    }

    public function body(): string
    {
        return $this->body;
    }
}