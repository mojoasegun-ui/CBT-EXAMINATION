<?php
declare(strict_types=1);

/**
 * Mathematics practice questions following the JAMB UTME Mathematics syllabus areas
 * (number and numeration, algebra, geometry/mensuration, trigonometry, statistics/probability, calculus).
 * Original questions; every correct answer is computed by this file, not typed by hand.
 */
require_once __DIR__ . '/_helpers.php';

$rows = [];
$n = 0;
$add = static function (array $row) use (&$rows): void { $rows[] = $row; };

// ---- Number and numeration -------------------------------------------------------------------
foreach ([[12, 18], [24, 36], [15, 20], [18, 27], [16, 24], [14, 21], [20, 30], [28, 42], [30, 45], [36, 48]] as [$a, $b]) {
    $g = qgcd($a, $b); $l = intdiv($a * $b, $g);
    $add(mc('LCM and HCF', 'easy', "Find the least common multiple (LCM) of {$a} and {$b}.", $l, [$g, $a * $b, $a + $b, $l * 2], "LCM = ({$a} × {$b}) ÷ HCF = " . ($a * $b) . " ÷ {$g} = {$l}.", $n++));
    $add(mc('LCM and HCF', 'easy', "Find the highest common factor (HCF) of {$a} and {$b}.", $g, [$l, $a - $b, 1, $g * 2], "The largest number dividing both {$a} and {$b} is {$g}.", $n++));
}
foreach ([[1, 2, 1, 3], [2, 3, 1, 4], [3, 4, 1, 6], [1, 2, 1, 4], [2, 5, 1, 2], [3, 5, 1, 10], [1, 6, 1, 3], [5, 6, 1, 3]] as [$p, $q, $r, $s]) {
    $num = $p * $s + $r * $q; $den = $q * $s;
    $add(mc('Fractions', 'medium', "Evaluate {$p}/{$q} + {$r}/{$s}.", qfrac($num, $den), [qfrac($p + $r, $q + $s), qfrac($p + $r, $q * $s), qfrac($num + 1, $den), qfrac($num, $den + $q)], "Common denominator {$den}: ({$p}×{$s} + {$r}×{$q})/{$den} = {$num}/{$den} = " . qfrac($num, $den) . '.', $n++));
}
foreach ([[20, 150], [15, 240], [35, 200], [12.5, 80], [8, 350], [45, 120], [60, 75], [25, 360]] as [$pc, $of]) {
    $v = $pc * $of / 100;
    $add(mc('Percentages', 'easy', "What is {$pc}% of {$of}?", $v, [$pc * $of / 10, $of - $v, $v * 2, $pc + $of], "{$pc}/100 × {$of} = " . qfmt($v) . '.', $n++));
}
foreach ([[400, 500], [250, 300], [800, 1000], [120, 150], [600, 750], [900, 1080], [50, 65], [200, 250]] as [$old, $new]) {
    $pc = ($new - $old) / $old * 100;
    $add(mc('Percentages', 'medium', "The price of an item rose from ₦{$old} to ₦{$new}. Find the percentage increase.", $pc, [$pc / 2, ($new - $old) / $new * 100, $pc + 10, $new - $old], "Increase = {$new} − {$old} = " . ($new - $old) . "; ÷ {$old} × 100 = " . qfmt($pc) . '%.', $n++));
}
foreach ([[600, 2, 3, 'larger'], [900, 4, 5, 'larger'], [1200, 3, 5, 'smaller'], [350, 2, 5, 'smaller'], [840, 3, 4, 'larger'], [720, 5, 7, 'larger']] as [$tot, $x, $y, $which]) {
    $part = $tot / ($x + $y); $big = max($x, $y) * $part; $small = min($x, $y) * $part; $ans = $which === 'larger' ? $big : $small;
    $add(mc('Ratio and proportion', 'medium', "Share ₦{$tot} in the ratio {$x} : {$y}. What is the {$which} share?", $ans, [$which === 'larger' ? $small : $big, $tot / 2, $ans + $part, $tot / $x], "Total parts = " . ($x + $y) . "; one part = " . qfmt($part) . "; {$which} share = ₦" . qfmt($ans) . '.', $n++));
}
foreach ([[5000, 4, 3], [8000, 5, 2], [12000, 10, 5], [2500, 8, 4], [6000, 3, 2], [20000, 6, 3]] as [$P, $R, $T]) {
    $i = $P * $R * $T / 100;
    $add(mc('Simple interest', 'easy', "Find the simple interest on ₦{$P} for {$T} years at {$R}% per annum.", $i, [$i * 2, $P * $R / 100, $i + $P, $i / 2], "I = PRT/100 = {$P} × {$R} × {$T} ÷ 100 = ₦" . qfmt($i) . '.', $n++));
}
foreach ([5, 6, 9, 10, 11, 13, 14, 15] as $d) {
    $bin = decbin($d);
    $add(mc('Number bases', 'medium', "Convert {$bin} (base 2) to base 10.", $d, [$d + 1, $d - 1, $d * 2, bindec(strrev($bin)) === $d ? $d + 2 : bindec(strrev($bin))], "Place values of {$bin}: " . implode(' + ', array_filter(array_map(static fn ($c, $i) => $c === '1' ? (string) (2 ** (strlen($bin) - 1 - $i)) : '', str_split($bin), array_keys(str_split($bin))))) . " = {$d}.", $n++));
}
foreach ([[2, 3, 4], [3, 2, 5], [5, 4, 3], [7, 3, 2], [2, 5, 3], [4, 6, 2]] as [$x, $a, $b]) {
    $add(mc('Indices', 'easy', "Simplify x^{$a} × x^{$b}.", "x^" . ($a + $b), ["x^" . ($a * $b), "x^" . ($a - $b), "2x^" . ($a + $b), "x^" . ($a + $b + 1)], "Add the powers when multiplying like bases: {$a} + {$b} = " . ($a + $b) . '.', $n++));
}
foreach ([[2, 3], [2, 5], [3, 2], [5, 2], [10, 3], [2, 6]] as [$base, $k]) {
    $add(mc('Logarithms', 'medium', "Evaluate log base {$base} of " . ($base ** $k) . '.', $k, [$k + 1, $k - 1, $base ** $k / $base, $base * $k], "{$base}^{$k} = " . ($base ** $k) . ", so the logarithm is {$k}.", $n++));
}

