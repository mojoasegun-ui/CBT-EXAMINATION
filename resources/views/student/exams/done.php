<?php
declare(strict_types=1);
/** @var array $student @var array $exam @var array $sitting @var bool $showScore @var list<array> $breakdown */
$pass = (float) $sitting['percent'] >= (float) $exam['settings']['pass_mark'];
$taken = max(0, strtotime($sitting['submitted_at'] . ' UTC') - strtotime($sitting['started_at'] . ' UTC'));
?>
<div class="mx-auto max-w-2xl">
    <section class="card overflow-hidden text-center">
        <div class="bg-mesh border-b border-line px-6 py-10 sm:px-10">
            <span class="pop-in mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-500 text-white shadow-[0_14px_30px_-10px_rgb(16_185_129/.7)]"><?= icon('check', 'h-8 w-8') ?></span>
            <h1 class="mt-5 font-display text-[28px] font-semibold text-ink"><?= $sitting['status'] === 'auto_submitted' ? 'Time up — your answers were submitted' : 'Exam submitted' ?></h1>
            <p class="mt-2 text-sm text-muted"><?= e($exam['title']) ?> · <?= e(local_date($sitting['submitted_at'], 'D j M, g:i a')) ?></p>
        </div>
        <div class="grid grid-cols-2 divide-x divide-line border-b border-line sm:grid-cols-3">
            <div class="p-5"><p class="font-display text-2xl font-semibold text-ink"><?= (int) $sitting['answered_count'] ?>/<?= (int) $sitting['question_count'] ?></p><p class="text-xs text-muted">answered</p></div>
            <div class="p-5"><p class="font-display text-2xl font-semibold text-ink"><?= intdiv($taken, 60) ?>m <?= $taken % 60 ?>s</p><p class="text-xs text-muted">time used</p></div>
            <?php if ($showScore): ?>
                <div class="col-span-2 border-t border-line p-5 sm:col-span-1 sm:border-t-0"><p class="font-display text-2xl font-semibold <?= $pass ? 'text-emerald-600' : 'text-red-600' ?>"><?= rtrim(rtrim(number_format((float) $sitting['percent'], 1), '0'), '.') ?>%</p><p class="text-xs text-muted"><?= (int) $sitting['score'] ?> correct · <?= $pass ? 'Pass' : 'Below pass mark' ?></p></div>
            <?php endif; ?>
        </div>
        <?php if ($showScore && count($breakdown) > 1): ?>
            <ul class="divide-y divide-line text-left">
                <?php foreach ($breakdown as $b): $pc = $b['total'] > 0 ? round($b['correct'] * 100 / $b['total']) : 0; ?>
                    <li class="flex items-center gap-4 px-6 py-3.5"><span class="w-40 truncate text-sm font-medium text-ink"><?= e($b['subject']) ?></span>
                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-soft"><span class="bar-fill block h-full rounded-full bg-signature" data-width="<?= (int) $pc ?>"></span></span>
                        <span class="w-20 text-right text-sm tabular-nums text-muted"><?= (int) $b['correct'] ?>/<?= (int) $b['total'] ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php elseif (!$showScore): ?>
            <p class="px-6 py-6 text-sm text-muted">Your answers are safely saved. Your score will appear here <?= ($exam['settings']['result_release'] ?? '') === 'manual' ? 'when your school releases the results' : 'when the exam window closes at ' . e(local_date($exam['ends_at'], 'g:i a')) ?>.</p>
        <?php endif; ?>
        <div class="flex justify-center gap-3 p-6"><a href="<?= e(url('/student')) ?>" class="btn btn-primary"><?= icon('dashboard') ?>Back to dashboard</a></div>
    </section>
</div>
<script nonce="<?= e($cspNonce) ?>">try { localStorage.removeItem('xr-<?= (int) $sitting['id'] ?>'); } catch (e) {}</script>
