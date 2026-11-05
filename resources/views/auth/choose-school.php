<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $choices */
$roleLabels = ['school_admin' => 'Administrator', 'teacher' => 'Teacher', 'reviewer' => 'Reviewer'];
?>
<h1 class="font-display text-[28px] font-semibold tracking-tight text-ink">Which school?</h1>
<p class="mt-2 text-muted">Your email is linked to more than one school. Choose where to sign in.</p>
<form method="post" action="<?= e(url('/login/choose')) ?>" class="mt-8 space-y-3">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <?php foreach ($choices as $choice): ?>
        <button type="submit" name="user_id" value="<?= (int) $choice['id'] ?>" class="flex w-full items-center gap-4 rounded-2xl border border-line bg-card p-4 text-left shadow-card transition hover:border-brand hover:shadow-lift">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand/10 font-display font-bold text-brand"><?= e(initials((string) $choice['school_name'])) ?></span>
            <span class="min-w-0 flex-1">
                <span class="block truncate font-semibold text-ink"><?= e($choice['school_name']) ?></span>
                <span class="block text-sm text-muted"><?= e($roleLabels[$choice['role']] ?? $choice['role']) ?></span>
            </span>
            <?= icon('chevron-right', 'h-5 w-5 text-subtle') ?>
        </button>
    <?php endforeach; ?>
</form>
