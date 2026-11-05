<?php
declare(strict_types=1);
/** QR verification result. @var bool $valid */
use ExamCore\Services\ResultService;

$fmt = static fn ($v): string => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
?>
<?php if (!$valid): ?>
    <span class="grid h-14 w-14 place-items-center rounded-full bg-red-50 text-red-600"><?= icon('alert-circle', 'h-7 w-7') ?></span>
    <h1 class="mt-6 font-display text-[30px] font-semibold leading-tight text-ink">Not verified</h1>
    <p class="mt-3 text-[15px] leading-7 text-muted">This link does not match any released result. The slip or report card may have been altered, or the school has not released the result yet.</p>
<?php else: ?>
    <span class="pop-in grid h-14 w-14 place-items-center rounded-full bg-emerald-500 text-white"><?= icon('check', 'h-7 w-7') ?></span>
    <p class="eyebrow mt-6">Genuine <?= $kind === 'slip' ? 'result slip' : 'report card' ?></p>
    <h1 class="mt-2 font-display text-[28px] font-semibold leading-tight text-ink"><?= e($student['name']) ?></h1>
    <p class="mt-1 text-sm text-muted"><?= e($student['admission_no']) ?> · <?= e((string) $student['class_name']) ?> · <?= e($school['name']) ?></p>
    <?php if ($kind === 'slip'): ?>
        <div class="mt-6 rounded-2xl border border-line bg-card p-5">
            <p class="text-sm font-semibold text-ink"><?= e($exam['title']) ?></p>
            <p class="text-xs text-muted"><?= e(local_date($sitting['submitted_at'], 'j M Y')) ?></p>
            <p class="mt-4 font-display text-4xl font-semibold text-ink"><?= $fmt($sitting['percent']) ?>% <span class="text-lg text-muted"><?= e($grade['grade']) ?></span></p>
            <p class="text-sm text-muted"><?= (int) $sitting['score'] ?> of <?= (int) $sitting['question_count'] ?> correct · <?= e($grade['remark']) ?></p>
        </div>
    <?php else: $row = $card['row']; ?>
        <div class="mt-6 rounded-2xl border border-line bg-card p-5">
            <p class="text-sm font-semibold text-ink"><?= e($term['name']) ?> · <?= e($term['session_name']) ?></p>
            <ul class="mt-3 divide-y divide-line text-sm">
                <?php foreach ($card['sheet']['subjects'] as $sub): $m = $row['subjects'][$sub] ?? null; ?>
                    <li class="flex justify-between py-2"><span class="text-ink"><?= e($sub) ?></span><span class="tabular-nums text-muted"><?= $fmt($m['total'] ?? null) ?> <?= ($m['total'] ?? null) !== null ? e($m['grade']) : '' ?></span></li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-3 border-t border-line pt-3 text-sm"><span class="text-muted">Average</span> <b class="text-ink"><?= $fmt($row['average'] ?? null) ?>%</b><?php if (!empty($card['settings']['show_position'])): ?> · <span class="text-muted">Position</span> <b class="text-ink"><?= e(ResultService::ordinal($row['position'] ?? null)) ?></b> of <?= (int) $card['sheet']['size'] ?><?php endif; ?></p>
        </div>
    <?php endif; ?>
    <p class="mt-5 text-xs text-subtle">Checked <?= e(gmdate('j M Y, H:i')) ?> UTC. Compare these details with the printed document.</p>
<?php endif; ?>
