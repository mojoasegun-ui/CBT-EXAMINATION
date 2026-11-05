<?php
declare(strict_types=1);
/** @var array<string,mixed>|null $student @var list<array<string,mixed>> $subjects @var array{session:?array,term:?array} $current @var array<string,mixed> $school */
$firstName = explode(' ', trim((string) ($student['name'] ?? $user['name'])))[0];
?>
<section class="bg-mesh relative overflow-hidden rounded-[32px] border border-line">
    <div class="relative grid items-center gap-6 p-7 sm:p-10 lg:grid-cols-[1.2fr_1fr]">
        <div>
            <p class="eyebrow"><?= e($school['name']) ?></p>
            <h1 class="mt-3 font-display text-[34px] font-semibold leading-[1.08] text-ink sm:text-[44px]">Hello, <span class="text-signature"><?= e($firstName) ?></span></h1>
            <p class="mt-4 max-w-md text-[15px] leading-7 text-muted">
                <?= e(($student['class_name'] ?? 'No class yet') . ($current['term'] ? ' · ' . $current['term']['name'] . ', ' . $current['term']['session_name'] : '')) ?>.
                Your scheduled exams will appear here when your teachers publish them.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="<?= e(url('/')) ?>" class="btn btn-primary btn-lg"><?= icon('sparkles') ?>Practise past questions</a>
                <a href="<?= e(url('/student/account/password')) ?>" class="btn btn-secondary btn-lg"><?= icon('key') ?>Change password</a>
            </div>
        </div>
        <div class="hidden justify-end lg:flex"><img src="<?= e(asset('images/art/exam-screen.svg')) ?>" alt="" width="560" height="420" class="float-slow -my-6 w-full max-w-[420px]"></div>
    </div>
</section>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <div class="card-header"><div><p class="eyebrow">Upcoming</p><h2 class="card-title mt-1">My exams</h2></div></div>
        <?php if (($exams ?? []) === []): ?>
        <div class="empty pt-6">
            <span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('calendar', 'h-6 w-6') ?></span>
            <p class="font-display text-lg font-semibold text-ink">No exams scheduled yet</p>
            <p class="max-w-sm text-sm text-muted">When your school schedules a test you will see the date, time and duration here, with a countdown.</p>
        </div>
        <?php else: ?>
        <ul class="space-y-3 px-4 pb-4">
            <?php foreach ($exams as $x): $start = strtotime($x['starts_at'] . ' UTC'); $end = strtotime($x['ends_at'] . ' UTC'); $live = time() >= $start && time() <= $end; $over = time() > $end; ?>
                <li class="flex flex-col gap-3 rounded-2xl border border-line p-4 sm:flex-row sm:items-center <?= $live ? 'border-emerald-300 bg-emerald-50/40' : '' ?>">
                    <span class="icon-tile shrink-0"><?= icon($live ? 'activity' : 'calendar') ?></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink"><?= e($x['title']) ?></p>
                        <p class="mt-0.5 text-sm text-muted"><?= e((string) $x['subjects']) ?> · <?= (int) $x['question_total'] ?> questions · <?= (int) $x['duration_minutes'] ?> min</p>
                        <p class="mt-0.5 text-xs text-subtle"><?= e(local_date($x['starts_at'], 'D j M, g:i a')) ?> – <?= e(local_date($x['ends_at'], 'g:i a')) ?></p>
                    </div>
                    <?php $sit = $sittings[(int) $x['id']] ?? null; $link = e(url('/student/exams/' . $x['id'])); ?>
                    <?php $isPaper = (json_decode((string) ($x['settings'] ?? ''), true)['delivery'] ?? 'cbt') === 'paper'; ?>
                    <?php if ($isPaper && $sit === null): ?><span class="badge badge-gray"><?= icon('file-text', 'h-3.5 w-3.5') ?>Paper exam · written in class</span>
                    <?php elseif ($sit !== null && $sit['status'] !== 'in_progress'): ?><span class="badge badge-green">Submitted</span><a class="btn btn-secondary btn-sm" href="<?= $link ?>">View</a>
                    <?php elseif ($live): ?><span class="badge badge-dot badge-green">Open now</span><a class="btn btn-primary btn-sm" href="<?= $link ?>"><?= $sit !== null ? 'Continue' : 'Start' ?></a>
                    <?php elseif ($over): ?><span class="badge badge-gray"><?= $sit === null ? 'Missed' : 'Closed' ?></span>
                    <?php else: ?><span class="badge badge-blue">Starts <?= e(time_until($start)) ?></span><a class="btn btn-secondary btn-sm" href="<?= e(url('/student/exams/' . $x['id'])) ?>">Details</a><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
    <section class="card">
        <div class="card-header"><div><p class="eyebrow">This session</p><h2 class="card-title mt-1">My subjects</h2></div><span class="badge badge-gray"><?= count($subjects) ?></span></div>
        <?php if ($subjects === []): ?><p class="px-6 pb-6 text-sm text-muted">Your subjects will show once your class is set up.</p><?php else: ?>
            <ul class="flex flex-wrap gap-2 px-6 pb-6"><?php foreach ($subjects as $s): ?><li class="badge badge-brand py-1.5"><?= e($s['name']) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </section>
</div>