// ---- Algebra --------------------------------------------------------------------------------------
foreach ([[3, 4, 2], [5, 2, 3], [4, 7, 5], [2, 9, 6], [6, 1, 4], [7, 3, 2], [3, 8, 4], [5, 6, 7]] as [$a, $b, $x]) {
    $c = $a * $x + $b;
    $add(mc('Linear equations', 'easy', "Solve {$a}x + {$b} = {$c}.", $x, [$x + 1, $x - 1, $c - $b, intdiv($c, $a)], "{$a}x = {$c} − {$b} = " . ($c - $b) . ", so x = {$x}.", $n++));
}
foreach ([[7, 3], [9, 5], [10, 4], [12, 6], [15, 9], [20, 8], [11, 3], [14, 2]] as [$s, $d]) {
    $x = ($s + $d) / 2; $y = ($s - $d) / 2;
    if ($x != (int) $x) { continue; }
    $add(mc('Simultaneous equations', 'medium', "If x + y = {$s} and x − y = {$d}, find the value of x.", $x, [$y, $x + 1, $s - $x + 1, $s], "Add the equations: 2x = " . ($s + $d) . ", so x = " . qfmt($x) . '.', $n++));
}
foreach ([[3, 4], [2, 5], [1, 6], [2, 7], [3, 5], [4, 6], [1, 9], [2, 8]] as [$r1, $r2]) {
    $sum = $r1 + $r2; $prod = $r1 * $r2;
    $add(mc('Quadratic equations', 'medium', "The roots of x² − {$sum}x + {$prod} = 0 are", "{$r1} and {$r2}", ['-' . $r1 . ' and -' . $r2, ($r1 + 1) . ' and ' . $r2, "{$r1} and -{$r2}", ($r1 - 1) . ' and ' . ($r2 + 1)], "(x − {$r1})(x − {$r2}) = x² − {$sum}x + {$prod}, so x = {$r1} or {$r2}.", $n++));
}
foreach ([[10, 2, 15], [12, 3, 5], [24, 4, 10], [18, 6, 9], [20, 5, 8], [30, 6, 11]] as [$y1, $x1, $x2]) {
    $k = $y1 / $x1; $y2 = $k * $x2;
    if ($k != (int) $k) { continue; }
    $add(mc('Variation', 'medium', "y varies directly as x. If y = {$y1} when x = {$x1}, find y when x = {$x2}.", $y2, [$y2 + $k, $y1 + $x2, $y2 / 2, $x2 + $k], "y = kx, so k = {$y1}/{$x1} = {$k}; y = {$k} × {$x2} = " . qfmt($y2) . '.', $n++));
}
foreach ([[3, 2, 4], [2, 3, 5], [5, 1, 2], [4, 3, 3], [2, 4, 1], [3, 5, 2]] as [$a, $b, $c]) {
    $v = $a * $c * $c - $b * $c;
    $add(mc('Algebraic expressions', 'easy', "Evaluate {$a}x² − {$b}x when x = {$c}.", $v, [$a * $c - $b * $c, ($a * $c) ** 2 - $b * $c, $v + $b, $a * $c * $c + $b * $c], "{$a}({$c})² − {$b}({$c}) = " . ($a * $c * $c) . ' − ' . ($b * $c) . ' = ' . $v . '.', $n++));
}
foreach ([[2, 3, 5], [3, 5, 2], [4, 1, 6], [6, 2, 4]] as [$a, $d, $m]) {
    $t = $a + ($m - 1) * $d;
    $add(mc('Sequences and series', 'medium', "The first term of an arithmetic progression is {$a} and the common difference is {$d}. Find the {$m}th term.", $t, [$t + $d, $t - $d, $a * $m, $a + $m * $d], "T_n = a + (n − 1)d = {$a} + ({$m} − 1)({$d}) = {$t}.", $n++));
    $s = $m * (2 * $a + ($m - 1) * $d) / 2;
    $add(mc('Sequences and series', 'hard', "Find the sum of the first {$m} terms of the arithmetic progression with first term {$a} and common difference {$d}.", $s, [$s + $d, $s - $a, $t * $m, $s * 2], "S_n = n/2 [2a + (n − 1)d] = {$m}/2 × [" . (2 * $a) . ' + ' . (($m - 1) * $d) . '] = ' . qfmt($s) . '.', $n++));
}
foreach ([[2, 3, 4], [3, 2, 5], [2, 2, 6], [5, 2, 3]] as [$a, $r, $m]) {
    $t = $a * $r ** ($m - 1);
    $add(mc('Sequences and series', 'medium', "The first term of a geometric progression is {$a} and the common ratio is {$r}. Find the {$m}th term.", $t, [$a * $r * $m, $t * $r, $t / $r, $a * ($r ** $m)], "T_n = ar^(n−1) = {$a} × {$r}^" . ($m - 1) . ' = ' . $t . '.', $n++));
}
foreach ([[30, 18, 10], [40, 25, 12], [50, 30, 15], [45, 22, 8], [60, 35, 20], [25, 15, 9]] as [$A, $B, $both]) {
    $u = $A + $B - $both;
    $add(mc('Sets', 'easy', "In a class, {$A} students offer Physics and {$B} offer Chemistry; {$both} offer both. How many offer at least one of the two subjects?", $u, [$A + $B, $u + $both, $A + $B + $both, $u - $both], "n(P ∪ C) = n(P) + n(C) − n(P ∩ C) = {$A} + {$B} − {$both} = {$u}.", $n++));
}
foreach ([[2, 3, 1, 4], [3, 1, 2, 5], [4, 2, 1, 3], [5, 3, 2, 2], [6, 1, 3, 4], [2, 5, 4, 3]] as [$a, $b, $c, $d]) {
    $det = $a * $d - $b * $c;
    $add(mc('Matrices', 'medium', "Find the determinant of the matrix with first row ({$a}  {$b}) and second row ({$c}  {$d}).", $det, [$a * $d + $b * $c, $a * $c - $b * $d, $b * $c - $a * $d, $a + $d], "Determinant = ad − bc = ({$a}×{$d}) − ({$b}×{$c}) = {$det}.", $n++));
}

