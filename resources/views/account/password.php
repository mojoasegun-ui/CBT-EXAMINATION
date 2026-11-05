<?php
declare(strict_types=1);
/** @var string $area @var bool $forced @var array<string,string> $errors */
$min = ($user['role'] ?? '') === 'owner' ? 10 : 8;
?>
<div class="mx-auto max-w-xl">
    <?php if ($forced): ?>
        <div class="mb-6 flex gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
            <?= icon('key', 'h-5 w-5 shrink-0') ?>
            <p><span class="font-semibold">Choose your own password to continue.</span> You signed in with a temporary password that was shared with you.</p>
        </div>
    <?php endif; ?>
    <h1 class="font-display text-2xl font-semibold text-ink"><?= $forced ? 'Set a new password' : 'Change password' ?></h1>
    <p class="mt-1 text-sm text-muted">Changing your password signs you out on every other device.</p>

    <form method="post" action="<?= e(url('/' . $area . '/account/password')) ?>" class="card card-body mt-6 space-y-5" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div>
            <label class="label" for="current_password"><?= $forced ? 'Temporary password' : 'Current password' ?></label>
            <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        </div>
        <div>
            <label class="label" for="password">New password <span class="label-hint">· at least <?= $min ?> characters, with a letter and a number</span></label>
            <div class="relative">
                <input class="input pr-11 <?= isset($errors['password']) ? 'input-error' : '' ?>" id="password" name="password" type="password" autocomplete="new-password" required minlength="<?= $min ?>" data-strength>
                <button type="button" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-subtle hover:text-ink" data-toggle-password="password" aria-label="Show password"><?= icon('eye', 'h-[18px] w-[18px]') ?></button>
            </div>
            <div class="mt-2 grid grid-cols-4 gap-1.5" aria-hidden="true" data-strength-bars><span class="h-1.5 rounded-full bg-ink/10"></span><span class="h-1.5 rounded-full bg-ink/10"></span><span class="h-1.5 rounded-full bg-ink/10"></span><span class="h-1.5 rounded-full bg-ink/10"></span></div>
            <p class="help" data-strength-label aria-live="polite">Tip: a short phrase like “Mango-Bus-Lagos-27” is strong and easy to remember.</p>
            <?= field_error($errors, 'password') ?>
        </div>
        <div>
            <label class="label" for="password_confirmation">Confirm new password</label>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button class="btn btn-primary w-full sm:w-auto"><?= icon('check') ?><span data-label>Update password</span></button>
    </form>
</div>
