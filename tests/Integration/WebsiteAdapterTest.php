<?php
declare(strict_types=1);

namespace ExamCore\Tests\Integration;

use ExamCore\Integrations\StudentIdentity;
use ExamCore\Integrations\WebsiteAdapter;
use PHPUnit\Framework\TestCase;

final class WebsiteAdapterTest extends TestCase
{
    public function testWebsiteAdapterAcceptsOnlyTrustedIdentityObjectsFromProvider(): void
    {
        $identity = new StudentIdentity('school-42', 'cbt-17', 'Test Student');
        $adapter = new WebsiteAdapter(static fn (): StudentIdentity => $identity);

        self::assertSame('website', $adapter->mode());
        self::assertSame($identity, $adapter->identity());
        self::assertNull((new WebsiteAdapter(static fn (): string => 'school-42'))->identity());
    }
}