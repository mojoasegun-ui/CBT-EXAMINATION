<?php
declare(strict_types=1);
/** @var string $tab @var array<string,int> $counts @var list<array> $rows */
$tabs = ['live' => 'Live now', 'upcoming' => 'Upcoming', 'draft' => 'Drafts', 'past' => 'Past'];
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">Exams</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Exams &amp; schedule</h1>
        <p class="mt-1 text-sm text-muted">Build exams from your question bank, choose classes and a time window, then publish.</p></div>
    <a href="<?= e(url('/school/exams/create')) ?>" class="btn btn-primary"><?= icon('plus') ?>New exam</a>
</div>
<nav class="tabs mt-6 w-fit" aria-label="Exam status">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab inline-flex items-center gap-2" href="<?= e(url('/school/exams?tab=' . $key)) ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>>
            <?php if ($key === 'live' && $counts['live'] > 0): ?><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span><?php endif; ?><?= e($label) ?>
            <span class="rounded-full bg-ink/5 px-2 py-0.5 text-[11px] tabular-nums text-muted"><?= $counts[$key] ?></span></a>
    <?php endforeach; ?>
</nav>
<div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-stagger>
    <?php foreach ($rows as $exam): $starts = strtotime($exam['starts_at'] . ' UTC'); ?>
        <a href="<?= e(url('/school/exams/' . $exam['id'])) ?>" class="card card-hover flex flex-col p-6">
            <div class="flex items-start justify-between gap-3">
                <span class="icon-tile"><?= icon('calendar') ?></span>
                <span class="badge <?= ['live' => 'badge-green', 'upcoming' => 'badge-blue', 'draft' => 'badge-gray', 'past' => 'badge-gray'][$tab] ?>"><?= $exam['status'] === 'cancelled' ? 'Cancelled' : e($tabs[$tab]) ?></span>
            </div>
            <h2 class="mt-5 font-display text-lg font-semibold leading-snug text-ink"><?= e($exam['title']) ?></h2>
            <p class="mt-1 text-sm text-muted"><?= e($exam['subjects'] ?? 'No papers yet') ?></p>
            <dl class="mt-5 space-y-1.5 text-sm">
                <div class="flex gap-2"><dt class="text-subtle"><?= icon('clock', 'h-4 w-4') ?></dt><dd class="text-ink"><?= e(local_date($exam['starts_at'], 'D j M, g:i a')) ?> · <?= (int) $exam['duration_minutes'] ?> min</dd></div>
                <div class="flex gap-2"><dt class="text-subtle"><?= icon('users', 'h-4 w-4') ?></dt><dd class="truncate text-muted"><?= e($exam['class_names'] ?? 'No classes') ?></dd></div>
            </dl>
            <?php if ($tab === 'upcoming'): ?><p class="mt-4 text-xs font-semibold text-brand" data-countdown="<?= $starts ?>">Starts <?= e(time_until($starts)) ?></p><?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
<?php if ($rows === []): ?>
    <div class="card empty"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('calendar', 'h-6 w-6') ?></span>
        <p class="font-display text-xl font-semibold text-ink">Nothing here yet</p><p class="text-sm text-muted">Create an exam, pick its subjects and classes, and publish it.</p>
        <a class="btn btn-primary" href="<?= e(url('/school/exams/create')) ?>"><?= icon('plus') ?>New exam</a></div>
<?php endif; ?>
