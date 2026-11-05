<?php
declare(strict_types=1);
/**
 * @var array<string,int> $schoolCounts @var int $totalSchools @var int $students @var int $staff @var int $attemptsToday @var int $attemptsTotal14
 * @var list<array{date:string,count:int}> $attemptSeries @var array{pending:int,reserved:int,failed:int} $queue
 * @var list<array<string,mixed>> $recentSchools @var list<array<string,mixed>> $activity @var array|null $lastBackup @var int $pendingMigrations
 */
$hour = (int) (new DateTimeImmutable('now'))->format('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', trim((string) $user['name']))[0];
$statusBadge = ['trial' => 'badge-blue', 'active' => 'badge-green', 'suspended' => 'badge-amber', 'archived' => 'badge-gray'];
$backupAge = $lastBackup === null ? null : time() - $lastBackup['time'];
$issues = ($pendingMigrations > 0 ? 1 : 0) + ($queue['failed'] > 0 ? 1 : 0) + ($lastBackup === null || $backupAge > 7 * 86400 ? 1 : 0);
?>
<!-- Hero -->
<section class="bg-mesh relative overflow-hidden rounded-[32px] border border-line">
    <div class="bg-dots pointer-events-none absolute inset-0 opacity-50 [mask-image:radial-gradient(60%_80%_at_30%_50%,black,transparent)]"></div>
    <div class="relative grid items-center gap-6 p-7 sm:p-10 lg:grid-cols-[1.1fr_1fr]">
        <div>
            <p class="eyebrow"><?= e((new DateTimeImmutable('now'))->format('l · j F Y')) ?></p>
            <h1 class="mt-3 font-display text-[34px] font-semibold leading-[1.08] text-ink sm:text-[44px]"><?= e($greeting) ?>, <span class="text-signature"><?= e($firstName) ?></span></h1>
            <p class="mt-4 max-w-md text-[15px] leading-7 text-muted">
                <?= $totalSchools === 0
                    ? 'Your platform is ready. Add your first school to give it a branded portal for classes, exams and results.'
                    : sprintf('%s school%s on the platform, %s exam attempt%s in the last 14 days.', number_format($totalSchools), $totalSchools === 1 ? '' : 's', number_format($attemptsTotal14), $attemptsTotal14 === 1 ? '' : 's') ?>
            </p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="<?= e(url('/console/schools/create')) ?>" class="btn btn-primary btn-lg"><?= icon('plus') ?>Add a school</a>
                <a href="<?= e(url('/console/system')) ?>" class="btn btn-secondary btn-lg">
                    <span class="h-2 w-2 rounded-full <?= $issues === 0 ? 'bg-emerald-500 shadow-[0_0_0_4px_rgb(16_185_129/.15)]' : 'bg-amber-500 shadow-[0_0_0_4px_rgb(245_158_11/.18)]' ?>"></span>
                    <?= $issues === 0 ? 'System healthy' : $issues . ' item' . ($issues === 1 ? '' : 's') . ' need attention' ?>
                </a>
            </div>
        </div>
        <div class="relative hidden justify-end lg:flex">
            <img src="<?= e(asset('images/art/results-insight.svg')) ?>" alt="" width="560" height="420" class="float-slow -my-6 w-full max-w-[460px] drop-shadow-[0_30px_40px_rgba(46,16,101,0.12)]">
        </div>
    </div>
</section>

