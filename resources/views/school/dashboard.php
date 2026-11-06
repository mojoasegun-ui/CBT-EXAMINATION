<?php
declare(strict_types=1);
/**
 * School dashboard for administrators and teachers (real data only).
 * @var array<string,mixed> $school @var bool $isAdmin @var bool $needsSetup @var array<string,int> $stats @var array{session:?array,term:?array} $current
 * @var int|null $remaining @var list<array{0:string,1:bool,2:string}> $checklist @var list<array<string,mixed>> $activity
 * @var array{upcoming:int,live:int,total:int} $examCounts @var array{avg:?float,top:int,completed:int} $totals @var list<array<string,mixed>> $performance
 * @var list<array<string,mixed>> $live @var int $myStudents
 */
$firstName = explode(' ', trim((string) $user['name']))[0];
$done = count(array_filter($checklist, static fn (array $item): bool => $item[1]));
$setupDone = $done === count($checklist);
$term = $current['term'];
$avg = $totals['avg'];
$insight = $avg === null ? ['No results yet', 'Once students finish an exam, their average appears here.']
    : ($avg >= 70 ? ['Excellent!', 'Your students are performing very well.']
        : ($avg >= 50 ? ['Good progress', 'Most students are passing. Keep reinforcing weak topics.'] : ['Needs attention', 'Scores are low. Review the hardest questions with the class.']));
$labels = array_map(static fn (array $p): string => mb_strimwidth((string) $p['title'], 0, 12, '…'), $performance);
$initials = static fn (string $name): string => strtoupper(mb_substr(trim($name), 0, 1) . mb_substr((string) (explode(' ', trim($name))[1] ?? ''), 0, 1));
$tiles = $isAdmin
    ? [['Students', $stats['students'], 'graduation', 'blue', '/school/students'], ['Teachers', $stats['teachers'], 'users', 'green', '/school/teachers'], ['Classes', $stats['classes'], 'school', 'violet', '/school/classes'], ['Exams', $examCounts['total'], 'calendar', 'amber', '/school/exams']]
    : [['Total Exams', $examCounts['total'], 'file-text', 'blue', '/school/exams'], ['My Students', $myStudents, 'users', 'green', '/school/my-classes'], ['Avg. Score', $avg === null ? '–' : (int) round($avg) . '%', 'chart', 'violet', null], ['Top Performers', $totals['top'], 'sparkles', 'amber', null]];
?>
<section class="pd-banner" aria-labelledby="welcome-title">
    <div>
        <small>Welcome back,</small>
        <h1 id="welcome-title"><?= e($firstName) ?></h1>
        <h2><?= $isAdmin ? 'School Administration' : 'Teacher Dashboard' ?></h2>
        <p><?php if ($isAdmin && $needsSetup): ?>Finish setting up your school in one tap.<?php elseif ($isAdmin): ?><?= e($school['name']) ?><?= $term !== null ? ' · ' . e($term['name']) : '' ?>. Manage students, exams and results.<?php else: ?>Create exams, monitor results and track student performance.<?php endif; ?></p>
        <?php if ($isAdmin && $needsSetup): ?>
            <form method="post" action="<?= e(url('/school/quick-setup')) ?>" data-submit-once>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button class="cta"><span data-label>Quick setup</span><?= icon('arrow-right', 'h-4 w-4') ?></button>
            </form>
        <?php else: ?>
            <a class="cta" href="#quick-tools">Quick Actions <?= icon('arrow-right', 'h-4 w-4') ?></a>
        <?php endif; ?>
    </div>
    <svg class="art" viewBox="0 0 320 240" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
        <defs><linearGradient id="bk1" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#93c5fd"/><stop offset="1" stop-color="#2563eb"/></linearGradient><linearGradient id="bk2" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fcd34d"/><stop offset="1" stop-color="#f59e0b"/></linearGradient></defs>
        <ellipse cx="160" cy="212" rx="130" ry="14" fill="#020a2e" opacity=".3"/>
        <path d="M40 170 160 200 280 170v-26L160 174 40 144Z" fill="#1e3a8a"/><path d="M40 144 160 174 280 144 160 116Z" fill="#fff"/><path d="M40 150l120 28" stroke="#cbd5e1" stroke-width="2" fill="none"/>
        <path d="M58 128 160 152 262 128v-24L160 128 58 104Z" fill="url(#bk2)"/><path d="M58 104 160 128 262 104 160 82Z" fill="#fff"/>
        <rect x="120" y="22" width="62" height="58" rx="10" fill="#fff"/><rect x="120" y="22" width="62" height="14" rx="7" fill="#e2e8f0"/><path d="M134 50h34M134 60h24" stroke="#1e40af" stroke-width="4" stroke-linecap="round"/>
        <path d="M140 22 132 2M152 22l2-22M164 22l10-18" stroke="#0f172a" stroke-width="5" stroke-linecap="round"/>
        <rect x="196" y="54" width="86" height="58" rx="8" fill="#cbd5e1" transform="rotate(-8 239 83)"/><rect x="202" y="60" width="74" height="44" rx="5" fill="#1e293b" transform="rotate(-8 239 83)"/>
    </svg>
</section>

<section class="pd-tiles" aria-label="Key numbers">
    <?php foreach ($tiles as $i => [$label, $value, $iconName, $tone, $href]): ?>
        <<?= $href ? 'a href="' . e(url($href)) . '"' : 'div' ?> class="pd-tile">
            <span class="pd-rg <?= $tone ?>"><?= icon($iconName, 'h-6 w-6') ?></span>
            <b <?= is_int($value) ? 'data-count="' . $value . '"' : '' ?>><?= is_int($value) ? e(number_format($value)) : e((string) $value) ?></b>
            <span><?= e($label) ?></span>
        </<?= $href ? 'a' : 'div' ?>>
    <?php endforeach; ?>