// ---- Geometry and mensuration --------------------------------------------------------------------
foreach ([[8, 5], [12, 7], [15, 9], [20, 6], [14, 11], [18, 4]] as [$l, $w]) {
    $add(mc('Mensuration', 'easy', "Find the perimeter of a rectangle {$l} cm long and {$w} cm wide.", 2 * ($l + $w), [$l * $w, $l + $w, 2 * $l * $w, 2 * $l + $w], "Perimeter = 2(l + w) = 2({$l} + {$w}) = " . (2 * ($l + $w)) . ' cm.', $n++));
}
foreach ([[10, 6], [12, 8], [9, 14], [16, 5], [20, 7], [18, 10]] as [$b, $h]) {
    $add(mc('Mensuration', 'easy', "Find the area of a triangle with base {$b} cm and height {$h} cm.", $b * $h / 2, [$b * $h, $b + $h, $b * $h / 4, ($b + $h) / 2], "Area = ½ × base × height = ½ × {$b} × {$h} = " . qfmt($b * $h / 2) . ' cm².', $n++));
}
foreach ([7, 14, 21, 28] as $r) {
    $add(mc('Circles', 'easy', "Find the circumference of a circle of radius {$r} cm. (Take π = 22/7)", 44 * $r / 7, [22 * $r * $r / 7, 22 * $r / 7, 88 * $r / 7, 11 * $r / 7], "C = 2πr = 2 × 22/7 × {$r} = " . qfmt(44 * $r / 7) . ' cm.', $n++));
    $add(mc('Circles', 'medium', "Find the area of a circle of radius {$r} cm. (Take π = 22/7)", 22 * $r * $r / 7, [44 * $r / 7, 22 * $r / 7, 44 * $r * $r / 7, 11 * $r * $r / 7], "A = πr² = 22/7 × {$r}² = " . qfmt(22 * $r * $r / 7) . ' cm².', $n++));
}
foreach ([[21, 60], [14, 90], [21, 120], [7, 180], [14, 45], [28, 45]] as [$r, $th]) {
    $len = $th / 360 * 2 * 22 / 7 * $r;
    $add(mc('Circles', 'hard', "Find the length of an arc that subtends an angle of {$th}° at the centre of a circle of radius {$r} cm. (Take π = 22/7)", $len, [$th / 360 * 22 / 7 * $r * $r, 2 * 22 / 7 * $r, $len * 2, $len / 2], "Arc length = θ/360 × 2πr = {$th}/360 × 2 × 22/7 × {$r} = " . qfmt($len) . ' cm.', $n++));
}
foreach ([[3, 4], [5, 12], [8, 15], [7, 24], [20, 21], [9, 12], [6, 8], [12, 16]] as [$a, $b]) {
    $c = sqrt($a * $a + $b * $b);
    $add(mc('Pythagoras theorem', 'easy', "The two shorter sides of a right-angled triangle are {$a} cm and {$b} cm. Find the hypotenuse.", $c, [$a + $b, $c + 1, $b - $a, $a * $b / 2], "c² = {$a}² + {$b}² = " . ($a * $a + $b * $b) . ", so c = " . qfmt($c) . ' cm.', $n++));
}
foreach ([5, 6, 8, 10, 12] as $sides) {
    $add(mc('Polygons', 'medium', "Find the sum of the interior angles of a polygon with {$sides} sides.", ($sides - 2) * 180, [$sides * 180, ($sides - 1) * 180, ($sides - 2) * 90, $sides * 90], "Sum = (n − 2) × 180° = (" . $sides . " − 2) × 180° = " . (($sides - 2) * 180) . '°.', $n++));
}
foreach ([5, 6, 8, 9, 10, 12] as $sides) {
    $ext = 360 / $sides;
    $add(mc('Polygons', 'medium', "Each exterior angle of a regular polygon is " . qfmt($ext) . '°. How many sides has it?', $sides, [$sides + 1, $sides - 1, $sides * 2, 360 - $sides], "Number of sides = 360° ÷ exterior angle = 360 ÷ " . qfmt($ext) . " = {$sides}.", $n++));
}
foreach ([[3, 4, 5], [4, 5, 6], [2, 3, 4], [5, 5, 8], [6, 4, 7]] as [$l, $w, $h]) {
    $add(mc('Mensuration', 'medium', "Find the volume of a cuboid measuring {$l} cm by {$w} cm by {$h} cm.", $l * $w * $h, [$l + $w + $h, 2 * ($l * $w + $w * $h + $l * $h), $l * $w * $h / 2, $l * $w + $h], "V = l × w × h = {$l} × {$w} × {$h} = " . ($l * $w * $h) . ' cm³.', $n++));
}
foreach ([[7, 10], [7, 5], [14, 3], [7, 20]] as [$r, $h]) {
    $v = 22 * $r * $r * $h / 7;
    $add(mc('Mensuration', 'hard', "Find the volume of a cylinder of radius {$r} cm and height {$h} cm. (Take π = 22/7)", $v, [$v * 2, $v / 2, 22 * $r * $h / 7, 44 * $r * $h / 7], "V = πr²h = 22/7 × {$r}² × {$h} = " . qfmt($v) . ' cm³.', $n++));
}

