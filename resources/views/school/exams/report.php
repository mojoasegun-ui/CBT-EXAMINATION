<?php
declare(strict_types=1);
/** @var array $exam @var array $sitting @var array|null $student @var list<array> $snapshots @var list<array> $timeline @var array{level:string,reasons:list<string>} $trust */
use ExamCore\Services\ProctorService;

$trustUi = ['clean' => ['badge-green', 'Clean', 'No problems found.'], 'check' => ['badge-amber', 'Check', 'Some things are worth a look.'], 'suspicious' => ['badge-red', 'Suspicious', 'Review the evidence before accepting this score.']][$trust['level']];
$faceUi = ['none' => ['badge-gray', 'Not checked'], 'verified' => ['badge-green', 'Verified'], 'mismatch' => ['badge-red', 'Did not match'], 'approved' => ['badge-amber', 'Admitted by PIN']][$sitting['face_status']];
$labels = ProctorService::FLAGS + [
    'focus_lost' => 'Left the exam screen', 'fullscreen_exit' => 'Left full screen', 'copy_attempt' => 'Tried to copy', 'print_attempt' => 'Tried to print',
    'resumed' => 'Opened the exam', 'offline' => 'Went offline', 'devtools' => 'Opened developer tools', 'face_check' => 'Face check', 'snapshot' => 'Camera snapshot',
    'pin_approved' => 'Admitted with invigilator PIN', 'auto_submitted' => 'Auto-submitted', 'no_photo' => 'No school photo to compare',
];
$bad = array_merge(array_keys(ProctorService::FLAGS), ['focus_lost', 'fullscreen_exit', 'copy_attempt', 'print_attempt', 'devtools', 'auto_submitted']);
$img = static fn (array $snap): string => e(url('/school/exams/' . $exam['id'] . '/sittings/' . $sitting['id'] . '/snapshots/' . $snap['id']));
$verify = array_values(array_filter($snapshots, static fn ($x) => $x['kind'] === 'verify'))[0] ?? null;
?>
<a href="<?= e(url('/school/exams/' . $exam['id'] . '/monitor')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Live monitor</a>
<div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">Integrity report</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink"><?= e($student['name'] ?? 'Student') ?></h1>
        <p class="mt-1 text-sm text-muted"><?= e(($student['admission_no'] ?? '') . ' · ' . ($student['class_name'] ?? '')) ?><?= $sitting['seat_no'] ? ' · Seat ' . e($sitting['seat_no']) : '' ?> · <?= e($exam['title']) ?></p></div>
    <span class="badge <?= $trustUi[0] ?> px-4 py-2 text-sm"><?= icon('shield', 'h-4 w-4') ?><?= $trustUi[1] ?></span>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card card-body lg:col-span-2">
        <p class="eyebrow">Identity check</p>
        <div class="mt-4 grid grid-cols-2 gap-4">
            <figure><div class="aspect-[4/3] overflow-hidden rounded-2xl border border-line bg-soft"><?php if (!empty($student['photo_path'])): ?><img src="<?= e(url('/' . $student['photo_path'])) ?>" alt="School photo" class="h-full w-full object-cover"><?php else: ?><div class="grid h-full place-items-center text-sm text-muted">No school photo</div><?php endif; ?></div><figcaption class="mt-2 text-xs text-muted">School photo</figcaption></figure>
            <figure><div class="aspect-[4/3] overflow-hidden rounded-2xl border border-line bg-soft"><?php if ($verify): ?><img src="<?= $img($verify) ?>" alt="Photo at the start of the exam" class="h-full w-full object-cover"><?php else: ?><div class="grid h-full place-items-center text-sm text-muted">No camera check</div><?php endif; ?></div>
                <figcaption class="mt-2 flex items-center justify-between text-xs text-muted"><span>At the start · <?= $verify ? e(local_date($verify['created_at'], 'g:i:s a')) : '—' ?></span><span class="badge <?= $faceUi[0] ?>"><?= $faceUi[1] ?><?= $sitting['face_score'] !== null ? ' · distance ' . e((string) $sitting['face_score']) : '' ?></span></figcaption></figure>
        </div>
        <p class="mt-4 text-xs text-subtle">Face matching runs on the student's device. Distance below 0.55 means the same person; always compare the photos yourself before acting.</p>
    </section>
    <aside class="card card-body">
        <p class="eyebrow">Summary</p>
        <p class="mt-3 text-sm text-muted"><?= e($trustUi[2]) ?></p>
        <?php if ($trust['reasons']): ?><ul class="mt-3 space-y-1.5 text-sm text-ink"><?php foreach ($trust['reasons'] as $r): ?><li class="flex gap-2"><?= icon('alert-circle', 'mt-0.5 h-4 w-4 shrink-0 text-amber-500') ?><?= e($r) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-2xl bg-soft p-3"><dt class="text-xs text-muted">Warnings</dt><dd class="font-display text-xl font-semibold text-ink"><?= (int) $sitting['warnings'] ?></dd></div>
            <div class="rounded-2xl bg-soft p-3"><dt class="text-xs text-muted">Camera alerts</dt><dd class="font-display text-xl font-semibold text-ink"><?= (int) $sitting['flags'] ?></dd></div>
            <div class="rounded-2xl bg-soft p-3"><dt class="text-xs text-muted">Snapshots</dt><dd class="font-display text-xl font-semibold text-ink"><?= count($snapshots) ?></dd></div>
            <div class="rounded-2xl bg-soft p-3"><dt class="text-xs text-muted">Score</dt><dd class="font-display text-xl font-semibold text-ink"><?= $sitting['percent'] !== null ? rtrim(rtrim(number_format((float) $sitting['percent'], 1), '0'), '.') . '%' : '—' ?></dd></div>
        </dl>
        <p class="mt-4 text-xs text-subtle">IP <?= e((string) $sitting['ip_address']) ?> · <?= e(mb_strimwidth((string) $sitting['user_agent'], 0, 70, '…')) ?></p>
    </aside>
