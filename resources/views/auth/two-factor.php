<?php
declare(strict_types=1);
/** @var string $title @var mixed $next @var array $errors */
?>
<span class="grid h-12 w-12 place-items-center rounded-2xl bg-brand/10 text-brand"><?= icon('shield', 'h-6 w-6') ?></span>
<h1 class="mt-5 font-display text-[28px] font-semibold tracking-tight text-ink"><?= e($title) ?></h1>
<p class="mt-2 text-muted">Open your authenticator app and enter the 6-digit code for <span class="font-semibold text-ink"><?= e($appName) ?></span>.</p>

<form method="post" action="<?= e(url('/login/two-factor')) ?>" class="mt-8 space-y-5" data-submit-once>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <?php if (is_string($next) && $next !== ''): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif; ?>
    <div>
        <label class="label" for="code">Verification code</label>
        <input class="input text-center font-mono text-2xl tracking-[.5em]" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
               maxlength="9" required autofocus placeholder="••••••" pattern="[0-9A-Za-z\- ]{6,9}">
        <p class="help">No phone? Enter one of your recovery codes instead (e.g. 7KQ2-M9XD).</p>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-full"><span data-label>Verify and continue</span></button>
</form>
<p class="mt-6 text-center text-sm"><a class="link" href="<?= e(url('/login')) ?>">Start over</a></p>
