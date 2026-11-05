<?php
declare(strict_types=1);

namespace ExamCore\Tests\Unit;

use ExamCore\Core\Auth\Totp;
use ExamCore\Core\Container;
use ExamCore\Core\Crypto;
use ExamCore\Core\Url;
use ExamCore\Services\Auth\PasswordPolicy;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SecurityTest extends TestCase
{
    public function testTotpMatchesRfc6238Vectors(): void
    {
        // RFC 6238 appendix B, SHA-1 seed "12345678901234567890" (base32 below), last 6 digits.
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        self::assertSame('287082', Totp::codeAt($secret, intdiv(59, 30)));
        self::assertSame('081804', Totp::codeAt($secret, intdiv(1111111109, 30)));
        self::assertSame('005924', Totp::codeAt($secret, intdiv(1234567890, 30)));
    }

    public function testTotpAcceptsDriftButRejectsReplay(): void
    {
        $secret = Totp::generateSecret();
        $now = 1_800_000_000;
        $previous = Totp::codeAt($secret, intdiv($now, 30) - 1);
        $step = Totp::verify($secret, $previous, null, $now);
        self::assertSame(intdiv($now, 30) - 1, $step, '30 s of clock drift is tolerated.');
        self::assertNull(Totp::verify($secret, $previous, $step, $now), 'The same code cannot be used twice.');
        self::assertNull(Totp::verify($secret, '000000x', null, $now));
        self::assertNull(Totp::verify($secret, Totp::codeAt($secret, intdiv($now, 30) + 3), null, $now), 'Codes far in the future are rejected.');
    }

    public function testCryptoRoundTripsAndDetectsTampering(): void
    {
        $crypto = new Crypto(Crypto::generateKey());
        $sealed = $crypto->encrypt('JBSWY3DPEHPK3PXP');
        self::assertNotSame($sealed, $crypto->encrypt('JBSWY3DPEHPK3PXP'), 'A fresh IV is used every time.');
        self::assertSame('JBSWY3DPEHPK3PXP', $crypto->decrypt($sealed));

        $raw = base64_decode(substr($sealed, 3));
        $raw[strlen($raw) - 1] = chr(ord($raw[strlen($raw) - 1]) ^ 1);
        $this->expectException(RuntimeException::class);
        $crypto->decrypt('v1:' . base64_encode($raw));
    }

    public function testCryptoWithoutKeyFailsOnlyWhenUsed(): void
    {
        $crypto = new Crypto(''); // Constructing must not throw: the app boots before keys exist.
        $this->expectExceptionMessage('php cbt key:generate');
        $crypto->encrypt('x');
    }

    public function testPasswordPolicy(): void
    {
        self::assertNotNull(PasswordPolicy::validate('short1'));
        self::assertNotNull(PasswordPolicy::validate('onlyletters'));
        self::assertNotNull(PasswordPolicy::validate('password123'));
        self::assertNotNull(PasswordPolicy::validate('adaeze2024!', 8, 'Adaeze Okafor', 'adaeze@school.ng'), 'Names are rejected.');
        self::assertNull(PasswordPolicy::validate('Mango-Bus-Lagos-27'));
        for ($i = 0; $i < 25; $i++) {
            self::assertNull(PasswordPolicy::validate(PasswordPolicy::temporary()), 'Generated passwords always satisfy the policy.');
        }
    }

    public function testRedirectTargetsStayOnSite(): void
    {
        Url::configure('/CBT-EXAMINATION');
        self::assertSame('/CBT-EXAMINATION/console/schools', Url::safeNext('/console/schools', '/console'));
        foreach (['//evil.example', 'https://evil.example', '/\\evil.example', 'javascript:alert(1)', '/console?x=1', null, ['/a']] as $bad) {
            self::assertSame('/CBT-EXAMINATION/console', Url::safeNext($bad, '/console'), 'Rejected: ' . json_encode($bad));
        }
    }

    public function testContainerAutowiresAndDetectsCycles(): void
    {
        $container = new Container();
        $built = $container->get(AutowireHost::class);
        self::assertInstanceOf(AutowireDependency::class, $built->dependency);
        self::assertSame($built, $container->get(AutowireHost::class), 'Autowired services are shared.');
        self::assertSame($container, $container->get(Container::class));

        $this->expectExceptionMessage('register a factory');
        $container->get(NeedsScalar::class);
    }
}

final class AutowireDependency
{
}

final class AutowireHost
{
    public function __construct(public readonly AutowireDependency $dependency, public readonly int $retries = 3)
    {
    }
}

final class NeedsScalar
{
    public function __construct(public readonly string $apiKey)
    {
    }
}
