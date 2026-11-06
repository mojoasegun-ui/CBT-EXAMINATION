<?php
declare(strict_types=1);
/** @var bool $sent @var array $errors @var array $old */
$hasError = $errors !== [];
?>
<h2 class="si-h">Forgot your password?</h2>
<p class="si-sub">For teachers, school administrators and reviewers. Enter your sign-in email and we will send you a link to choose a new password.</p>
<?php if ($sent): ?>
    <p class="si-note si-ok">Check your inbox. If that email belongs to a staff account, a reset link is on its way. It works once and expires in one hour. Look in Spam if you do not see it.</p>
<?php endif; ?>
<form method="post" action="<?= e(url('/forgot-password')) ?>" class="si-form" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <label class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('mail', 'h-5 w-5') ?></span>
        <input name="email" type="email" inputmode="email" autocomplete="username" required autofocus value="<?= e((string) ($old['email'] ?? '')) ?>" placeholder="Email address" aria-label="Email address"></label>
    <button type="submit" class="si-btn"><span data-label>Send reset link</span><?= icon('arrow-right', 'h-5 w-5') ?></button>
</form>
<p class="si-help">Students: ask your class teacher or school administrator to reset your password.</p>
<p class="si-help si-help-2"><a href="<?= e(url('/login')) ?>">Back to sign in</a></p>
