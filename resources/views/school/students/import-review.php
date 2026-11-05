<?php
declare(strict_types=1);
/**
 * @var string $fileName @var list<array<string,mixed>> $valid @var list<array{row:int,name:string,messages:list<string>}> $errors
 * @var int $total @var int|null $batchId @var array<int,string> $classNames
 */
$preview = array_slice($valid, 0, 15);
?>
<a href="<?= e(url('/school/students/import')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Choose another file</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">Review the import</h1>
<p class="mt-1 text-sm text-muted"><?= e($fileName) ?> · <?= number_format($total) ?> row<?= $total === 1 ? '' : 's' ?> checked. Nothing has been saved yet.</p>

<section class="mt-6 grid gap-4 sm:grid-cols-3" data-stagger>
    <div class="card p-5"><p class="stat-label">Ready to import</p><p class="stat-value text-emerald-600 tabular-nums dark:text-emerald-400"><?= number_format(count($valid)) ?></p></div>
    <div class="card p-5"><p class="stat-label">Rows with problems</p><p class="stat-value tabular-nums <?= $errors !== [] ? 'text-red-600 dark:text-red-400' : '' ?>"><?= number_format(count($errors)) ?></p></div>
    <div class="card p-5"><p class="stat-label">Admission numbers to generate</p><p class="stat-value tabular-nums"><?= number_format(count(array_filter($valid, static fn (array $r): bool => $r['admission_no'] === ''))) ?></p></div>
</section>

<?php if ($batchId !== null): ?>
    <section class="card mt-6 overflow-hidden">
        <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="card-title">Import <?= number_format(count($valid)) ?> student<?= count($valid) === 1 ? '' : 's' ?></h2>
                <p class="mt-1 text-sm text-muted"><?= $errors !== [] ? 'Rows with problems are skipped. Fix them and import them in a second file.' : 'Every row passed the checks.' ?> Passwords are created during import.</p>
            </div>
            <form method="post" action="<?= e(url('/school/students/import/' . $batchId . '/process')) ?>" data-import-start>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button class="btn btn-primary btn-lg"><?= icon('upload') ?><span data-label>Start import</span></button>
            </form>
        </div>
        <div class="hidden border-t border-line p-6" data-import-progress aria-live="polite">
            <div class="flex items-center justify-between text-sm"><span class="font-semibold text-ink" data-import-label>Starting…</span><span class="tabular-nums text-muted" data-import-count></span></div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-soft ring-1 ring-line"><div class="h-full w-0 rounded-full bg-signature transition-[width] duration-500" data-import-bar></div></div>
            <p class="mt-3 text-xs text-muted">Keep this tab open. You can use the portal in other tabs meanwhile.</p>
        </div>
        <?php if ($preview !== []): ?>
            <div class="overflow-x-auto border-t border-line">
                <table class="table">
                    <thead><tr><th scope="col">Row</th><th scope="col">Name</th><th scope="col">Admission no.</th><th scope="col">Class</th><th scope="col">Gender</th><th scope="col">Guardian phone</th></tr></thead>
                    <tbody>
                    <?php foreach ($preview as $row): ?>
                        <tr><td class="text-subtle tabular-nums"><?= (int) $row['row'] ?></td><td class="font-medium text-ink"><?= e($row['name']) ?></td>
                            <td class="font-mono text-[13px] text-muted"><?= $row['admission_no'] !== '' ? e($row['admission_no']) : '<span class="badge badge-gray">auto</span>' ?></td>
                            <td><?= e($row['class_id'] ? ($classNames[$row['class_id']] ?? '') : '—') ?></td><td class="text-muted"><?= e(ucfirst((string) $row['gender'])) ?: '—' ?></td><td class="text-muted"><?= e($row['guardian_phone'] ?? '—') ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($valid) > count($preview)): ?><p class="px-6 py-3 text-xs text-muted">…and <?= number_format(count($valid) - count($preview)) ?> more.</p><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <section class="card mt-6 overflow-hidden">
        <div class="card-header"><div><p class="eyebrow text-red-600 dark:text-red-400">Needs fixing</p><h2 class="card-title mt-1">Rows that will be skipped</h2></div></div>
        <ul class="max-h-[28rem] divide-y divide-line overflow-y-auto">
            <?php foreach (array_slice($errors, 0, 300) as $error): ?>
                <li class="flex gap-4 px-6 py-3">
                    <span class="mt-0.5 w-14 shrink-0 text-xs font-semibold tabular-nums text-subtle">Row <?= (int) $error['row'] ?></span>
                    <div class="min-w-0"><p class="text-sm font-medium text-ink"><?= e($error['name'] !== '' ? $error['name'] : '(no name)') ?></p>
                        <ul class="mt-0.5 space-y-0.5 text-xs text-red-600 dark:text-red-400"><?php foreach ($error['messages'] as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
