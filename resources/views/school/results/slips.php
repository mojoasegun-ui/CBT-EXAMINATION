<?php
declare(strict_types=1);
/** Printable result slips (two or three per A4 page). @var array $exam @var list<array> $rows @var int $size @var array $school */
use ExamCore\Services\ResultService;

$fmt = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
?>
<div class="slips-page">
<?php foreach ($rows as $r): ?>
    <article class="slip">
        <header class="slip-head flex items-center gap-3 pb-3">
            <?php if (!empty($school['logo_path'])): ?><img src="<?= e(url('/' . $school['logo_path'])) ?>" alt="" class="h-11 w-11 object-contain"><?php endif; ?>
            <div class="flex-1"><p class="slip-school font-display text-[15px] font-bold uppercase leading-tight"><?= e($school['name']) ?></p><p class="text-[11px] text-slate-600">Result slip · <?= e($exam['title']) ?></p></div>
            <img src="<?= e($r['qr']) ?>" alt="QR code to verify this slip" class="h-[72px] w-[72px]">
        </header>
        <div class="mt-3 grid grid-cols-[1fr_auto] gap-4">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-1">
                <div><dt class="rc-label">Name</dt><dd class="font-semibold"><?= e($r['name']) ?></dd></div>
                <div><dt class="rc-label">Admission no</dt><dd class="font-semibold"><?= e($r['admission_no']) ?></dd></div>
                <div><dt class="rc-label">Class</dt><dd><?= e((string) $r['class_name']) ?></dd></div>
                <div><dt class="rc-label">Date</dt><dd><?= e(local_date($r['submitted_at'], 'j M Y')) ?></dd></div>
                <?php if (count($r['papers']) > 1): ?>
                    <div class="col-span-2 mt-1"><dt class="rc-label">Subjects</dt><dd class="text-[12px]"><?= e(implode(' · ', array_map(static fn ($s, $v) => $s . ' ' . rtrim(rtrim(number_format($v, 1), '0'), '.') . '%', array_keys($r['papers']), $r['papers']))) ?></dd></div>
                <?php endif; ?>
            </dl>
            <div class="slip-score rounded-xl px-5 py-2 text-center">
                <p class="rc-label">Score</p>
                <p class="font-display text-[26px] font-bold leading-none"><?= $fmt($r['percent']) ?>%</p>
                <p class="mt-1 text-[12px] font-semibold"><?= (int) $r['score'] ?>/<?= (int) $r['question_count'] ?> · <?= e($r['grade']['grade']) ?></p>
                <p class="text-[11px] text-slate-600"><?= e(ResultService::ordinal($r['position'])) ?> of <?= (int) $size ?></p>
            </div>
        </div>
        <p class="mt-3 text-[10px] text-slate-500">Verify at <span class="font-mono"><?= e(preg_replace('~^https?://~', '', $r['verify'])) ?></span></p>
    </article>
<?php endforeach; ?>
<?php if ($rows === []): ?><div class="card empty"><p class="text-sm text-muted">No submitted papers yet.</p></div><?php endif; ?>
</div>
