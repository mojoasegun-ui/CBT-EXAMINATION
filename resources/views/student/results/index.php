<?php
declare(strict_types=1);
/** @var array $student @var list<array> $rows @var array<int|string, ?int> $positions @var array|null $term */
use ExamCore\Services\ResultService;

$fmt = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
$shown = array_values(array_filter($rows, static fn ($r) => $r['visible']));
$avg = $shown ? array_sum(array_map(static fn ($r) => (float) $r['percent'], $shown)) / count($shown) : null;
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">My results</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Scores &amp; report card</h1>
        <p class="mt-1 text-sm text-muted"><?= count($shown) ?> released result<?= count($shown) === 1 ? '' : 's' ?><?= $avg !== null ? ' · average ' . $fmt($avg) . '%' : '' ?></p></div>
    <?php if ($term): ?><a class="btn btn-primary" href="<?= e(url('/student/results/report?term=' . $term['id'])) ?>"><?= icon('file-text') ?>My report card · <?= e($term['name']) ?></a><?php endif; ?>
</div>

<div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-stagger>
    <?php foreach ($rows as $r): ?>
        <article class="card flex flex-col p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0"><p class="eyebrow"><?= e(\ExamCore\Models\SchoolExam::TYPES[$r['type']] ?? 'Exam') ?></p><h2 class="mt-1 truncate font-display text-lg font-semibold text-ink"><?= e($r['title']) ?></h2>
                    <p class="text-xs text-muted"><?= e(local_date($r['submitted_at'], 'D j M Y')) ?></p></div>
                <?php if ($r['visible']): ?><span class="badge <?= $r['grade']['pass'] ? 'badge-green' : 'badge-red' ?> px-3 py-1.5 text-sm"><?= e($r['grade']['grade']) ?></span><?php endif; ?>
            </div>
            <?php if ($r['visible']): ?>
                <div class="mt-5 flex items-end justify-between">
                    <p class="font-display text-4xl font-semibold tabular-nums text-ink"><?= $fmt($r['percent']) ?><span class="text-xl text-muted">%</span></p>
                    <p class="text-right text-sm text-muted"><?= (int) $r['score'] ?>/<?= (int) $r['question_count'] ?> correct<?php $pos = $positions[(int) $r['exam_id']] ?? null; if ($pos): ?><br><span class="font-semibold text-ink"><?= e(ResultService::ordinal($pos)) ?></span> of <?= (int) ($positions[$r['exam_id'] . ':of'] ?? 0) ?><?php endif; ?></p>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full bg-signature" data-width="<?= (int) round((float) $r['percent']) ?>"></span></div>
                <?php $papers = json_decode((string) $r['breakdown'], true) ?: []; if (count($papers) > 1): ?>
                    <ul class="mt-4 space-y-1 text-xs text-muted"><?php foreach ($papers as $p): ?><li class="flex justify-between"><span><?= e($p['subject']) ?></span><span class="tabular-nums"><?= (int) $p['correct'] ?>/<?= (int) $p['total'] ?></span></li><?php endforeach; ?></ul>
                <?php endif; ?>
                <p class="mt-4 text-xs text-muted"><?= e($r['grade']['remark']) ?></p>
            <?php else: ?>
                <p class="mt-5 rounded-2xl bg-soft p-4 text-sm text-muted"><?= icon('clock', 'mr-1 inline h-4 w-4') ?>Submitted. Your score appears when your school releases the results.</p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php if ($rows === []): ?>
    <div class="card empty mt-2"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('chart', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">No results yet</p><p class="text-sm text-muted">After you write an exam, your score shows here.</p></div>
<?php endif; ?>
