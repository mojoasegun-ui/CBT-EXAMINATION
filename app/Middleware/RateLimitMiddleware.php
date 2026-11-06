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
 *
 * The cookie value is not proof of a real session, so a script could invent a
 * new one per request and get a fresh allowance every time. Sensitive buckets
 * (sign-in, result check, attempt creation) therefore also carry an "ip_limit":
 * a much larger per-IP ceiling that a whole school can stay under but a
 * cookie-rotating script cannot. Exam traffic never has an ip_limit.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * @param list<array{name: string, pattern: string, methods?: list<string>, limit: int, window: int, ip_limit?: int}> $buckets First match wins.
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
        [$name, $limit, $window, $ipLimit] = $this->bucketFor($request);
        if ($limit < 1 || $window < 1) {
            return $next($request);
        }
        if (!is_dir($this->storagePath) || !is_writable($this->storagePath)) {
            throw new RuntimeException('Rate limiter storage is unavailable.');
        }
        $key = $this->clientKey($request);
        $this->consume($name . '|' . $key, $limit, $window);
        // A made-up cookie per request would dodge the counter above; the per-IP ceiling cannot be dodged that way.
        if ($ipLimit > 0 && !str_starts_with($key, 'ip:')) {
            $this->consume($name . '|ceiling|' . $request->ip(), $ipLimit, $window);
        }
        return $next($request);
    }

    /** Count one hit in a fixed window; throws 429 when the allowance is used up. */
    private function consume(string $identity, int $limit, int $window): void
    {
        $file = $this->storagePath . '/rate-' . hash('sha256', $identity) . '.json';
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
    }

    /** @return array{0: string, 1: int, 2: int, 3: int} name, limit, window, per-IP ceiling (0 = none) */
    private function bucketFor(Request $request): array
    {
        foreach ($this->buckets as $bucket) {
            $methods = $bucket['methods'] ?? [];
            if ($methods !== [] && !in_array($request->method(), $methods, true)) {
                continue;
            }
            if (preg_match($bucket['pattern'], $request->path()) === 1) {
                return [$bucket['name'], (int) $bucket['limit'], (int) $bucket['window'], (int) ($bucket['ip_limit'] ?? 0)];
            }
        }
        return ['default', (int) $this->default['limit'], (int) $this->default['window'], 0];
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