// ---- Coordinate geometry ---------------------------------------------------------------------------
foreach ([[1, 2, 3, 8], [0, 1, 2, 7], [2, 3, 4, 11], [1, 1, 4, 10], [3, 2, 5, 12], [0, 5, 3, 14]] as [$x1, $y1, $x2, $y2]) {
    $m = ($y2 - $y1) / ($x2 - $x1);
    $add(mc('Coordinate geometry', 'medium', "Find the gradient of the line joining ({$x1}, {$y1}) and ({$x2}, {$y2}).", $m, [1 / $m, -$m, $m + 1, $y2 - $y1], "m = (y₂ − y₁)/(x₂ − x₁) = (" . ($y2 - $y1) . ")/(" . ($x2 - $x1) . ") = " . qfmt($m) . '.', $n++));
}
foreach ([[0, 0, 3, 4], [1, 2, 4, 6], [2, 1, 5, 5], [0, 0, 6, 8], [-1, 0, 2, 4]] as [$x1, $y1, $x2, $y2]) {
    $dist = sqrt(($x2 - $x1) ** 2 + ($y2 - $y1) ** 2);
    $add(mc('Coordinate geometry', 'medium', "Find the distance between the points ({$x1}, {$y1}) and ({$x2}, {$y2}).", $dist, [$dist + 1, abs($x2 - $x1) + abs($y2 - $y1), $dist * 2, $dist - 1], "d = √[(" . ($x2 - $x1) . ")² + (" . ($y2 - $y1) . ")²] = √" . (($x2 - $x1) ** 2 + ($y2 - $y1) ** 2) . ' = ' . qfmt($dist) . '.', $n++));
}
foreach ([[2, 4, 6, 10], [0, 2, 8, 6], [1, 3, 5, 9], [4, 2, 10, 8]] as [$x1, $y1, $x2, $y2]) {
    $mx = ($x1 + $x2) / 2; $my = ($y1 + $y2) / 2;
    $add(mc('Coordinate geometry', 'easy', "Find the midpoint of the line joining ({$x1}, {$y1}) and ({$x2}, {$y2}).", "(" . qfmt($mx) . ", " . qfmt($my) . ")", ["(" . qfmt($mx + 1) . ", " . qfmt($my) . ")", "(" . qfmt($x2 - $x1) . ", " . qfmt($y2 - $y1) . ")", "(" . qfmt($mx) . ", " . qfmt($my + 1) . ")", "(" . qfmt($x1 + $x2) . ", " . qfmt($y1 + $y2) . ")"], "Midpoint = ((x₁ + x₂)/2, (y₁ + y₂)/2) = (" . qfmt($mx) . ", " . qfmt($my) . ').', $n++));
}