<!-- Key numbers -->
<section class="mt-8 grid grid-cols-2 gap-4 xl:grid-cols-4" aria-label="Key numbers" data-stagger>
    <?php foreach ([
        ['Schools', $totalSchools, 'school', sprintf('%d active · %d on trial', $schoolCounts['active'], $schoolCounts['trial']), url('/console/schools')],
        ['Students', $students, 'graduation', 'Across all schools', null],
        ['Staff accounts', $staff, 'users', 'Admins, teachers & reviewers', null],
        ['Exams today', $attemptsToday, 'activity', number_format($attemptsTotal14) . ' in the last 14 days', null],
    ] as [$label, $value, $iconName, $sub, $href]): ?>
        <div class="card card-hover relative p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <span class="icon-tile"><?= icon($iconName) ?></span>
                <?php if ($href !== null): ?><span class="text-subtle"><?= icon('arrow-right', 'h-4 w-4') ?></span><?php endif; ?>
            </div>
            <p class="stat-value mt-6 tabular-nums" data-count="<?= (int) $value ?>"><?= e(number_format($value)) ?></p>
            <p class="stat-label mt-2"><?= e($label) ?></p>
            <p class="mt-1 truncate text-xs text-subtle"><?= e($sub) ?></p>
            <?php if ($href !== null): ?><a href="<?= e($href) ?>" class="absolute inset-0 rounded-[22px]" aria-label="View <?= e(strtolower($label)) ?>"></a><?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <!-- Activity chart -->
    <section class="card lg:col-span-2" aria-labelledby="chart-title">
        <div class="card-header">
            <div>
                <p class="eyebrow">Last 14 days</p>
                <h2 id="chart-title" class="card-title mt-1">Exam attempts</h2>
            </div>
            <div class="text-right">
                <p class="font-display text-[30px] font-semibold leading-none tabular-nums text-ink" data-count="<?= (int) $attemptsTotal14 ?>"><?= e(number_format($attemptsTotal14)) ?></p>
                <p class="mt-1 text-xs text-muted">total attempts</p>
            </div>
        </div>
        <div class="px-6 pb-6 pt-2">
            <?php $series = $attemptSeries; $chartLabel = 'Exam attempts per day, last 14 days'; require __DIR__ . '/../partials/portal/bar-chart.php'; ?>
        </div>
    </section>

    <!-- Platform health -->
    <section class="card" aria-labelledby="health-title">
        <div class="card-header">
            <div><p class="eyebrow">Status</p><h2 id="health-title" class="card-title mt-1">Platform health</h2></div>
            <a class="btn btn-secondary btn-sm" href="<?= e(url('/console/system')) ?>">Details</a>
        </div>
        <ul class="space-y-2 px-4 pb-4">
            <?php
            $rows = [
                ['database', 'Database updates', $pendingMigrations === 0 ? 'Up to date' : $pendingMigrations . ' pending', $pendingMigrations === 0 ? 'good' : 'warn'],
                ['download', 'Last backup', $lastBackup === null ? 'None yet' : time_ago(gmdate('Y-m-d H:i:s', $lastBackup['time'])), $lastBackup === null || $backupAge > 7 * 86400 ? 'warn' : 'good'],
                ['mail', 'Email queue', $queue['pending'] . ' waiting', 'good'],
                ['alert', 'Failed jobs', (string) $queue['failed'], $queue['failed'] === 0 ? 'good' : 'bad'],
            ];
            $tone = ['good' => ['check-circle', 'text-emerald-500', 'OK'], 'warn' => ['alert', 'text-amber-500', 'Needs attention'], 'bad' => ['alert-circle', 'text-red-500', 'Problem']];
            foreach ($rows as [$iconName, $label, $value, $state]): [$stateIcon, $stateColor, $stateText] = $tone[$state]; ?>
                <li class="flex items-center gap-3 rounded-2xl bg-soft px-4 py-3">
                    <span class="icon-soft h-9 w-9"><?= icon($iconName, 'h-[17px] w-[17px]') ?></span>
                    <span class="flex-1 text-sm font-medium text-ink"><?= e($label) ?></span>
                    <span class="text-sm tabular-nums text-muted"><?= e($value) ?></span>
                    <span class="<?= $stateColor ?>"><?= icon($stateIcon, 'h-4 w-4', $stateText) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($lastBackup === null || $backupAge > 7 * 86400): ?>
            <form method="post" action="<?= e(url('/console/system/backup')) ?>" class="px-4 pb-5" data-submit-once>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button class="btn btn-secondary w-full"><?= icon('download') ?><span data-label>Back up now</span></button>
            </form>
        <?php endif; ?>
    </section>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <!-- Newest schools -->
    <section class="card overflow-hidden lg:col-span-2" aria-labelledby="schools-title">
        <div class="card-header">
            <div><p class="eyebrow">Customers</p><h2 id="schools-title" class="card-title mt-1">Newest schools</h2></div>
            <a class="btn btn-secondary btn-sm" href="<?= e(url('/console/schools')) ?>">View all</a>
        </div>
        <?php if ($recentSchools === []): ?>
            <div class="empty">
                <img src="<?= e(asset('images/art/exam-screen.svg')) ?>" alt="" class="float-slow w-56" width="560" height="420" loading="lazy">
                <div>
                    <p class="font-display text-xl font-semibold text-ink">No schools yet</p>
                    <p class="mt-1 max-w-sm text-sm text-muted">Each school gets its own branded portal for classes, exams and results.</p>
                </div>
                <a class="btn btn-primary" href="<?= e(url('/console/schools/create')) ?>"><?= icon('plus') ?>Add a school</a>
            </div>
        <?php else: ?>
            <ul class="space-y-1 px-3 pb-3">
                <?php foreach ($recentSchools as $row): ?>
                    <li>
                        <a href="<?= e(url('/console/schools/' . $row['id'])) ?>" class="group flex items-center gap-4 rounded-2xl px-3 py-3 transition hover:bg-soft">
                            <?php $avatarSize = 'h-11 w-11'; require __DIR__ . '/../partials/portal/school-avatar.php'; ?>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-ink"><?= e($row['name']) ?></span>
                                <span class="block truncate text-xs text-muted"><?= e(trim(($row['state'] ?? '') . ' · ' . $row['plan_name'] . ' plan', ' ·')) ?></span>
                            </span>
                            <span class="badge badge-dot <?= $statusBadge[$row['status']] ?? 'badge-gray' ?>"><?= e(ucfirst((string) $row['status'])) ?></span>
                            <span class="hidden w-20 text-right text-xs text-subtle sm:block"><?= e(time_ago((string) $row['created_at'])) ?></span>
                            <span class="text-subtle transition group-hover:translate-x-0.5 group-hover:text-ink"><?= icon('chevron-right', 'h-4 w-4') ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- Recent activity -->
    <section class="card" aria-labelledby="activity-title">
        <div class="card-header">
            <div><p class="eyebrow">Audit</p><h2 id="activity-title" class="card-title mt-1">Recent activity</h2></div>
            <a class="btn btn-secondary btn-sm" href="<?= e(url('/console/audit')) ?>">All</a>
        </div>
        <?php if ($activity === []): ?>
            <p class="px-6 pb-10 pt-4 text-center text-sm text-muted">Actions by you and school administrators will appear here.</p>
        <?php else: ?>
            <ol class="px-6 pb-6 pt-2">
                <?php foreach ($activity as $i => $event): ?>
                    <li class="relative flex gap-4 pb-5 last:pb-0">
                        <?php if ($i < count($activity) - 1): ?><span class="absolute left-[9px] top-6 h-full w-px bg-gradient-to-b from-line to-transparent" aria-hidden="true"></span><?php endif; ?>
                        <span class="relative mt-1 grid h-[19px] w-[19px] shrink-0 place-items-center rounded-full bg-brand/10"><span class="h-2 w-2 rounded-full bg-signature"></span></span>
                        <div class="min-w-0">
                            <p class="text-sm leading-6 text-ink"><span class="font-semibold"><?= e($event['actor_name'] ?? 'System') ?></span> <?= e(lcfirst((string) $event['summary'])) ?></p>
                            <p class="mt-0.5 text-xs text-subtle"><?= e(time_ago((string) $event['created_at'])) ?><?= $event['school_name'] ? ' · ' . e($event['school_name']) : '' ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>
