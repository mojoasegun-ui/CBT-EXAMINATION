<?php
declare(strict_types=1);
/** @var array $student @var array $exam @var array $payload @var array $school */
$payload['csrf'] = $csrfToken;
$payload['urls'] = [
    'sync' => url('/student/exams/' . $exam['id'] . '/sync'),
    'submit' => url('/student/exams/' . $exam['id'] . '/submit'),
    'done' => url('/student/exams/' . $exam['id'] . '/done'),
];
$mark = $student['name'] . ' · ' . $student['admission_no'];
?>
<script type="application/json" id="exam-data" nonce="<?= e($cspNonce) ?>"><?= json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>

<div class="watermark" aria-hidden="true"><?php for ($i = 0; $i < 40; $i++): ?><span><?= e($mark) ?></span><?php endfor; ?></div>

<div class="flex min-h-screen flex-col" data-exam-room>
    <!-- Top bar -->
    <header class="sticky top-0 z-30 border-b border-line bg-card/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-[1400px] items-center gap-3 px-3 py-2.5 sm:px-5">
            <div class="hidden min-w-0 items-center gap-3 sm:flex">
                <?php $row = $school; $avatarSize = 'h-9 w-9'; require __DIR__ . '/../../partials/portal/school-avatar.php'; ?>
                <div class="min-w-0"><p class="truncate text-[13px] font-semibold text-ink"><?= e($exam['title']) ?></p><p class="truncate text-xs text-muted"><?= e($student['name']) ?> · <?= e($student['admission_no']) ?></p></div>
            </div>
            <div class="flex flex-1 items-center justify-center">
                <div class="xr-timer" data-timer role="timer" aria-live="off"><?= icon('clock', 'h-4 w-4') ?><span data-timer-text>--:--</span></div>
            </div>
            <div class="flex items-center gap-2">
                <span class="xr-net" data-net title="Connection"><span class="xr-net-dot"></span><span data-net-text class="hidden md:inline">Saved</span></span>
                <?php if (!empty($payload['settings']['calculator'])): ?><button type="button" class="btn btn-secondary btn-icon" data-open="calc" aria-label="Calculator"><?= icon('sliders') ?></button><?php endif; ?>
                <button type="button" class="btn btn-secondary btn-icon hidden sm:inline-flex" data-fullscreen aria-label="Full screen"><?= icon('external') ?></button>
                <button type="button" class="btn btn-primary" data-open="review"><?= icon('check') ?>Submit</button>
            </div>
        </div>
        <div class="xr-progress"><span data-progress></span></div>
        <nav class="mx-auto flex max-w-[1400px] gap-1 overflow-x-auto px-3 pb-2 pt-1 sm:px-5" data-tabs aria-label="Subjects"></nav>
    </header>

    <div class="mx-auto grid w-full max-w-[1400px] flex-1 gap-5 px-3 py-4 sm:px-5 lg:grid-cols-[minmax(0,1fr)_320px]">
        <main class="flex min-w-0 flex-col">
            <article class="card flex flex-1 flex-col p-5 sm:p-8" data-question aria-live="polite">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-muted"><span class="text-ink" data-q-number>Question 1</span> <span data-q-of></span></p>
                    <div class="flex items-center gap-1.5">
                        <button type="button" class="btn btn-ghost btn-sm" data-size="-1" aria-label="Smaller text">A−</button>
                        <button type="button" class="btn btn-ghost btn-sm" data-size="1" aria-label="Larger text">A+</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-flag aria-pressed="false"><?= icon('alert', 'h-4 w-4') ?><span>Flag for review</span></button>
                    </div>
                </div>
                <details class="xr-passage mt-5 hidden" data-passage open><summary class="cursor-pointer text-sm font-semibold text-brand" data-passage-title>Read the passage</summary><div class="xr-rich mt-3 max-h-[40vh] overflow-y-auto rounded-2xl bg-soft p-4 leading-8" data-passage-body></div></details>
                <div class="xr-rich mt-5 text-ink" data-q-body></div>
                <img class="mt-4 hidden max-h-72 w-auto rounded-2xl border border-line" data-q-image alt="Diagram for this question">
                <div class="mt-6 grid gap-3" data-options role="radiogroup" aria-label="Options"></div>
                <div class="mt-auto flex items-center justify-between gap-3 pt-8">
                    <button type="button" class="btn btn-secondary" data-prev><?= icon('chevron-left') ?><span class="hidden sm:inline">Previous</span></button>
                    <button type="button" class="btn btn-ghost btn-sm" data-clear>Clear answer</button>
                    <button type="button" class="btn btn-primary" data-next><span class="hidden sm:inline">Next</span><?= icon('chevron-right') ?></button>
                </div>
            </article>
            <p class="mt-3 hidden text-center text-xs text-subtle lg:block">Keys: <kbd>A</kbd>–<kbd>E</kbd> answer · <kbd>N</kbd> next · <kbd>P</kbd> previous · <kbd>F</kbd> flag</p>
        </main>

        <!-- Palette: sidebar on desktop, bottom sheet on phones -->
        <aside class="xr-sheet" data-sheet>
            <div class="card p-5">
                <div class="flex items-center justify-between"><p class="card-title">Questions</p><button type="button" class="btn btn-ghost btn-icon lg:hidden" data-close-sheet aria-label="Close"><?= icon('x') ?></button></div>
                <div class="mt-3 flex flex-wrap gap-3 text-xs text-muted">
                    <span class="flex items-center gap-1.5"><i class="xr-key xr-key-answered"></i>Answered</span>
                    <span class="flex items-center gap-1.5"><i class="xr-key xr-key-flagged"></i>Flagged</span>
                    <span class="flex items-center gap-1.5"><i class="xr-key"></i>Not answered</span>
                </div>
                <div class="mt-4 max-h-[55vh] space-y-4 overflow-y-auto pr-1" data-palette></div>
                <p class="mt-4 text-sm text-muted"><span class="font-semibold text-ink" data-count-answered>0</span> answered · <span data-count-left>0</span> left</p>
            </div>
        </aside>
    </div>

    <button type="button" class="xr-sheet-toggle lg:hidden" data-open-sheet><?= icon('dashboard', 'h-4 w-4') ?><span data-sheet-label>Questions</span></button>
