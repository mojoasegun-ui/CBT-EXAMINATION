<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $student @var list<array<string,mixed>> $classes @var list<array<string,mixed>> $departments
 * @var array<string,string>|null $credentials @var array<string,string> $statuses @var array $errors @var array $old
 */
$v = static fn (string $k): string => (string) ($old[$k] ?? $student[$k] ?? '');
$statusBadge = ['active' => 'badge-green', 'suspended' => 'badge-amber', 'graduated' => 'badge-blue', 'withdrawn' => 'badge-gray'];
$age = $student['date_of_birth'] ? (new DateTimeImmutable($student['date_of_birth']))->diff(new DateTimeImmutable('today'))->y : null;
?>
<a href="<?= e(url('/school/students')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Students</a>

<section class="bg-mesh relative mt-3 overflow-hidden rounded-[32px] border border-line p-6 sm:p-8">
    <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center">
        <form method="post" action="<?= e(url('/school/students/' . $student['id'] . '/photo')) ?>" enctype="multipart/form-data" class="group relative shrink-0" data-auto-upload>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <label class="relative block h-28 w-28 cursor-pointer overflow-hidden rounded-[28px] border-4 border-card bg-brand/[.08] shadow-[0_18px_40px_-18px_rgb(24_22_61/.5)]" title="Change photo">
                <?php if ($student['photo_path']): ?>
                    <img src="<?= e(url('/' . $student['photo_path'])) ?>" alt="Photo of <?= e($student['name']) ?>" class="h-full w-full object-cover" data-logo-preview-img>
                <?php else: ?>
                    <span class="grid h-full w-full place-items-center font-display text-3xl font-semibold text-brand"><?= e(initials((string) $student['name'])) ?></span>
                <?php endif; ?>
                <span class="absolute inset-x-0 bottom-0 grid place-items-center bg-black/55 py-1.5 text-[11px] font-semibold text-white opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100"><?= icon('upload', 'h-3.5 w-3.5') ?></span>
                <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" capture="user" class="sr-only" aria-label="Upload a photo">
            </label>
        </form>
        <div class="min-w-0 flex-1">
            <p class="eyebrow">Student</p>
            <h1 class="mt-1 truncate font-display text-[30px] font-semibold text-ink"><?= e($student['name']) ?></h1>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted">
                <span class="badge badge-dot <?= $statusBadge[$student['status']] ?? 'badge-gray' ?>"><?= e(ucfirst((string) $student['status'])) ?></span>
                <span class="font-mono"><?= e($student['admission_no']) ?></span>
                <span>· <?= e($student['class_name'] ?? 'No class') ?></span>
                <?php if ($age !== null): ?><span>· <?= $age ?> years old</span><?php endif; ?>
            </div>
        </div>
        <form method="post" action="<?= e(url('/school/students/' . $student['id'] . '/reset-password')) ?>" data-confirm="The current password stops working immediately and a new one is shown to you once. Any lockout is also cleared." data-confirm-title="Reset this student’s password?" data-confirm-ok="Reset password">
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <button class="btn btn-secondary"><?= icon('key') ?>Reset password</button>
        </form>
    </div>
</section>

<?php if (is_array($credentials)): ?>
    <?php require __DIR__ . '/../../partials/portal/credentials.php'; ?>
<?php endif; ?>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <form method="post" action="<?= e(url('/school/students/' . $student['id'])) ?>" class="space-y-6 lg:col-span-2" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <section class="card"><div class="card-header"><h2 class="card-title">Student details</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <?= field('name', 'Full name', $v('name'), $errors, ['class' => 'sm:col-span-2', 'required' => true]) ?>
                <?= field('admission_no', 'Admission number', $v('admission_no'), $errors, ['hint' => 'used to sign in']) ?>
                <?= field('status', 'Status', $v('status'), $errors, ['type' => 'select', 'options' => $statuses]) ?>
                <?= field('class_id', 'Class', $v('class_id'), $errors, ['type' => 'select', 'empty' => 'No class', 'options' => array_column($classes, 'name', 'id')]) ?>
                <?= field('department_id', 'Department', $v('department_id'), $errors, ['type' => 'select', 'empty' => 'None', 'options' => array_column($departments, 'name', 'id')]) ?>
                <?= field('gender', 'Gender', $v('gender'), $errors, ['type' => 'select', 'empty' => 'Not specified', 'options' => ['female' => 'Female', 'male' => 'Male']]) ?>
                <?= field('date_of_birth', 'Date of birth', $v('date_of_birth'), $errors, ['type' => 'date', 'attrs' => ' max="' . date('Y-m-d') . '"']) ?>
                <?= field('admitted_on', 'Admission date', $v('admitted_on'), $errors, ['type' => 'date']) ?>
            </div>
        </section>
        <section class="card"><div class="card-header"><h2 class="card-title">Parent or guardian</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <?= field('guardian_name', 'Full name', $v('guardian_name'), $errors) ?>
                <?= field('guardian_phone', 'Phone number', $v('guardian_phone'), $errors, ['type' => 'tel', 'attrs' => ' inputmode="tel"']) ?>
                <?= field('guardian_email', 'Email', $v('guardian_email'), $errors, ['type' => 'email']) ?>
                <?= field('address', 'Home address', $v('address'), $errors) ?>
            </div>
            <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save changes</span></button></div>
        </section>
    </form>

    <aside class="space-y-6">
        <section class="card card-body">
            <p class="eyebrow">Account</p>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-muted">School code</dt><dd class="font-mono font-semibold text-ink"><?= e($school['slug']) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">Sign-in name</dt><dd class="font-mono font-semibold text-ink"><?= e($student['admission_no']) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">Password</dt><dd><?= (int) $student['must_change_password'] === 1 ? '<span class="badge badge-amber">Temporary</span>' : '<span class="badge badge-green">Changed by student</span>' ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">Last sign-in</dt><dd class="text-ink"><?= e(time_ago($student['last_login_at'])) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted">Enrolled</dt><dd class="text-ink"><?= e(local_date((string) $student['created_at'], 'j M Y')) ?></dd></div>
            </dl>
        </section>
        <section class="card card-body">
            <span class="icon-soft"><?= icon('scan-face') ?></span>
            <p class="mt-4 font-semibold text-ink">Photo for identity checks</p>
            <p class="mt-1 text-sm leading-6 text-muted">Tap the photo to upload or take one. A clear, front-facing photo lets proctored exams confirm the right student is sitting the paper.</p>
        </section>
        <section class="card card-body">
            <span class="icon-soft"><?= icon('chart') ?></span>
            <p class="mt-4 font-semibold text-ink">Results &amp; parents</p>
            <p class="mt-1 text-sm leading-6 text-muted">Parents check results at <span class="font-mono text-xs"><?= e(preg_replace('~^https?://~', '', url('/check-result'))) ?></span> with the admission number and this code. It is also printed on the report card.</p>
            <p class="mt-3 rounded-2xl bg-soft px-4 py-3 text-center font-mono text-xl font-bold tracking-[.25em] text-ink"><?= e($student['result_code'] ?: '— not issued —') ?></p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a class="btn btn-secondary btn-sm" href="<?= e(url('/school/results/students/' . $student['id'])) ?>"><?= icon('file-text') ?>Report card</a>
                <form method="post" action="<?= e(url('/school/results/students/' . $student['id'] . '/code')) ?>" data-confirm="The old code stops working. Give parents the new one." data-confirm-ok="Issue new code">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-ghost btn-sm"><?= $student['result_code'] ? 'New code' : 'Issue code' ?></button>
                </form>
            </div>
        </section>
    </aside>
</div>
