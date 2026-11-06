<?php
declare(strict_types=1);
/**
 * Practice result page: white, compact score card first, then the questions to review with their explanations.
 * @var array $attempt @var array $questions @var float $score @var float $total @var int|float $percent @var bool $passed
 */
$homeUrl = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') . '/' : './';
$minutesUsed = intdiv((int) $timeUsedSeconds, 60);
$secondsUsed = (int) $timeUsedSeconds % 60;
$optionMap = ['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'];
$correctCount = $wrongCount = $skippedCount = 0;
foreach ($questions as $question) {
    $picked = (string) ($question['selected_option'] ?? '');
    if ($picked === '') {
        $skippedCount++;
    } elseif ($picked === (string) $question['correct_option']) {
        $correctCount++;
    } else {
        $wrongCount++;
    }
}
$questionCount = count($questions);
$reviewCount = $wrongCount + $skippedCount;
$pct = max(0, min(100, (int) round((float) $percent)));
$tone = $passed ? ['text-emerald-600', 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'Well done', 'You passed. Keep that momentum going.']
    : ($pct >= 35 ? ['text-amber-500', 'bg-amber-50 text-amber-700 ring-amber-200', 'Almost there', 'Close to the pass mark. Review the questions below.']
        : ['text-rose-500', 'bg-rose-50 text-rose-700 ring-rose-200', 'Keep going', 'Every mistake below comes with an explanation. Learn it, then retake.']);
$statusText = $passed ? 'Passed' : ($pct >= 35 ? 'Borderline' : 'Needs work');
$first = trim(explode(' ', trim((string) ($attempt['student_name'] ?? '')))[0] ?? '');
$title = (string) ($attempt['title'] ?? 'Practice exam');
$circ = 2 * M_PI * 52;
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <?php require __DIR__ . '/../partials/pwa-tags.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <meta name="theme-color" content="#ffffff">
    <meta name="robots" content="noindex">
    <title>Your result | <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script defer src="<?= e(asset('js/results-modern.js')) ?>"></script>
</head>
<body class="min-h-screen bg-white pb-28 text-ink antialiased sm:pb-10" style="background:#fff">
<header class="sticky top-0 z-30 border-b border-slate-100 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-14 max-w-3xl items-center gap-3 px-4">
        <a href="<?= e($homeUrl) ?>" class="grid h-10 w-10 place-items-center rounded-full text-slate-600 transition hover:bg-slate-100" aria-label="Back to home"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></a>
        <p class="min-w-0 flex-1 truncate font-display text-[15px] font-bold"><?= e($title) ?></p>
        <a href="<?= e($homeUrl) ?>" class="hidden rounded-full bg-primary px-4 py-2 text-sm font-bold text-white sm:inline-flex">Practice again</a>
    </div>
</header>

