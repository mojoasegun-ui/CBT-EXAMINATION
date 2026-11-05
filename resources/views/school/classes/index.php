<?php
declare(strict_types=1);
/** @var array<string,list<array<string,mixed>>> $byLevel @var int $total @var int $students @var list<string> $levels @var list<array> $departments @var list<array> $teachers @var array $errors @var array $old */
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">Academics</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Classes</h1>
        <p class="mt-1 text-sm text-muted"><?= $total ?> class<?= $total === 1 ? '' : 'es' ?> · <?= number_format($students) ?> active students</p></div>
    <a href="<?= e(url('/school/classes/promote')) ?>" class="btn btn-secondary"><?= icon('arrow-right') ?>End-of-session promotion</a>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <?php if ($byLevel === []): ?>
            <div class="card empty"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('school', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">No classes yet</p><p class="text-sm text-muted">Add your first class with the form, or run Quick setup from the dashboard.</p></div>
        <?php endif; ?>
        <?php foreach ($byLevel as $level => $arms): ?>
            <section>
                <h2 class="eyebrow mb-3"><?= e($level) ?></h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <?php foreach ($arms as $class): ?>
                        <a href="<?= e(url('/school/classes/' . $class['id'])) ?>" class="card card-hover group flex items-center gap-4 p-5 <?= $class['is_active'] ? '' : 'opacity-60' ?>">
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-signature font-display text-lg font-bold text-brand-ink"><?= e($class['arm']) ?></span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-display text-[17px] font-semibold text-ink"><?= e($class['name']) ?></span>
                                <span class="block truncate text-sm text-muted"><?= e($class['class_teacher_name'] ?? 'No class teacher') ?><?= $class['department_name'] ? ' · ' . e($class['department_name']) : '' ?></span>
                                <span class="mt-2 flex gap-2"><span class="badge badge-brand"><?= (int) $class['student_count'] ?><?= $class['capacity'] ? '/' . (int) $class['capacity'] : '' ?> students</span><span class="badge badge-gray"><?= (int) $class['subject_count'] ?> subjects</span></span>
                            </span>
                            <span class="text-subtle transition group-hover:translate-x-0.5 group-hover:text-ink"><?= icon('chevron-right', 'h-4 w-4') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <form method="post" action="<?= e(url('/school/classes')) ?>" class="card self-start lg:sticky lg:top-28" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="card-header"><div><p class="eyebrow">New</p><h2 class="card-title mt-1">Add classes</h2></div></div>
        <div class="space-y-5 px-6 pb-6">
            <?= field('level', 'Level', (string) ($old['level'] ?? 'JSS 1'), $errors, ['type' => 'select', 'options' => array_combine($levels, $levels)]) ?>
            <?= field('arm', 'Arms', (string) ($old['arm'] ?? 'A'), $errors, ['hint' => 'comma-separated', 'placeholder' => 'A, B, C']) ?>
            <?= field('department_id', 'Department', (string) ($old['department_id'] ?? ''), $errors, ['type' => 'select', 'empty' => 'None', 'options' => array_column($departments, 'name', 'id')]) ?>
            <?= field('class_teacher_id', 'Class teacher', (string) ($old['class_teacher_id'] ?? ''), $errors, ['type' => 'select', 'empty' => 'Assign later', 'options' => array_column(array_filter($teachers, static fn ($t) => $t['role'] !== 'reviewer'), 'name', 'id')]) ?>
            <?= field('capacity', 'Capacity', (string) ($old['capacity'] ?? ''), $errors, ['type' => 'number', 'hint' => 'optional', 'attrs' => ' min="1" max="999"']) ?>
            <button class="btn btn-primary w-full"><?= icon('plus') ?><span data-label>Add classes</span></button>
        </div>
    </form>
</div>
