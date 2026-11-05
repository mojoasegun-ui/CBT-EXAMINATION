<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $class @var array<int,array> $assigned @var list<array> $subjects @var list<array> $teachers @var list<array> $departments
 * @var list<string> $levels @var int $studentCount
 */
$categories = \ExamCore\Models\SchoolSubject::CATEGORIES;
$grouped = [];
foreach ($subjects as $s) {
    $grouped[$s['category']][] = $s;
}
$teacherOptions = array_filter($teachers, static fn ($t) => $t['role'] !== 'reviewer' && $t['status'] === 'active');
?>
<a href="<?= e(url('/school/classes')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Classes</a>
<div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-4">
        <span class="grid h-16 w-16 place-items-center rounded-3xl bg-signature font-display text-2xl font-bold text-brand-ink shadow-[0_14px_30px_-12px_rgb(var(--brand)/.7)]"><?= e($class['arm']) ?></span>
        <div><h1 class="font-display text-[30px] font-semibold text-ink"><?= e($class['name']) ?></h1><p class="text-sm text-muted"><?= $studentCount ?> active student<?= $studentCount === 1 ? '' : 's' ?> · <?= count($assigned) ?> subjects</p></div>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/school/students?class=' . $class['id'])) ?>" class="btn btn-secondary"><?= icon('graduation') ?>View students</a>
        <a href="<?= e(url('/school/students/create?class=' . $class['id'])) ?>" class="btn btn-secondary"><?= icon('plus') ?>Enrol</a>
        <a href="<?= e(url('/school/students/slips?class=' . $class['id'])) ?>" target="_blank" rel="noopener" class="btn btn-secondary"><?= icon('key') ?>Slips</a>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <form method="post" action="<?= e(url('/school/classes/' . $class['id'] . '/subjects')) ?>" class="card lg:col-span-2" data-submit-once>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="card-header"><div><p class="eyebrow">Timetable</p><h2 class="card-title mt-1">Subjects &amp; teachers</h2></div><span class="text-xs text-muted">Tick the subjects offered in <?= e($class['name']) ?></span></div>
        <div class="space-y-6 px-6 pb-6">
            <?php foreach ($grouped as $category => $list): ?>
                <fieldset>
                    <legend class="eyebrow mb-2"><?= e($categories[$category] ?? ucfirst($category)) ?></legend>
                    <div class="divide-y divide-line overflow-hidden rounded-2xl border border-line">
                        <?php foreach ($list as $subject): $on = isset($assigned[(int) $subject['id']]); ?>
                            <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center <?= $on ? 'bg-brand/[.03]' : '' ?>">
                                <label class="flex flex-1 cursor-pointer items-center gap-3 text-sm">
                                    <input type="checkbox" name="subjects[]" value="<?= (int) $subject['id'] ?>" class="checkbox" <?= $on ? 'checked' : '' ?> data-subject-toggle>
                                    <span class="font-medium text-ink"><?= e($subject['name']) ?></span>
                                    <?php if ($subject['code']): ?><span class="font-mono text-xs text-subtle"><?= e($subject['code']) ?></span><?php endif; ?>
                                </label>
                                <select name="teacher[<?= (int) $subject['id'] ?>]" class="select py-2 text-sm sm:w-56" aria-label="Teacher for <?= e($subject['name']) ?>">
                                    <option value="">No teacher yet</option>
                                    <?php foreach ($teacherOptions as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) ($assigned[(int) $subject['id']]['teacher_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>
            <?php if ($subjects === []): ?><p class="text-sm text-muted">No subjects yet. <a class="link" href="<?= e(url('/school/subjects')) ?>">Add subjects</a> first.</p><?php endif; ?>
        </div>
        <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save subjects</span></button></div>
    </form>

    <aside class="space-y-6">
        <form method="post" action="<?= e(url('/school/classes/' . $class['id'])) ?>" class="card" data-submit-once>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <div class="card-header"><h2 class="card-title">Class details</h2></div>
            <div class="space-y-4 px-6 pb-6">
                <?= field('level', 'Level', (string) $class['level'], [], ['type' => 'select', 'options' => array_combine($levels, $levels)]) ?>
                <?= field('arm', 'Arm', (string) $class['arm'], []) ?>
                <?= field('department_id', 'Department', (string) $class['department_id'], [], ['type' => 'select', 'empty' => 'None', 'options' => array_column($departments, 'name', 'id')]) ?>
                <?= field('class_teacher_id', 'Class teacher', (string) $class['class_teacher_id'], [], ['type' => 'select', 'empty' => 'None', 'options' => array_column($teacherOptions, 'name', 'id')]) ?>
                <?= field('capacity', 'Capacity', (string) $class['capacity'], [], ['type' => 'number', 'attrs' => ' min="1" max="999"']) ?>
                <button class="btn btn-secondary w-full"><span data-label>Save details</span></button>
            </div>
        </form>
        <form method="post" action="<?= e(url('/school/classes/' . $class['id'] . '/delete')) ?>" class="card card-body" data-confirm="The class and its subject list are removed. This only works when it has no active students." data-confirm-title="Delete <?= e($class['name']) ?>?" data-confirm-ok="Delete class">
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <p class="text-sm font-semibold text-ink">Delete class</p><p class="mt-1 text-sm text-muted">Only possible when no active students are in it.</p>
            <button class="btn btn-danger-soft mt-4 w-full" <?= $studentCount > 0 ? 'disabled' : '' ?>><?= icon('trash') ?>Delete <?= e($class['name']) ?></button>
        </form>
    </aside>
</div>