<main class="mx-auto max-w-3xl px-4 pt-5">
    <!-- Score card: compact, white -->
    <section class="rm-rise rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_12px_40px_-18px_rgba(30,64,175,.25)] sm:p-6" aria-labelledby="score-title">
        <div class="flex items-center gap-4 sm:gap-6">
            <div class="relative h-28 w-28 shrink-0 sm:h-32 sm:w-32" role="img" aria-label="Score <?= $pct ?> percent">
                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                    <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" class="<?= $tone[0] ?>" stroke-width="10" stroke-linecap="round" stroke-dasharray="<?= number_format($circ, 2, '.', '') ?>" stroke-dashoffset="<?= number_format($circ, 2, '.', '') ?>" data-ring="<?= number_format($circ * (1 - $pct / 100), 2, '.', '') ?>"/>
                </svg>
                <div class="absolute inset-0 grid place-items-center text-center"><div><span class="font-display text-3xl font-extrabold leading-none sm:text-4xl" data-count="<?= $pct ?>"><?= $pct ?></span><span class="font-display text-lg font-bold text-slate-400">%</span><span class="mt-0.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-400"><?= e(number_format((float) $score, 0)) ?>/<?= e(number_format((float) $total, 0)) ?></span></div></div>
            </div>
            <div class="min-w-0 flex-1">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?= $tone[1] ?>"><?= e($statusText) ?></span>
                <h1 id="score-title" class="mt-2 font-display text-xl font-extrabold leading-tight sm:text-2xl"><?= e($tone[2]) ?><?= $first !== '' ? ', ' . e($first) : '' ?>!</h1>
                <p class="mt-1 text-sm leading-6 text-slate-500"><?= e($tone[3]) ?></p>
            </div>
        </div>
        <dl class="mt-5 grid grid-cols-4 gap-2 text-center">
            <div class="rounded-2xl bg-emerald-50 px-1 py-3"><dd class="font-display text-xl font-extrabold text-emerald-600"><?= $correctCount ?></dd><dt class="text-[11px] font-semibold text-emerald-700/80">Correct</dt></div>
            <div class="rounded-2xl bg-rose-50 px-1 py-3"><dd class="font-display text-xl font-extrabold text-rose-600"><?= $wrongCount ?></dd><dt class="text-[11px] font-semibold text-rose-700/80">Wrong</dt></div>
            <div class="rounded-2xl bg-amber-50 px-1 py-3"><dd class="font-display text-xl font-extrabold text-amber-600"><?= $skippedCount ?></dd><dt class="text-[11px] font-semibold text-amber-700/80">Skipped</dt></div>
            <div class="rounded-2xl bg-slate-50 px-1 py-3"><dd class="font-display text-xl font-extrabold text-slate-700"><?= sprintf('%d:%02d', $minutesUsed, $secondsUsed) ?></dd><dt class="text-[11px] font-semibold text-slate-500">Time</dt></div>
        </dl>
        <?php if (!empty($attempt['student_email'])): ?>
            <p class="mt-4 text-center text-xs text-slate-400">A copy of this result was sent to <?= e((string) $attempt['student_email']) ?>.</p>
        <?php endif; ?>
    </section>

    <!-- Review -->
    <section class="mt-7" aria-labelledby="review-title" data-review>
        <div class="flex items-end justify-between gap-3">
            <h2 id="review-title" class="font-display text-lg font-extrabold">Review your answers</h2>
            <span class="text-xs font-semibold text-slate-400"><?= $questionCount ?> questions</span>
        </div>
        <div class="mt-3 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]" role="tablist" aria-label="Which questions to show">
            <button type="button" role="tab" class="rm-tab" data-tab="review" aria-selected="<?= $reviewCount > 0 ? 'true' : 'false' ?>">To review <span class="rm-count"><?= $reviewCount ?></span></button>
            <button type="button" role="tab" class="rm-tab" data-tab="all" aria-selected="<?= $reviewCount === 0 ? 'true' : 'false' ?>">All <span class="rm-count"><?= $questionCount ?></span></button>
            <button type="button" role="tab" class="rm-tab" data-tab="correct" aria-selected="false">Correct <span class="rm-count"><?= $correctCount ?></span></button>
        </div>

        <ol class="mt-4 space-y-3">
            <?php foreach ($questions as $i => $q):
                $picked = (string) ($q['selected_option'] ?? '');
                $right = (string) $q['correct_option'];
                $state = $picked === '' ? 'skipped' : ($picked === $right ? 'correct' : 'wrong');
                $badge = ['correct' => 'bg-emerald-500', 'wrong' => 'bg-rose-500', 'skipped' => 'bg-amber-400'][$state];
                $label = ['correct' => 'Correct', 'wrong' => 'Wrong', 'skipped' => 'Skipped'][$state];
                ?>
                <li class="rm-q rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5" data-state="<?= $state ?>">
                    <div class="flex items-start gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-sm font-extrabold text-white <?= $badge ?>"><?= $i + 1 ?></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-[15px] font-semibold leading-7"><?= e((string) $q['question_text']) ?></p>
                            <ul class="mt-3 space-y-2">
                                <?php foreach ($optionMap as $letter => $col):
                                    $isRight = $letter === $right;
                                    $isMine = $letter === $picked && !$isRight;
                                    $cls = $isRight ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : ($isMine ? 'border-rose-300 bg-rose-50 text-rose-900' : 'border-slate-100 bg-slate-50 text-slate-600');
                                    ?>
                                    <li class="flex items-start gap-2.5 rounded-xl border px-3 py-2.5 text-sm leading-6 <?= $cls ?>">
                                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-white text-[11px] font-extrabold ring-1 ring-black/10"><?= $letter ?></span>
                                        <span class="min-w-0 flex-1"><?= e((string) ($q[$col] ?? '')) ?></span>
                                        <?php if ($isRight): ?><span class="shrink-0 text-xs font-bold text-emerald-700">Correct answer</span><?php elseif ($isMine): ?><span class="shrink-0 text-xs font-bold text-rose-700">Your answer</span><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if ($state === 'skipped'): ?><p class="mt-2 text-xs font-semibold text-amber-600">You skipped this question.</p><?php endif; ?>
                            <?php if (trim((string) ($q['explanation'] ?? '')) !== ''): ?>
                                <div class="mt-3 rounded-xl bg-blue-50 px-3.5 py-3 text-sm leading-6 text-slate-700"><span class="font-bold text-primary">Why: </span><?= e((string) $q['explanation']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="rm-empty mt-6 hidden rounded-2xl bg-emerald-50 p-5 text-center text-sm font-semibold text-emerald-700" data-empty>Nothing to review here. Great work!</p>
    </section>
</main>

<!-- Actions: always within thumb reach on phones -->
<div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-100 bg-white/95 px-4 py-3 backdrop-blur sm:static sm:mx-auto sm:mt-8 sm:max-w-3xl sm:border-0 sm:bg-transparent sm:px-4 sm:py-0">
    <div class="mx-auto flex max-w-3xl gap-3">
        <form action="<?= e($baseUrl) ?>/start" method="post" class="flex-1">
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="exam_id" value="<?= e((string) $attempt['exam_id']) ?>">
            <button type="submit" class="min-h-12 w-full rounded-2xl bg-primary px-5 text-[15px] font-bold text-white shadow-lg shadow-blue-900/20 transition active:scale-[.98]">Retake exam</button>
        </form>
        <a href="<?= e($homeUrl) ?>" class="inline-flex min-h-12 flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-[15px] font-bold text-ink transition active:scale-[.98]">New subject</a>
    </div>
</div>
</body>
</html>
