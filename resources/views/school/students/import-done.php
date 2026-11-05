<?php
declare(strict_types=1);
/** @var array<string,mixed> $batch @var bool $hasCredentials */
?>
<div class="mx-auto max-w-2xl py-8 text-center">
    <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-500 text-white shadow-[0_18px_40px_-14px_rgb(16_185_129/.8)]"><?= icon('check', 'h-8 w-8') ?></span>
    <h1 class="mt-6 font-display text-[32px] font-semibold text-ink">Import complete</h1>
    <p class="mt-2 text-muted"><?= e($batch['file_name']) ?></p>
    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <div class="card p-6"><p class="stat-label">Students added</p><p class="stat-value text-emerald-600 tabular-nums dark:text-emerald-400" data-count="<?= (int) $batch['created_rows'] ?>"><?= number_format((int) $batch['created_rows']) ?></p></div>
        <div class="card p-6"><p class="stat-label">Skipped</p><p class="stat-value tabular-nums"><?= number_format((int) $batch['skipped_rows']) ?></p><p class="mt-1 text-xs text-muted">Already enrolled, or plan limit reached</p></div>
    </div>

    <?php if ($hasCredentials): ?>
        <section class="card mt-6 p-6 text-left">
            <div class="flex items-start gap-4">
                <span class="icon-tile"><?= icon('key') ?></span>
                <div>
                    <h2 class="card-title">Give students their sign-in details</h2>
                    <p class="mt-1 text-sm text-muted">Each student got a first password. Print slips to hand out, or download the list. For privacy these are deleted automatically after 24 hours.</p>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= e(url('/school/students/slips?import=' . (int) $batch['id'])) ?>" target="_blank" rel="noopener"><?= icon('file-text') ?>Print sign-in slips</a>
                <a class="btn btn-secondary" href="<?= e(url('/school/students/import/' . (int) $batch['id'] . '/credentials')) ?>"><?= icon('download') ?>Download as Excel</a>
            </div>
        </section>
    <?php endif; ?>
    <div class="mt-8 flex justify-center gap-3">
        <a class="btn btn-secondary" href="<?= e(url('/school/students/import')) ?>"><?= icon('upload') ?>Import another file</a>
        <a class="btn btn-ghost" href="<?= e(url('/school/students?sort=newest')) ?>">View students<?= icon('arrow-right') ?></a>
    </div>
</div>
