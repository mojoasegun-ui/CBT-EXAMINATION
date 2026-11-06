<?php
declare(strict_types=1);

namespace ExamCore\Tests\Unit;

use ExamCore\Models\PasswordReset;
use ExamCore\Services\PasswordRecoveryService;
use PHPUnit\Framework\TestCase;

final class PasswordResetTest extends TestCase
{
    public function testOnlyWellFormedTokensAreLookedUp(): void
    {
        self::assertTrue(PasswordReset::wellFormed(str_repeat('a1', 32)));
        foreach (['', 'short', str_repeat('g', 64), str_repeat('A', 64), str_repeat('a', 63), str_repeat('a', 65), "x' OR '1'='1"] as $bad) {
            self::assertFalse(PasswordReset::wellFormed($bad), $bad);
        }
    }

    public function testTokensAreStoredOnlyAsHashes(): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = PasswordReset::hashToken($token);
        self::assertSame(64, strlen($hash));
        self::assertNotSame($token, $hash);
        self::assertSame($hash, PasswordReset::hashToken($token));
    }

    public function testAFullBaseUrlIsNotPrefixedTwice(): void
    {
        \ExamCore\Core\Url::configure('https://school.example/app');
        try {
            self::assertSame('https://school.example/app/reset-password/x', PasswordRecoveryService::absolute('/reset-password/x'));
        } finally {
            \ExamCore\Core\Url::configure('');
        }
    }

    public function testEmailLinksNeverTrustTheHostHeader(): void
    {
        $saved = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'evil.example';
        putenv('APP_URL=https://school.example');
        $_ENV['APP_URL'] = 'https://school.example';
        try {
            self::assertStringStartsWith('https://school.example', PasswordRecoveryService::absolute('/reset-password/x'));
        } finally {
            putenv('APP_URL');
            unset($_ENV['APP_URL']);
            $_SERVER = $saved;
        }
    }
}
