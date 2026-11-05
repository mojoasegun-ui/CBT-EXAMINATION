<?php
declare(strict_types=1);

namespace ExamCore\Core\Spreadsheet;

use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Lean .xlsx / .csv reader and .xlsx / .csv writer (no PhpSpreadsheet, which needs GD).
 * The .xlsx reader streams the sheet with XMLReader, so large files use little memory.
 */
final class Spreadsheet
{
    public const MAX_ROWS = 50000;

    /**
     * First worksheet as rows of trimmed strings (empty trailing rows dropped).
     *
     * @return list<list<string>>
     */
    public static function read(string $path, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return match ($extension) {
            'xlsx' => self::readXlsx($path),
            'csv', 'txt' => self::readCsv($path),
            default => throw new RuntimeException('Upload an Excel (.xlsx) or CSV file. Older .xls files: open in Excel and "Save As" .xlsx.'),
        };
    }

    /** @return list<list<string>> */
    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The file could not be opened.');
        }
        $first = (string) fgets($handle);
        rewind($handle);
        $delimiter = ',';
        foreach ([';', "\t", '|'] as $candidate) {
            if (substr_count($first, $candidate) > substr_count($first, $delimiter)) {
                $delimiter = $candidate;
            }
        }
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                throw new RuntimeException('The file has more than ' . number_format(self::MAX_ROWS) . ' rows. Split it into smaller files.');
            }
            $rows[] = array_map(static fn ($cell): string => self::clean((string) $cell), $row);
        }
        fclose($handle);
        if (isset($rows[0][0])) {
            $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]) ?? $rows[0][0]; // UTF-8 BOM from Excel
        }
        return self::trimEmpty($rows);
    }

    /** @return list<list<string>> */
    private static function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('This does not look like a valid Excel file.');
        }
        try {
            $strings = self::sharedStrings((string) $zip->getFromName('xl/sharedStrings.xml'));
            $sheetPath = self::firstSheetPath($zip);
            $xml = $zip->getFromName($sheetPath);
            if ($xml === false) {
                throw new RuntimeException('The workbook has no readable worksheet.');
            }
        } finally {
            $zip->close();
        }

        $reader = new XMLReader();
        if (!$reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('The worksheet could not be read.');
        }
        $rows = [];
        $row = null;
        $column = 0;
        $type = '';
        $value = null;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                switch ($reader->localName) {
                    case 'row':
                        $row = [];
                        if ($reader->isEmptyElement) {
                            $rows[] = [];
                            $row = null;
                        }
                        break;
                    case 'c':
                        $column = self::columnIndex((string) $reader->getAttribute('r'), $row === null ? 0 : count($row));
                        $type = (string) $reader->getAttribute('t');
                        $value = '';
                        if ($reader->isEmptyElement && $row !== null) {
                            $row[$column] = '';
                        }
                        break;
                    case 'v':
                    case 't':
                        $text = $reader->readString();
                        $value = ($value ?? '') . $text;
                        break;
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if ($reader->localName === 'c' && $row !== null) {
                    $row[$column] = self::clean($type === 's' ? ($strings[(int) $value] ?? '') : ($type === 'b' ? ((string) $value === '1' ? 'TRUE' : 'FALSE') : (string) $value));
                } elseif ($reader->localName === 'row' && $row !== null) {
                    if (count($rows) >= self::MAX_ROWS) {
                        throw new RuntimeException('The file has more than ' . number_format(self::MAX_ROWS) . ' rows. Split it into smaller files.');
                    }
                    $filled = [];
                    $max = $row === [] ? -1 : max(array_keys($row));
                    for ($i = 0; $i <= $max; $i++) {
                        $filled[] = $row[$i] ?? '';
                    }
                    $rows[] = $filled;
                    $row = null;
                }
            }
        }
        $reader->close();
        return self::trimEmpty($rows);
    }

    /** @return list<string> */
    private static function sharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }
        $reader = new XMLReader();
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);
        $strings = [];
        $current = null;
        $inPhonetic = false;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                if ($reader->localName === 'si') {
                    $current = '';
                } elseif ($reader->localName === 'rPh') {
                    $inPhonetic = true; // Skip Japanese reading hints.
                } elseif ($reader->localName === 't' && $current !== null && !$inPhonetic) {
                    $current .= $reader->readString();
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                if ($reader->localName === 'rPh') {
                    $inPhonetic = false;
                } elseif ($reader->localName === 'si') {
                    $strings[] = (string) $current;
                    $current = null;
                }
            }
        }
        $reader->close();
        return $strings;
    }

    private static function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = (string) $zip->getFromName('xl/workbook.xml');
        $rels = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');
        if (preg_match('/<sheet\b[^>]*\br:id="([^"]+)"/', $workbook, $sheet) && preg_match('/<Relationship\b[^>]*Id="' . preg_quote($sheet[1], '/') . '"[^>]*Target="([^"]+)"/', $rels, $target)
            || preg_match('/<sheet\b[^>]*\br:id="([^"]+)"/', $workbook, $sheet) && preg_match('/<Relationship\b[^>]*Target="([^"]+)"[^>]*Id="' . preg_quote($sheet[1], '/') . '"/', $rels, $target)) {
            $path = ltrim($target[1], '/');
            return str_starts_with($path, 'xl/') ? $path : 'xl/' . $path;
        }
        return 'xl/worksheets/sheet1.xml';
    }

    private static function columnIndex(string $reference, int $fallback): int
    {
        if (!preg_match('/^([A-Z]+)\d+$/', $reference, $m)) {
            return $fallback;
        }
        $index = 0;
        foreach (str_split($m[1]) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }
        return $index - 1;
    }

    private static function clean(string $value): string
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252'); // CSVs saved by older Excel
        }
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '');
    }

    /** @param list<list<string>> $rows @return list<list<string>> */
    private static function trimEmpty(array $rows): array
    {
        while ($rows !== [] && implode('', end($rows)) === '') {
            array_pop($rows);
        }
        return array_values($rows);
    }

    /** Convert an Excel date serial (e.g. 38353) or common text dates to Y-m-d. */
    public static function toDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value > 1000 && (float) $value < 80000) {
            return gmdate('Y-m-d', (int) round(((float) $value - 25569) * 86400));
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y', 'd/m/y', 'j F Y', 'j M Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }

    // ---- Writing ------------------------------------------------------------------

    /**
     * Build an .xlsx file (bold, frozen header row; sensible column widths).
     *
     * @param list<string> $header
     * @param list<list<string|int|float|null>> $rows
     */
    public static function xlsx(array $header, array $rows, string $sheetName = 'Sheet1'): string
    {
        $escape = static fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $widths = array_map(static fn (string $h): int => max(12, min(48, mb_strlen($h) + 4)), $header);
        foreach (array_slice($rows, 0, 200) as $row) {
            foreach (array_values($row) as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 12, min(48, mb_strlen((string) $cell) + 2));
            }
        }
        $cols = '';
        foreach ($widths as $i => $width) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $width . '" customWidth="1"/>';
        }
        $cell = static function (int $col, int $rowNumber, mixed $value, bool $bold) use ($escape): string {
            $ref = self::columnName($col) . $rowNumber;
            $style = $bold ? ' s="1"' : '';
            if (is_int($value) || is_float($value)) {
                return '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
            }
            return '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">' . $escape((string) $value) . '</t></is></c>';
        };
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>';
        $sheet .= '<row r="1">' . implode('', array_map(static fn ($v, $i) => $cell($i, 1, $v, true), $header, array_keys($header))) . '</row>';
        foreach ($rows as $r => $row) {
            $sheet .= '<row r="' . ($r + 2) . '">' . implode('', array_map(static fn ($v, $i) => $cell($i, $r + 2, $v, false), array_values($row), array_keys(array_values($row)))) . '</row>';
        }
        $sheet .= '</sheetData></worksheet>';

        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $escape(mb_substr($sheetName, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEEF0FF"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        $binary = (string) file_get_contents($file);
        @unlink($file);
        return $binary;
    }

    /** @param list<string> $header @param list<list<string|int|float|null>> $rows */
    public static function csv(array $header, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF"); // BOM so Excel opens UTF-8 names (Adaeze, Ọlá) correctly
        foreach ([$header, ...$rows] as $row) {
            // Neutralise spreadsheet formula injection (=, +, -, @ at the start of a cell).
            fputcsv($handle, array_map(static fn ($v): string => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v, $row), ',', '"', '\\');
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);
        return $csv;
    }

    private static function columnName(int $index): string
    {
        $name = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $name = chr(65 + ($n - 1) % 26) . $name;
        }
        return $name;
    }
}
