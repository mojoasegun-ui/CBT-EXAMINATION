<?php
declare(strict_types=1);
/**
 * Student dashboard in the platform's pro style. Real data only; scores appear only once the school has released them.
 * @var array<string,mixed>|null $student @var list<array<string,mixed>> $subjects @var array{session:?array,term:?array} $current @var array<string,mixed> $school
 * @var list<array<string,mixed>> $exams @var array<int,array<string,mixed>> $sittings @var list<array<string,mixed>> $recent @var array{taken:int,avg:?float,best:?float} $summary
 * @var array{current:int,best:int} $streak @var int $answeredToday @var list<float> $trend @var list<string> $trendLabels
 */
$firstName = explode(' ', trim((string) ($student['name'] ?? $user['name'])))[0];
$term = $current['term'];
$now = time();
$live = [];
$next = null;
foreach ($exams as $x) {
    $start = strtotime($x['starts_at'] . ' UTC');
    $end = strtotime($x['ends_at'] . ' UTC');
    $sit = $sittings[(int) $x['id']] ?? null;
    $done = $sit !== null && $sit['status'] !== 'in_progress';
    $paper = (json_decode((string) ($x['settings'] ?? ''), true)['delivery'] ?? 'cbt') === 'paper';
    if ($paper || $done) {
        continue;
    }
    if ($now >= $start && $now <= $end) {
        $live[] = $x;
    } elseif ($now < $start && $next === null) {
        $next = $x;
    }
}
$headline = $live[0] ?? $next;
$summaryAvg = $summary['avg'];
$grade = static fn (?float $p): string => $p === null ? 'text-slate-400' : ($p >= 70 ? 'text-emerald-600' : ($p >= 50 ? 'text-amber-600' : 'text-rose-600'));
?>
<div class="pd-hello">
    <div>
        <h1>Welcome Back, <b><?= e($firstName) ?></b></h1>
        <p><?= e(($student['class_name'] ?? 'No class yet') . ($term ? ' · ' . $term['name'] . ', ' . $term['session_name'] : '')) ?></p>
    </div>
    <span class="pd-pill"><?= icon('school', 'h-4 w-4') ?> <?= e((string) ($school['short_name'] ?: $school['name'])) ?></span>
</div>

<!-- Next up -->
<section class="pd-banner pd-next" aria-labelledby="next-title">
    <div>
        <small><?= $live !== [] ? 'Open right now' : ($next !== null ? 'Coming up next' : 'Nothing scheduled') ?></small>
        <?php if ($headline !== null): ?>
            <h1 id="next-title"><?= e($headline['title']) ?></h1>
            <p><?= e((string) $headline['subjects']) ?> · <?= (int) $headline['question_total'] ?> questions · <?= (int) $headline['duration_minutes'] ?> min<br>
                <?= $live !== [] ? 'Closes ' . e(local_date((string) $headline['ends_at'], 'g:i a')) : 'Starts ' . e(local_date((string) $headline['starts_at'], 'D j M, g:i a')) . ' (' . e(time_until(strtotime($headline['starts_at'] . ' UTC'))) . ')' ?></p>
            <a class="cta" href="<?= e(url('/student/exams/' . (int) $headline['id'])) ?>"><?= $live !== [] ? (isset($sittings[(int) $headline['id']]) ? 'Continue exam' : 'Start exam') : 'View details' ?> <?= icon('arrow-right', 'h-4 w-4') ?></a>
        <?php else: ?>
            <h1 id="next-title">You are all caught up</h1>
            <p>No exam is scheduled right now. Keep sharp with free practice questions.</p>
            <a class="cta" href="<?= e(url('/practice')) ?>">Open Practice Centre <?= icon('arrow-right', 'h-4 w-4') ?></a>
        <?php endif; ?>
    </div>
    <svg class="art" viewBox="0 0 320 240" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
        <defs><linearGradient id="sb1" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#93c5fd"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
        <ellipse cx="160" cy="212" rx="130" ry="14" fill="#020a2e" opacity=".3"/>
        <path d="M40 170 160 200 280 170v-26L160 174 40 144Z" fill="#1e3a8a"/><path d="M40 144 160 174 280 144 160 116Z" fill="#fff"/>
        <path d="M58 128 160 152 262 128v-24L160 128 58 104Z" fill="url(#sb1)"/><path d="M58 104 160 128 262 104 160 82Z" fill="#fff"/>
        <path d="M160 20 76 52l84 32 84-32-84-32Z" fill="#0f172a"/><path d="M110 70v22c14 10 32 16 50 16s36-6 50-16V70l-50 20-50-20Z" fill="#1e293b"/>
        <path d="M160 52 244 56v36" stroke="#f59e0b" stroke-width="4" stroke-linecap="round" fill="none"/><circle cx="244" cy="56" r="5" fill="#fbbf24"/>
    </svg>
