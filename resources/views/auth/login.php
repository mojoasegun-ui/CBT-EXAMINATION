<?php
declare(strict_types=1);
/** @var string $title @var string $subtitle @var string $action @var string $area @var array $errors @var array $old @var mixed $next */
$hasError = $errors !== [];
?>
<div>
    <p class="eyebrow <?= $area === 'console' ? 'text-gold' : '' ?>"><?= $area === 'console' ? 'Restricted access' : 'Welcome back' ?></p>
    <h1 class="mt-3 font-display text-[38px] font-semibold leading-[1.08] text-ink"><?= e($title) ?></h1>
    <p class="mt-3 text-[15px] leading-7 text-muted"><?= e($subtitle) ?></p>
    <?php if (($_GET['switch'] ?? '') === '1' && !empty($user)): ?>
        <p class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">You are signed in as <strong><?= e($user['name']) ?></strong>, which cannot open that page. Sign in with the right account to continue.</p>
    <?php endif; ?>
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

<?php if ($area === 'school'): ?>
    <div class="mt-8 rounded-3xl border border-line bg-soft p-5 text-sm leading-6 text-muted">
        <p><span class="font-semibold text-ink">Student?</span> Your school will share your exam link and sign-in details. You can also <a class="link" href="<?= e(url('/')) ?>">practise past questions</a> without an account.</p>
    </div>
    <p class="mt-4 text-center text-xs text-subtle">Forgot your password? Ask your school administrator to reset it.</p>
<?php else: ?>
    <p class="mt-6 text-center text-xs text-subtle">Lost access to your authenticator? Use one of your recovery codes on the next screen.</p>
<?php endif; ?>
