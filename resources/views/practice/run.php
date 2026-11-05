<?php
declare(strict_types=1);
/** The question screen. @var array $payload @var array $session */
$learning = $payload['learning'];
?>
<script type="application/json" id="pc-data" nonce="<?= e($cspNonce) ?>"><?= json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<div class="flex min-h-screen flex-col" data-practice>
    <header class="sticky top-0 z-30 border-b border-line bg-card/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-5xl items-center gap-2 px-3 py-2.5 sm:gap-3 sm:px-5">
            <a href="<?= e(url('/practice')) ?>" class="btn btn-ghost btn-icon shrink-0" aria-label="Leave (your answers are saved)" data-leave><?= icon('arrow-left', 'h-4 w-4') ?></a>
            <p class="min-w-0 flex-1 truncate text-sm font-semibold text-ink"><?= e($session['title']) ?></p>
            <?php if ($payload['deadline'] !== null): ?><div class="xr-timer shrink-0" data-timer role="timer"><?= icon('clock', 'h-4 w-4') ?><span data-timer-text>--:--</span></div><?php endif; ?>
            <span class="xr-net shrink-0" data-net><span class="xr-net-dot"></span><span data-net-text class="hidden sm:inline">Saved</span></span>
            <button type="button" class="btn btn-primary btn-sm shrink-0" data-finish><?= icon('check', 'h-4 w-4') ?><span>Finish</span></button>
        </div>
        <div class="xr-progress"><span data-progress></span></div>
    </header>

    <div class="mx-auto grid w-full max-w-5xl flex-1 gap-5 px-3 pb-24 pt-4 sm:px-5 lg:pb-4 lg:grid-cols-[minmax(0,1fr)_280px]">
        <main class="flex min-w-0 flex-col">
            <article class="card flex flex-1 flex-col p-5 sm:p-8" aria-live="polite">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-muted"><span class="text-ink" data-q-number>Question 1</span> <span data-q-of></span></p>
                    <span class="badge badge-gray max-w-full truncate" data-q-topic></span>
                </div>
                <div class="xr-rich mt-5 text-ink" data-q-body></div>
                <img class="mt-4 hidden max-h-72 w-auto max-w-full rounded-2xl border border-line" data-q-image alt="Diagram for this question">
                <div class="mt-6 grid gap-3" data-options role="radiogroup" aria-label="Options"></div>
                <?php if ($learning): ?>
                    <div class="mt-5 hidden rounded-2xl border p-4 text-sm leading-7" data-feedback role="status"><p class="font-semibold" data-feedback-title></p><div class="xr-rich mt-1 text-ink" data-feedback-text></div></div>
                <?php endif; ?>
                <div class="mt-auto flex items-center justify-between gap-3 pt-8">
                    <button type="button" class="btn btn-secondary" data-prev><?= icon('chevron-left') ?><span class="hidden sm:inline">Previous</span></button>
                    <?php if (!$learning): ?><button type="button" class="btn btn-ghost btn-sm" data-clear>Clear answer</button><?php endif; ?>
                    <button type="button" class="btn btn-primary" data-next><span>Next</span><?= icon('chevron-right') ?></button>
                </div>
            </article>
            <p class="mt-3 hidden text-center text-xs text-subtle lg:block">Keys: <kbd>A</kbd>–<kbd>E</kbd> answer · <kbd>N</kbd> next · <kbd>P</kbd> previous</p>
        </main>
        <aside class="xr-sheet" data-sheet>
            <div class="card p-5">
                <div class="flex items-center justify-between"><p class="card-title">Questions</p><button type="button" class="btn btn-ghost btn-icon lg:hidden" data-close-sheet aria-label="Close"><?= icon('x') ?></button></div>
                <div class="mt-4 max-h-[55vh] overflow-y-auto pr-1"><div class="xr-pal" data-palette></div></div>
                <p class="mt-4 text-sm text-muted"><span class="font-semibold text-ink" data-count-answered>0</span> answered · <span data-count-left>0</span> left</p>
            </div>
        </aside>
    </div>
    <button type="button" class="xr-sheet-toggle lg:hidden" data-open-sheet><?= icon('dashboard', 'h-4 w-4') ?><span data-sheet-label>Questions</span></button>
</div>

<dialog class="xr-dialog" data-dialog="finish">
    <div class="p-6 sm:p-7">
        <p class="eyebrow"><?= $learning ? 'Finish practice' : 'Before you submit' ?></p>
        <h2 class="mt-1 font-display text-2xl font-semibold text-ink" data-finish-title>Finish now?</h2>
        <p class="mt-3 text-sm leading-6 text-muted" data-finish-note></p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-secondary" data-close>Keep going</button>
            <button type="button" class="btn btn-primary" data-finish-final><?= icon('check') ?><span>See my result</span></button>
        </div>
    </div>
</dialog>
<div class="xr-overlay hidden" data-overlay role="alertdialog" aria-modal="true">
    <div class="card max-w-sm p-7 text-center"><p class="font-display text-xl font-semibold text-ink" data-overlay-title>Submitting</p><p class="mt-2 text-sm text-muted" data-overlay-text>Saving your answers…</p></div>
</div>
<div class="xr-toast hidden" data-xr-toast role="status"></div>
