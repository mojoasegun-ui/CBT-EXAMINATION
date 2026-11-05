<?php
declare(strict_types=1);
/** @var array<string,list<array>> $grouped @var int $total @var array<string,string> $categories @var list<array> $departments @var array $errors @var array $old */
$deptOptions = array_column($departments, 'name', 'id');
?>
<div><p class="eyebrow">Academics</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Subjects</h1>
    <p class="mt-1 text-sm text-muted"><?= $total ?> subjects. Choose which ones each class offers from the class’s page.</p></div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <?php foreach ($categories as $key => $label): if (empty($grouped[$key])) { continue; } ?>
            <section class="card overflow-hidden">
                <div class="card-header"><h2 class="card-title"><?= e($label) ?></h2><span class="badge badge-gray"><?= count($grouped[$key]) ?></span></div>
                <ul class="divide-y divide-line border-t border-line">
                    <?php foreach ($grouped[$key] as $s): ?>
                        <li>
                            <details class="group">
                                <summary class="flex cursor-pointer list-none items-center gap-3 px-6 py-3.5 transition hover:bg-soft">
                                    <span class="icon-soft h-9 w-9 text-xs font-bold"><?= e($s['code'] ?? mb_substr((string) $s['name'], 0, 3)) ?></span>
                                    <span class="min-w-0 flex-1"><span class="block truncate font-medium text-ink <?= $s['is_active'] ? '' : 'line-through opacity-50' ?>"><?= e($s['name']) ?></span>
                                        <span class="block text-xs text-muted"><?= (int) $s['class_count'] ?> class<?= (int) $s['class_count'] === 1 ? '' : 'es' ?><?= $s['department_name'] ? ' · ' . e($s['department_name']) : '' ?></span></span>
                                    <span class="text-subtle transition group-open:rotate-180"><?= icon('chevron-down', 'h-4 w-4') ?></span>
                                </summary>
                                <form method="post" action="<?= e(url('/school/subjects/' . $s['id'])) ?>" class="grid gap-4 bg-soft px-6 py-5 sm:grid-cols-2" data-submit-once>
                                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                                    <?= field('name', 'Name', (string) $s['name'], [], ['class' => 'sm:col-span-2']) ?>
                                    <?= field('code', 'Code', (string) $s['code'], []) ?>
                                    <?= field('category', 'Category', (string) $s['category'], [], ['type' => 'select', 'options' => $categories]) ?>
                                    <?= field('department_id', 'Department', (string) $s['department_id'], [], ['type' => 'select', 'empty' => 'None', 'options' => $deptOptions]) ?>
                                    <label class="flex items-center gap-2.5 self-end pb-3 text-sm"><input type="checkbox" class="checkbox" name="is_active" value="1" <?= $s['is_active'] ? 'checked' : '' ?>>Offered this session</label>
                                    <div class="flex justify-end sm:col-span-2"><button class="btn btn-primary btn-sm"><span data-label>Save</span></button></div>
                                </form>
                            </details>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
        <?php if ($total === 0): ?><div class="card empty"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('book', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">No subjects yet</p><p class="text-sm text-muted">Run Quick setup from the dashboard for the standard Nigerian list, or add your own.</p></div><?php endif; ?>
    </div>
    <form method="post" action="<?= e(url('/school/subjects')) ?>" class="card self-start lg:sticky lg:top-28" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="card-header"><div><p class="eyebrow">New</p><h2 class="card-title mt-1">Add a subject</h2></div></div>
        <div class="space-y-4 px-6 pb-6">
            <?= field('name', 'Name', (string) ($old['name'] ?? ''), $errors, ['required' => true, 'placeholder' => 'e.g. Visual Arts']) ?>
            <?= field('code', 'Code', (string) ($old['code'] ?? ''), $errors, ['hint' => 'optional', 'placeholder' => 'VIS']) ?>
            <?= field('category', 'Category', (string) ($old['category'] ?? 'general'), $errors, ['type' => 'select', 'options' => $categories]) ?>
            <?= field('department_id', 'Department', (string) ($old['department_id'] ?? ''), $errors, ['type' => 'select', 'empty' => 'None', 'options' => $deptOptions]) ?>
            <button class="btn btn-primary w-full"><?= icon('plus') ?><span data-label>Add subject</span></button>
        </div>
    </form>
</div>
