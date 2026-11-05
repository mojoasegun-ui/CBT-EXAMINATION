<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $school @var bool $isAdmin @var bool $needsSetup @var array<string,int> $stats @var array<string,int> $gender
 * @var list<array{label:string,short:string,count:int}> $classSeries @var array{session:?array,term:?array} $current @var int|null $remaining
 * @var list<array{0:string,1:bool,2:string}> $checklist @var list<array<string,mixed>> $activity
 */
$firstName = explode(' ', trim((string) $user['name']))[0];
$done = count(array_filter($checklist, static fn (array $item): bool => $item[1]));
$progress = (int) round($done / max(1, count($checklist)) * 100);
$term = $current['term'];
$totalGender = array_sum($gender);
?>
<!-- Hero -->
<section class="bg-mesh relative overflow-hidden rounded-[32px] border border-line">
    <div class="bg-dots pointer-events-none absolute inset-0 opacity-50 [mask-image:radial-gradient(60%_80%_at_30%_50%,black,transparent)]"></div>
    <div class="relative grid items-center gap-6 p-7 sm:p-10 lg:grid-cols-[1.2fr_1fr]">
        <div>
            <div class="flex items-center gap-4">
                <?php if ($school['logo_path']): ?>
                    <img src="<?= e(url('/' . $school['logo_path'])) ?>" alt="" class="h-16 w-16 rounded-3xl border border-line bg-white object-contain p-1.5 shadow-sm">
                <?php else: ?>
                    <span class="shine grid h-16 w-16 place-items-center rounded-3xl bg-signature font-display text-xl font-bold text-brand-ink shadow-[0_14px_30px_-10px_rgb(var(--brand)/.7)]"><?= e(initials((string) $school['name'])) ?></span>
                <?php endif; ?>
                <div>
                    <p class="eyebrow">Welcome back, <?= e($firstName) ?></p>
                    <p class="mt-1 text-sm text-muted"><?= $term !== null ? e($term['name'] . ' · ' . $term['session_name']) : 'No current term set' ?></p>
                </div>
            </div>
            <h1 class="mt-5 font-display text-[32px] font-semibold leading-[1.1] text-ink sm:text-[42px]"><?= e($school['name']) ?></h1>
            <?php if ($school['motto']): ?><p class="mt-2 text-[15px] italic text-muted">“<?= e($school['motto']) ?>”</p><?php endif; ?>
            <div class="mt-6 flex flex-wrap gap-3">
                <?php if ($isAdmin && $needsSetup): ?>
                    <form method="post" action="<?= e(url('/school/quick-setup')) ?>" data-submit-once>
                        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                        <button class="btn btn-primary btn-lg"><?= icon('sparkles') ?><span data-label>Quick setup: create classes & subjects</span></button>
                    </form>
                <?php elseif ($isAdmin): ?>
                    <a href="<?= e(url('/school/students/create')) ?>" class="btn btn-primary btn-lg"><?= icon('plus') ?>Enrol a student</a>
                    <a href="<?= e(url('/school/students/import')) ?>" class="btn btn-secondary btn-lg"><?= icon('upload') ?>Import from Excel</a>
                <?php else: ?>
                    <a href="<?= e(url('/school/my-classes')) ?>" class="btn btn-primary btn-lg"><?= icon('school') ?>My classes</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hidden justify-end lg:flex">
            <img src="<?= e(asset('images/art/exam-screen.svg')) ?>" alt="" width="560" height="420" class="float-slow -my-6 w-full max-w-[440px]">
        </div>
    </div>
</section>

