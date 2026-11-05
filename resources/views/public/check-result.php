<?php
declare(strict_types=1);
/** Parent view of released results. @var array $school @var array $student @var list<array> $rows @var list<array> $cards */
$fmt = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
?>
<p class="eyebrow"><?= e($school['name']) ?></p>
<h1 class="mt-3 font-display text-[30px] font-semibold leading-tight text-ink"><?= e($student['name']) ?></h1>
<p class="mt-1 text-sm text-muted"><?= e($student['admission_no']) ?> · <?= e((string) $student['class_name']) ?></p>

<?php if ($cards): ?>
    <div class="mt-6 space-y-2">
        <?php foreach ($cards as $c): ?><a class="btn btn-primary w-full" href="<?= e($c['url']) ?>"><?= icon('file-text') ?>Report card · <?= e($c['term']['name'] . ' ' . $c['term']['session_name']) ?></a><?php endforeach; ?>
    </div>
<?php endif; ?>

<ul class="mt-6 divide-y divide-line rounded-2xl border border-line bg-card">
    <?php foreach (array_slice($rows, 0, 30) as $r): ?>
        <li class="flex items-center justify-between gap-3 px-4 py-3"><span class="min-w-0"><span class="block truncate text-sm font-medium text-ink"><?= e($r['title']) ?></span><span class="text-xs text-muted"><?= e(local_date($r['submitted_at'], 'j M Y')) ?></span></span>
            <span class="text-right"><span class="block font-semibold tabular-nums text-ink"><?= $fmt($r['percent']) ?>%</span><span class="badge <?= $r['grade']['pass'] ? 'badge-green' : 'badge-red' ?>"><?= e($r['grade']['grade']) ?></span></span></li>
    <?php endforeach; ?>
    <?php if ($rows === []): ?><li class="px-4 py-8 text-center text-sm text-muted">No released results yet.</li><?php endif; ?>
</ul>
<a href="<?= e(url('/check-result')) ?>" class="btn btn-ghost mt-6 w-full">Check another student</a>
