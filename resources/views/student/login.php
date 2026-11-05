<?php
declare(strict_types=1);
/** @var string $schoolCode @var array $errors @var array $old */
$hasError = $errors !== [];
?>
<div>
    <p class="eyebrow">Students</p>
    <h1 class="mt-3 font-display text-[38px] font-semibold leading-[1.08] text-ink">Sign in to your exams</h1>
    <p class="mt-3 text-[15px] leading-7 text-muted">Use the details on the sign-in slip from your school.</p>
</div>
<form method="post" action="<?= e(url('/student/login')) ?>" class="mt-8 space-y-5" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <div>
        <label class="label" for="school">School code</label>
        <input class="input font-mono lowercase" id="school" name="school" required autocomplete="organization" autocapitalize="none" spellcheck="false" value="<?= e($schoolCode) ?>" placeholder="e.g. kings-college" <?= $schoolCode === '' ? 'autofocus' : '' ?>>
    </div>
    <div>
        <label class="label" for="admission_no">Admission number</label>
        <input class="input font-mono uppercase <?= $hasError ? 'input-error' : '' ?>" id="admission_no" name="admission_no" required autocomplete="username" autocapitalize="characters" spellcheck="false" value="<?= e($old['admission_no'] ?? '') ?>" placeholder="KCL/2026/0042" <?= $schoolCode !== '' ? 'autofocus' : '' ?>>
    </div>
    <div>
        <label class="label" for="password">Password</label>
        <div class="relative">
            <input class="input pr-11 <?= $hasError ? 'input-error' : '' ?>" id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="button" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-subtle hover:text-ink" data-toggle-password="password" aria-label="Show password"><?= icon('eye', 'h-[18px] w-[18px]') ?></button>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-full"><span data-label>Sign in</span><?= icon('arrow-right') ?></button>
</form>
<p class="mt-6 text-center text-sm text-muted">Forgot your password? Ask your class teacher or school administrator to reset it.</p>
<p class="mt-2 text-center text-xs text-subtle">Staff? <a class="link" href="<?= e(url('/login')) ?>">Sign in here</a> · Just practising? <a class="link" href="<?= e(url('/')) ?>">Free practice</a></p>
