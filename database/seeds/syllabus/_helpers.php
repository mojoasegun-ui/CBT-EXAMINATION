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

if (!function_exists('pairq')) {
    /**
     * Recall questions from (left, right) pairs, in both directions. Wrong options are other pairs' answers, so
     * keep every right-hand side distinct and unambiguous.
     *
     * @param list<array{0:string,1:string}> 
     * @return list<array<int, string>>
     */
    function pairq(string $topic, string $difficulty, string $stemLR, ?string $stemRL, array $pairs, string $why = '%1$s: %2$s.'): array
    {
        static $seed = 5000;
        $rows = [];
        $n = count($pairs);
        $pick = static function (array $pairs, int $i, int $side, string $right) use ($n): array {
            $out = [];
            foreach ([1, 5, 11, 17, 23, 29, 31, 37] as $step) {
                $cand = $pairs[($i + $step) % $n][$side];
                if ($cand !== $right && !in_array($cand, $out, true)) {
                    $out[] = $cand;
                }
            }
            return $out;
        };
        foreach ($pairs as $i => [$l, $r]) {
            $rows[] = mc($topic, $difficulty, sprintf($stemLR, $l), $r, $pick($pairs, $i, 1, $r), sprintf($why, $l, $r), $seed++);
            if ($stemRL !== null) {
                $rows[] = mc($topic, $difficulty, sprintf($stemRL, $r), $l, $pick($pairs, $i, 0, $l), sprintf($why, $l, $r), $seed++);
            }
        }
        return $rows;
    }
}
