<?php
declare(strict_types=1);

namespace ExamCore\Tests\Unit;

use ExamCore\Models\PlatformSettings;
use PHPUnit\Framework\TestCase;

/** What the owner types into the Brand & name page is checked before it is saved and printed on every page. */
final class PlatformSettingsTest extends TestCase
{
    public function testAGoodNameIsAccepted(): void
    {
        [$clean, $errors] = PlatformSettings::validate(['product_name' => '  Aremu   CBT Pro ', 'short_name' => 'Aremu', 'tagline' => 'Assess · Monitor · Excel', 'support_email' => 'Help@Aremu.NG ']);
        self::assertSame([], $errors);
        self::assertSame('Aremu CBT Pro', $clean['product_name']);
        self::assertSame('help@aremu.ng', $clean['support_email']);
    }

    public function testMarkupAndSymbolsAreRefused(): void
    {
        foreach (['<script>alert(1)</script>', 'Name"onload="x', 'A', str_repeat('x', 41), "Line\nbreak<b>", '{{name}}'] as $bad) {
            [, $errors] = PlatformSettings::validate(['product_name' => $bad]);
            self::assertArrayHasKey('product_name', $errors, $bad);
        }
    }

    public function testOptionalFieldsMayBeEmptyButNotUnsafe(): void
    {
        [, $errors] = PlatformSettings::validate(['product_name' => 'Valid Name', 'short_name' => '', 'tagline' => '', 'support_email' => '']);
        self::assertSame([], $errors);
        [, $errors] = PlatformSettings::validate(['product_name' => 'Valid Name', 'tagline' => '<img src=x>', 'support_email' => 'not-an-email', 'short_name' => 'This name is far too long']);
        self::assertSame(['short_name', 'tagline', 'support_email'], array_keys($errors));
    }
}