// ---- Trigonometry (exact values) ---------------------------------------------------------------------
foreach ([['sin 30°', '1/2', ['√3/2', '1', '√2/2']], ['cos 60°', '1/2', ['√3/2', '0', '1']], ['tan 45°', '1', ['0', '√3', '1/2']], ['sin 90°', '1', ['0', '1/2', '√3/2']],
    ['cos 0°', '1', ['0', '1/2', '-1']], ['sin 60°', '√3/2', ['1/2', '1', '√2']], ['cos 30°', '√3/2', ['1/2', '1', '0']], ['tan 60°', '√3', ['1', '1/√3', '0']], ['tan 30°', '1/√3', ['√3', '1', '1/2']]] as [$expr, $val, $wrong]) {
    $add(mc('Trigonometry', 'easy', "What is the value of {$expr}?", $val, $wrong, "From the standard table of exact trigonometric ratios, {$expr} = {$val}.", $n++));
}
foreach ([[3, 5], [4, 5], [5, 13], [12, 13], [8, 17], [7, 25]] as [$opp, $hyp]) {
    $adj = sqrt($hyp * $hyp - $opp * $opp);
    $add(mc('Trigonometry', 'medium', "In a right-angled triangle the side opposite angle θ is {$opp} cm and the hypotenuse is {$hyp} cm. Find cos θ.", qfrac((int) $adj, $hyp), [qfrac($opp, $hyp), qfrac($hyp, (int) $adj), qfrac($opp, (int) $adj)], "Adjacent = √({$hyp}² − {$opp}²) = " . qfmt($adj) . '; cos θ = adjacent ÷ hypotenuse = ' . qfrac((int) $adj, $hyp) . '.', $n++));
}

