<?php
declare(strict_types=1);

namespace ExamCore\Tests\Unit;

use ExamCore\Core\Spreadsheet\Spreadsheet;
use ExamCore\Services\StudentService;
use PHPUnit\Framework\TestCase;

final class SpreadsheetTest extends TestCase
{
    private function temp(string $contents, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ss') . '.' . $extension;
        file_put_contents($path, $contents);
        return $path;
    }

    public function testXlsxRoundTripKeepsUnicodeAndLeadingZeros(): void
    {
        $binary = Spreadsheet::xlsx(['Admission No', 'Name', 'Phone', 'Score'], [
            ['KCL/2026/0001', 'Ọláolúwa Adébáyọ̀', '08031234567', 87.5],
            ['', 'Chukwuemeka Obi', '07065554433', 0],
        ]);
        $path = $this->temp($binary, 'xlsx');
        $rows = Spreadsheet::read($path, 'students.xlsx');
        @unlink($path);

        self::assertSame(['Admission No', 'Name', 'Phone', 'Score'], $rows[0]);
        self::assertSame('Ọláolúwa Adébáyọ̀', $rows[1][1]);
        self::assertSame('08031234567', $rows[1][2], 'Phone numbers keep their leading zero.');
        self::assertSame('87.5', $rows[1][3]);
        self::assertSame('', $rows[2][0]);
        self::assertCount(3, $rows);
    }

    public function testCsvWithBomSemicolonsAndWindowsEncoding(): void
    {
        $path = $this->temp("\xEF\xBB\xBFName;Class\r\nAdaeze Okafor;JSS 1 A\r\n;\r\n", 'csv');
        $rows = Spreadsheet::read($path, 'list.csv');
        @unlink($path);
        self::assertSame([['Name', 'Class'], ['Adaeze Okafor', 'JSS 1 A']], $rows, 'BOM stripped, ; detected, trailing blank rows dropped.');

        $path = $this->temp("Name\n" . "Ren\xE9e\n", 'csv'); // Windows-1252 é
        $rows = Spreadsheet::read($path, 'old.csv');
        @unlink($path);
        self::assertSame('Renée', $rows[1][0]);
    }

    public function testCsvExportNeutralisesFormulaInjection(): void
    {
        $csv = Spreadsheet::csv(['Name'], [['=HYPERLINK("http://evil","x")'], ['+2348031234567'], ['Normal']]);
        self::assertStringContainsString("'=HYPERLINK", $csv);
        self::assertStringContainsString("'+2348031234567", $csv);
        self::assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function testRejectsUnsupportedFiles(): void
    {
        $this->expectExceptionMessage('Upload an Excel (.xlsx) or CSV file');
        Spreadsheet::read(__FILE__, 'old-format.xls');
    }

    public function testDatesFromExcelAndCommonFormats(): void
    {
        self::assertSame('2012-03-14', Spreadsheet::toDate('40982'), 'Excel serial date.');
        self::assertSame('2009-09-14', Spreadsheet::toDate('14/09/2009'), 'Nigerian day/month order.');
        self::assertSame('2012-03-14', Spreadsheet::toDate('2012-03-14'));
        self::assertSame('2012-03-04', Spreadsheet::toDate('4 March 2012'));
        self::assertNull(Spreadsheet::toDate('31/02/2012'), 'Impossible dates are rejected, not rolled over.');
        self::assertNull(Spreadsheet::toDate('next week'));
    }

    public function testNigerianPhoneNormalisation(): void
    {
        self::assertSame('08031234567', StudentService::normalizePhone('0803 123 4567'));
        self::assertSame('08031234567', StudentService::normalizePhone('+234 803 123 4567'));
        self::assertSame('08031234567', StudentService::normalizePhone('8031234567'), 'Excel dropped the leading zero.');
        self::assertSame('09012345678', StudentService::normalizePhone('0901-234-5678'));
        self::assertNull(StudentService::normalizePhone('12345'));
    }
}
