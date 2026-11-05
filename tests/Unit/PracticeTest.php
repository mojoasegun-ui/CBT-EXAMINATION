<?php
declare(strict_types=1);

namespace ExamCore\Tests\Unit;

use ExamCore\Services\PracticeService;
use PHPUnit\Framework\TestCase;

final class PracticeTest extends TestCase
{
    /** @param list<string> $days */
    private static function days(array $days): array
    {
        return array_map(static fn (string $d): array => ['day' => $d, 'answered' => 5, 'correct' => 3], $days);
    }

    public function testStreakCountsConsecutiveDaysEndingToday(): void
    {
        $s = PracticeService::streak(self::days(['2026-11-05', '2026-11-04', '2026-11-03', '2026-11-01']), '2026-11-05');
        self::assertSame(3, $s['current']);
        self::assertSame(3, $s['best']);
    }

    public function testStreakSurvivesUntilTheEndOfTomorrowIfYouPractisedYesterday(): void
    {
        $s = PracticeService::streak(self::days(['2026-11-04', '2026-11-03']), '2026-11-05');
        self::assertSame(2, $s['current']);
    }

    public function testStreakBreaksAfterAMissedDay(): void
    {
        $s = PracticeService::streak(self::days(['2026-11-03', '2026-11-02', '2026-11-01', '2026-10-31']), '2026-11-05');
        self::assertSame(0, $s['current']);
        self::assertSame(4, $s['best']);
    }

    public function testStreakWithNoActivity(): void
    {
        self::assertSame(['current' => 0, 'best' => 0], PracticeService::streak([], '2026-11-05'));
    }

    public function testStreakAcrossMonthEnd(): void
    {
        $s = PracticeService::streak(self::days(['2026-11-01', '2026-10-31', '2026-10-30']), '2026-11-01');
        self::assertSame(3, $s['current']);
    }

    public function testSubjectSlugsRoundTrip(): void
    {
        self::assertSame('english-language', PracticeService::slug('English Language'));
        self::assertSame('christian-religious-studies', PracticeService::slug('Christian Religious Studies'));
        $catalog = ['English Language' => [], 'Mathematics' => []];
        self::assertSame('English Language', PracticeService::subjectBySlug($catalog, 'english-language'));
        self::assertNull(PracticeService::subjectBySlug($catalog, 'nonsense'));
    }
}
