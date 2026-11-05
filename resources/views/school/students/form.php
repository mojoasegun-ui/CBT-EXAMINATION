<?php
declare(strict_types=1);
/** Enrol form. @var list<array<string,mixed>> $classes @var list<array<string,mixed>> $departments @var int $preselectClass @var array $errors @var array $old */
$v = static fn (string $k, string $d = ''): string => (string) ($old[$k] ?? $d);
$classOptions = array_column($classes, 'name', 'id');
$deptOptions = array_column($departments, 'name', 'id');
?>
<a href="<?= e(url('/school/students')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Students</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">Enrol a student</h1>
<p class="mt-1 text-sm text-muted">A sign-in password is generated automatically. You will see it once, and the student must change it at first sign-in.</p>

<form method="post" action="<?= e(url('/school/students')) ?>" class="mt-6 grid gap-6 lg:grid-cols-3" data-submit-once data-draft="student-new" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <div class="space-y-6 lg:col-span-2">
        <section class="card"><div class="card-header"><h2 class="card-title">Student details</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <?= field('name', 'Full name', $v('name'), $errors, ['class' => 'sm:col-span-2', 'required' => true, 'placeholder' => 'Surname, first name and other names', 'attrs' => ' autofocus maxlength="120"']) ?>
                <?= field('admission_no', 'Admission number', $v('admission_no'), $errors, ['hint' => 'leave blank to generate one', 'placeholder' => 'e.g. KCL/2026/0042', 'attrs' => ' maxlength="40"']) ?>
                <?= field('gender', 'Gender', $v('gender'), $errors, ['type' => 'select', 'empty' => 'Choose', 'options' => ['female' => 'Female', 'male' => 'Male']]) ?>
                <?= field('class_id', 'Class', $v('class_id', $preselectClass > 0 ? (string) $preselectClass : ''), $errors, ['type' => 'select', 'empty' => 'No class yet', 'options' => $classOptions]) ?>
                <?= field('department_id', 'Department', $v('department_id'), $errors, ['type' => 'select', 'empty' => 'None', 'options' => $deptOptions, 'hint' => 'for SS students']) ?>
                <?= field('date_of_birth', 'Date of birth', $v('date_of_birth'), $errors, ['type' => 'date', 'attrs' => ' max="' . date('Y-m-d') . '"']) ?>
                <?= field('admitted_on', 'Admission date', $v('admitted_on'), $errors, ['type' => 'date', 'hint' => 'optional']) ?>
            </div>
        </section>
        <section class="card"><div class="card-header"><h2 class="card-title">Parent or guardian</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <?= field('guardian_name', 'Full name', $v('guardian_name'), $errors, ['placeholder' => 'Mr. Emeka Okafor']) ?>
                <?= field('guardian_phone', 'Phone number', $v('guardian_phone'), $errors, ['type' => 'tel', 'placeholder' => '0803 123 4567', 'attrs' => ' inputmode="tel"']) ?>
                <?= field('guardian_email', 'Email', $v('guardian_email'), $errors, ['type' => 'email', 'hint' => 'for result notifications', 'attrs' => ' inputmode="email"']) ?>
                <?= field('address', 'Home address', $v('address'), $errors) ?>
            </div>
        </section>
    </div>
    <aside class="space-y-4 lg:sticky lg:top-28 lg:self-start">
        <div class="card card-body">
            <span class="icon-tile"><?= icon('key') ?></span>
            <p class="mt-4 font-semibold text-ink">How students sign in</p>
            <p class="mt-1 text-sm leading-6 text-muted">At <span class="font-mono text-ink"><?= e(preg_replace('#^https?://#', '', url('/student/login'))) ?></span> with your school code <span class="font-mono font-semibold text-ink"><?= e($school['slug']) ?></span>, their admission number and the password shown after saving.</p>
        </div>
        <button type="submit" name="after" value="view" class="btn btn-primary btn-lg w-full"><?= icon('check') ?><span data-label>Enrol student</span></button>
        <button type="submit" name="after" value="another" class="btn btn-secondary w-full">Save and add another</button>
        <p class="text-center text-xs text-subtle" data-draft-status></p>
    </aside>
</form>
