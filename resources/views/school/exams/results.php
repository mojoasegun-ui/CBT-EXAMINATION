<?php
declare(strict_types=1);
/** @var array $exam @var array $result @var list<array> $items */
use ExamCore\Services\ResultService;

$st = $result['stats'];
$fmt = static fn ($v): string => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
$multi = count($result['subjects']) > 1;
$trustBadge = ['clean' => ['badge-green', 'Clean'], 'check' => ['badge-amber', 'Check'], 'suspicious' => ['badge-red', 'Suspicious']];
$base = '/school/exams/' . $exam['id'];
$released = !empty($exam['results_released_at']) || ($exam['settings']['result_release'] ?? '') === 'instant' || (($exam['settings']['result_release'] ?? '') === 'after_window' && time() > strtotime($exam['ends_at'] . ' UTC'));
?>
<a href="<?= e(url($base)) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?><?= e($exam['title']) ?></a>
<div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div><p class="eyebrow">Results</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink"><?= e($exam['title']) ?></h1>
        <p class="mt-1 text-sm text-muted"><?= e(implode(', ', $result['subjects'])) ?> · pass mark <?= (int) $exam['settings']['pass_mark'] ?>% · <?= $released ? '<span class="font-semibold text-emerald-600">visible to students</span>' : '<span class="font-semibold text-amber-600">not yet released to students</span>' ?></p></div>
    <div class="flex flex-wrap gap-2">
        <?php if (!$released): ?>
            <form method="post" action="<?= e(url($base . '/action')) ?>" data-confirm="Students will see their scores straight away." data-confirm-ok="Release results"><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="release"><button class="btn btn-primary"><?= icon('chart') ?>Release results</button></form>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= e(url($base . '/slips')) ?>"><?= icon('file-text') ?>Result slips</a>
        <a class="btn btn-secondary" href="<?= e(url($base . '/results/export')) ?>"><?= icon('download') ?>Excel</a>
        <a class="btn btn-ghost" href="<?= e(url($base . '/monitor')) ?>"><?= icon('activity') ?>Monitor</a>
    </div>
</div>

<div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5" data-stagger>
    <?php foreach ([['Sat the exam', (string) $st['count']], ['Average', $fmt($st['average']) . ($st['average'] !== null ? '%' : '')], ['Highest', $fmt($st['highest']) . ($st['highest'] !== null ? '%' : '')], ['Lowest', $fmt($st['lowest']) . ($st['lowest'] !== null ? '%' : '')], ['Pass rate', $st['passRate'] !== null ? $st['passRate'] . '%' : '—']] as [$l, $v]): ?>
        <div class="card p-4"><p class="text-xs text-muted"><?= $l ?></p><p class="mt-1 font-display text-2xl font-semibold tabular-nums text-ink"><?= e($v) ?></p></div>
    <?php endforeach; ?>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <div class="card-header"><div><p class="eyebrow">Grades</p><h2 class="card-title mt-1">How the class performed</h2></div></div>
        <div class="px-6 pb-6"><?php $series = $result['distribution']; $chartLabel = 'Students per grade'; $unit = 'student'; require __DIR__ . '/../../partials/portal/bar-chart.php'; ?></div>
    </section>
    <section class="card">
        <div class="card-header"><div><p class="eyebrow">By subject</p><h2 class="card-title mt-1">Average score</h2></div></div>
        <ul class="space-y-3 px-6 pb-6">
            <?php foreach ($result['subjectAvg'] as $sub => $avg): ?>
                <li><div class="flex justify-between text-sm"><span class="font-medium text-ink"><?= e($sub) ?></span><span class="tabular-nums text-muted"><?= $fmt($avg) ?>%</span></div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full bg-signature" data-width="<?= (int) round((float) $avg) ?>"></span></div></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="card mt-6 overflow-hidden">
    <div class="card-header"><div><p class="eyebrow">Ranking</p><h2 class="card-title mt-1">All students</h2></div></div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="border-y border-line bg-soft/60 text-left text-xs font-semibold uppercase tracking-wider text-subtle">
                <tr><th class="px-5 py-3">Pos.</th><th class="px-3 py-3">Student</th><?php if ($multi): foreach ($result['subjects'] as $sub): ?><th class="px-3 py-3 text-right"><?= e($sub) ?></th><?php endforeach; endif; ?><th class="px-3 py-3 text-right">Score</th><th class="px-3 py-3">Grade</th><th class="px-3 py-3">Integrity</th><th class="px-5 py-3 text-right">Slip</th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($result['rows'] as $r): ?>
                    <tr>
                        <td class="px-5 py-3 font-semibold tabular-nums text-ink"><?= e(ResultService::ordinal($r['position'])) ?></td>
                        <td class="px-3 py-3"><p class="font-medium text-ink"><?= e($r['name']) ?></p><p class="text-xs text-muted"><?= e($r['admission_no']) ?> · <?= e((string) $r['class_name']) ?></p></td>
                        <?php if ($multi): foreach ($result['subjects'] as $sub): ?><td class="px-3 py-3 text-right tabular-nums text-muted"><?= $fmt($r['papers'][$sub] ?? null) ?></td><?php endforeach; endif; ?>
                        <td class="px-3 py-3 text-right tabular-nums"><span class="font-semibold text-ink"><?= $fmt($r['percent']) ?>%</span> <span class="text-xs text-muted"><?= (int) $r['score'] ?>/<?= (int) $r['question_count'] ?></span></td>
                        <td class="px-3 py-3"><span class="badge <?= $r['passed'] ? 'badge-green' : 'badge-red' ?>"><?= e($r['grade']['grade']) ?></span> <span class="text-xs text-muted"><?= e($r['grade']['remark']) ?></span></td>
                        <td class="px-3 py-3"><a href="<?= e(url($base . '/sittings/' . $r['id'])) ?>" class="badge <?= $trustBadge[$r['trust']][0] ?> hover:underline"><?= $trustBadge[$r['trust']][1] ?></a></td>
                        <td class="px-5 py-3 text-right"><a class="btn btn-ghost btn-sm" href="<?= e(url($base . '/slips?sitting=' . $r['id'])) ?>">Print</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($result['rows'] === []): ?><tr><td colspan="9" class="px-5 py-12 text-center text-muted">Nobody has submitted yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($items): ?>
<section class="card mt-6">
    <div class="card-header"><div><p class="eyebrow">Item analysis</p><h2 class="card-title mt-1">Questions students found hardest</h2></div><span class="text-xs text-muted">Use these to plan revision</span></div>
    <ul class="divide-y divide-line border-t border-line">
        <?php foreach ($items as $q): ?>
            <li class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1"><p class="text-xs text-muted"><?= e($q['subject']) ?><?= $q['topic'] ? ' · ' . e($q['topic']) : '' ?></p><p class="mt-0.5 line-clamp-2 text-sm text-ink"><?= rich_text($q['body']) ?></p></div>
                <div class="flex shrink-0 items-center gap-4 text-xs">
                    <span class="text-muted">Answer <b class="text-ink"><?= e($q['correct_option']) ?></b><?= $q['common_wrong'] ? ' · most chose <b class="text-red-600">' . e($q['common_wrong']) . '</b>' : '' ?></span>
                    <span class="w-24"><span class="flex justify-between"><span class="text-muted">Correct</span><b class="<?= $q['rate'] < 40 ? 'text-red-600' : 'text-ink' ?>"><?= (int) $q['rate'] ?>%</b></span><span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full <?= $q['rate'] < 40 ? 'bg-red-500' : 'bg-emerald-500' ?>" data-width="<?= (int) $q['rate'] ?>"></span></span></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
