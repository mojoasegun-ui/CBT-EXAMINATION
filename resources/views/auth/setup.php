<?php
declare(strict_types=1);
/** @var string $token @var array $errors @var array $old */
$err = static fn (string $f): ?string => $errors[$f] ?? null;
?>
<span class="inline-flex items-center gap-2 rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold uppercase tracking-[.14em] text-brand"><?= icon('sparkles', 'h-3.5 w-3.5') ?> First-time setup</span>
<h1 class="mt-4 font-display text-[28px] font-semibold tracking-tight text-ink">Create the owner account</h1>
<p class="mt-2 text-muted">This account controls every school on the platform. Use a strong, unique password. You will add two-step verification next.</p>

<form method="post" action="<?= e(url('/setup')) ?>" class="mt-8 space-y-5" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <?php foreach ([['name', 'Full name', 'text', 'name'], ['email', 'Email address', 'email', 'username']] as [$f, $label, $type, $ac]): ?>
        <div>
            <label class="label" for="<?= $f ?>"><?= $label ?></label>
            <input class="input <?= $err($f) ? 'input-error' : '' ?>" id="<?= $f ?>" name="<?= $f ?>" type="<?= $type ?>" autocomplete="<?= $ac ?>" required value="<?= e($old[$f] ?? '') ?>">
            <?php if ($err($f)): ?><p class="field-error"><?= icon('alert-circle', 'h-3.5 w-3.5') ?><?= e($err($f)) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div>
        <label class="label" for="password">Password <span class="label-hint">· at least 10 characters</span></label>
        <input class="input <?= $err('password') ? 'input-error' : '' ?>" id="password" name="password" type="password" autocomplete="new-password" required minlength="10">
        <?php if ($err('password')): ?><p class="field-error"><?= icon('alert-circle', 'h-3.5 w-3.5') ?><?= e($err('password')) ?></p><?php endif; ?>
    </div>
    <div>
        <label class="label" for="password_confirmation">Confirm password</label>
        <input class="input <?= $err('password_confirmation') ? 'input-error' : '' ?>" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        <?php if ($err('password_confirmation')): ?><p class="field-error"><?= icon('alert-circle', 'h-3.5 w-3.5') ?><?= e($err('password_confirmation')) ?></p><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-full"><span data-label>Create owner account</span></button>
</form>
