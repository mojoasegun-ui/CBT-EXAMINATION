<?php
declare(strict_types=1);
/** One-time sign-in details card. @var array{name?:string,email?:string,admission_no?:string,password:string} $credentials */
$isStudent = isset($credentials['admission_no']);
$items = $isStudent
    ? [['School code', $school['slug'] ?? ''], ['Admission no.', $credentials['admission_no']], ['Password', $credentials['password']]]
    : [['Sign-in page', url('/login')], ['Email', $credentials['email'] ?? ''], ['Temporary password', $credentials['password']]];
?>
<section class="mt-6 overflow-hidden rounded-[28px] border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white dark:border-emerald-900 dark:from-emerald-950/50 dark:to-transparent" aria-labelledby="cred-title">
    <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-start">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-emerald-600 text-white shadow-[0_10px_20px_-8px_rgb(5_150_105/.7)]"><?= icon('key', 'h-5 w-5') ?></span>
        <div class="flex-1">
            <h2 id="cred-title" class="font-display text-lg font-semibold text-emerald-950 dark:text-emerald-100">Sign-in details for <?= e($credentials['name'] ?? 'this account') ?></h2>
            <p class="mt-1 text-sm text-emerald-900/75 dark:text-emerald-200/80">Shown once and not stored. They must choose a new password at first sign-in<?= $isStudent ? ' at ' . e(preg_replace('#^https?://#', '', url('/student/login'))) : '' ?>.</p>
            <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                <?php foreach ($items as [$label, $value]): ?>
                    <div class="rounded-2xl bg-white/90 p-3 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:ring-emerald-800">
                        <dt class="text-xs font-medium text-emerald-800 dark:text-emerald-300"><?= e($label) ?></dt>
                        <dd class="mt-1 flex items-center justify-between gap-2"><span class="truncate font-mono text-sm font-semibold text-emerald-950 dark:text-emerald-50"><?= e($value) ?></span>
                            <button type="button" class="shrink-0 rounded-lg p-1.5 text-emerald-700 hover:bg-emerald-100 dark:text-emerald-300 dark:hover:bg-emerald-900" data-copy="<?= e($value) ?>" aria-label="Copy <?= e(strtolower($label)) ?>"><?= icon('copy', 'h-4 w-4') ?></button></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
</section>
