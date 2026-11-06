<?php
declare(strict_types=1);
/**
 * School dashboard (administrator and teacher), laid out like the Console dashboard. Real data only.
 * @var array<string,mixed> $school @var bool $isAdmin @var bool $needsSetup @var array<string,int> $stats @var array{session:?array,term:?array} $current
 * @var int|null $remaining @var list<array{0:string,1:bool,2:string}> $checklist @var list<array<string,mixed>> $activity
 * @var array{upcoming:int,live:int,total:int} $examCounts @var array{avg:?float,top:int,completed:int,pass:?float} $totals @var list<array<string,mixed>> $performance
 * @var list<array<string,mixed>> $live @var int $myStudents @var array{upcoming:int,live:int,past:int,draft:int} $examStatus
 * @var list<array{date:string,count:int}> $seriesStarted @var list<array{date:string,count:int}> $seriesDone @var list<array{date:string,count:int}> $seriesEnrolled
 * @var list<array<string,mixed>> $upcoming @var list<array<string,mixed>> $topStudents @var array{with:int,total:int} $photo @var int $newStudents @var int $alerts @var list<array<string,mixed>> $myClasses
 */
$firstName = explode(' ', trim((string) $user['name']))[0];
$done = count(array_filter($checklist, static fn (array $item): bool => $item[1]));
$setupPct = (int) round($done / max(1, count($checklist)) * 100);
$term = $current['term'];
$avg = $totals['avg'];
$initials = static fn (string $name): string => strtoupper(mb_substr(trim($name), 0, 1) . mb_substr((string) (explode(' ', trim($name))[1] ?? ''), 0, 1));
$ring = static function (int $pct, string $color): string {
    $pct = max(0, min(100, $pct));
    return '<svg viewBox="0 0 36 36" aria-hidden="true"><circle cx="18" cy="18" r="15.915" fill="none" stroke="' . $color . '" stroke-opacity=".18" stroke-width="3.2"/><circle cx="18" cy="18" r="15.915" fill="none" stroke="' . $color . '" stroke-width="3.2" stroke-linecap="round" stroke-dasharray="' . $pct . ' ' . (100 - $pct) . '"/></svg>';
};
$labels = array_map(static fn (array $d): string => gmdate('M j', strtotime($d['date'])), $seriesStarted);
$statusParts = [['Upcoming', $examStatus['upcoming'], '#3b82f6'], ['Live now', $examStatus['live'], '#10b981'], ['Completed', $examStatus['past'], '#8b5cf6'], ['Draft', $examStatus['draft'], '#f59e0b']];
$statusTotal = array_sum(array_column($statusParts, 1));
$segments = [];
$offset = 0.0;
foreach ($statusParts as [, $n, $color]) {
    $share = $statusTotal > 0 ? $n / $statusTotal * 100 : 0;
    if ($share > 0) {
        $segments[] = [$color, round($share, 2), round($offset, 2)];
        $offset += $share;
    }
}
$seatPct = $remaining === null ? null : (int) round($stats['students'] / max(1, $stats['students'] + $remaining) * 100);
$photoPct = $photo['total'] > 0 ? (int) round($photo['with'] / $photo['total'] * 100) : 0;
$passPct = $totals['pass'] === null ? 0 : (int) round($totals['pass']);
$publishedShare = $examStatus['upcoming'] + $examStatus['live'] + $examStatus['past'];
$readyPct = $publishedShare + $examStatus['draft'] > 0 ? (int) round($publishedShare / ($publishedShare + $examStatus['draft']) * 100) : 0;
$healthRings = $isAdmin
    ? [['Setup', $setupPct . '%', $setupPct, $setupPct >= 100 ? '#10b981' : '#f59e0b'], ['Photos', $photoPct . '%', $photoPct, $photoPct >= 80 ? '#10b981' : '#f59e0b'], ['Seats', $seatPct === null ? 'Unlimited' : $seatPct . '%', $seatPct ?? 100, ($seatPct ?? 0) >= 90 ? '#f59e0b' : '#10b981']]
    : [['Avg score', $avg === null ? '–' : (int) round($avg) . '%', (int) round($avg ?? 0), ($avg ?? 0) >= 50 ? '#10b981' : '#f59e0b'], ['Pass rate', $totals['pass'] === null ? '–' : $passPct . '%', $passPct, $passPct >= 50 ? '#10b981' : '#f59e0b'], ['Published', $readyPct . '%', $readyPct, '#3b82f6']];
