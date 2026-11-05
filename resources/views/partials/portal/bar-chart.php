<?php
declare(strict_types=1);
/**
 * Single-series column chart (no library). Dataviz mark spec: columns ≤ 24px, 4px rounded data
 * ends, square baseline, hairline solid grid, hover/focus tooltip, staggered grow-in, and an
 * accessible table with the same numbers.
 *
 * @var list<array{count: int, date?: string, label?: string, short?: string}> $series
 * @var string $chartLabel
 * @var string|null $unit   singular noun for tooltips, e.g. "attempt" or "student"
 */
$unit ??= 'attempt';
$values = array_column($series, 'count');
$peak = max([0, ...$values]);
$step = 1;
foreach ([1, 2, 2.5, 5] as $base) {
    for ($exp = 0; $exp < 7; $exp++) {
        $candidate = $base * 10 ** $exp;
        if ($candidate * 4 >= max(4, $peak)) {
            $step = $candidate;
            break 2;
        }
    }
}
$axisMax = $step * 4;
$chartId = 'c' . substr(md5($chartLabel), 0, 6);
$label = static fn (array $p): string => $p['label'] ?? local_date(($p['date'] ?? 'now') . ' 12:00:00', 'D j M');
$short = static fn (array $p): string => $p['short'] ?? local_date(($p['date'] ?? 'now') . ' 12:00:00', 'j');
$rules = [];
for ($i = 0; $i <= 4; $i++) {
    $rules[] = ".{$chartId}-y{$i}{top:" . (100 - $i * 25) . '%}';
}
foreach ($series as $index => $point) {
    $h = $axisMax > 0 ? round(($point['count'] / $axisMax) * 100, 2) : 0;
    // Zero values keep a 2px stub so the column still reads as "measured, none".
    $rules[] = ".{$chartId}-b{$index}{height:" . ($point['count'] > 0 ? $h . '%' : '2px') . ';animation-delay:' . ($index * 45) . 'ms}';
}
$dense = count($series) > 10;
?>
<style nonce="<?= e($cspNonce) ?>"><?= implode('', $rules) ?></style>
<div class="relative" data-chart>
    <div class="relative grid grid-cols-[2.25rem_1fr] gap-x-2">
        <div class="relative h-48" aria-hidden="true">
            <?php for ($i = 4; $i >= 0; $i--): ?>
                <span class="absolute right-0 -translate-y-1/2 text-[11px] tabular-nums text-subtle <?= $chartId ?>-y<?= $i ?>"><?= e(number_format($step * $i, $step < 1 ? 1 : 0)) ?></span>
            <?php endfor; ?>
        </div>
        <div class="relative h-48">
            <?php for ($i = 0; $i <= 4; $i++): ?>
                <div class="absolute inset-x-0 h-px bg-[var(--chart-grid)] <?= $chartId ?>-y<?= $i ?>" aria-hidden="true"></div>
            <?php endfor; ?>
            <div class="absolute inset-0 flex items-end justify-between gap-0.5">
                <?php foreach ($series as $index => $point):
                    $tip = $label($point) . ': ' . number_format($point['count']) . ' ' . $unit . ($point['count'] === 1 ? '' : 's'); ?>
                    <button type="button" class="group relative flex h-full flex-1 cursor-default items-end justify-center focus:outline-none" aria-label="<?= e($tip) ?>" data-tip="<?= e($tip) ?>">
                        <span class="chart-bar block w-full max-w-[24px] rounded-t-[4px] bg-[var(--chart)] transition-opacity group-hover:opacity-80 group-focus-visible:outline group-focus-visible:outline-2 group-focus-visible:outline-offset-2 group-focus-visible:outline-[rgb(var(--brand))] <?= $chartId ?>-b<?= $index ?> <?= $point['count'] === 0 ? 'opacity-30' : '' ?>"></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div></div>
        <div class="mt-2 flex justify-between gap-0.5" aria-hidden="true">
            <?php foreach ($series as $index => $point): ?>
                <span class="flex-1 truncate text-center text-[11px] tabular-nums text-subtle <?= $dense && $index % 2 === 1 && $index !== count($series) - 1 ? 'hidden sm:block' : '' ?>"><?= e($short($point)) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="pointer-events-none absolute z-10 hidden -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white shadow-lg dark:bg-slate-100 dark:text-slate-900" data-chart-tip role="tooltip"></div>
    <table class="sr-only">
        <caption><?= e($chartLabel) ?></caption>
        <thead><tr><th scope="col">Item</th><th scope="col"><?= e(ucfirst($unit)) ?>s</th></tr></thead>
        <tbody>
        <?php foreach ($series as $point): ?>
            <tr><td><?= e($label($point)) ?></td><td><?= (int) $point['count'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