<!-- Numbers -->
<section class="mt-8 grid grid-cols-2 gap-4 xl:grid-cols-4" aria-label="Key numbers" data-stagger>
    <?php foreach ([
        ['Students', $stats['students'], 'graduation', $remaining === null ? 'Unlimited on your plan' : number_format($remaining) . ' seats left on your plan', $isAdmin ? '/school/students' : null],
        ['Teachers', $stats['teachers'], 'users', 'Active teaching staff', $isAdmin ? '/school/teachers' : null],
        ['Classes', $stats['classes'], 'school', 'Across all levels', $isAdmin ? '/school/classes' : null],
        ['Subjects', $stats['subjects'], 'book', 'Offered this session', $isAdmin ? '/school/subjects' : null],
    ] as [$label, $value, $iconName, $sub, $href]): ?>
        <div class="card card-hover relative p-5 sm:p-6">
            <div class="flex items-start justify-between"><span class="icon-tile"><?= icon($iconName) ?></span><?php if ($href): ?><span class="text-subtle"><?= icon('arrow-right', 'h-4 w-4') ?></span><?php endif; ?></div>
            <p class="stat-value mt-6 tabular-nums" data-count="<?= (int) $value ?>"><?= e(number_format($value)) ?></p>
            <p class="stat-label mt-2"><?= e($label) ?></p>
            <p class="mt-1 truncate text-xs text-subtle"><?= e($sub) ?></p>
            <?php if ($href): ?><a href="<?= e(url($href)) ?>" class="absolute inset-0 rounded-[22px]" aria-label="Open <?= e(strtolower($label)) ?>"></a><?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2" aria-labelledby="class-chart-title">
        <div class="card-header">
            <div><p class="eyebrow">Enrolment</p><h2 id="class-chart-title" class="card-title mt-1">Students per class</h2></div>
            <?php if ($totalGender > 0): ?>
                <div class="flex items-center gap-4 text-xs text-muted">
                    <span><span class="font-semibold tabular-nums text-ink"><?= number_format($gender['female'] ?? 0) ?></span> girls</span>
                    <span><span class="font-semibold tabular-nums text-ink"><?= number_format($gender['male'] ?? 0) ?></span> boys</span>
                </div>
            <?php endif; ?>
        </div>
        <div class="px-6 pb-6 pt-2">
            <?php if ($classSeries === []): ?>
                <p class="py-12 text-center text-sm text-muted">Create classes to see enrolment here.</p>
            <?php else: ?>
                <?php $series = $classSeries; $chartLabel = 'Active students per class'; $unit = 'student'; require __DIR__ . '/../partials/portal/bar-chart.php'; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="card" aria-labelledby="setup-title">
        <div class="card-header">
            <div><p class="eyebrow">Getting started</p><h2 id="setup-title" class="card-title mt-1">School setup</h2></div>
            <div class="relative grid h-14 w-14 place-items-center" role="img" aria-label="<?= $progress ?>% complete">
                <svg class="absolute inset-0 -rotate-90" viewBox="0 0 36 36" aria-hidden="true"><circle cx="18" cy="18" r="15.5" fill="none" class="stroke-line" stroke-width="3"/><circle cx="18" cy="18" r="15.5" fill="none" class="stroke-brand" stroke-width="3" stroke-linecap="round" stroke-dasharray="<?= round(97.4 * $progress / 100, 1) ?> 97.4"/></svg>
                <span class="text-xs font-bold tabular-nums text-ink"><?= $progress ?>%</span>
            </div>
        </div>
        <ul class="space-y-1.5 px-4 pb-5">
            <?php foreach ($checklist as [$label, $complete, $href]): ?>
                <li>
                    <a href="<?= e($isAdmin ? url($href) : '#') ?>" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm transition <?= $isAdmin ? 'hover:bg-soft' : 'pointer-events-none' ?>">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full <?= $complete ? 'bg-emerald-500 text-white' : 'border-2 border-line text-transparent' ?>"><?= icon('check', 'h-3.5 w-3.5') ?></span>
                        <span class="flex-1 <?= $complete ? 'text-muted line-through decoration-line' : 'font-medium text-ink' ?>"><?= e($label) ?></span>
                        <?php if (!$complete && $isAdmin): ?><span class="text-subtle"><?= icon('chevron-right', 'h-4 w-4') ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2" aria-labelledby="coming-title">
        <div class="card-header"><div><p class="eyebrow">Next on your portal</p><h2 id="coming-title" class="card-title mt-1">Exams are on the way</h2></div><span class="badge badge-blue">Coming next</span></div>
        <div class="grid gap-3 px-6 pb-6 sm:grid-cols-3">
            <?php foreach ([['library', 'Question bank', 'Write or import questions with images and formulas.'], ['calendar', 'Scheduled exams', 'Set classes, dates, time windows and anti-cheating rules.'], ['chart', 'Results & slips', 'Release results, print slips with QR verification.']] as [$iconName, $title, $text]): ?>
                <div class="rounded-3xl border border-line bg-soft p-5">
                    <span class="icon-soft"><?= icon($iconName) ?></span>
                    <p class="mt-4 font-semibold text-ink"><?= e($title) ?></p>
                    <p class="mt-1 text-sm leading-6 text-muted"><?= e($text) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card" aria-labelledby="activity-title">
        <div class="card-header"><div><p class="eyebrow">Audit</p><h2 id="activity-title" class="card-title mt-1">Recent activity</h2></div><?php if ($isAdmin): ?><a class="btn btn-secondary btn-sm" href="<?= e(url('/school/activity')) ?>">All</a><?php endif; ?></div>
        <?php if ($activity === []): ?>
            <p class="px-6 pb-10 pt-2 text-center text-sm text-muted">Changes to classes, students and staff appear here.</p>
        <?php else: ?>
            <ol class="px-6 pb-6 pt-1">
                <?php foreach ($activity as $i => $event): ?>
                    <li class="relative flex gap-4 pb-5 last:pb-0">
                        <?php if ($i < count($activity) - 1): ?><span class="absolute left-[9px] top-6 h-full w-px bg-gradient-to-b from-line to-transparent" aria-hidden="true"></span><?php endif; ?>
                        <span class="relative mt-1 grid h-[19px] w-[19px] shrink-0 place-items-center rounded-full bg-brand/10"><span class="h-2 w-2 rounded-full bg-signature"></span></span>
                        <div class="min-w-0">
                            <p class="text-sm leading-6 text-ink"><span class="font-semibold"><?= e($event['actor_name'] ?? 'System') ?></span> <?= e(lcfirst((string) $event['summary'])) ?></p>
                            <p class="mt-0.5 text-xs text-subtle"><?= e(time_ago((string) $event['created_at'])) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
</div>
