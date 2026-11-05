<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $classes @var int|null $remaining @var list<array<string,mixed>> $recentImports */
?>
<a href="<?= e(url('/school/students')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Students</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">Import students</h1>
<p class="mt-1 max-w-2xl text-sm text-muted">Upload your class lists from Excel or CSV. Every row is checked first, and nothing is saved until you confirm. Big files are imported in small batches, so you can keep working in other tabs.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <form method="post" action="<?= e(url('/school/students/import')) ?>" enctype="multipart/form-data" class="p-6" data-submit-once>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <label class="group flex cursor-pointer flex-col items-center justify-center rounded-[28px] border-2 border-dashed border-line bg-soft px-6 py-14 text-center transition hover:border-brand/50 hover:bg-brand/[.03]" data-dropzone>
                <span class="icon-tile h-14 w-14 rounded-3xl transition group-hover:scale-105"><?= icon('upload', 'h-6 w-6') ?></span>
                <span class="mt-5 font-display text-lg font-semibold text-ink" data-dropzone-label>Drop your Excel or CSV file here</span>
                <span class="mt-1 text-sm text-muted">or tap to choose a file · .xlsx or .csv · up to 10 MB</span>
                <input type="file" name="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" class="sr-only" required data-dropzone-input>
            </label>
            <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?= e(url('/school/students/import/template')) ?>" class="btn btn-ghost"><?= icon('download') ?>Download the Excel template</a>
                <button class="btn btn-primary btn-lg"><?= icon('check-circle') ?><span data-label>Check file</span></button>
            </div>
        </form>
    </section>

    <aside class="space-y-6">
        <section class="card card-body">
            <p class="eyebrow">Columns we understand</p>
            <ul class="mt-4 space-y-2 text-sm text-muted">
                <li><span class="font-semibold text-ink">Name</span>, or Surname + First Name (+ Other Names)</li>
                <li><span class="font-semibold text-ink">Admission No</span>: optional; we create one if blank</li>
                <li><span class="font-semibold text-ink">Class</span>: must match an existing class, e.g. <?= e($classes[0]['name'] ?? 'JSS 1 A') ?></li>
                <li>Gender (M/F), Date of Birth, Department, Guardian Name, Phone, Email, Address</li>
            </ul>
            <p class="mt-4 rounded-2xl bg-soft p-3 text-xs leading-5 text-muted">Phone numbers that lost their leading zero in Excel (803…) are fixed automatically.</p>
        </section>
        <?php if ($remaining !== null): ?>
            <section class="card card-body">
                <p class="stat-label">Seats left on your plan</p>
                <p class="stat-value tabular-nums"><?= number_format($remaining) ?></p>
            </section>
        <?php endif; ?>
        <?php if ($recentImports !== []): ?>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Recent imports</h2></div>
                <ul class="space-y-1 px-3 pb-3">
                    <?php foreach ($recentImports as $import): ?>
                        <li><a href="<?= e(url('/school/students/import/' . $import['id'])) ?>" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 hover:bg-soft">
                            <span class="icon-soft h-9 w-9"><?= icon('file-text', 'h-4 w-4') ?></span>
                            <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-ink"><?= e($import['file_name']) ?></span><span class="block text-xs text-muted"><?= (int) $import['created_rows'] ?> added · <?= e(time_ago((string) $import['created_at'])) ?></span></span>
                            <span class="badge <?= $import['status'] === 'completed' ? 'badge-green' : 'badge-amber' ?>"><?= e(ucfirst((string) $import['status'])) ?></span>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </aside>
</div>