// ---- Statistics and probability --------------------------------------------------------------------------
foreach ([[4, 6, 8, 10, 12], [3, 5, 7, 9, 11], [10, 20, 30, 40, 50], [2, 4, 6, 8, 20], [5, 7, 9, 11, 13], [12, 15, 18, 21, 24], [6, 8, 8, 10, 13]] as $set) {
    $mean = array_sum($set) / count($set); $list = implode(', ', $set);
    $add(mc('Statistics', 'easy', "Find the mean of {$list}.", $mean, [$mean + 1, $set[2], max($set) - min($set), $mean - 1], "Mean = sum ÷ number = " . array_sum($set) . ' ÷ ' . count($set) . ' = ' . qfmt($mean) . '.', $n++));
}
foreach ([[3, 7, 2, 9, 5], [12, 8, 15, 10, 6], [20, 4, 16, 8, 12], [11, 5, 9, 14, 7], [30, 10, 20, 50, 40]] as $set) {
    $sorted = $set; sort($sorted); $med = $sorted[2]; $list = implode(', ', $set);
    $add(mc('Statistics', 'easy', "Find the median of {$list}.", $med, [$set[2], array_sum($set) / 5, max($set), min($set) + 1], "Arrange in order: " . implode(', ', $sorted) . '; the middle value is ' . $med . '.', $n++));
}
foreach ([[2, 3, 3, 4, 5, 3, 6], [7, 8, 8, 9, 10, 8, 11], [1, 4, 4, 5, 6, 4, 7], [10, 12, 12, 13, 14, 12, 15]] as $set) {
    $count = array_count_values($set); arsort($count); $mode = array_key_first($count); $list = implode(', ', $set);
    $add(mc('Statistics', 'easy', "Find the mode of {$list}.", $mode, [$mode + 1, max($set), min($set), $mode - 1], "{$mode} appears most often (" . $count[$mode] . ' times).', $n++));
}
foreach ([[5, 3], [4, 6], [7, 3], [2, 8], [6, 4], [9, 6], [3, 9], [8, 12]] as [$red, $blue]) {
    $add(mc('Probability', 'easy', "A bag contains {$red} red balls and {$blue} blue balls. One ball is picked at random. What is the probability that it is red?", qfrac($red, $red + $blue), [qfrac($blue, $red + $blue), qfrac($red, $blue), qfrac(1, $red + $blue), qfrac($red + 1, $red + $blue)], "P(red) = number of red ÷ total = {$red}/" . ($red + $blue) . ' = ' . qfrac($red, $red + $blue) . '.', $n++));
}
foreach ([[5, 2], [6, 2], [6, 3], [7, 2], [8, 3], [10, 2]] as [$nn, $r]) {
    $perm = 1; for ($i = 0; $i < $r; $i++) { $perm *= $nn - $i; }
    $comb = $perm / array_product(range(1, $r));
    $add(mc('Permutation and combination', 'medium', "Evaluate {$nn}C{$r} (the number of ways of choosing {$r} from {$nn}).", $comb, [$perm, $comb + $nn, $nn * $r, $comb * 2], "{$nn}C{$r} = {$nn}! ÷ ({$r}!({$nn}−{$r})!) = " . qfmt($comb) . '.', $n++));
}
foreach ([3, 4, 5, 6] as $k) {
    $f = array_product(range(1, $k));
    $add(mc('Permutation and combination', 'medium', "In how many ways can {$k} different books be arranged on a shelf?", $f, [$f / $k, $f * $k, $k * $k, $f + $k], "{$k}! = " . implode(' × ', range($k, 1)) . " = {$f}.", $n++));
}

