<?php
declare(strict_types=1);
/**
 * Smooth multi-line chart. Lines are drawn in SVG (stretches to any width); axis text is plain HTML so it stays crisp.
 * @var list<array{label:string,color:string,values:list<int|float>}> $lines
 * @var list<string> $labels one per point (shown sparsely)
 * @var int|float|null $chartMax fixed top of the scale (e.g. 100 for percentages)
 * @var string $chartLabel @var string $chartUnit
 */
static $chartSeq = 0;
$chartSeq++;
$chartUnit ??= '';
$n = count($labels);
$all = [];
foreach ($lines as $l) {
    foreach ($l['values'] as $v) {
        $all[] = (float) $v;
    }
}
$top = isset($chartMax) && $chartMax > 0 ? (float) $chartMax : max(5.0, ceil((max($all ?: [0]) * 1.15) / 5) * 5);
$W = 600;
$H = 200;
$pad = 8;
$xy = static function (int $i, float $v) use ($n, $W, $H, $pad, $top): array {
    $x = $n <= 1 ? $W / 2 : $i / ($n - 1) * $W;
    return [round($x, 1), round($H - $pad - min(1.0, max(0.0, $v / $top)) * ($H - 2 * $pad), 1)];
};
$path = static function (array $values) use ($xy): string {
    $pts = [];
    foreach (array_values($values) as $i => $v) {
        $pts[] = $xy($i, (float) $v);
    }
    if ($pts === []) {
        return '';
    }
    $d = 'M' . $pts[0][0] . ' ' . $pts[0][1];
    for ($i = 1; $i < count($pts); $i++) {
        $mx = round(($pts[$i - 1][0] + $pts[$i][0]) / 2, 1);
        $d .= ' C' . $mx . ' ' . $pts[$i - 1][1] . ' ' . $mx . ' ' . $pts[$i][1] . ' ' . $pts[$i][0] . ' ' . $pts[$i][1];
    }
    return $d;
};
$ticks = [];
$count = min(5, max(1, $n));
for ($k = 0; $k < $count; $k++) {
    $ticks[] = $labels[(int) round($k * ($n - 1) / max(1, $count - 1))] ?? '';
}
$gid = 'lc' . $chartSeq;
?>
<?php if (count($lines) > 1): ?>
    <div class="pd-legend"><?php foreach ($lines as $l): ?><span><svg class="pd-dot" viewBox="0 0 10 10" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="<?= e($l['color']) ?>"/></svg><?= e($l['label']) ?></span><?php endforeach; ?></div>
<?php endif; ?>
<div class="pd-chart" role="img" aria-label="<?= e($chartLabel ?? 'Chart') ?>">
    <div class="y" aria-hidden="true"><span><?= e(rtrim(rtrim(number_format($top, 1), '0'), '.') . $chartUnit) ?></span><span><?= e(rtrim(rtrim(number_format($top / 2, 1), '0'), '.') . $chartUnit) ?></span><span>0</span></div>
    <svg viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none" aria-hidden="true">
        <defs><linearGradient id="<?= $gid ?>" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="<?= e($lines[0]['color'] ?? '#2563eb') ?>" stop-opacity=".28"/><stop offset="1" stop-color="<?= e($lines[0]['color'] ?? '#2563eb') ?>" stop-opacity="0"/></linearGradient></defs>
        <?php foreach ([0, .5, 1] as $g): $gy = round($pad + $g * ($H - 2 * $pad), 1); ?><line x1="0" x2="<?= $W ?>" y1="<?= $gy ?>" y2="<?= $gy ?>" stroke="#eef2f7" stroke-width="1" vector-effect="non-scaling-stroke"/><?php endforeach; ?>
        <?php if (isset($lines[0]) && $n > 1): $d0 = $path($lines[0]['values']); ?>
            <path d="<?= $d0 ?> L<?= $W ?> <?= $H - $pad ?> L0 <?= $H - $pad ?> Z" fill="url(#<?= $gid ?>)"/>
        <?php endif; ?>
        <?php foreach ($lines as $l): ?><path class="pd-line" d="<?= $path($l['values']) ?>" stroke="<?= e($l['color']) ?>"/><?php endforeach; ?>
    </svg>
    <div class="x" aria-hidden="true"><?php foreach ($ticks as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></div>
</div>