$healthOk = $isAdmin ? ($setupPct >= 100 && $photoPct >= 80) : ($avg === null || $avg >= 50);
$dayName = (new DateTimeImmutable('now'))->format('D, j M Y');
$analyticsLines = [
    ['label' => 'Exams started', 'color' => '#2563eb', 'values' => array_column($seriesStarted, 'count')],
    ['label' => 'Exams completed', 'color' => '#10b981', 'values' => array_column($seriesDone, 'count')],
];
if ($isAdmin) {
    $analyticsLines[] = ['label' => 'New students', 'color' => '#f59e0b', 'values' => array_column($seriesEnrolled, 'count')];
}
?>
<div class="pd-hello">
    <div>
        <h1>Welcome Back, <b><?= e($firstName) ?></b></h1>
        <p><?= $isAdmin ? 'Manage. Monitor. Grow — all in one place.' : 'Teach. Assess. Inspire — all in one place.' ?><?= $term !== null ? ' · ' . e($term['name']) : '' ?></p>
    </div>
    <span class="pd-pill"><?= icon('calendar', 'h-4 w-4') ?> <?= e($dayName) ?></span>
</div>

<?php if ($isAdmin && $needsSetup): ?>
    <section class="pd-setup">
        <div><b>Your school is almost ready</b><span>Create the standard classes and subjects in one tap, then enrol students.</span></div>
        <form method="post" action="<?= e(url('/school/quick-setup')) ?>" data-submit-once>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <button class="btn btn-primary"><?= icon('sparkles', 'h-4 w-4') ?><span data-label>Quick setup</span></button>
        </form>
    </section>
<?php endif; ?>

<section class="pd-stats" aria-label="Key numbers">
    <?php if ($isAdmin): ?>
        <a class="pd-stat blue" href="<?= e(url('/school/students')) ?>"><div><div class="top"><span class="ico"><?= icon('graduation', 'h-6 w-6') ?></span><span class="lab">Total Students</span></div>
            <div class="num" data-count="<?= (int) $stats['students'] ?>"><?= e(number_format($stats['students'])) ?></div><div class="sub"><?= $newStudents > 0 ? '+' . (int) $newStudents . ' this week' : ($remaining === null ? 'Unlimited seats' : number_format($remaining) . ' seats left') ?></div></div>
            <span class="go">View Students <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('graduation', 'h-full w-full') ?></span></a>
        <a class="pd-stat violet" href="<?= e(url('/school/teachers')) ?>"><div><div class="top"><span class="ico"><?= icon('users', 'h-6 w-6') ?></span><span class="lab">Total Teachers</span></div>
            <div class="num" data-count="<?= (int) $stats['teachers'] ?>"><?= e(number_format($stats['teachers'])) ?></div><div class="sub"><?= (int) $stats['classes'] ?> classes · <?= (int) $stats['subjects'] ?> subjects</div></div>
            <span class="go">View Teachers <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('users', 'h-full w-full') ?></span></a>
        <a class="pd-stat green" href="<?= e(url('/school/exams')) ?>"><div><div class="top"><span class="ico"><?= icon('activity', 'h-6 w-6') ?></span><span class="lab">Ongoing CBT Exams</span></div>
            <div class="num" data-count="<?= count($live) ?>"><?= count($live) ?></div><div class="sub"><?= (int) $examStatus['live'] ?> exam<?= $examStatus['live'] === 1 ? '' : 's' ?> open · <?= (int) $examStatus['upcoming'] ?> upcoming</div></div>
            <span class="go">View Monitor <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('activity', 'h-full w-full') ?></span></a>
        <a class="pd-stat amber" href="<?= e(url('/school/results')) ?>"><div><div class="top"><span class="ico"><?= icon('chart', 'h-6 w-6') ?></span><span class="lab">Average Score</span></div>
            <div class="num"><?= $avg === null ? '–' : (int) round($avg) . '%' ?></div><div class="sub"><?= (int) $totals['completed'] ?> results · last 30 days</div></div>
            <span class="go">View Results <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('chart', 'h-full w-full') ?></span></a>
    <?php else: ?>
        <a class="pd-stat blue" href="<?= e(url('/school/exams')) ?>"><div><div class="top"><span class="ico"><?= icon('file-text', 'h-6 w-6') ?></span><span class="lab">My Exams</span></div>
            <div class="num" data-count="<?= (int) $examCounts['total'] ?>"><?= e(number_format($examCounts['total'])) ?></div><div class="sub"><?= (int) $examCounts['upcoming'] ?> upcoming · <?= (int) $examCounts['live'] ?> live</div></div>
            <span class="go">View Exams <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('file-text', 'h-full w-full') ?></span></a>
        <a class="pd-stat violet" href="<?= e(url('/school/my-classes')) ?>"><div><div class="top"><span class="ico"><?= icon('users', 'h-6 w-6') ?></span><span class="lab">My Students</span></div>
            <div class="num" data-count="<?= (int) $myStudents ?>"><?= e(number_format($myStudents)) ?></div><div class="sub">In <?= count($myClasses) ?> class<?= count($myClasses) === 1 ? '' : 'es' ?></div></div>
            <span class="go">My Classes <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('users', 'h-full w-full') ?></span></a>
        <a class="pd-stat green" href="<?= e(url('/school/exams')) ?>"><div><div class="top"><span class="ico"><?= icon('activity', 'h-6 w-6') ?></span><span class="lab">Ongoing CBT Exams</span></div>
            <div class="num" data-count="<?= count($live) ?>"><?= count($live) ?></div><div class="sub"><?= count($live) === 0 ? 'No one is writing right now' : 'Students writing right now' ?></div></div>
            <span class="go">View Monitor <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('activity', 'h-full w-full') ?></span></a>
        <a class="pd-stat amber" href="<?= e(url('/school/exams?tab=past')) ?>"><div><div class="top"><span class="ico"><?= icon('chart', 'h-6 w-6') ?></span><span class="lab">Average Score</span></div>
            <div class="num"><?= $avg === null ? '–' : (int) round($avg) . '%' ?></div><div class="sub"><?= (int) $totals['completed'] ?> results · last 30 days</div></div>
            <span class="go">View Results <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('chart', 'h-full w-full') ?></span></a>
    <?php endif; ?>
