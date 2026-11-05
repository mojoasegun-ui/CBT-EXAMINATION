<?php
declare(strict_types=1);

/**
 * Helpers for the syllabus question banks. Calculation questions are produced by code that computes the
 * correct answer itself, so the answer key can never contain an arithmetic slip.
 */
if (!function_exists('qfmt')) {
    /** Number -> string without float noise: 12, 0.5, 3.14, 1200. */
    function qfmt(int|float|string $v): string
    {
        if (is_string($v)) {
            return $v;
        }
        if (is_int($v) || abs($v - round($v)) < 1e-9) {
            return number_format((int) round($v), 0, '.', '');
        }
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    function qgcd(int $a, int $b): int
    {
        return $b === 0 ? abs($a) : qgcd($b, $a % $b);
    }

    /** Simplified fraction as "a/b" (or "a" when whole). */
    function qfrac(int $n, int $d): string
    {
        $g = qgcd($n, $d) ?: 1;
        $n = intdiv($n, $g);
        $d = intdiv($d, $g);
        if ($d < 0) {
            [$n, $d] = [-$n, -$d];
        }
        return $d === 1 ? (string) $n : $n . '/' . $d;
    }

    /**
     * One multiple-choice row: [topic, difficulty, body, A, B, C, D, answer letter, explanation].
     * The correct option goes to a position that varies with $seed; distractors are de-duplicated.
     *
     * @param list<int|float|string> $wrong candidate wrong answers, best first
     */
    function mc(string $topic, string $difficulty, string $body, int|float|string $correct, array $wrong, string $explanation, int $seed): array
    {
        $right = qfmt($correct);
        $options = [];
        foreach ($wrong as $w) {
            $s = qfmt($w);
            if ($s !== $right && !in_array($s, $options, true) && $s !== '' && !(is_numeric($s) && (float) $s < 0 && is_numeric($right) && (float) $right >= 0 && false)) {
                $options[] = $s;
            }
            if (count($options) === 3) {
                break;
            }
        }
        $pad = 1;
        while (count($options) < 3) { // never happens with sensible inputs; keeps the row valid if it does
            $candidate = is_numeric($right) ? qfmt((float) $right + $pad * 2) : $right . ' (' . $pad . ')';
            if ($candidate !== $right && !in_array($candidate, $options, true)) {
                $options[] = $candidate;
            }
            $pad++;
        }
        $pos = $seed % 4;
        array_splice($options, $pos, 0, [$right]);
        return [$topic, $difficulty, $body, $options[0], $options[1], $options[2], $options[3], chr(65 + $pos), $explanation];
    }
}
