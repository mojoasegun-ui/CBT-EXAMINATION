<?php
declare(strict_types=1);
/** @var string $title @var string $subtitle @var string $action @var string $area @var array $errors @var array $old @var mixed $next */
$hasError = $errors !== [];
if ($area !== 'console'):
    $mode = 'staff'; ?>
    <h2 class="si-h">Sign In to Your Account</h2>
    <p class="si-sub">Teachers, school administrators and reviewers. Access your dashboard and manage your CBT activities.</p>
    <?php require __DIR__ . '/../partials/signin-tabs.php'; ?>
    <?php if (($_GET['switch'] ?? '') === '1' && !empty($user)): ?>
        <p class="si-note">You are signed in as <strong><?= e($user['name']) ?></strong>, which cannot open that page. Sign in with the right account.</p>
    <?php endif; ?>
    <form method="post" action="<?= e($action) ?>" class="si-form" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <?php if (is_string($next) && $next !== ''): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
        <label class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('user', 'h-5 w-5') ?></span>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="username" required autofocus value="<?= e($old['email'] ?? '') ?>" placeholder="Email address" aria-label="Email address" <?= $hasError ? 'aria-invalid="true"' : '' ?>></label>
        <div class="si-field<?= $hasError ? ' is-error' : '' ?>"><span class="si-ico"><?= icon('lock', 'h-5 w-5') ?></span>
            <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Password" aria-label="Password" <?= $hasError ? 'aria-invalid="true"' : '' ?>>
            <button type="button" class="si-eye" data-toggle-password="password" aria-label="Show password"><?= icon('eye', 'h-5 w-5') ?></button></div>
        <button type="submit" class="si-btn"><span data-label>Sign In</span><?= icon('arrow-right', 'h-5 w-5') ?></button>
    </form>
    <p class="si-help">Forgot your password? Ask your school administrator to reset it.</p>
    <p class="si-help si-help-2">Just want to practise? <a href="<?= e(url('/')) ?>">Free practice, no account</a></p>
<?php else: ?>
<div>
    <p class="eyebrow text-gold">Restricted access</p>
    <h1 class="mt-3 font-display text-[38px] font-semibold leading-[1.08] text-ink"><?= e($title) ?></h1>
    <p class="mt-3 text-[15px] leading-7 text-muted"><?= e($subtitle) ?></p>
</div>

<form method="post" action="<?= e($action) ?>" class="mt-8 space-y-5" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <?php if (is_string($next) && $next !== ''): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>

    <div>
        <label class="label" for="email">Email address</label>
        <input class="input <?= $hasError ? 'input-error' : '' ?>" id="email" name="email" type="email" inputmode="email" autocomplete="username" required autofocus
               value="<?= e($old['email'] ?? '') ?>" placeholder="you@school.edu.ng" <?= $hasError ? 'aria-invalid="true"' : '' ?>>
    </div>

    <div>
        <div class="mb-1.5 flex items-center justify-between">
            <label class="label mb-0" for="password">Password</label>
        </div>
        <div class="relative">
            <input class="input pr-11 <?= $hasError ? 'input-error' : '' ?>" id="password" name="password" type="password" autocomplete="current-password" required <?= $hasError ? 'aria-invalid="true"' : '' ?>>
            <button type="button" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-subtle hover:text-ink" data-toggle-password="password" aria-label="Show password"><?= icon('eye', 'h-[18px] w-[18px]') ?></button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-full">
        <span data-label>Sign in</span><?= icon('arrow-right') ?>
    </button>
</form>
<p class="mt-6 text-center text-xs text-subtle">Lost access to your authenticator? Use one of your recovery codes on the next screen.</p>
<?php endif; ?>