</section>

<div class="pd-grid two">
    <section class="pd-card" aria-labelledby="an-title">
        <div class="pd-head"><h2 id="an-title"><?= icon('chart', 'h-5 w-5') ?> <?= $isAdmin ? 'School Analytics' : 'Exam Analytics' ?></h2><span class="pd-pill pd-pill-sm">Last 14 days</span></div>
        <?php $lines = $analyticsLines; $chartLabel = 'Exams started and completed per day, last 14 days'; require __DIR__ . '/../partials/portal/line-chart.php'; ?>
    </section>

    <section class="pd-card" aria-labelledby="live-title">
        <div class="pd-head"><h2 id="live-title"><?= icon('scan-face', 'h-5 w-5') ?> Live CBT Monitoring <?php if ($live !== []): ?><span class="pd-live">Live</span><?php endif; ?></h2><a class="pd-link" href="<?= e(url('/school/exams')) ?>">View All <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <?php if ($live === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('clock', 'h-6 w-6') ?></span><b>No exam is running right now</b><span>When students write an exam, they show up here live.</span></div>
        <?php else: ?>
            <div class="pd-table">
                <div class="pd-row pd-th"><span></span><span>Student</span><span class="col3">Exam</span><span>Time</span></div>
                <?php foreach ($live as $row): $left = max(0, strtotime((string) $row['deadline_at'] . ' UTC') - time()); ?>
                    <div class="pd-row">
                        <span class="pd-av"><?= e($initials((string) $row['student_name'])) ?></span>
                        <div><b><?= e($row['student_name']) ?></b><small><?= e($row['admission_no'] ?? '') ?></small></div>
                        <div class="col3"><b><?= e($row['exam_title']) ?></b></div>
                        <span class="pd-chip"><?= sprintf('%d:%02d left', intdiv($left, 60), $left % 60) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="pd-grid three">
    <section class="pd-card" aria-labelledby="eo-title">
        <div class="pd-head"><h2 id="eo-title"><?= icon('calendar', 'h-5 w-5') ?> Exams Overview</h2><a class="pd-link" href="<?= e(url('/school/exams')) ?>">View All <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <div class="pd-donut-wrap">
            <div class="pd-donut" role="img" aria-label="<?= (int) $statusTotal ?> exams by status">
                <svg viewBox="0 0 36 36" aria-hidden="true"><circle cx="18" cy="18" r="15.915" fill="none" stroke="#e2e8f0" stroke-width="4.2"/><?php foreach ($segments as [$color, $share, $off]): ?><circle cx="18" cy="18" r="15.915" fill="none" stroke="<?= $color ?>" stroke-width="4.2" stroke-dasharray="<?= $share ?> <?= round(100 - $share, 2) ?>" stroke-dashoffset="<?= -$off ?>"/><?php endforeach; ?></svg>
                <div class="mid"><div><b><?= (int) $statusTotal ?></b><small>Exams</small></div></div>
            </div>
            <div class="pd-keys">
                <?php foreach ($statusParts as [$name, $n, $color]): ?><div><svg class="pd-dot" viewBox="0 0 10 10" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="<?= $color ?>"/></svg><?= e($name) ?> <b><?= (int) $n ?></b></div><?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="pd-card" aria-labelledby="ra-title">
        <div class="pd-head"><h2 id="ra-title"><?= icon('activity', 'h-5 w-5') ?> Recent Activities</h2><a class="pd-link" href="<?= e(url($isAdmin ? '/school/activity' : '/school/exams?tab=past')) ?>">View All <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <?php $feed = []; ?>
        <?php if ($isAdmin): ?>
            <?php foreach (array_slice($activity, 0, 5) as $event): $feed[] = [mb_strimwidth((string) $event['summary'], 0, 50, '…'), (string) ($event['actor_name'] ?? 'System'), (string) $event['created_at']]; endforeach; ?>
        <?php else: ?>
            <?php foreach (array_slice(array_reverse($performance), 0, 5) as $p): $feed[] = [mb_strimwidth((string) $p['title'], 0, 40, '…'), 'Completed by ' . (int) $p['taken'] . ' · avg ' . (int) round((float) $p['avg_percent']) . '%', (string) $p['last_at']]; endforeach; ?>
        <?php endif; ?>
        <?php if ($feed === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('list-checks', 'h-6 w-6') ?></span><span>Activity appears here as exams are written.</span></div>
        <?php else: ?>
            <ul class="pd-tl">
                <?php $tones = ['blue', 'green', 'violet', 'amber']; foreach ($feed as $i => [$title, $by, $at]): ?>
                    <li><span class="dot <?= $tones[$i % 4] ?>"><?= icon(['check-circle', 'activity', 'users', 'sliders'][$i % 4], 'h-[18px] w-[18px]') ?></span><div class="min-w-0"><b><?= e($title) ?></b><small><?= e($by) ?></small></div><time><?= e(time_ago($at)) ?></time></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pd-card" aria-labelledby="sh-title">
        <div class="pd-head"><h2 id="sh-title"><?= icon('shield', 'h-5 w-5') ?> <?= $isAdmin ? 'School Health' : 'Class Health' ?></h2></div>
        <div class="pd-health">
            <?php foreach ($healthRings as [$name, $text, $pct, $color]): ?>
                <div><div class="pd-ring"><?= $ring((int) $pct, $color) ?><span class="pd-ring-text"><?= e($text) ?></span></div><b><?= e($name) ?></b></div>
            <?php endforeach; ?>
        </div>
        <div class="pd-ok <?= $healthOk ? '' : 'warn' ?>"><?= icon($healthOk ? 'check-circle' : 'alert', 'h-5 w-5') ?><span><?php if ($isAdmin): ?><?= $healthOk ? 'Your school is in great shape' : ($photoPct < 80 ? 'Add student photos for exam identity checks' : 'Finish the setup checklist') ?> · <?= (int) $alerts ?> with phone alerts on<?php else: ?><?= $healthOk ? 'Your classes are doing well' : 'Scores are low: review hard questions' ?><?php endif; ?></span></div>
    </section>
</div>

<div class="pd-grid three">
    <section class="pd-card" aria-labelledby="up-title">
        <div class="pd-head"><h2 id="up-title"><?= icon('clock', 'h-5 w-5') ?> Upcoming Exams</h2><a class="pd-link" href="<?= e(url('/school/exams')) ?>">Schedule <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <?php if ($upcoming === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('calendar', 'h-6 w-6') ?></span><b>Nothing scheduled</b><a class="pd-link" href="<?= e(url('/school/exams/create')) ?>">Create an exam <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <?php else: ?>
            <ul class="pd-tl">
                <?php foreach ($upcoming as $x): ?>
                    <li><span class="dot blue"><?= icon('calendar', 'h-[18px] w-[18px]') ?></span><div class="min-w-0"><b><?= e(mb_strimwidth((string) $x['title'], 0, 34, '…')) ?></b><small><?= e(local_date((string) $x['starts_at'], 'D j M, g:ia')) ?> · <?= (int) $x['duration_minutes'] ?> min</small></div><time><?= e(time_until(strtotime((string) $x['starts_at'] . ' UTC'))) ?></time></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pd-card" aria-labelledby="tp-title">
        <div class="pd-head"><h2 id="tp-title"><?= icon('sparkles', 'h-5 w-5') ?> Top Performers</h2><span class="pd-pill pd-pill-sm">30 days</span></div>
        <?php if ($topStudents === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('graduation', 'h-6 w-6') ?></span><span>Your best students appear here after results come in.</span></div>
        <?php else: ?>
            <ul class="pd-tl">
                <?php foreach ($topStudents as $i => $s): ?>
                    <li><span class="pd-rank r<?= min($i + 1, 4) ?>"><?= $i + 1 ?></span><div class="min-w-0"><b><?= e($s['name']) ?></b><small><?= e((string) ($s['class_name'] ?? '')) ?> · <?= (int) $s['taken'] ?> exam<?= $s['taken'] === 1 ? '' : 's' ?></small></div><time class="pd-score"><?= (int) round($s['avg']) ?>%</time></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section id="quick-tools" class="pd-tools" aria-labelledby="qt-title">
        <h2 id="qt-title"><?= icon('dashboard', 'h-5 w-5') ?> Quick Tools</h2>
        <a href="<?= e(url('/school/exams/create')) ?>"><span class="i"><?= icon('plus', 'h-[18px] w-[18px]') ?></span>Create Exam<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
        <a href="<?= e(url($isAdmin ? '/school/results' : '/school/exams?tab=past')) ?>"><span class="i"><?= icon('chart', 'h-[18px] w-[18px]') ?></span>View Results<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
        <?php if ($isAdmin): ?>
            <a href="<?= e(url('/school/students/create')) ?>"><span class="i"><?= icon('graduation', 'h-[18px] w-[18px]') ?></span>Enrol a Student<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
            <a href="<?= e(url('/school/students/import')) ?>"><span class="i"><?= icon('upload', 'h-[18px] w-[18px]') ?></span>Import from Excel<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
        <?php else: ?>
            <a href="<?= e(url('/school/my-classes')) ?>"><span class="i"><?= icon('users', 'h-[18px] w-[18px]') ?></span>My Classes<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
            <a href="<?= e(url('/school/bank')) ?>"><span class="i"><?= icon('library', 'h-[18px] w-[18px]') ?></span>Question Bank<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
        <?php endif; ?>
        <a href="<?= e(url('/school/announcements')) ?>"><span class="i"><?= icon('bell', 'h-[18px] w-[18px]') ?></span>Post Announcement<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
    </section>
</div>

<?php if (!$isAdmin && $myClasses !== []): ?>
    <section class="pd-card pd-mt" aria-labelledby="mc-title">
        <div class="pd-head"><h2 id="mc-title"><?= icon('school', 'h-5 w-5') ?> My Classes</h2><a class="pd-link" href="<?= e(url('/school/my-classes')) ?>">View All <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <div class="pd-chips">
            <?php foreach ($myClasses as $c): ?><span class="pd-cls"><b><?= e($c['class_name']) ?></b><small><?= e($c['subject_name']) ?> · <?= (int) $c['students'] ?> student<?= (int) $c['students'] === 1 ? '' : 's' ?></small></span><?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($isAdmin && $setupPct < 100): ?>
    <section class="pd-card pd-mt" aria-labelledby="cl-title">
        <div class="pd-head"><h2 id="cl-title"><?= icon('list-checks', 'h-5 w-5') ?> Setup Checklist</h2><span class="pd-pill pd-pill-sm"><?= $done ?>/<?= count($checklist) ?> done</span></div>
        <ul class="pd-tl">
            <?php foreach ($checklist as [$label, $ok, $href]): ?>
                <li><span class="dot <?= $ok ? 'green' : 'amber' ?>"><?= icon($ok ? 'check-circle' : 'alert-circle', 'h-[18px] w-[18px]') ?></span><div class="min-w-0"><b><?= e($label) ?></b></div><?php if (!$ok): ?><a class="pd-link ml-auto" href="<?= e(url($href)) ?>">Do it <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a><?php endif; ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
