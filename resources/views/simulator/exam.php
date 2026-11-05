<?php
declare(strict_types=1);
$assetRoot = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') . '/assets' : './assets';
$publicQuestions = array_map(static function (array $question): array {
    return [
        'id' => (int) $question['id'],
        'question_text' => (string) $question['question_text'],
        'option_a' => (string) $question['option_a'],
        'option_b' => (string) $question['option_b'],
        'option_c' => (string) $question['option_c'],
        'option_d' => (string) $question['option_d'],
    ];
}, $questions);
$questionJson = json_encode($publicQuestions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$answerJson = json_encode($answers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$flagJson = json_encode(array_map('intval', $flaggedQuestions ?? []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/pwa-tags.php'; ?>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>"><meta name="theme-color" content="#1e40af">
    <title><?= e($attempt['title']) ?> | <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>"><script defer src="<?= e(asset('js/simulator.js')) ?>"></script>
</head>
<body class="min-h-screen bg-slate-50 text-ink antialiased" data-exam-runner data-attempt-token="<?= e($attemptToken) ?>" data-base-url="<?= e($baseUrl) ?>" data-remaining-seconds="<?= e((string) $remainingSeconds) ?>">
<?php require __DIR__ . '/../partials/exam-header.php'; ?>
<main class="mx-auto max-w-7xl px-4 pb-28 pt-5 sm:px-6 sm:pt-8 md:pb-10 lg:px-8">
    <div class="grid gap-6 md:grid-cols-[minmax(0,7fr)_minmax(250px,3fr)] md:items-start">
        <section class="min-w-0 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-8" aria-labelledby="question-title">
            <div class="flex items-center justify-between gap-3"><p class="text-sm font-bold text-muted" data-question-count>Question 1 of <?= count($questions) ?></p><button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-bold text-slate-700 hover:bg-amber-50 hover:text-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" data-flag aria-pressed="false"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 3v18m0-16h12l-3 5 3 5H6"/></svg><span data-flag-label>Flag</span></button></div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full w-[10%] rounded-full bg-primary transition-[width] duration-200" data-progress></div></div>
            <h1 id="question-title" class="mt-8 max-w-prose font-display text-xl font-bold leading-relaxed text-ink sm:text-2xl" data-question-text></h1>
            <fieldset class="mt-7 grid gap-3" data-options><legend class="sr-only">Choose one answer</legend></fieldset>
            <div class="mt-8 hidden items-center gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900" data-save-error role="alert">Your answer has not been saved. Check your connection before submitting.</div>
            <div class="mt-8 hidden items-center gap-2 text-sm text-muted" data-saving aria-live="polite"><span class="h-2 w-2 animate-pulse rounded-full bg-amber-500"></span><span data-saving-label>Saving…</span></div>
            <div class="mt-8 hidden grid-cols-2 gap-3 border-t border-slate-200 pt-5 md:grid">
                <button type="button" class="min-h-12 rounded-xl border border-slate-300 px-4 font-bold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" data-previous>Previous</button>
                <button type="button" class="min-h-12 rounded-xl bg-primary px-4 font-bold text-white hover:bg-primary-600" data-next>Next question</button>
            </div>
        </section>
        <?php require __DIR__ . '/../partials/exam-palette.php'; ?>
    </div>
</main>
<div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 p-3 shadow-[0_-8px_24px_rgba(15,23,42,.08)] backdrop-blur md:hidden">
    <div class="mx-auto grid max-w-7xl grid-cols-[1fr_auto_1fr] gap-2">
        <button type="button" class="min-h-12 rounded-xl border border-slate-300 px-3 text-sm font-bold text-slate-700 disabled:opacity-40" data-previous>Previous</button>
        <button type="button" class="min-h-12 rounded-xl bg-slate-100 px-3 text-sm font-bold text-ink" data-palette-open aria-controls="question-palette" aria-expanded="false">Questions <span data-mobile-count>(0/<?= count($questions) ?>)</span></button>
        <button type="button" class="min-h-12 rounded-xl bg-primary px-3 text-sm font-bold text-white" data-next data-mobile-next="true">Next</button>
    </div>
</div>
<form class="hidden" action="<?= e($baseUrl) ?>/exam/<?= e($attemptToken) ?>/submit" method="post" data-submit-form><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"></form>
<dialog class="m-auto w-[calc(100%_-_2rem)] max-w-lg rounded-2xl p-0 text-ink shadow-2xl backdrop:bg-slate-950/55" data-submit-dialog aria-labelledby="submit-title">
    <div class="p-6 sm:p-8"><p class="text-sm font-extrabold uppercase tracking-[.13em] text-primary">Check your progress</p><h2 id="submit-title" class="mt-2 font-display text-2xl font-bold">Submit this exam?</h2><p class="mt-3 text-muted">You have answered <strong data-confirm-answered>0</strong> of <?= count($questions) ?>. Unanswered: <strong data-confirm-unanswered><?= count($questions) ?></strong>.</p><div class="mt-7 grid grid-cols-2 gap-3"><button type="button" class="min-h-12 rounded-xl border border-slate-300 px-4 font-bold hover:bg-slate-50" data-cancel-submit>Keep working</button><button type="button" class="min-h-12 rounded-xl bg-primary px-4 font-bold text-white hover:bg-primary-600" data-confirm-submit>Submit now</button></div></div>
</dialog>
<dialog class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-3xl p-0 text-ink shadow-[0_24px_80px_rgba(15,23,42,0.25)] backdrop:bg-slate-950/50" data-submit-success-dialog aria-labelledby="success-title">
    <div class="overflow-hidden rounded-3xl bg-white">
        <div class="bg-gradient-to-r from-emerald-500 via-emerald-600 to-teal-500 px-6 py-7 text-center text-white">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/15 ring-4 ring-white/20 backdrop-blur-sm">
                <svg class="h-8 w-8 animate-[bounce_1.1s_ease-in-out_1]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M5 12.5L9.5 17L19 7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
        <div class="px-6 py-7 text-center sm:px-8">
            <p class="text-xs font-extrabold uppercase tracking-[.2em] text-emerald-600">Success</p>
            <h2 id="success-title" class="mt-2 font-display text-2xl font-bold text-slate-900">Exam Submitted</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600" data-submit-success-message>Your exam has been submitted successfully. Preparing your result dashboard…</p>
            <div class="mt-6 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full w-full origin-left rounded-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 animate-pulse" aria-hidden="true"></div>
            </div>
        </div>
    </div>
</dialog>
<script type="application/json" id="exam-questions"><?= $questionJson ?></script>
<script type="application/json" id="exam-answers"><?= $answerJson ?></script>
<script type="application/json" id="exam-flags"><?= $flagJson ?></script>
</body>
</html>