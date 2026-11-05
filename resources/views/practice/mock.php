<?php
declare(strict_types=1);
/** @var array $catalog @var bool $english */
use ExamCore\Services\PracticeService;

$others = array_filter($catalog, static fn ($info, $s) => $s !== 'English Language' && $info['total'] >= 20, ARRAY_FILTER_USE_BOTH);
?>
<div><p class="eyebrow">Timed practice</p><h1 class="mt-1 font-display text-[28px] font-semibold text-ink sm:text-[34px]">Mock exam</h1>
    <p class="mt-1 max-w-xl text-sm leading-6 text-muted">The clock runs, there are no hints, and you see your score and every explanation only at the end, like the real thing.</p></div>

<div class="mt-6 grid gap-5 lg:grid-cols-2">
    <?php if ($english): ?>
    <form method="post" action="<?= e(url('/practice/start')) ?>" class="card p-6" data-submit-once data-pick-form>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="mode" value="mock"><input type="hidden" name="type" value="jamb">
        <span class="icon-tile"><?= icon('library') ?></span>
        <h2 class="mt-4 font-display text-xl font-semibold text-ink">JAMB-style mock</h2>
        <p class="mt-1 text-sm leading-6 text-muted">English Language plus <b>three</b> subjects of your choice · up to 40 questions each · <b>2 hours</b>.</p>
        <p class="label mt-5">Choose 3 subjects <span class="label-hint">· <span data-pick-count>0</span> of 3</span></p>
        <div class="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <?php foreach ($others as $subject => $info): ?>
                <label class="flex items-center gap-2.5 rounded-xl border border-line px-3 py-2.5 text-sm has-[:checked]:border-brand/40 has-[:checked]:bg-brand/5 has-[:disabled]:opacity-50">
                    <input type="checkbox" class="checkbox" name="subjects[]" value="<?= e((string) $subject) ?>" data-pick><span class="min-w-0 flex-1 truncate"><?= e((string) $subject) ?></span><span class="text-xs text-subtle"><?= $info['total'] ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary mt-5 w-full" data-pick-submit disabled><?= icon('arrow-right') ?><span data-label>Start JAMB-style mock</span></button>
    </form>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/practice/start')) ?>" class="card p-6" data-submit-once>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="mode" value="mock"><input type="hidden" name="type" value="single">
        <span class="icon-tile"><?= icon('clock') ?></span>
        <h2 class="mt-4 font-display text-xl font-semibold text-ink">Single-subject exam</h2>
        <p class="mt-1 text-sm leading-6 text-muted">One subject, timed. Good for WAEC and NECO objective papers and for school tests.</p>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <label><span class="label">Subject</span><select class="select" name="subject" required><?php foreach ($catalog as $subject => $info): ?><option value="<?= e((string) $subject) ?>"><?= e((string) $subject) ?> (<?= $info['total'] ?>)</option><?php endforeach; ?></select></label>
            <label><span class="label">Questions</span><select class="select" name="questions"><option value="20">20 questions · ~22 min</option><option value="40" selected>40 questions · ~44 min</option><option value="50">50 questions · ~55 min</option></select></label>
        </div>
        <button class="btn btn-primary mt-5 w-full"><?= icon('arrow-right') ?><span data-label>Start exam</span></button>
    </form>
</div>
<p class="mt-5 text-xs text-subtle">If a subject has fewer questions than you ask for, you get all that it has. Your answers are saved as you go, even if the network drops.</p>