</div>

<!-- Review & submit -->
<dialog class="xr-dialog" data-dialog="review">
    <div class="p-6 sm:p-7">
        <p class="eyebrow">Before you submit</p>
        <h2 class="mt-1 font-display text-2xl font-semibold text-ink">Review your paper</h2>
        <div class="mt-5 space-y-2" data-review-list></div>
        <p class="mt-4 text-sm text-muted" data-review-note></p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-secondary" data-close>Go back to questions</button>
            <button type="button" class="btn btn-primary" data-submit-final><?= icon('check') ?><span>Submit now</span></button>
        </div>
    </div>
</dialog>

<?php if (!empty($payload['settings']['calculator'])): ?>
<dialog class="xr-dialog xr-calc" data-dialog="calc">
    <div class="p-4">
        <div class="flex items-center justify-between"><p class="text-sm font-semibold text-ink">Calculator</p><button type="button" class="btn btn-ghost btn-icon" data-close aria-label="Close"><?= icon('x') ?></button></div>
        <output class="mt-2 block truncate rounded-2xl bg-soft px-4 py-3 text-right font-mono text-2xl text-ink" data-calc-display>0</output>
        <div class="mt-3 grid grid-cols-4 gap-2" data-calc-keys>
            <?php foreach (['C', '±', '%', '÷', '7', '8', '9', '×', '4', '5', '6', '−', '1', '2', '3', '+', '√', '0', '.', '='] as $k): ?>
                <button type="button" class="xr-calc-key <?= in_array($k, ['÷', '×', '−', '+', '='], true) ? 'xr-calc-op' : '' ?>" data-k="<?= e($k) ?>"><?= e($k) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</dialog>
<?php endif; ?>

<!-- Blocking overlay: time up / submitting / offline at the end -->
<div class="xr-overlay hidden" data-overlay role="alertdialog" aria-modal="true">
    <div class="card max-w-sm p-7 text-center">
        <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-brand/10 text-brand" data-overlay-icon><?= icon('clock', 'h-6 w-6') ?></span>
        <p class="mt-4 font-display text-xl font-semibold text-ink" data-overlay-title>Time is up</p>
        <p class="mt-2 text-sm text-muted" data-overlay-text>Submitting your answers…</p>
    </div>
</div>
<div class="xr-toast hidden" data-xr-toast role="status"></div>

<?php if (($payload['proctor']['mode'] ?? 'off') !== 'off'): ?>
<div class="xr-cam hidden" data-cam title="Your camera is on for this exam"><video autoplay muted playsinline></video><span class="xr-cam-dot"></span></div>
<?php endif; ?>
<div class="xr-overlay hidden" data-gate role="alertdialog" aria-modal="true">
    <div class="card max-w-sm p-7 text-center">
        <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-amber-100 text-amber-600"><?= icon('shield', 'h-6 w-6') ?></span>
        <p class="mt-4 font-display text-xl font-semibold text-ink" data-gate-title></p>
        <p class="mt-2 text-sm text-muted" data-gate-text></p>
        <button type="button" class="btn btn-primary mt-5 w-full" data-gate-btn></button>
    </div>
</div>
