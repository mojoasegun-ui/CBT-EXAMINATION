<?php
declare(strict_types=1);
/**
 * Owner Console dashboard (real data only).
 * @var array<string,int> $schoolCounts @var int $totalSchools @var int $students @var int $staff @var int $attemptsToday @var int $attemptsTotal14
 * @var list<array{date:string,count:int}> $attemptSeries @var list<array{date:string,count:int}> $sittingSeries @var array{pending:int,reserved:int,failed:int} $queue
 * @var list<array<string,mixed>> $recentSchools @var list<array<string,mixed>> $activity @var array|null $lastBackup @var int $pendingMigrations
 * @var list<array<string,mixed>> $live @var int $liveCount @var int $bankCount
 */
$hour = (int) (new DateTimeImmutable('now'))->format('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', trim((string) $user['name']))[0];
$backupAge = $lastBackup === null ? null : time() - $lastBackup['time'];
$backupOk = $lastBackup !== null && $backupAge <= 7 * 86400;
$allGood = $pendingMigrations === 0 && $queue['failed'] === 0 && $backupOk;
$totalUsers = $students + $staff;
$statusColors = ['active' => '#10b981', 'trial' => '#3b82f6', 'suspended' => '#f59e0b', 'archived' => '#94a3b8'];
$segments = [];
$offset = 0.0;
foreach ($statusColors as $key => $color) {
    $share = $totalSchools > 0 ? ($schoolCounts[$key] ?? 0) / $totalSchools * 100 : 0;
    if ($share > 0) {
        $segments[] = [$color, round($share, 2), round($offset, 2)];
        $offset += $share;
    }
}
$labels = array_map(static fn (array $d): string => gmdate('M j', strtotime($d['date'])), $attemptSeries);
$health = [
    ['Database', $pendingMigrations === 0 ? 'Up to date' : $pendingMigrations . ' update(s) waiting', $pendingMigrations === 0, 'database'],
    ['Backups', $lastBackup === null ? 'None yet' : time_ago(gmdate('Y-m-d H:i:s', $lastBackup['time'])), $backupOk, 'download'],
    ['Job queue', $queue['failed'] === 0 ? $queue['pending'] . ' waiting' : $queue['failed'] . ' failed', $queue['failed'] === 0, 'mail'],
];
$initials = static fn (string $name): string => strtoupper(mb_substr(trim($name), 0, 1) . (mb_substr((string) (explode(' ', trim($name))[1] ?? ''), 0, 1)));
?>
<div class="pd-hello">
    <div>
        <h1><?= e($greeting) ?>, <b><?= e($firstName) ?></b></h1>
        <p>Control. Monitor. Grow — all in one powerful platform.</p>
    </div>
    <span class="pd-pill"><?= icon('calendar', 'h-4 w-4') ?> <?= e((new DateTimeImmutable('now'))->format('D, j M Y')) ?></span>
</div>

<section class="pd-stats" aria-label="Key numbers">
    <a class="pd-stat blue" href="<?= e(url('/console/schools')) ?>">
        <div><div class="top"><span class="ico"><?= icon('school', 'h-6 w-6') ?></span><span class="lab">Total Schools</span></div>
        <div class="num" data-count="<?= (int) $totalSchools ?>"><?= e(number_format($totalSchools)) ?></div><div class="sub"><?= (int) ($schoolCounts['active'] ?? 0) ?> active · <?= (int) ($schoolCounts['trial'] ?? 0) ?> on trial</div></div>
        <span class="go">View Schools <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('school', 'h-full w-full') ?></span>
    </a>
    <a class="pd-stat violet" href="<?= e(url('/console/schools')) ?>">
        <div><div class="top"><span class="ico"><?= icon('users', 'h-6 w-6') ?></span><span class="lab">Total Users</span></div>
        <div class="num" data-count="<?= (int) $totalUsers ?>"><?= e(number_format($totalUsers)) ?></div><div class="sub"><?= e(number_format($students)) ?> students · <?= e(number_format($staff)) ?> staff</div></div>
        <span class="go">Across schools <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('users', 'h-full w-full') ?></span>
    </a>
    <a class="pd-stat green" href="<?= e(url('/console/schools')) ?>">
        <div><div class="top"><span class="ico"><?= icon('activity', 'h-6 w-6') ?></span><span class="lab">Ongoing CBT Exams</span></div>
        <div class="num" data-count="<?= (int) $liveCount ?>"><?= e(number_format($liveCount)) ?></div><div class="sub"><?= $liveCount === 0 ? 'No one is writing right now' : 'Students writing right now' ?></div></div>
        <span class="go">Live now <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('activity', 'h-full w-full') ?></span>
    </a>
    <a class="pd-stat amber" href="<?= e(url('/console/bank')) ?>">
        <div><div class="top"><span class="ico"><?= icon('library', 'h-6 w-6') ?></span><span class="lab">Question Bank</span></div>
        <div class="num" data-count="<?= (int) $bankCount ?>"><?= e(number_format($bankCount)) ?></div><div class="sub">Published platform questions</div></div>
        <span class="go">Open bank <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('library', 'h-full w-full') ?></span>
    </a>
