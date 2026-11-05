<?php
declare(strict_types=1);
/**
 * Add / edit staff. @var array<string,mixed>|null $staffMember @var array<string,string> $roles @var list<string> $titles
 * @var list<array<string,mixed>> $assignments @var array|null $credentials @var array $errors @var array $old
 */
$isEdit = $staffMember !== null;
$assignments ??= [];
$credentials ??= null;
$v = static fn (string $k, string $d = ''): string => (string) ($old[$k] ?? ($staffMember[$k] ?? $d));
$action = $isEdit ? url('/school/teachers/' . $staffMember['id']) : url('/school/teachers');
?>
<a href="<?= e(url('/school/teachers')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Teachers &amp; staff</a>
<div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div><h1 class="font-display text-[30px] font-semibold text-ink"><?= $isEdit ? e($staffMember['name']) : 'Add staff' ?></h1>
        <p class="mt-1 text-sm text-muted"><?= $isEdit ? 'Last signed in ' . e(time_ago($staffMember['last_login_at'])) . '.' : 'They sign in at ' . e(preg_replace('#^https?://#', '', url('/login'))) . ' with their email and a temporary password shown after saving.' ?></p></div>
    <?php if ($isEdit): ?>
        <div class="flex flex-wrap gap-2">
            <form method="post" action="<?= e(url('/school/teachers/' . $staffMember['id'] . '/reset-password')) ?>" data-confirm="Their current password stops working and a new temporary one is shown once." data-confirm-title="Reset password?" data-confirm-ok="Reset">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-secondary"><?= icon('key') ?>Reset password</button></form>
            <form method="post" action="<?= e(url('/school/teachers/' . $staffMember['id'] . '/toggle')) ?>" <?= $staffMember['status'] === 'active' ? 'data-confirm="They will not be able to sign in. Their questions and history stay." data-confirm-title="Deactivate ' . e($staffMember['name']) . '?" data-confirm-ok="Deactivate"' : '' ?>>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn <?= $staffMember['status'] === 'active' ? 'btn-danger-soft' : 'btn-secondary' ?>"><?= $staffMember['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?></button></form>
        </div>
    <?php endif; ?>
</div>

<?php if (is_array($credentials)): require __DIR__ . '/../../partials/portal/credentials.php'; endif; ?>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <form method="post" action="<?= e($action) ?>" class="card lg:col-span-2" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="card-body grid gap-5 sm:grid-cols-[8rem_1fr]">
            <?= field('title', 'Title', $v('title'), $errors, ['type' => 'select', 'empty' => '—', 'options' => array_combine($titles, $titles)]) ?>
            <?= field('name', 'Full name', $v('name'), $errors, ['required' => true, 'attrs' => $isEdit ? '' : ' autofocus']) ?>
        </div>
        <div class="grid gap-5 px-6 pb-6 sm:grid-cols-2">
            <?= field('email', 'Email (used to sign in)', $v('email'), $errors, ['type' => 'email', 'required' => true]) ?>
            <?= field('phone', 'Phone', $v('phone'), $errors, ['type' => 'tel', 'hint' => 'optional']) ?>
            <?= field('role', 'Role', $v('role', 'teacher'), $errors, ['type' => 'select', 'options' => $roles]) ?>
            <?= field('staff_no', 'Staff number', $v('staff_no'), $errors, ['hint' => 'optional']) ?>
            <?= field('qualification', 'Qualification', $v('qualification'), $errors, ['class' => 'sm:col-span-2', 'placeholder' => 'e.g. B.Sc. (Ed) Mathematics', 'hint' => 'optional']) ?>
        </div>
        <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary"><?= icon('check') ?><span data-label><?= $isEdit ? 'Save changes' : 'Add and create sign-in' ?></span></button></div>
    </form>
    <aside class="card self-start">
        <div class="card-header"><h2 class="card-title">Classes &amp; subjects</h2></div>
        <?php if (!$isEdit): ?>
            <p class="px-6 pb-6 text-sm text-muted">After saving, assign this teacher to subjects from each class’s page.</p>
        <?php elseif ($assignments === []): ?>
            <p class="px-6 pb-6 text-sm text-muted">Not assigned yet. Open a class and choose this teacher for a subject.</p>
            <div class="px-6 pb-6"><a href="<?= e(url('/school/classes')) ?>" class="btn btn-secondary btn-sm">Go to classes</a></div>
        <?php else: ?>
            <ul class="space-y-1 px-3 pb-3">
                <?php foreach ($assignments as $a): ?>
                    <li><a href="<?= e(url('/school/classes/' . $a['class_id'])) ?>" class="flex items-center justify-between gap-3 rounded-2xl px-3 py-2.5 text-sm hover:bg-soft"><span class="font-medium text-ink"><?= e($a['subject_name']) ?></span><span class="text-muted"><?= e($a['class_name']) ?></span></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </aside>
</div>
