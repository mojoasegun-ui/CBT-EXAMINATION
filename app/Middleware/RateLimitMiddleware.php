<?php
declare(strict_types=1);

namespace ExamCore\Middleware;

use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use RuntimeException;

/**
 * Fixed-window rate limiter with per-route buckets, backed by small lock files
 * so it works on shared hosting without Redis.
 *
 * Clients are identified by their session cookie when present and by IP
 * otherwise. Keying on IP alone would throttle a whole school, because every
 * student behind the school's router shares one public address.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @param list<array{name: string, pattern: string, methods?: list<string>, limit: int, window: int}> $buckets First match wins.
     * @param array{limit: int, window: int} $default
     */
    public function __construct(
        private readonly string $storagePath,
        private readonly array $buckets,
        private readonly array $default,
        private readonly string $sessionCookie = 'examcore_session',
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        [$name, $limit, $window] = $this->bucketFor($request);
        if ($limit < 1 || $window < 1) {
            return $next($request);
        }
        if (!is_dir($this->storagePath) || !is_writable($this->storagePath)) {
            throw new RuntimeException('Rate limit storage is unavailable.');
        }

        $file = $this->storagePath . '/rate-' . hash('sha256', $name . '|' . $this->clientKey($request)) . '.json';
        $handle = fopen($file, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Rate limiter could not initialize.');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Rate limiter could not lock its state.');
            }
            $stored = json_decode((string) stream_get_contents($handle), true);
            $now = time();
            $start = is_array($stored) ? (int) ($stored['start'] ?? 0) : 0;
            $count = is_array($stored) ? (int) ($stored['count'] ?? 0) : 0;
            if ($now - $start >= $window) {
                $start = $now;
                $count = 0;
            }
            if ($count >= $limit) {
                throw new HttpException(429, '', ['Retry-After' => (string) max(1, $window - ($now - $start))]);
            }
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode(['start' => $start, 'count' => $count + 1], JSON_THROW_ON_ERROR));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
        return $next($request);
    }

    /** @return array{0: string, 1: int, 2: int} */
    private function bucketFor(Request $request): array
    {
        foreach ($this->buckets as $bucket) {
            $methods = $bucket['methods'] ?? [];
            if ($methods !== [] && !in_array($request->method(), $methods, true)) {
                continue;
            }
            if (preg_match($bucket['pattern'], $request->path()) === 1) {
                return [$bucket['name'], (int) $bucket['limit'], (int) $bucket['window']];
            }
        }
        return ['default', (int) $this->default['limit'], (int) $this->default['window']];
    }

    private function clientKey(Request $request): string
    {
        $session = $request->cookie($this->sessionCookie);
        if (is_string($session) && preg_match('/^[A-Za-z0-9,-]{22,256}$/', $session)) {
            return 's:' . $session;
        }
        return 'ip:' . $request->ip();
    }
}
