<?php
declare(strict_types=1);
/** Parent result checker. @var string $slug @var array $errors @var array $old */
$hasError = $errors !== [];
$mode = 'parent';
?>
<h2 class="si-h">Check Your Child's Result</h2>
<p class="si-sub">For parents and guardians. Use the school code, admission number and the result code from the report card.</p>
<?php require __DIR__ . '/../partials/signin-tabs.php'; ?>
<form method="post" action="<?= e(url('/check-result')) ?>" class="si-form" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <label class="si-field"><span class="si-ico"><?= icon('school', 'h-5 w-5') ?></span>
        <input name="school" required autocomplete="off" autocapitalize="none" spellcheck="false" value="<?= e((string) ($old['school'] ?? $slug)) ?>" placeholder="School code (e.g. kings-college)" aria-label="School code"></label>
    <label class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('user', 'h-5 w-5') ?></span>
        <input name="admission_no" required autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?= e((string) ($old['admission_no'] ?? '')) ?>" placeholder="Admission number" aria-label="Admission number"></label>
    <label class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('key', 'h-5 w-5') ?></span>
        <input name="code" required autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="12" placeholder="Result code (8 letters and numbers)" aria-label="Result code"></label>
    <button type="submit" class="si-btn"><span data-label>Show Result</span><?= icon('arrow-right', 'h-5 w-5') ?></button>
</form>
<p class="si-help si-help-2">Student? <a href="<?= e(url('/student/login')) ?>">Sign in here</a></p>
