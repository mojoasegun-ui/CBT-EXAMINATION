<?php
declare(strict_types=1);
/** @var string $schoolCode @var array $errors @var array $old */
$hasError = $errors !== [];
$mode = 'student';
?>
<h2 class="si-h">Sign In to Your Exams</h2>
<p class="si-sub">Use the details on the sign-in slip from your school.</p>
<?php require __DIR__ . '/../partials/signin-tabs.php'; ?>
<form method="post" action="<?= e(url('/student/login')) ?>" class="si-form" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <label class="si-field"><span class="si-ico"><?= icon('school', 'h-5 w-5') ?></span>
        <input id="school" name="school" required autocomplete="organization" autocapitalize="none" spellcheck="false" value="<?= e($schoolCode) ?>" placeholder="School code (e.g. kings-college)" aria-label="School code"></label>
    <label class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('user', 'h-5 w-5') ?></span>
        <input id="admission_no" name="admission_no" required autocomplete="username" autocapitalize="characters" spellcheck="false" value="<?= e((string) ($old['admission_no'] ?? '')) ?>" placeholder="Admission number" aria-label="Admission number" <?= $hasError ? 'aria-invalid="true"' : '' ?>></label>
    <div class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('lock', 'h-5 w-5') ?></span>
        <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Password" aria-label="Password">
        <button type="button" class="si-eye" data-toggle-password="password" aria-label="Show password"><?= icon('eye', 'h-5 w-5') ?></button></div>
    <button type="submit" class="si-btn"><span data-label>Sign In</span><?= icon('arrow-right', 'h-5 w-5') ?></button>
</form>
<p class="si-help">Forgot your password? Ask your class teacher or school administrator to reset it.</p>
<p class="si-help si-help-2">Just practising? <a href="<?= e(url('/')) ?>">Free practice, no account</a></p>