</section>

<section class="pd-stats" aria-label="My numbers">
    <a class="pd-stat blue" href="<?= e(url('/student/results')) ?>"><div><div class="top"><span class="ico"><?= icon('file-text', 'h-6 w-6') ?></span><span class="lab">Exams Taken</span></div>
        <div class="num" data-count="<?= (int) $summary['taken'] ?>"><?= (int) $summary['taken'] ?></div><div class="sub">Since you joined</div></div>
        <span class="go">My Results <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('file-text', 'h-full w-full') ?></span></a>
    <a class="pd-stat violet" href="<?= e(url('/student/results')) ?>"><div><div class="top"><span class="ico"><?= icon('chart', 'h-6 w-6') ?></span><span class="lab">Average Score</span></div>
        <div class="num"><?= $summaryAvg === null ? '–' : (int) round($summaryAvg) . '%' ?></div><div class="sub"><?= $summaryAvg === null ? 'After results are released' : 'Across released results' ?></div></div>
        <span class="go">See details <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('chart', 'h-full w-full') ?></span></a>
    <a class="pd-stat green" href="<?= e(url('/student/results')) ?>"><div><div class="top"><span class="ico"><?= icon('sparkles', 'h-6 w-6') ?></span><span class="lab">Best Score</span></div>
        <div class="num"><?= $summary['best'] === null ? '–' : (int) round($summary['best']) . '%' ?></div><div class="sub">Your personal best</div></div>
        <span class="go">View all <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('sparkles', 'h-full w-full') ?></span></a>
    <a class="pd-stat amber" href="<?= e(url('/practice/progress')) ?>"><div><div class="top"><span class="ico"><?= icon('activity', 'h-6 w-6') ?></span><span class="lab">Practice Streak</span></div>
        <div class="num" data-count="<?= (int) $streak['current'] ?>"><?= (int) $streak['current'] ?></div><div class="sub"><?= $answeredToday > 0 ? $answeredToday . ' answered today' : 'Practise today to grow it' ?></div></div>
        <span class="go">My progress <?= icon('arrow-right', 'h-3.5 w-3.5') ?></span><span class="ghost"><?= icon('activity', 'h-full w-full') ?></span></a>
</section>

