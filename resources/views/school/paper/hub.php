<?php
declare(strict_types=1);
/** Paper exam hub. @var array $exam @var array $versions @var list<array> $roster @var int $scanned @var bool $locked @var list<string> $theory @var string $classes */
$base = '/school/exams/' . $exam['id'] . '/paper';
$total = count($roster);
$theoryMax = (int) $exam['settings']['theory_max'];
$questions = count(reset($versions) ?: []);
$pct = $total ? (int) round($scanned * 100 / $total) : 0;
$steps = [
    ['1', 'Print the question paper', count($versions) > 1 ? 'Papers ' . implode(', ', array_keys($versions)) . ' have the same questions in a different order.' : 'One version (Paper A).', 'file-text', url($base . '/print?version=all'), 'Print papers'],
    ['2', 'Print the answer sheets', 'One per student with their name and QR code. Papers alternate A, B… down the class list.', 'list-checks', url($base . '/sheets'), 'Print sheets'],
    ['3', 'Marking scheme', 'Answer key for each version (keep it confidential).', 'key', url($base . '/key'), 'Open key'],
    ['4', 'Scan the shaded sheets', 'Use the school phone: snap each sheet, check, save. Works without data; saves when online.', 'scan-face', url($base . '/scan'), 'Start scanning'],
];
if ($theoryMax > 0) {
    $steps[] = ['5', 'Enter theory scores', 'Type each student\'s theory mark (out of ' . $theoryMax . '), or shade it on their sheet before scanning.', 'sliders', url($base . '/theory'), 'Enter scores'];
}
?>
<a href="<?= e(url('/school/exams/' . $exam['id'])) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?><?= e($exam['title']) ?></a>
<div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">Paper exam</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink"><?= e($exam['title']) ?></h1>
        <p class="mt-1 text-sm text-muted"><?= $questions ?> objective questions<?= $theory ? ' · ' . count($theory) . ' theory questions' : '' ?><?= $theoryMax ? ' (' . $theoryMax . ' marks)' : '' ?> · <?= e($classes) ?></p></div>
    <a class="btn btn-secondary" href="<?= e(url('/school/exams/' . $exam['id'] . '/results')) ?>"><?= icon('chart') ?>Results</a>
</div>

<section class="card mt-6 p-6">
    <div class="flex items-center justify-between text-sm"><span class="font-semibold text-ink">Sheets scanned</span><span class="tabular-nums text-muted"><?= $scanned ?> of <?= $total ?></span></div>
    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full bg-signature" data-width="<?= $pct ?>"></span></div>
</section>

<div class="mt-6 grid gap-4 md:grid-cols-2" data-stagger>
    <?php foreach ($steps as [$n, $title, $text, $ic, $href, $cta]): ?>
        <article class="card flex gap-4 p-6">
            <span class="icon-tile"><?= icon($ic) ?></span>
            <div class="min-w-0 flex-1">
                <p class="eyebrow">Step <?= $n ?></p>
                <h2 class="mt-1 font-display text-lg font-semibold text-ink"><?= e($title) ?></h2>
                <p class="mt-1 text-sm text-muted"><?= e($text) ?></p>
                <a class="btn <?= $n === '4' ? 'btn-primary' : 'btn-secondary' ?> btn-sm mt-4" href="<?= e($href) ?>"><?= e($cta) ?></a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if (!$locked): ?>
    <form method="post" action="<?= e(url($base . '/regenerate')) ?>" class="mt-6 flex items-center justify-between gap-4 rounded-2xl border border-line p-4 text-sm" data-confirm="Papers already printed will no longer match. Only do this before printing." data-confirm-ok="Create new order">
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <span class="text-muted">Changed questions in the bank or want a fresh order? You can recreate the papers until the first sheet is scanned.</span>
        <button class="btn btn-ghost btn-sm shrink-0"><?= icon('refresh') ?>Recreate papers</button>
    </form>
<?php else: ?>
    <p class="mt-6 text-sm text-muted"><?= icon('lock', 'mr-1 inline h-4 w-4') ?>Papers are locked because sheets have been scanned.</p>
<?php endif; ?>
