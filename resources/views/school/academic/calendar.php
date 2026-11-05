<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $sessions @var array{session:?array,term:?array} $current @var array $errors */
$nextYear = $sessions === [] ? (int) date('Y') - ((int) date('n') < 8 ? 1 : 0) : (int) substr((string) $sessions[0]['name'], 0, 4) + 1;
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">Academics</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Academic year</h1>
        <p class="mt-1 text-sm text-muted">Sessions and terms. Exams and results are filed under the current term.</p></div>
    <form method="post" action="<?= e(url('/school/academic/sessions')) ?>" class="flex items-end gap-2" data-submit-once>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <label><span class="label">New session starting</span>
            <select class="select" name="start_year"><?php for ($y = $nextYear - 2; $y <= $nextYear + 1; $y++): ?><option value="<?= $y ?>" <?= $y === $nextYear ? 'selected' : '' ?>><?= $y ?>/<?= $y + 1 ?></option><?php endfor; ?></select></label>
        <button class="btn btn-primary"><?= icon('plus') ?><span data-label>Create</span></button>
    </form>
</div>
<?= field_error($errors, 'start_year') ?>

<?php if ($current['term'] !== null): ?>
    <section class="bg-mesh mt-6 flex flex-col gap-4 overflow-hidden rounded-[28px] border border-line p-6 sm:flex-row sm:items-center">
        <span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('calendar', 'h-6 w-6') ?></span>
        <div><p class="eyebrow">Current term</p><p class="mt-1 font-display text-2xl font-semibold text-ink"><?= e($current['term']['name']) ?> · <?= e($current['term']['session_name']) ?></p>
            <p class="text-sm text-muted"><?= e(local_date(($current['term']['starts_on'] ?? '') . ' 12:00:00', 'j M Y')) ?> – <?= e(local_date(($current['term']['ends_on'] ?? '') . ' 12:00:00', 'j M Y')) ?></p></div>
    </section>
<?php endif; ?>

<div class="mt-6 space-y-6">
    <?php foreach ($sessions as $session): ?>
        <section class="card overflow-hidden">
            <div class="card-header"><div class="flex items-center gap-3"><h2 class="card-title"><?= e($session['name']) ?> session</h2><?php if ($session['is_current']): ?><span class="badge badge-green badge-dot">Current</span><?php endif; ?></div></div>
            <div class="grid gap-px border-t border-line bg-line md:grid-cols-3">
                <?php foreach ($session['terms'] as $term): ?>
                    <div class="bg-card p-5 <?= $term['is_current'] ? 'bg-brand/[.03]' : '' ?>">
                        <div class="flex items-center justify-between gap-2"><p class="font-semibold text-ink"><?= e($term['name']) ?></p>
                            <?php if ($term['is_current']): ?><span class="badge badge-brand">Current</span><?php else: ?>
                                <form method="post" action="<?= e(url('/school/academic/terms/' . $term['id'] . '/current')) ?>"><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-secondary btn-sm">Make current</button></form>
                            <?php endif; ?></div>
                        <form method="post" action="<?= e(url('/school/academic/terms/' . $term['id'])) ?>" class="mt-4 grid grid-cols-2 gap-2" data-submit-once>
                            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                            <label class="text-xs text-muted">Starts<input class="input mt-1 py-2 text-sm" type="date" name="starts_on" value="<?= e($term['starts_on'] ?? '') ?>"></label>
                            <label class="text-xs text-muted">Ends<input class="input mt-1 py-2 text-sm" type="date" name="ends_on" value="<?= e($term['ends_on'] ?? '') ?>"></label>
                            <button class="btn btn-ghost btn-sm col-span-2 justify-self-end"><span data-label>Save dates</span></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <?php if ($sessions === []): ?><div class="card empty"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('calendar', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">No sessions yet</p><p class="text-sm text-muted">Create your current session above; its three terms are added automatically.</p></div><?php endif; ?>
</div>