</div>

<?php $flagged = array_filter($snapshots, static fn ($x) => $x['kind'] === 'random'); ?>
<?php if ($flagged): ?>
<section class="card mt-6">
    <div class="card-header"><div><p class="eyebrow">During the exam</p><h2 class="card-title mt-1">Snapshots</h2></div><span class="text-xs text-muted">Red = alert</span></div>
    <div class="grid grid-cols-2 gap-3 px-6 pb-6 sm:grid-cols-4 lg:grid-cols-6">
        <?php foreach ($flagged as $snap): ?>
            <a href="<?= $img($snap) ?>" target="_blank" rel="noopener" class="group block">
                <div class="aspect-[4/3] overflow-hidden rounded-xl border-2 <?= $snap['flag'] ? 'border-red-400' : 'border-transparent' ?> bg-soft"><img src="<?= $img($snap) ?>" alt="" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105"></div>
                <p class="mt-1 text-[11px] <?= $snap['flag'] ? 'font-semibold text-red-600' : 'text-muted' ?>"><?= e(local_date($snap['created_at'], 'g:i a')) ?><?= $snap['flag'] ? ' · ' . e($labels[$snap['flag']] ?? $snap['flag']) : '' ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="card mt-6">
    <div class="card-header"><div><p class="eyebrow">Timeline</p><h2 class="card-title mt-1">Everything that happened</h2></div></div>
    <ol class="relative mx-6 mb-6 border-l border-line">
        <?php foreach (array_slice($timeline, 0, 200) as $t): $isBad = in_array($t['type'], $bad, true); ?>
            <li class="ml-5 py-2.5"><span class="absolute -left-[5px] mt-1.5 h-2.5 w-2.5 rounded-full <?= $isBad ? 'bg-red-500' : 'bg-line' ?>"></span>
                <p class="text-sm <?= $isBad ? 'font-semibold text-red-600' : 'text-ink' ?>"><?= e($labels[$t['type']] ?? ucfirst(str_replace('_', ' ', $t['type']))) ?><?= $t['detail'] && !in_array($t['type'], ['resumed'], true) ? '<span class="font-normal text-muted"> · ' . e($t['detail']) . '</span>' : '' ?></p>
                <p class="text-xs text-subtle"><?= e(local_date($t['at'], 'D j M, g:i:s a')) ?></p></li>
        <?php endforeach; ?>
        <?php if ($timeline === []): ?><li class="ml-5 py-3 text-sm text-muted">Nothing recorded yet.</li><?php endif; ?>
    </ol>
</section>
