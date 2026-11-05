<?php
declare(strict_types=1);
/** @var array $student @var array $exam @var array|null $sitting @var string $window */
use ExamCore\Services\SittingService;

$s = $exam['settings'];
$total = array_sum(array_map('intval', array_column($exam['papers'], 'question_count')));
$start = strtotime($exam['starts_at'] . ' UTC');
$resume = $sitting !== null && $sitting['status'] === 'in_progress';
?>
<a href="<?= e(url('/student')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>My dashboard</a>
<div class="mt-4 grid gap-6 lg:grid-cols-[1.3fr_1fr]">
    <section class="card overflow-hidden">
        <div class="bg-mesh border-b border-line p-7 sm:p-9">
            <p class="eyebrow"><?= e(\ExamCore\Models\SchoolExam::TYPES[$exam['type']] ?? 'Exam') ?></p>
            <h1 class="mt-2 font-display text-[28px] font-semibold leading-tight text-ink sm:text-[34px]"><?= e($exam['title']) ?></h1>
            <div class="mt-6 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-2xl bg-card/80 p-3 backdrop-blur"><p class="font-display text-2xl font-semibold text-ink"><?= (int) $exam['duration_minutes'] ?></p><p class="text-xs text-muted">minutes</p></div>
                <div class="rounded-2xl bg-card/80 p-3 backdrop-blur"><p class="font-display text-2xl font-semibold text-ink"><?= $total ?></p><p class="text-xs text-muted">questions</p></div>
                <div class="rounded-2xl bg-card/80 p-3 backdrop-blur"><p class="font-display text-2xl font-semibold text-ink"><?= count($exam['papers']) ?></p><p class="text-xs text-muted">subject<?= count($exam['papers']) === 1 ? '' : 's' ?></p></div>
            </div>
        </div>
        <div class="card-body space-y-5">
            <div><p class="label">Subjects</p><div class="flex flex-wrap gap-2"><?php foreach ($exam['papers'] as $p): ?><span class="badge badge-brand py-1.5"><?= e($p['subject']) ?> · <?= (int) $p['question_count'] ?></span><?php endforeach; ?></div></div>
            <div><p class="label">Window</p><p class="text-sm text-ink"><?= e(local_date($exam['starts_at'], 'l j M Y, g:i a')) ?> – <?= e(local_date($exam['ends_at'], 'g:i a')) ?></p></div>
            <?php if (trim((string) $exam['instructions']) !== ''): ?><div><p class="label">Instructions from your teacher</p><div class="rounded-2xl bg-soft p-4 text-sm leading-7 text-ink"><?= nl2br(e((string) $exam['instructions'])) ?></div></div><?php endif; ?>
            <div>
                <p class="label">Exam rules</p>
                <ul class="space-y-2 text-sm text-muted">
                    <li class="flex gap-2"><?= icon('clock', 'mt-0.5 h-4 w-4 shrink-0 text-brand') ?>The timer starts when you press Start and does not stop, even if you close the page.</li>
                    <li class="flex gap-2"><?= icon('check-circle', 'mt-0.5 h-4 w-4 shrink-0 text-brand') ?>Every answer saves automatically. If the light or network goes off, sign in again and continue.</li>
                    <li class="flex gap-2"><?= icon('eye', 'mt-0.5 h-4 w-4 shrink-0 text-brand') ?>Leaving the exam screen, copying or printing is recorded and reported to your school.</li>
                    <li class="flex gap-2"><?= icon('alert-circle', 'mt-0.5 h-4 w-4 shrink-0 text-brand') ?>When time is up your answers are submitted for you.<?= empty($s['allow_back']) ? ' You cannot go back to earlier questions in this exam.' : '' ?></li>
                </ul>
            </div>
        </div>
    </section>

    <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
        <section class="card card-body">
            <?php if ($resume): ?>
                <p class="eyebrow">In progress</p>
                <p class="mt-2 font-display text-3xl font-semibold text-ink"><?= intdiv(SittingService::remaining($sitting), 60) ?> min left</p>
                <p class="mt-1 text-sm text-muted"><?= (int) $sitting['answered_count'] ?> of <?= (int) $sitting['question_count'] ?> answered so far.</p>
                <form method="post" action="<?= e(url('/student/exams/' . $exam['id'] . '/start')) ?>" class="mt-5" data-submit-once>
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                    <button class="btn btn-primary btn-lg w-full"><?= icon('arrow-right') ?><span data-label>Continue exam</span></button>
                </form>
            <?php elseif ($window === 'upcoming'): ?>
                <p class="eyebrow">Not open yet</p>
                <p class="mt-2 font-display text-3xl font-semibold text-ink tabular-nums" data-countdown-to="<?= $start ?>"><?= e(time_until($start)) ?></p>
                <p class="mt-1 text-sm text-muted">Opens <?= e(local_date($exam['starts_at'], 'D j M, g:i a')) ?>. This page will let you start when it opens.</p>
            <?php elseif ($window === 'closed'): ?>
                <p class="eyebrow">Closed</p><p class="mt-2 text-sm text-muted">This exam window has ended.</p>
            <?php else: $needsFace = ($s['proctoring'] ?? 'off') !== 'off'; $faceOk = in_array($verify['status'], ['verified', 'approved'], true); ?>
                <?php if ($needsFace): ?>
                    <div data-face-check="<?= e(json_encode($proctor + ['verified' => $faceOk])) ?>">
                        <p class="eyebrow">Step 1 · Face check</p>
                        <div class="relative mt-3 aspect-[4/3] overflow-hidden rounded-2xl bg-ink/90">
                            <video class="h-full w-full -scale-x-100 object-cover" autoplay muted playsinline></video>
                            <span class="pointer-events-none absolute inset-[18%] rounded-[50%] border-2 border-dashed border-white/60"></span>
                        </div>
                        <p class="mt-3 text-sm text-muted" data-face-status>Your camera checks that you are the student sitting this exam. The photo goes only to your school.</p>
                        <?php if (!$faceOk): ?><button type="button" class="btn btn-secondary mt-3 w-full" data-face-run><?= icon('scan-face') ?>Start face check</button><?php endif; ?>
                    </div>
                    <?php if (($s['invigilator_pin'] ?? '') !== ''): ?>
                        <form method="post" action="<?= e(url('/student/exams/' . $exam['id'] . '/approve')) ?>" class="mt-4 <?= $verify['status'] === 'mismatch' ? '' : 'hidden' ?> rounded-2xl border border-amber-200 bg-amber-50 p-4" data-pin-box>
                            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                            <p class="text-sm font-semibold text-amber-800">Invigilator approval</p>
                            <p class="mt-1 text-xs text-amber-700">If the camera cannot recognise you, your invigilator can admit you with their PIN.</p>
                            <div class="mt-3 flex gap-2"><input class="input" type="password" name="pin" inputmode="numeric" autocomplete="off" maxlength="8" placeholder="PIN" aria-label="Invigilator PIN"><button class="btn btn-secondary">Approve</button></div>
                        </form>
                    <?php endif; ?>
                    <div class="my-5 border-t border-line"></div>
                    <p class="eyebrow">Step 2 · Start</p>
                <?php else: ?>
                    <p class="eyebrow">Ready</p>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/student/exams/' . $exam['id'] . '/start')) ?>" class="mt-3 space-y-4" data-submit-once>
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                    <?php if (trim((string) $s['access_code']) !== ''): ?>
                        <?= field('access_code', 'Access code', '', [], ['hint' => 'from your invigilator', 'attrs' => ' autocomplete="off" autocapitalize="characters"', 'required' => true]) ?>
                    <?php endif; ?>
                    <?php if (!empty($s['ask_seat'])): ?>
                        <?= field('seat_no', 'Seat / computer number', '', [], ['attrs' => ' autocomplete="off" maxlength="12"', 'required' => true, 'placeholder' => 'e.g. PC 14']) ?>
                    <?php endif; ?>
                    <label class="flex items-start gap-2.5 text-sm text-ink"><input type="checkbox" class="checkbox mt-0.5" name="agree" value="1" required>I have read the rules and I am <?= e($student['name']) ?> (<?= e($student['admission_no']) ?>).</label>
                    <button class="btn btn-primary btn-lg w-full" data-start-button <?= $needsFace && !$faceOk ? 'disabled' : '' ?>><?= icon('arrow-right') ?><span data-label>Start exam</span></button>
                </form>
            <?php endif; ?>
            <?php if (!empty($blockedDevice)): ?>
                <p class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">This exam is already open on another phone or computer. Ask your invigilator to tap <strong>New device</strong> for you on the monitor, then refresh this page.</p>
            <?php endif; ?>
        </section>
        <section class="card card-body" data-device-check>
            <p class="eyebrow">Device check</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li class="flex justify-between"><span class="text-muted">Connection</span><span data-check="net" class="font-medium text-ink">Checking…</span></li>
                <li class="flex justify-between"><span class="text-muted">Screen</span><span data-check="screen" class="font-medium text-ink">Checking…</span></li>
                <li class="flex justify-between"><span class="text-muted">Battery</span><span data-check="battery" class="font-medium text-ink">Not reported</span></li>
                <li class="flex justify-between"><span class="text-muted">Saving on this device</span><span data-check="storage" class="font-medium text-ink">Checking…</span></li>
            </ul>
        </section>
    </aside>
</div>
<?php if (($exam['settings']['proctoring'] ?? 'off') !== 'off'): ?><script defer src="<?= e(asset('js/proctor.js')) ?>"></script><?php endif; ?>