</section>

<div class="pd-grid pd-stack">
    <div class="pd-autogrid">
        <section class="pd-card" aria-labelledby="ra-title">
            <div class="pd-head"><h2 id="ra-title"><?= icon('activity', 'h-5 w-5') ?> Recent Activity</h2><a class="pd-link" href="<?= e(url($isAdmin ? '/school/activity' : '/school/exams?tab=past')) ?>">View All <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a></div>
            <?php if ($performance === [] && $live === []): ?>
                <div class="pd-empty"><span class="ico"><?= icon('clock', 'h-6 w-6') ?></span><b>Nothing yet</b><span>Exam activity and results appear here once students write.</span></div>
            <?php else: ?>
                <ul class="pd-tl">
                    <?php foreach ($live as $row): ?>
                        <li><span class="dot blue"><?= icon('activity', 'h-[18px] w-[18px]') ?></span><div class="min-w-0"><b><?= e($row['exam_title']) ?></b><small><?= e($row['student_name']) ?> is writing now</small></div><time>live</time></li>
                    <?php endforeach; ?>
                    <?php foreach (array_slice(array_reverse($performance), 0, 4) as $i => $p): ?>
                        <li><span class="dot <?= $i % 2 ? 'violet' : 'green' ?>"><?= icon('check-circle', 'h-[18px] w-[18px]') ?></span><div class="min-w-0"><b><?= e(mb_strimwidth((string) $p['title'], 0, 34, '…')) ?></b><small>Completed by <?= (int) $p['taken'] ?> student<?= (int) $p['taken'] === 1 ? '' : 's' ?> · avg <?= e((string) round((float) $p['avg_percent'])) ?>%</small></div><time><?= e(time_ago((string) $p['last_at'])) ?></time></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section id="quick-tools" class="pd-tools" aria-labelledby="qt-title">
            <h2 id="qt-title"><?= icon('dashboard', 'h-5 w-5') ?> Quick Tools</h2>
            <a href="<?= e(url('/school/exams/create')) ?>"><span class="i"><?= icon('plus', 'h-[18px] w-[18px]') ?></span>Create Exam<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
            <a href="<?= e(url($isAdmin ? '/school/results' : '/school/exams?tab=past')) ?>"><span class="i"><?= icon('chart', 'h-[18px] w-[18px]') ?></span>View Results<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
            <?php if ($isAdmin): ?>
                <a href="<?= e(url('/school/students')) ?>"><span class="i"><?= icon('users', 'h-[18px] w-[18px]') ?></span>Manage Students<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
                <a href="<?= e(url('/school/announcements')) ?>"><span class="i"><?= icon('bell', 'h-[18px] w-[18px]') ?></span>Post Announcement<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
            <?php else: ?>
                <a href="<?= e(url('/school/my-classes')) ?>"><span class="i"><?= icon('users', 'h-[18px] w-[18px]') ?></span>My Classes<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
                <a href="<?= e(url('/school/announcements')) ?>"><span class="i"><?= icon('bell', 'h-[18px] w-[18px]') ?></span>Post Announcement<span class="go"><?= icon('chevron-right', 'h-4 w-4') ?></span></a>
            <?php endif; ?>
        </section>
    </div>

    <section class="pd-card" aria-labelledby="po-title">
        <div class="pd-head"><h2 id="po-title"><?= icon('chart', 'h-5 w-5') ?> Performance Overview</h2><span class="pd-pill pd-pill-sm"><?= $totals['completed'] ?> results · 30 days</span></div>
        <?php if (count($performance) < 2): ?>
            <div class="pd-empty"><span class="ico"><?= icon('chart', 'h-6 w-6') ?></span><b>Not enough results to chart yet</b><span>The average score of your last exams will be drawn here after two exams have results.</span></div>
        <?php else: ?>
            <div class="pd-perf">
                <div>
                    <?php
                    $lines = [['label' => 'Average score', 'color' => '#2563eb', 'values' => array_map(static fn (array $p): float => (float) $p['avg_percent'], $performance)]];
                    $chartMax = 100;
                    $chartUnit = '%';
                    $chartLabel = 'Average score of the latest exams';
                    require __DIR__ . '/../partials/portal/line-chart.php';
                    ?>
                </div>
                <div class="pd-insight"><b><?= e($insight[0]) ?></b><span><?= e($insight[1]) ?></span></div>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($isAdmin && !$setupDone): ?>
        <section class="pd-card" aria-labelledby="cl-title">
            <div class="pd-head"><h2 id="cl-title"><?= icon('list-checks', 'h-5 w-5') ?> Setup Checklist</h2><span class="pd-pill pd-pill-sm"><?= $done ?>/<?= count($checklist) ?> done</span></div>
            <ul class="pd-tl">
                <?php foreach ($checklist as [$label, $ok, $href]): ?>
                    <li><span class="dot <?= $ok ? 'green' : 'amber' ?>"><?= icon($ok ? 'check-circle' : 'alert-circle', 'h-[18px] w-[18px]') ?></span><div class="min-w-0"><b><?= e($label) ?></b></div><?php if (!$ok): ?><a class="pd-link ml-auto" href="<?= e(url($href)) ?>">Do it <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a><?php endif; ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</div>
