<?php
declare(strict_types=1);
?>
<header class="sticky top-0 z-30 border-b border-slate-200 bg-white shadow-sm">
    <?php $timerMinutes = intdiv((int) $remainingSeconds, 60); $timerSeconds = (int) $remainingSeconds % 60; ?>
    <div class="mx-auto grid min-h-[4.5rem] max-w-7xl grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-4 py-2 sm:grid-cols-[auto_minmax(0,1fr)_auto_auto] sm:px-6 lg:px-8">
        <a href="<?= e($baseUrl) ?>/" class="hidden min-h-11 items-center gap-2 rounded-xl font-display font-extrabold text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 sm:inline-flex" aria-label="Exit to practice selector">
            <svg class="h-8 w-8" viewBox="0 0 40 40" aria-hidden="true"><path d="M5 8.5 20 2l15 6.5v13c0 8.4-6.1 13.2-15 16C11.1 34.7 5 29.9 5 21.5v-13Z" fill="currentColor"/><path d="M13 13h14M13 19h14M13 25h8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2.5"/></svg>
            <span>ExamCore <span class="font-semibold text-ink">CBT</span></span>
        </a>
        <div class="min-w-0"><p class="truncate font-display text-sm font-bold text-ink sm:text-base"><?= e($attempt['title']) ?></p><p class="mt-0.5 truncate text-xs text-muted"><?= e($attempt['exam_type_name']) ?> · <?= e($attempt['subject_name']) ?> · <?= e($attempt['year']) ?></p></div>
        <div class="text-center"><p class="mb-1 text-[10px] font-extrabold uppercase tracking-[.13em] text-muted sm:text-xs">Time left</p><div class="rounded-xl bg-slate-100 px-3 py-1 text-center font-mono text-2xl font-extrabold tabular-nums text-ink motion-reduce:animate-none sm:px-5 sm:text-3xl" data-timer data-state="normal" aria-live="off" aria-label="Time remaining"><?= sprintf('%02d:%02d', $timerMinutes, $timerSeconds) ?></div></div>
        <div class="hidden justify-self-end text-right sm:block"><p class="inline-flex items-center gap-2 text-xs font-bold text-emerald-800"><span class="h-2 w-2 rounded-full bg-success"></span> Anonymous session</p><p class="mt-1 text-xs text-slate-500">Attempt #<?= e($attempt['id']) ?></p></div>
    </div>
</header>