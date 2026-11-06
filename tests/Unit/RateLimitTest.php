<?php
declare(strict_types=1);

namespace ExamCore\Tests\Unit;

use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;

/** The rate limiter must not be dodged by inventing a new session cookie on every request. */
final class RateLimitTest extends TestCase
{
    private string $dir;
    private array $server;
    private array $cookies;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/xc-rate-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
        $_SERVER['REMOTE_ADDR'] = '203.0.113.9';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            unlink($f);
        }
        rmdir($this->dir);
        $_SERVER = $this->server;
        $_COOKIE = $this->cookies;
    }

    private function limiter(): RateLimitMiddleware
    {
        return new RateLimitMiddleware($this->dir, [
            ['name' => 'check', 'pattern' => '#^/check-result$#', 'methods' => ['POST'], 'limit' => 3, 'window' => 60, 'ip_limit' => 10],
            ['name' => 'exam', 'pattern' => '#^/exam/#', 'methods' => ['POST'], 'limit' => 3, 'window' => 60],
        ], ['limit' => 1000, 'window' => 60], 'examcore_session');
    }

    private function hit(string $path, string $cookie): int
    {
        $_COOKIE = ['examcore_session' => $cookie];
        try {
            $this->limiter()->process(new Request('POST', $path), static fn (): Response => new Response('ok'));
            return 200;
        } catch (HttpException $e) {
            return $e->status();
        }
    }

    public function testOneSessionIsLimited(): void
    {
        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $codes[] = $this->hit('/check-result', 'abcdefghijklmnopqrstuv1');
        }
        self::assertSame([200, 200, 200, 429, 429], $codes);
    }

    public function testInventingACookiePerRequestStillHitsTheIpCeiling(): void
    {
        $blocked = 0;
        for ($i = 0; $i < 30; $i++) {
            if ($this->hit('/check-result', 'fake' . str_pad((string) $i, 20, 'x')) === 429) {
                $blocked++;
            }
        }
        self::assertGreaterThan(0, $blocked, 'A cookie-rotating script must be stopped by the per-IP ceiling.');
        self::assertSame(10, 30 - $blocked, 'Exactly the ceiling (10) requests get through.');
    }

    public function testExamTrafficHasNoIpCeiling(): void
    {
        // Many different students behind one school router, each within their own allowance, must never be blocked.
        for ($i = 0; $i < 60; $i++) {
            self::assertSame(200, $this->hit('/exam/token/answer', 'student' . str_pad((string) $i, 20, 'y')));
        }
    }
}
