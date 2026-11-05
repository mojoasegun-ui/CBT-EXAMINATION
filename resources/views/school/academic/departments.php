<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $departments @var array $errors @var array $old */
?>
<div><p class="eyebrow">Academics</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Departments</h1>
    <p class="mt-1 text-sm text-muted">Streams such as Science, Commercial and Arts. Classes, subjects and students can belong to a department.</p></div>
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2" data-stagger>
        <?php foreach ($departments as $d): ?>
            <section class="card p-6 <?= $d['is_active'] ? '' : 'opacity-60' ?>">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3"><span class="icon-tile"><?= icon('layers') ?></span><div><h2 class="card-title"><?= e($d['name']) ?></h2><p class="font-mono text-xs text-subtle"><?= e($d['code'] ?? '') ?></p></div></div>
                    <?php if (!$d['is_active']): ?><span class="badge badge-gray">Inactive</span><?php endif; ?>
                </div>
                <?php if ($d['description']): ?><p class="mt-3 text-sm leading-6 text-muted"><?= e($d['description']) ?></p><?php endif; ?>
                <div class="mt-4 flex flex-wrap gap-2"><span class="badge badge-brand"><?= (int) $d['student_count'] ?> students</span><span class="badge badge-gray"><?= (int) $d['class_count'] ?> classes</span><span class="badge badge-gray"><?= (int) $d['subject_count'] ?> subjects</span></div>
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-brand">Edit</summary>
                    <form method="post" action="<?= e(url('/school/departments/' . $d['id'])) ?>" class="mt-3 space-y-3" data-submit-once>
                        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                        <?= field('name', 'Name', (string) $d['name'], []) ?>
                        <?= field('code', 'Code', (string) $d['code'], []) ?>
                        <?= field('description', 'Description', (string) $d['description'], []) ?>
                        <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" class="checkbox" name="is_active" value="1" <?= $d['is_active'] ? 'checked' : '' ?>>Active</label>
                        <div class="flex justify-between gap-2"><button class="btn btn-primary btn-sm"><span data-label>Save</span></button></div>
                    </form>
                    <form method="post" action="<?= e(url('/school/departments/' . $d['id'] . '/delete')) ?>" class="mt-2" data-confirm="Classes, subjects and students in this department will have no department. Nothing else is deleted." data-confirm-title="Delete <?= e($d['name']) ?>?" data-confirm-ok="Delete">
                        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-ghost btn-sm text-red-600"><?= icon('trash') ?>Delete department</button>
                    </form>
                </details>
            </section>
        <?php endforeach; ?>
    </div>
    <form method="post" action="<?= e(url('/school/departments')) ?>" class="card self-start" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="card-header"><div><p class="eyebrow">New</p><h2 class="card-title mt-1">Add a department</h2></div></div>
        <div class="space-y-4 px-6 pb-6">
            <?= field('name', 'Name', (string) ($old['name'] ?? ''), $errors, ['required' => true, 'placeholder' => 'e.g. Technical']) ?>
            <?= field('code', 'Code', (string) ($old['code'] ?? ''), $errors, ['hint' => 'optional']) ?>
            <?= field('description', 'Description', (string) ($old['description'] ?? ''), $errors, ['hint' => 'optional']) ?>
            <button class="btn btn-primary w-full"><?= icon('plus') ?><span data-label>Add department</span></button>
        </div>
    </form>
</div>
