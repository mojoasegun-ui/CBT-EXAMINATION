<?php
declare(strict_types=1);
/** @var string $token @var bool $valid @var array $errors */
$hasError = $errors !== [];
?>
<?php if (!$valid): ?>
    <h2 class="si-h">This link cannot be used</h2>
    <p class="si-sub">It has expired or was already used. Reset links work once and last one hour.</p>
    <a class="si-btn si-btn-link" href="<?= e(url('/forgot-password')) ?>"><span>Request a new link</span><?= icon('arrow-right', 'h-5 w-5') ?></a>
<?php else: ?>
    <h2 class="si-h">Choose a new password</h2>
    <p class="si-sub">Use at least 8 characters with a letter and a number. You will be signed out on other devices.</p>
    <form method="post" action="<?= e(url('/reset-password/' . $token)) ?>" class="si-form" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('lock', 'h-5 w-5') ?></span>
            <input id="password" name="password" type="password" autocomplete="new-password" required autofocus minlength="8" placeholder="New password" aria-label="New password">
            <button type="button" class="si-eye" data-toggle-password="password" aria-label="Show password"><?= icon('eye', 'h-5 w-5') ?></button></div>
        <div class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('lock', 'h-5 w-5') ?></span>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" placeholder="Repeat the new password" aria-label="Repeat the new password"></div>
        <button type="submit" class="si-btn"><span data-label>Save new password</span><?= icon('check', 'h-5 w-5') ?></button>
    </form>
<?php endif; ?>
