<?php
declare(strict_types=1);
/** @var string $area @var bool $enabled @var array{secret:string,uri:string,qr:string}|null $setup @var list<string>|null $recoveryCodes @var int $remainingCodes @var bool $required @var array<string,string> $errors */
?>
<div class="mx-auto max-w-3xl">
    <h1 class="font-display text-2xl font-semibold text-ink">Two-step verification</h1>
    <p class="mt-1 text-sm text-muted">After your password, you will also enter a code from an authenticator app on your phone, so a stolen password alone cannot open your account.</p>

    <?php if (is_array($recoveryCodes) && $recoveryCodes !== []): ?>
        <section class="mt-6 rounded-2xl border border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-950/40" aria-labelledby="codes-title">
            <h2 id="codes-title" class="font-display font-semibold text-emerald-950 dark:text-emerald-100">Save your recovery codes</h2>
            <p class="mt-1 text-sm text-emerald-900/80 dark:text-emerald-200/80">If you lose your phone, each code lets you sign in once. Store them somewhere safe (a password manager or printed paper). They will not be shown again.</p>
            <ol class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm font-semibold text-emerald-950 sm:grid-cols-4 dark:text-emerald-50" data-codes>
                <?php foreach ($recoveryCodes as $code): ?><li class="rounded-lg bg-white/80 px-3 py-2 text-center ring-1 ring-emerald-200 dark:bg-emerald-950 dark:ring-emerald-800"><?= e($code) ?></li><?php endforeach; ?>
            </ol>
            <div class="mt-4 flex flex-wrap gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-copy="<?= e(implode("\n", $recoveryCodes)) ?>"><?= icon('copy') ?>Copy all</button>
                <button type="button" class="btn btn-secondary btn-sm" data-download-text="<?= e(implode("\r\n", $recoveryCodes)) ?>" data-filename="<?= e(strtolower(preg_replace('/\W+/', '-', $appName))) ?>-recovery-codes.txt"><?= icon('download') ?>Download</button>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($enabled): ?>
        <section class="card mt-6">
            <div class="card-body flex flex-col gap-4 sm:flex-row sm:items-center">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"><?= icon('shield', 'h-6 w-6') ?></span>
                <div class="flex-1">
                    <p class="font-semibold text-ink">Two-step verification is on</p>
                    <p class="text-sm text-muted"><?= $remainingCodes ?> unused recovery code<?= $remainingCodes === 1 ? '' : 's' ?> left.</p>
                </div>
                <span class="badge badge-green badge-dot">Protected</span>
            </div>
            <?php if ($required): ?>
            <p class="border-t border-line p-5 text-sm text-muted">Two-step verification is required for the Owner Console, so it cannot be turned off here. Keep your recovery codes safe: they let you in if you lose your phone.</p>
            <?php else: ?>
            <form method="post" action="<?= e(url('/' . $area . '/account/two-factor/disable')) ?>" class="flex flex-col gap-3 border-t border-line p-5 sm:flex-row sm:items-end" data-confirm="Your account will be protected by your password only." data-confirm-title="Turn off two-step verification?" data-confirm-ok="Turn off">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <div class="flex-1">
                    <label class="label" for="current_password">Confirm with your password to turn it off</label>
                    <input class="input <?= isset($errors['current_password']) ? 'input-error' : '' ?>" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    <?= field_error($errors, 'current_password') ?>
                </div>
                <button class="btn btn-danger-soft">Turn off</button>
            </form>
            <?php endif; ?>
        </section>
    <?php elseif ($setup !== null): ?>
        <?php if ($required): ?>
            <div class="mt-6 flex gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                <?= icon('lock', 'h-5 w-5 shrink-0') ?><p><span class="font-semibold">Required for the Owner Console.</span> Finish this once and you are done.</p>
            </div>
        <?php endif; ?>
        <section class="card mt-6 overflow-hidden">
            <div class="grid md:grid-cols-[auto_1fr]">
                <div class="flex flex-col items-center justify-center gap-3 border-b border-line bg-soft p-6 md:border-b-0 md:border-r">
                    <img src="<?= e($setup['qr']) ?>" alt="QR code to add <?= e($appName) ?> to your authenticator app" class="h-48 w-48 rounded-xl bg-white p-2 ring-1 ring-line">
                    <p class="text-xs text-muted">Scan with your phone camera</p>
                    <p class="max-w-[12rem] text-center text-[11px] leading-4 text-amber-700 dark:text-amber-300">Set this up before? Delete the old <?= e($appName) ?> entry in your app first. Old codes no longer work.</p>
                </div>
                <div class="p-6">
                    <ol class="space-y-5 text-sm">
                        <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand text-xs font-bold text-brand-ink">1</span><span class="text-ink">Install <strong>Google Authenticator</strong>, <strong>Microsoft Authenticator</strong> or <strong>Authy</strong> on your phone.</span></li>
                        <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand text-xs font-bold text-brand-ink">2</span>
                            <span class="min-w-0 text-ink">Scan the QR code. Can’t scan? Enter this key instead:
                                <span class="mt-2 flex items-center gap-2 rounded-lg bg-canvas px-3 py-2 ring-1 ring-line"><code class="flex-1 break-all font-mono text-[13px] font-semibold tracking-wider"><?= e($setup['secret']) ?></code><button type="button" class="shrink-0 rounded-md p-1 text-muted hover:text-ink" data-copy="<?= e(str_replace(' ', '', $setup['secret'])) ?>" aria-label="Copy key"><?= icon('copy', 'h-4 w-4') ?></button></span>
                            </span></li>
                        <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand text-xs font-bold text-brand-ink">3</span><span class="flex-1 text-ink">Enter the 6-digit code the app shows:
                            <form method="post" action="<?= e(url('/' . $area . '/account/two-factor')) ?>" class="mt-2 flex flex-col gap-2 sm:flex-row" data-submit-once>
                                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                                <input class="input max-w-[12rem] text-center font-mono text-lg tracking-[.35em] <?= isset($errors['code']) ? 'input-error' : '' ?>" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required placeholder="000000" aria-label="6-digit code" autofocus>
                                <button class="btn btn-primary"><span data-label>Verify &amp; turn on</span></button>
                            </form>
                            <?= field_error($errors, 'code') ?>
                        </span></li>
                    </ol>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>