<div class="pd-grid two">
    <section class="pd-card" aria-labelledby="ex-title">
        <div class="pd-head"><h2 id="ex-title"><?= icon('calendar', 'h-5 w-5') ?> My Exams</h2><span class="pd-pill pd-pill-sm"><?= count($exams) ?></span></div>
        <?php if ($exams === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('calendar', 'h-6 w-6') ?></span><b>No exams scheduled yet</b><span>When your school schedules a test you will see the date, time and a countdown here.</span></div>
        <?php else: ?>
            <ul class="pd-tl pd-exams">
                <?php foreach (array_slice($exams, 0, 8) as $x):
                    $start = strtotime($x['starts_at'] . ' UTC');
                    $end = strtotime($x['ends_at'] . ' UTC');
                    $isLive = $now >= $start && $now <= $end;
                    $over = $now > $end;
                    $sit = $sittings[(int) $x['id']] ?? null;
                    $isPaper = (json_decode((string) ($x['settings'] ?? ''), true)['delivery'] ?? 'cbt') === 'paper';
                    $link = e(url('/student/exams/' . $x['id']));
                    ?>
                    <li>
                        <span class="dot <?= $isLive ? 'green' : 'blue' ?>"><?= icon($isLive ? 'activity' : 'calendar', 'h-[18px] w-[18px]') ?></span>
                        <div class="min-w-0 flex-1">
                            <b><?= e($x['title']) ?></b>
                            <small><?= e((string) $x['subjects']) ?> · <?= (int) $x['question_total'] ?> Qs · <?= (int) $x['duration_minutes'] ?> min</small>
                            <small><?= e(local_date($x['starts_at'], 'D j M, g:i a')) ?></small>
                        </div>
                        <div class="pd-act">
                            <?php if ($isPaper && $sit === null): ?><span class="badge badge-gray">Paper exam</span>
                            <?php elseif ($sit !== null && $sit['status'] !== 'in_progress'): ?><span class="badge badge-green">Submitted</span>
                            <?php elseif ($isLive): ?><a class="btn btn-primary btn-sm" href="<?= $link ?>"><?= $sit !== null ? 'Continue' : 'Start' ?></a>
                            <?php elseif ($over): ?><span class="badge badge-gray"><?= $sit === null ? 'Missed' : 'Closed' ?></span>
                            <?php else: ?><a class="btn btn-secondary btn-sm" href="<?= $link ?>">Details</a><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pd-card" aria-labelledby="po-title">
        <div class="pd-head"><h2 id="po-title"><?= icon('chart', 'h-5 w-5') ?> Performance Overview</h2><span class="pd-pill pd-pill-sm">My scores</span></div>
        <?php if (count($trend) < 2): ?>
            <div class="pd-empty"><span class="ico"><?= icon('chart', 'h-6 w-6') ?></span><b>Your score chart appears here</b><span>After two released results, you will see how you are improving.</span></div>
        <?php else: ?>
            <?php
            $lines = [['label' => 'My score', 'color' => '#2563eb', 'values' => $trend]];
            $labels = $trendLabels;
            $chartMax = 100;
            $chartUnit = '%';
            $chartLabel = 'My latest exam scores';
            require __DIR__ . '/../partials/portal/line-chart.php';
            ?>
        <?php endif; ?>
    </section>
</div>

<div class="pd-grid three">
    <section class="pd-card" aria-labelledby="rr-title">
        <div class="pd-head"><h2 id="rr-title"><?= icon('check-circle', 'h-5 w-5') ?> Recent Results</h2><a class="pd-link" href="<?= e(url('/student/results')) ?>">View All <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
        <?php if ($recent === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('file-text', 'h-6 w-6') ?></span><span>Released results show here.</span></div>
        <?php else: ?>
            <ul class="pd-tl">
                <?php foreach ($recent as $r): ?>
                    <li><span class="dot <?= (float) $r['percent'] >= 50 ? 'green' : 'amber' ?>"><?= icon('check-circle', 'h-[18px] w-[18px]') ?></span>
                        <div class="min-w-0 flex-1"><b><?= e(mb_strimwidth((string) $r['title'], 0, 30, '…')) ?></b><small><?= e(time_ago((string) $r['submitted_at'])) ?><?= !empty($r['grade']['grade']) && $r['grade']['grade'] !== '—' ? ' · Grade ' . e((string) $r['grade']['grade']) : '' ?></small></div>
                        <time class="pd-score <?= $grade((float) $r['percent']) ?>"><?= (int) round((float) $r['percent']) ?>%</time></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pd-card" aria-labelledby="su-title">
        <div class="pd-head"><h2 id="su-title"><?= icon('book', 'h-5 w-5') ?> My Subjects</h2><span class="pd-pill pd-pill-sm"><?= count($subjects) ?></span></div>
        <?php if ($subjects === []): ?>
            <div class="pd-empty"><span class="ico"><?= icon('book', 'h-6 w-6') ?></span><span>Your subjects show once your class is set up.</span></div>
        <?php else: ?>
            <div class="pd-chips"><?php foreach ($subjects as $s): ?><span class="pd-cls"><b><?= e($s['name']) ?></b></span><?php endforeach; ?></div>
        <?php endif; ?>
    </section>

    <section id="quick-tools" class="pd-tools" aria-labelledby="qt-title">
        <h2 id="qt-title"><?= icon('dashboard', 'h-5 w-5') ?> Quick Tools</h2>
        <a href="<?= e(url('/practice')) ?>"><span class="i"><?= icon('sparkles', 'h-[18px] w-[18px]') ?></span>Practice Centre<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
        <a href="<?= e(url('/practice/mock')) ?>"><span class="i"><?= icon('clock', 'h-[18px] w-[18px]') ?></span>JAMB Mock Exam<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
        <a href="<?= e(url('/student/results')) ?>"><span class="i"><?= icon('chart', 'h-[18px] w-[18px]') ?></span>My Results<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
        <a href="<?= e(url('/student/announcements')) ?>"><span class="i"><?= icon('bell', 'h-[18px] w-[18px]') ?></span>Announcements<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
        <a href="<?= e(url('/student/account/password')) ?>"><span class="i"><?= icon('key', 'h-[18px] w-[18px]') ?></span>Change Password<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
    </section>
</div>
