<?php
declare(strict_types=1);
/** @var array $progress @var array $catalog @var array|null $board @var bool $signedIn */
use ExamCore\Services\PracticeService;

$pct = static fn (array $r): int => $r['attempts'] > 0 ? (int) round($r['correct'] * 100 / $r['attempts']) : 0;
$bar = static fn (int $p): string => $p >= 70 ? 'bg-emerald-500' : ($p >= 50 ? 'bg-amber-400' : 'bg-red-500');
$fmtDate = static fn (string $d): string => local_date($d . ' 12:00:00', 'D j M');
$post = static fn (string $csrf, array $fields, string $label): string => '<form method="post" action="' . e(url('/practice/start')) . '"><input type="hidden" name="_csrf_token" value="' . e($csrf) . '"><input type="hidden" name="back" value="/practice/progress">'
    . implode('', array_map(static fn ($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string) $v) . '">', array_keys($fields), $fields)) . '<button class="btn btn-secondary btn-sm" data-submit-once><span data-label>' . e($label) . '</span></button></form>';
?>
<div><p class="eyebrow">My progress</p><h1 class="mt-1 font-display text-[28px] font-semibold text-ink sm:text-[34px]">How you are doing</h1></div>

<?php if ($progress['attempts'] === 0): ?>
    <div class="card empty mt-6"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('chart', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">Nothing to show yet</p><p class="max-w-sm text-sm text-muted">Finish a practice session and your scores, weak topics and streak will appear here.</p><a class="btn btn-primary" href="<?= e(url('/practice')) ?>">Start practising</a></div>
<?php else: ?>
<div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4" data-stagger>
    <div class="card p-4"><p class="text-xs text-muted">Questions answered</p><p class="mt-1 font-display text-2xl font-semibold tabular-nums text-ink"><?= number_format($progress['attempts']) ?></p></div>
    <div class="card p-4"><p class="text-xs text-muted">Accuracy</p><p class="mt-1 font-display text-2xl font-semibold tabular-nums text-ink"><?= (int) $progress['accuracy'] ?>%</p></div>
    <div class="card p-4"><p class="text-xs text-muted">Day streak</p><p class="mt-1 font-display text-2xl font-semibold tabular-nums text-ink"><?= $progress['streak']['current'] ?> <span class="text-sm font-normal text-muted">best <?= $progress['streak']['best'] ?></span></p></div>
    <div class="card p-4"><p class="text-xs text-muted">Mistakes to review</p><p class="mt-1 font-display text-2xl font-semibold tabular-nums text-ink"><?= $progress['mistakes'] ?></p></div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <div class="card-header"><div><p class="eyebrow">Last 14 days</p><h2 class="card-title mt-1">Questions answered per day</h2></div></div>
        <div class="px-6 pb-6"><?php $series = $progress['series']; $chartLabel = 'Questions answered per day, last 14 days'; $unit = 'question'; require __DIR__ . '/../partials/portal/bar-chart.php'; ?></div>
    </section>
    <section class="card">
        <div class="card-header"><div><p class="eyebrow">By subject</p><h2 class="card-title mt-1">Accuracy</h2></div></div>
        <ul class="space-y-3 px-6 pb-6">
            <?php foreach ($progress['subjects'] as $s): $p = $pct($s); ?>
                <li><div class="flex justify-between gap-3 text-sm"><span class="min-w-0 truncate font-medium text-ink"><?= e($s['subject']) ?></span><span class="shrink-0 tabular-nums text-muted"><?= $p ?>% · <?= $s['attempts'] ?></span></div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full <?= $bar($p) ?>" data-width="<?= $p ?>"></span></div></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<div class="mt-6 grid gap-6 md:grid-cols-2">
    <section class="card">
        <div class="card-header"><div><p class="eyebrow">Needs work</p><h2 class="card-title mt-1">Weak topics</h2></div></div>
        <?php if ($progress['weak'] === []): ?><p class="px-6 pb-6 text-sm text-muted">No weak topics yet. Practise a topic at least 5 questions and it will show here if it needs attention.</p><?php else: ?>
            <ul class="divide-y divide-line border-t border-line">
                <?php foreach ($progress['weak'] as $t): ?>
                    <li class="flex items-center gap-3 px-6 py-3"><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-ink"><?= e($t['topic']) ?></p><p class="text-xs text-muted"><?= e($t['subject']) ?> · <?= $pct($t) ?>% of <?= $t['attempts'] ?></p></div><?= $post($csrfToken, ['mode' => 'topic', 'subject' => $t['subject'], 'topic' => $t['topic'], 'count' => 10], 'Practise') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="card">
        <div class="card-header"><div><p class="eyebrow">Going well</p><h2 class="card-title mt-1">Strong topics</h2></div></div>
        <?php if ($progress['strong'] === []): ?><p class="px-6 pb-6 text-sm text-muted">Topics where you score 80% or more will show here.</p><?php else: ?>
            <ul class="divide-y divide-line border-t border-line">
                <?php foreach ($progress['strong'] as $t): ?>
                    <li class="flex items-center justify-between gap-3 px-6 py-3"><div class="min-w-0"><p class="truncate text-sm font-medium text-ink"><?= e($t['topic']) ?></p><p class="text-xs text-muted"><?= e($t['subject']) ?></p></div><span class="badge badge-green shrink-0"><?= $pct($t) ?>%</span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php if ($progress['sessions']): ?>
<section class="card mt-6">
    <div class="card-header"><div><p class="eyebrow">History</p><h2 class="card-title mt-1">Recent sessions</h2></div></div>
    <ul class="divide-y divide-line border-t border-line">
        <?php foreach ($progress['sessions'] as $s): ?>
            <li><a href="<?= e(url('/practice/s/' . $s['token'] . '/result')) ?>" class="flex items-center gap-3 px-6 py-3 hover:bg-soft"><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-ink"><?= e($s['title']) ?></p><p class="text-xs text-muted"><?= e(local_date((string) $s['submitted_at'], 'D j M, g:i a')) ?></p></div>
                <span class="shrink-0 text-sm tabular-nums text-muted"><?= (int) $s['correct_count'] ?>/<?= (int) $s['question_count'] ?></span><span class="badge <?= (float) $s['percent'] >= 70 ? 'badge-green' : ((float) $s['percent'] >= 50 ? 'badge-amber' : 'badge-red') ?> shrink-0"><?= (int) round((float) $s['percent']) ?>%</span></a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
<?php endif; ?>

<section class="mt-6">
    <p class="eyebrow">This week</p><h2 class="mt-1 font-display text-xl font-semibold text-ink">Leaderboard</h2>
    <?php if ($board === null): ?>
        <div class="card mt-4 p-6 text-sm text-muted"><?= icon('users', 'mr-1 inline h-4 w-4') ?>Sign in with your school account to see how you rank against your class and school. <a class="font-semibold text-brand hover:underline" href="<?= e(url('/student/login')) ?>">Student sign-in</a></div>
    <?php else: ?>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <?php foreach ([['Your class' . ($board['className'] ? ' · ' . $board['className'] : ''), $board['class']], ['Your school', $board['school']]] as [$label, $b]): if ($b === null) { continue; } ?>
                <div class="card">
                    <div class="card-header"><div><p class="eyebrow"><?= e($label) ?></p><h3 class="card-title mt-1">Correct answers this week</h3></div></div>
                    <?php if ($b['rows'] === []): ?><p class="px-6 pb-6 text-sm text-muted">Nobody has scored yet this week. Be the first.</p><?php else: ?>
                        <ol class="divide-y divide-line border-t border-line">
                            <?php foreach ($b['rows'] as $r): ?>
                                <li class="flex items-center gap-3 px-6 py-2.5 <?= $r['me'] ? 'bg-brand/[.06]' : '' ?>"><span class="w-6 text-sm font-semibold tabular-nums text-subtle"><?= $r['rank'] ?></span><span class="min-w-0 flex-1 truncate text-sm <?= $r['me'] ? 'font-semibold text-ink' : 'text-ink' ?>"><?= e($r['name']) ?><?= $r['me'] ? ' (you)' : '' ?></span><span class="text-sm font-semibold tabular-nums text-ink"><?= $r['points'] ?></span></li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if (!$b['inTop']): ?><p class="border-t border-line px-6 py-3 text-xs text-muted">You: <b class="text-ink"><?= $b['mine'] ?></b> correct this week</p><?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="mt-3 text-xs text-subtle">Points count each different question you answer correctly in the last 7 days, so repeating the same questions does not add up.</p>
    <?php endif; ?>
</section>
