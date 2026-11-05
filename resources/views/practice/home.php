<?php
declare(strict_types=1);
/** @var array $catalog @var array $progress @var array<string,array> $bySubject @var array|null $open @var bool $signedIn */
use ExamCore\Services\PracticeService;

$acc = static fn (?array $r): ?int => $r && $r['attempts'] > 0 ? (int) round($r['correct'] * 100 / $r['attempts']) : null;
$streak = $progress['streak']['current'];
$totalQuestions = array_sum(array_column($catalog, 'total'));
$start = static fn (string $csrf, array $fields, string $label, string $class = 'btn btn-secondary btn-sm', string $icon = 'arrow-right') =>
    '<form method="post" action="' . e(url('/practice/start')) . '" class="inline"><input type="hidden" name="_csrf_token" value="' . e($csrf) . '">'
    . implode('', array_map(static fn ($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string) $v) . '">', array_keys($fields), $fields))
    . '<button class="' . $class . '" data-submit-once>' . icon($icon) . '<span data-label>' . e($label) . '</span></button></form>';
?>
<section class="bg-mesh relative overflow-hidden rounded-[28px] border border-line">
    <div class="relative p-6 sm:p-10">
        <p class="eyebrow">Free practice</p>
        <h1 class="mt-3 max-w-2xl font-display text-[30px] font-semibold leading-[1.1] text-ink sm:text-[42px]">Practise by topic. <span class="text-signature">Know what to study next.</span></h1>
        <p class="mt-4 max-w-xl text-[15px] leading-7 text-muted"><?= number_format($totalQuestions) ?> questions across <?= count($catalog) ?> subjects, written to the JAMB, WAEC and NECO syllabus topics. Every answer comes with an explanation.</p>
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <?= $start($csrfToken, ['mode' => 'quick', 'count' => 10, 'back' => '/practice'], 'Quick 10 questions', 'btn btn-primary btn-lg', 'sparkles') ?>
            <a href="<?= e(url('/practice/mock')) ?>" class="btn btn-secondary btn-lg"><?= icon('clock') ?>Mock exam</a>
            <?php if ($progress['mistakes'] > 0): ?><?= $start($csrfToken, ['mode' => 'mistakes', 'count' => 15, 'back' => '/practice'], 'My mistakes (' . $progress['mistakes'] . ')', 'btn btn-ghost btn-lg', 'refresh') ?><?php endif; ?>
        </div>
        <?php if ($streak > 0 || $progress['attempts'] > 0): ?>
            <div class="mt-6 flex flex-wrap gap-2 text-sm">
                <?php if ($streak > 0): ?><span class="badge badge-gold py-1.5"><?= icon('activity', 'h-3.5 w-3.5') ?><?= $streak ?>-day streak</span><?php endif; ?>
                <span class="badge badge-gray py-1.5"><?= number_format($progress['attempts']) ?> answered</span>
                <?php if ($progress['accuracy'] !== null): ?><span class="badge badge-blue py-1.5"><?= (int) $progress['accuracy'] ?>% accuracy</span><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($open): ?>
    <a href="<?= e(url('/practice/s/' . $open['token'])) ?>" class="card card-hover mt-5 flex items-center gap-4 p-5">
        <span class="icon-tile"><?= icon('clock') ?></span>
        <span class="min-w-0 flex-1"><span class="block text-xs font-semibold uppercase tracking-wider text-subtle">Unfinished</span><span class="block truncate font-semibold text-ink">Continue: <?= e($open['title']) ?></span></span>
        <?= icon('arrow-right', 'h-5 w-5 shrink-0 text-muted') ?>
    </a>
<?php endif; ?>

<?php if ($progress['next']): ?>
<section class="mt-8">
    <div class="flex items-end justify-between gap-3"><div><p class="eyebrow">Study next</p><h2 class="mt-1 font-display text-xl font-semibold text-ink">Where a little practice helps most</h2></div></div>
    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <?php foreach ($progress['next'] as $t): $a = $acc($t); ?>
            <div class="card flex flex-col p-5">
                <p class="text-xs font-semibold text-subtle"><?= e($t['subject']) ?></p>
                <p class="mt-1 font-semibold text-ink"><?= e($t['topic']) ?></p>
                <p class="mt-1 text-sm text-muted"><?= $a === null ? 'Not tried yet' : $a . '% correct so far' ?></p>
                <div class="mt-4"><?= $start($csrfToken, ['mode' => 'topic', 'subject' => $t['subject'], 'topic' => $t['topic'], 'count' => 10, 'back' => '/practice'], 'Practise', 'btn btn-secondary btn-sm') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="mt-8">
    <p class="eyebrow">Subjects</p><h2 class="mt-1 font-display text-xl font-semibold text-ink">Choose a subject</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-stagger>
        <?php foreach ($catalog as $subject => $info): $a = $acc($bySubject[$subject] ?? null); ?>
            <a href="<?= e(url('/practice/subject/' . PracticeService::slug((string) $subject))) ?>" class="card card-hover flex flex-col p-6">
                <div class="flex items-start justify-between gap-3"><span class="icon-tile"><?= icon('book') ?></span><?php if ($a !== null): ?><span class="badge <?= $a >= 70 ? 'badge-green' : ($a >= 50 ? 'badge-amber' : 'badge-red') ?>"><?= $a ?>%</span><?php endif; ?></div>
                <h3 class="mt-5 font-display text-lg font-semibold text-ink"><?= e((string) $subject) ?></h3>
                <p class="mt-1 text-sm text-muted"><?= count($info['topics']) ?> topics · <?= number_format($info['total']) ?> questions</p>
                <?php if ($a !== null): ?><div class="mt-4 h-1.5 overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full bg-signature" data-width="<?= $a ?>"></span></div><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if (!$signedIn): ?>
    <p class="mt-8 rounded-2xl border border-line bg-soft p-4 text-sm text-muted">Your progress is saved on this device. <a class="font-semibold text-brand hover:underline" href="<?= e(url('/student/login')) ?>">Sign in as a student</a> to keep it on every phone and join your class leaderboard.</p>
<?php endif; ?>
