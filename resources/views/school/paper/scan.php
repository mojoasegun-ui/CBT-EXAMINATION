<?php
declare(strict_types=1);
/** Phone scanner for shading sheets. @var array $exam @var array $config */
?>
<script type="application/json" id="omr-config" nonce="<?= e($cspNonce) ?>"><?= json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<a href="<?= e(url('/school/exams/' . $exam['id'] . '/paper')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Paper exam</a>
<div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">Scan sheets</p><h1 class="mt-1 font-display text-[28px] font-semibold text-ink"><?= e($exam['title']) ?></h1></div>
    <p class="text-sm text-muted"><span class="font-semibold text-ink" data-omr-count>0</span> saved this session · <span data-omr-queue>0</span> waiting for network</p>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-[1.2fr_1fr]">
    <section class="card p-5">
        <label class="btn btn-primary btn-lg w-full cursor-pointer" data-omr-pick>
            <?= icon('scan-face') ?><span>Snap a sheet</span>
            <input type="file" accept="image/*" capture="environment" class="sr-only" data-omr-file>
        </label>
        <p class="mt-3 text-xs text-muted">Lay the sheet flat in good light. Fit all <b>four black corner squares</b> in the photo and hold the phone straight above it.</p>
        <div class="relative mt-4 overflow-hidden rounded-2xl bg-soft">
            <canvas class="block w-full" data-omr-canvas></canvas>
            <p class="absolute inset-0 grid place-items-center p-6 text-center text-sm text-muted" data-omr-placeholder>The photo of the sheet appears here with every detected answer marked.</p>
        </div>
    </section>

    <section class="card flex flex-col p-5" data-omr-result>
        <p class="eyebrow">Result</p>
        <p class="mt-2 text-sm text-muted" data-omr-status>Waiting for a photo…</p>
        <div class="mt-3 hidden" data-omr-student>
            <p class="font-display text-xl font-semibold text-ink" data-omr-name></p>
            <p class="text-sm text-muted" data-omr-meta></p>
        </div>
        <div class="mt-4 hidden" data-omr-review>
            <p class="text-sm font-semibold text-amber-700">Please check these questions:</p>
            <div class="mt-2 space-y-2" data-omr-uncertain></div>
        </div>
        <div class="mt-4 hidden grid grid-cols-5 gap-1.5 text-xs tabular-nums sm:grid-cols-8" data-omr-answers></div>
        <div class="mt-4 hidden" data-omr-theory-box>
            <label class="label" for="omr-theory">Theory score</label>
            <input id="omr-theory" class="input w-28" type="number" inputmode="decimal" step="0.5" min="0" data-omr-theory>
        </div>
        <div class="mt-auto flex flex-wrap gap-2 pt-5">
            <button type="button" class="btn btn-primary flex-1 hidden" data-omr-save><?= icon('check') ?><span>Save &amp; next sheet</span></button>
            <button type="button" class="btn btn-secondary hidden" data-omr-retry>Retake photo</button>
        </div>
    </section>
</div>

<section class="card mt-5 hidden" data-omr-log-box>
    <div class="card-header"><p class="card-title">Saved</p></div>
    <ul class="divide-y divide-line border-t border-line text-sm" data-omr-log></ul>
</section>
<script defer src="<?= e(asset('js/omr-scan.js')) ?>"></script>