</section>

<div class="pd-grid two">
    <section class="pd-card" aria-labelledby="an-title">
        <div class="pd-head"><h2 id="an-title"><?= icon('chart', 'h-5 w-5') ?> Platform Analytics</h2><span class="pd-pill pd-pill-sm">Last 14 days</span></div>
        <?php
        $lines = [
            ['label' => 'Practice attempts', 'color' => '#2563eb', 'values' => array_column($attemptSeries, 'count')],
            ['label' => 'School exams', 'color' => '#10b981', 'values' => array_column($sittingSeries, 'count')],
        ];
        $chartLabel = 'Practice attempts and school exams per day, last 14 days';
        require __DIR__ . '/../partials/portal/line-chart.php';
        ?>
    </section>

    <section class="pd-card" aria-labelledby="live-title">
        <div class="pd-head"><h2 id="live-title"><?= icon('scan-face', 'h-5 w-5') ?> Live CBT Monitoring <?php if ($liveCount > 0): ?><span class="pd-live">Live</span><?php endif; ?></h2><a class="pd-link" href="<?= e(url('/console/schools')) ?>">View all <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <?php if ($live === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('clock', 'h-6 w-6') ?></span><b>No exam is running right now</b><span>When students write a school exam, they show up here live.</span></div>
        <?php else: ?>
            <div class="pd-table">
                <?php foreach ($live as $row): $left = max(0, strtotime((string) $row['deadline_at'] . ' UTC') - time()); ?>
                    <div class="pd-row">
                        <span class="pd-av"><?= e($initials((string) $row['student_name'])) ?></span>
                        <div><b><?= e($row['student_name']) ?></b><small><?= e($row['school_name']) ?></small></div>
                        <div class="col3"><b><?= e($row['exam_title']) ?></b><small><?= e($row['admission_no'] ?? '') ?></small></div>
                        <span class="pd-chip"><?= sprintf('%d:%02d left', intdiv($left, 60), $left % 60) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="pd-grid three">
    <section class="pd-card" aria-labelledby="so-title">
        <div class="pd-head"><h2 id="so-title"><?= icon('school', 'h-5 w-5') ?> Schools Overview</h2><a class="pd-link" href="<?= e(url('/console/schools')) ?>">View all <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <div class="pd-donut-wrap">
            <div class="pd-donut" role="img" aria-label="<?= (int) $totalSchools ?> schools by status">
                <svg viewBox="0 0 36 36" aria-hidden="true"><circle cx="18" cy="18" r="15.915" fill="none" stroke="#e2e8f0" stroke-width="4.2"/>
                    <?php foreach ($segments as [$color, $share, $off]): ?><circle cx="18" cy="18" r="15.915" fill="none" stroke="<?= $color ?>" stroke-width="4.2" stroke-dasharray="<?= $share ?> <?= round(100 - $share, 2) ?>" stroke-dashoffset="<?= -$off ?>"/><?php endforeach; ?></svg>
                <div class="mid"><div><b><?= (int) $totalSchools ?></b><small>Schools</small></div></div></div>
            <div class="pd-keys">
                <div><svg class="pd-dot" viewBox="0 0 10 10" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="#10b981"/></svg>Active <b><?= (int) ($schoolCounts['active'] ?? 0) ?></b></div>
                <div><svg class="pd-dot" viewBox="0 0 10 10" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="#3b82f6"/></svg>On trial <b><?= (int) ($schoolCounts['trial'] ?? 0) ?></b></div>
                <div><svg class="pd-dot" viewBox="0 0 10 10" aria-hidden="true"><circle cx="5" cy="5" r="5" fill="#f59e0b"/></svg>Suspended <b><?= (int) ($schoolCounts['suspended'] ?? 0) ?></b></div>
            </div>
        </div>
        <?php if ($totalSchools === 0): ?><a class="pd-link pd-link-top" href="<?= e(url('/console/schools/create')) ?>"><?= icon('plus', 'h-4 w-4') ?> Add your first school</a><?php endif; ?>
    </section>

    <section class="pd-card" aria-labelledby="ra-title">
        <div class="pd-head"><h2 id="ra-title"><?= icon('activity', 'h-5 w-5') ?> Recent Activities</h2><a class="pd-link" href="<?= e(url('/console/audit')) ?>">View all <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <?php if ($activity === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('list-checks', 'h-6 w-6') ?></span><span>Actions by you and school administrators appear here.</span></div>
        <?php else: ?>
            <ul class="pd-tl">
                <?php $tones = ['blue', 'green', 'violet', 'amber']; foreach (array_slice($activity, 0, 5) as $i => $event): ?>
                    <li><span class="dot <?= $tones[$i % 4] ?>"><?= icon(['check-circle', 'activity', 'users', 'sliders'][$i % 4], 'h-[18px] w-[18px]') ?></span>
                        <div class="min-w-0"><b><?= e(mb_strimwidth((string) $event['summary'], 0, 52, '…')) ?></b><small><?= e($event['actor_name'] ?? 'System') ?><?= $event['school_name'] ? ' · ' . e($event['school_name']) : '' ?></small></div>
                        <time><?= e(time_ago((string) $event['created_at'])) ?></time></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pd-card" aria-labelledby="sh-title">
        <div class="pd-head"><h2 id="sh-title"><?= icon('shield', 'h-5 w-5') ?> System Health</h2><a class="pd-link" href="<?= e(url('/console/system')) ?>">Details <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <div class="pd-health">
            <?php foreach ($health as [$name, $text, $ok, $iconName]): $c = $ok ? '#10b981' : '#f59e0b'; ?>
                <div><div class="pd-ring"><svg viewBox="0 0 36 36" aria-hidden="true"><circle cx="18" cy="18" r="15.915" fill="none" stroke="<?= $c ?>" stroke-opacity=".18" stroke-width="3.2"/><circle cx="18" cy="18" r="15.915" fill="none" stroke="<?= $c ?>" stroke-width="3.2" stroke-linecap="round" stroke-dasharray="100 0"/></svg><span class="<?= $ok ? 'text-emerald-500' : 'text-amber-500' ?>"><?= icon($ok ? 'check-circle' : 'alert', 'h-6 w-6') ?></span></div><b><?= e($name) ?></b><small><?= e($text) ?></small></div>
            <?php endforeach; ?>
        </div>
        <div class="pd-ok <?= $allGood ? '' : 'warn' ?>"><?= icon($allGood ? 'check-circle' : 'alert', 'h-5 w-5') ?><span><?= $allGood ? 'Platform is running smoothly' : 'Some items need your attention' ?></span></div>
        <?php if (!$backupOk): ?>
            <form method="post" action="<?= e(url('/console/system/backup')) ?>" class="mt-3" data-submit-once>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button class="btn btn-secondary w-full"><?= icon('download') ?><span data-label>Back up now</span></button>
            </form>
        <?php endif; ?>
    </section>
</div>