// ---- Calculus --------------------------------------------------------------------------------------------------
foreach ([[3, 4], [2, 5], [5, 3], [4, 2], [6, 3], [7, 2], [2, 6], [3, 5]] as [$c, $p]) {
    $add(mc('Differentiation', 'medium', "Differentiate {$c}x^{$p} with respect to x.", ($c * $p) . 'x^' . ($p - 1), [($c * $p) . 'x^' . $p, $c . 'x^' . ($p - 1), ($c * ($p - 1)) . 'x^' . ($p - 1), ($c + $p) . 'x^' . ($p - 1)], "d/dx (ax^n) = anx^(n−1) = {$c}×{$p}x^" . ($p - 1) . '.', $n++));
}
foreach ([[2, 3, 1], [3, 2, 2], [1, 4, 3], [2, 5, 2], [4, 1, 3]] as [$a, $b, $x]) {
    $g = 2 * $a * $x + $b;
    $add(mc('Differentiation', 'hard', "Find the gradient of the curve y = {$a}x² + {$b}x at x = {$x}.", $g, [$a * $x * $x + $b * $x, $g + $a, $g - $b, $a * $x + $b], "dy/dx = " . (2 * $a) . 'x + ' . $b . "; at x = {$x}: " . (2 * $a) . "({$x}) + {$b} = {$g}.", $n++));
}
foreach ([[6, 2], [15, 3], [8, 2], [9, 3], [12, 3], [20, 4]] as [$c, $p]) {
    $q = intdiv($c, $p);
    $add(mc('Integration', 'hard', "Evaluate the indefinite integral of {$c}x^" . ($p - 1) . ' with respect to x.', $q . 'x^' . $p . ' + k', [$c . 'x^' . $p . ' + k', ($c * $p) . 'x^' . $p . ' + k', $q . 'x^' . ($p - 1) . ' + k', ($c + $p) . 'x^' . $p . ' + k'], "∫ax^(n−1) dx = (a/n)x^n + k = ({$c}/{$p})x^{$p} + k = {$q}x^{$p} + k.", $n++));
}

return ['Mathematics' => $rows];
