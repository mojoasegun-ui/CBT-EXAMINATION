<?php
declare(strict_types=1);
/**
 * One student's full record across sessions.
 * @var array<string,mixed> $student @var list<array<string,mixed>> $timeline @var list<array<string,mixed>> $results
 * @var array<string,array<string,list<array<string,mixed>>>> $bySession @var array{exams:int,avg:?float,best:?float} $summary @var bool $isAdmin
 */
use ExamCore\Models\Enrolment;

$tone = ['in_progress' => 'badge-blue', 'promoted' => 'badge-green', 'repeated' => 'badge-amber', 'graduated' => 'badge-green', 'withdrawn' => 'badge-gray', 'suspended' => 'badge-amber'];
$id = (int) $student['id'];
$grade = static fn (?float $p): string => $p === null ? 'text-slate-400' : ($p >= 70 ? 'text-emerald-600' : ($p >= 50 ? 'text-amber-600' : 'text-rose-600'));
?>
<a href="<?= e(url('/school/records')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Records &amp; history</a>

<section class="pd-banner mt-3" aria-labelledby="stu-name">
    <div>
        <small><?= e((string) $student['admission_no']) ?></small>
        <h1 id="stu-name"><?= e($student['name']) ?></h1>
        <h2><?= e((string) ($student['class_name'] ?? 'No class')) ?> · <?= e(ucfirst((string) $student['status'])) ?></h2>
        <div class="pd-banner-actions">
            <a class="cta" href="<?= e(url('/school/records/' . $id . '/print')) ?>"><?= icon('download', 'h-4 w-4') ?> Print / PDF</a>
            <a class="cta cta-ghost" href="<?= e(url('/school/records/' . $id . '/export')) ?>"><?= icon('file-text', 'h-4 w-4') ?> Download results (CSV)</a>
        </div>
    </div>
    <?php if (!empty($student['photo_path'])): ?><img class="pd-photo" src="<?= e(url('/' . $student['photo_path'])) ?>" alt="" width="140" height="140"><?php endif; ?>
</section>

<section class="pd-stats pd-stats-3" aria-label="Summary">
    <div class="pd-stat blue"><div><div class="top"><span class="ico"><?= icon('file-text', 'h-6 w-6') ?></span><span class="lab">Exams taken</span></div><div class="num"><?= (int) $summary['exams'] ?></div><div class="sub">All sessions</div></div></div>
    <div class="pd-stat violet"><div><div class="top"><span class="ico"><?= icon('chart', 'h-6 w-6') ?></span><span class="lab">Average</span></div><div class="num"><?= $summary['avg'] === null ? '–' : (int) round($summary['avg']) . '%' ?></div><div class="sub">Across every exam</div></div></div>
    <div class="pd-stat green"><div><div class="top"><span class="ico"><?= icon('sparkles', 'h-6 w-6') ?></span><span class="lab">Best score</span></div><div class="num"><?= $summary['best'] === null ? '–' : (int) round($summary['best']) . '%' ?></div><div class="sub">Personal best</div></div></div>
</section>

<div class="pd-grid two">
    <section class="pd-card" aria-labelledby="tl-title">
        <div class="pd-head"><h2 id="tl-title"><?= icon('school', 'h-5 w-5') ?> Class history</h2><span class="pd-pill pd-pill-sm"><?= count($timeline) ?> session<?= count($timeline) === 1 ? '' : 's' ?></span></div>
        <?php if ($timeline === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('calendar', 'h-6 w-6') ?></span><span>No session history yet.</span></div>
        <?php else: ?>
            <ul class="pd-tl">
                <?php foreach ($timeline as $t): ?>
                    <li><span class="dot <?= $t['is_current'] ? 'blue' : 'green' ?>"><?= icon('calendar', 'h-[18px] w-[18px]') ?></span>
                        <div class="min-w-0 flex-1"><b><?= e($t['session_name']) ?></b><small><?= e($t['class_name'] !== '' ? $t['class_name'] : 'No class recorded') ?></small></div>
                        <span class="badge <?= $tone[$t['outcome']] ?? 'badge-gray' ?>"><?= e(Enrolment::OUTCOMES[$t['outcome']] ?? $t['outcome']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pd-card" aria-labelledby="pf-title">
        <div class="pd-head"><h2 id="pf-title"><?= icon('user', 'h-5 w-5') ?> Profile</h2></div>
        <dl class="pd-dl">
            <div><dt>Admission no.</dt><dd><?= e((string) $student['admission_no']) ?></dd></div>
            <div><dt>Class now</dt><dd><?= e((string) ($student['class_name'] ?? '–')) ?></dd></div>
            <div><dt>Gender</dt><dd><?= e(ucfirst((string) ($student['gender'] ?? '–'))) ?></dd></div>
            <div><dt>Admitted</dt><dd><?= !empty($student['admitted_on']) ? e(local_date($student['admitted_on'] . ' 12:00:00', 'j M Y')) : '–' ?></dd></div>
            <?php if ($isAdmin): ?>
                <div><dt>Date of birth</dt><dd><?= !empty($student['date_of_birth']) ? e(local_date($student['date_of_birth'] . ' 12:00:00', 'j M Y')) : '–' ?></dd></div>
                <div><dt>Guardian</dt><dd><?= e(trim((string) ($student['guardian_name'] ?? '') . (!empty($student['guardian_phone']) ? (!empty($student['guardian_name']) ? ' · ' : '') . $student['guardian_phone'] : '')) ?: '–') ?></dd></div>
            <?php endif; ?>
        </dl>
        <?php if ($isAdmin): ?><p class="mt-3"><a class="pd-link" href="<?= e(url('/school/students/' . $id)) ?>">Edit student <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></p><?php endif; ?>
    </section>
</div>

<section class="pd-card pd-mt" aria-labelledby="rs-title">
    <div class="pd-head"><h2 id="rs-title"><?= icon('chart', 'h-5 w-5') ?> Results by session and term</h2></div>
    <?php if ($bySession === []): ?>
        <div class="pd-empty"><span class="ico"><?= icon('file-text', 'h-6 w-6') ?></span><b>No exam results yet</b></div>
    <?php else: ?>
        <?php foreach ($bySession as $sessionName => $terms): ?>
            <h3 class="pd-sub-h"><?= e($sessionName) ?></h3>
            <?php foreach ($terms as $termName => $rows): ?>
                <div class="pd-term-block">
                    <div class="pd-term-head"><b><?= e($termName) ?></b><?php if ($isAdmin && !empty($rows[0]['term_id'])): ?><a class="pd-link" href="<?= e(url('/school/results/students/' . $id . '?term=' . (int) $rows[0]['term_id'])) ?>">Report card <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a><?php endif; ?></div>
                    <ul class="pd-tl">
                        <?php foreach ($rows as $r): ?>
                            <li><span class="dot <?= (float) ($r['percent'] ?? 0) >= 50 ? 'green' : 'amber' ?>"><?= icon('check-circle', 'h-[18px] w-[18px]') ?></span>
                                <div class="min-w-0 flex-1"><b><?= e(mb_strimwidth((string) $r['title'], 0, 44, '…')) ?></b><small><?= e(ucfirst((string) $r['type'])) ?> · <?= $r['submitted_at'] ? e(local_date((string) $r['submitted_at'], 'j M Y')) : '' ?></small></div>
                                <time class="pd-score <?= $grade($r['percent'] === null ? null : (float) $r['percent']) ?>"><?= $r['percent'] === null ? '–' : (int) round((float) $r['percent']) . '%' ?></time></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
