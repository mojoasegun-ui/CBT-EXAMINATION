<?php
declare(strict_types=1);
/** @var array<string,mixed> $exam @var list<array> $checks @var string $state @var array<string,int> $classNames */
use ExamCore\Models\SchoolExam;

$ready = !in_array(false, array_column($checks, 'ok'), true);
$s = $exam['settings'];
$stateBadge = ['draft' => ['badge-gray', 'Draft'], 'upcoming' => ['badge-blue', 'Scheduled'], 'live' => ['badge-green', 'Live now'], 'closed' => ['badge-gray', 'Closed'], 'cancelled' => ['badge-red', 'Cancelled']][$state];
$act = static fn (string $action, string $label, string $class, string $confirm = '') => '<form method="post" action="' . e(url('/school/exams/' . $exam['id'] . '/action')) . '"' . ($confirm ? ' data-confirm="' . e($confirm) . '" data-confirm-ok="' . e($label) . '"' : '') . '><input type="hidden" name="_csrf_token" value="' . e($csrfToken) . '"><input type="hidden" name="action" value="' . $action . '"><button class="btn ' . $class . '">' . e($label) . '</button></form>';
?>
<a href="<?= e(url('/school/exams')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Exams</a>
<div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div><div class="flex flex-wrap items-center gap-3"><h1 class="font-display text-[30px] font-semibold text-ink"><?= e($exam['title']) ?></h1><span class="badge badge-dot <?= $stateBadge[0] ?>"><?= $stateBadge[1] ?></span></div>
        <p class="mt-1 text-sm text-muted"><?= e(SchoolExam::TYPES[$exam['type']] ?? $exam['type']) ?> · <?= e(local_date($exam['starts_at'], 'D j M Y, g:i a')) ?> – <?= e(local_date($exam['ends_at'], 'g:i a')) ?> · <?= (int) $exam['duration_minutes'] ?> minutes</p></div>
    <div class="flex flex-wrap gap-2">
        <?php if ($state === 'draft'): ?>
            <a class="btn btn-secondary" href="<?= e(url('/school/exams/' . $exam['id'] . '/edit')) ?>"><?= icon('sliders') ?>Edit</a>
            <?= $act('publish', 'Publish', 'btn-primary') ?>
        <?php elseif ($state === 'upcoming'): ?>
            <?= $act('unpublish', 'Unpublish to edit', 'btn-secondary') ?>
            <?= $act('cancel', 'Cancel exam', 'btn-danger-soft', 'Students will no longer see this exam.') ?>
        <?php endif; ?>
        <?php if (in_array($state, ['upcoming', 'live', 'closed'], true)): ?><a class="btn <?= $state === 'live' ? 'btn-primary' : 'btn-secondary' ?>" href="<?= e(url('/school/exams/' . $exam['id'] . '/monitor')) ?>"><?= icon('activity') ?><?= $state === 'closed' ? 'Scores & sittings' : 'Live monitor' ?></a><?php endif; ?>
        <?php if (in_array($state, ['live', 'closed'], true)): ?><a class="btn btn-secondary" href="<?= e(url('/school/exams/' . $exam['id'] . '/results')) ?>"><?= icon('chart') ?>Results</a><?php endif; ?>
        <?= $act('duplicate', 'Duplicate', 'btn-ghost') ?>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <div class="card-header"><div><p class="eyebrow">Before publishing</p><h2 class="card-title mt-1">Readiness</h2></div><span class="badge <?= $ready ? 'badge-green' : 'badge-amber' ?>"><?= $ready ? 'Ready' : 'Needs attention' ?></span></div>
        <ul class="space-y-2 px-4 pb-4">
            <?php foreach ($checks as $c): ?>
                <li class="flex items-center gap-3 rounded-2xl bg-soft px-4 py-3">
                    <span class="<?= $c['ok'] ? 'text-emerald-500' : 'text-red-500' ?>"><?= icon($c['ok'] ? 'check-circle' : 'alert-circle', 'h-5 w-5', $c['ok'] ? 'OK' : 'Problem') ?></span>
                    <span class="flex-1 text-sm font-medium text-ink"><?= e($c['label']) ?></span><span class="text-sm text-muted"><?= e($c['detail']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="border-t border-line px-6 py-5">
            <p class="eyebrow">Papers</p>
            <ul class="mt-3 space-y-2">
                <?php foreach ($exam['papers'] as $p): ?>
                    <li class="flex flex-wrap items-center gap-3 text-sm">
                        <span class="font-semibold text-ink"><?= e($p['subject']) ?></span>
                        <span class="text-muted"><?= (int) $p['question_count'] ?> questions · <?= $p['mode'] === 'fixed' ? 'hand-picked' : 'random' ?><?= $p['topics'] ? ' · topics: ' . e(implode(', ', $p['topics'])) : '' ?><?= $p['difficulty'] ? ' · ' . e($p['difficulty']) : '' ?></span>
                        <?php if ($p['mode'] === 'fixed' && $state === 'draft'): ?><a class="btn btn-secondary btn-sm ml-auto" href="<?= e(url('/school/exams/' . $exam['id'] . '/papers/' . $p['id'] . '/questions')) ?>">Choose questions (<?= (int) $p['fixed_count'] ?>)</a><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
    <aside class="space-y-6">
        <section class="card card-body">
            <p class="eyebrow">Classes</p>
            <ul class="mt-3 space-y-2 text-sm"><?php foreach ($classNames as $name => $count): ?><li class="flex justify-between"><span class="font-medium text-ink"><?= e($name) ?></span><span class="text-muted"><?= (int) $count ?> students</span></li><?php endforeach; ?></ul>
        </section>
        <section class="card card-body text-sm">
            <p class="eyebrow">Settings</p>
            <ul class="mt-3 space-y-1.5 text-muted">
                <li><?= $s['shuffle_questions'] ? '✓' : '✗' ?> Shuffled questions</li><li><?= $s['shuffle_options'] ? '✓' : '✗' ?> Shuffled options</li>
                <li><?= $s['allow_back'] ? '✓' : '✗' ?> Can go back</li><li><?= $s['calculator'] ? '✓' : '✗' ?> Calculator</li>
                <li>Results: <?= e(['instant' => 'immediately', 'after_window' => 'when the window closes', 'manual' => 'when released'][$s['result_release']]) ?></li>
                <li>Camera: <?= e(['off' => 'off', 'presence' => 'face present', 'match' => 'match school photo'][$s['proctoring']] ?? 'off') ?><?= $s['proctoring'] !== 'off' ? ' · photo every ' . (int) $s['snapshot_minutes'] . ' min' : '' ?></li>
                <li><?= $s['single_device'] ? '✓' : '✗' ?> One device only · <?= $s['require_fullscreen'] ? '✓' : '✗' ?> Full screen</li>
                <li>Auto-submit: <?= (int) $s['max_warnings'] > 0 ? 'after ' . (int) $s['max_warnings'] . ' warnings' : 'never' ?><?= $s['invigilator_pin'] !== '' ? ' · PIN set' : '' ?></li>
                <li>Pass mark: <?= (int) $s['pass_mark'] ?>%<?= $s['access_code'] !== '' ? ' · Access code: <span class="font-mono font-semibold text-ink">' . e($s['access_code']) . '</span>' : '' ?></li>
            </ul>
        </section>
    </aside>
</div>
