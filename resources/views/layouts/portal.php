<?php
declare(strict_types=1);
/**
 * App shell for the Owner Console and the school portal: premium white.
 * @var string $content @var string $title @var string $area @var array<string,mixed> $user @var string $currentPath @var bool $impersonating
 */
$area ??= 'console';
$isConsole = $area === 'console';
$isStudentArea = $area === 'student';
$school ??= null;
$brandColor = $isConsole ? null : ($school['primary_color'] ?? null);
$soonExams = [['/school/bank', 'library', 'Question bank'], ['/school/exams', 'calendar', 'Exams & schedule']];
if (($user['role'] ?? '') === 'school_admin') {
    $soonExams[] = ['/school/results', 'chart', 'Results & report cards'];
}
$nav = match (true) {
    $isConsole => [
        'Overview' => [['/console', 'dashboard', 'Dashboard']],
        'Platform' => [['/console/schools', 'school', 'Schools'], ['/console/bank', 'library', 'Platform bank'], ['/console/plans', 'layers', 'Plans & features'], ['/console/flyer', 'sparkles', 'Flyer & QR'], ['/console/audit', 'list-checks', 'Audit log'], ['/console/system', 'server', 'System']],
        'Account' => [['/console/account/password', 'key', 'Password'], ['/console/account/two-factor', 'shield', 'Two-step verification']],
    ],
    $isStudentArea => [
        'Overview' => [['/student', 'dashboard', 'My dashboard']],
        'Exams' => [['/student', 'calendar', 'My exams'], ['/student/results', 'chart', 'My results']],
        'Account' => [['/student/account/password', 'key', 'Password']],
    ],
    ($user['role'] ?? '') === 'school_admin' => [
        'Overview' => [['/school', 'dashboard', 'Dashboard']],
        'People' => [['/school/students', 'graduation', 'Students'], ['/school/teachers', 'users', 'Teachers & staff']],
        'Academics' => [['/school/classes', 'school', 'Classes'], ['/school/subjects', 'book', 'Subjects'], ['/school/departments', 'layers', 'Departments'], ['/school/academic', 'calendar', 'Academic year'], ['/school/grading', 'sliders', 'Grading scale']],
        'Exams' => $soonExams,
        'School' => [['/school/settings', 'palette', 'Profile & branding'], ['/school/flyer', 'sparkles', 'Flyer & QR'], ['/school/activity', 'list-checks', 'Activity log']],
        'Account' => [['/school/account/password', 'key', 'Password'], ['/school/account/two-factor', 'shield', 'Two-step verification']],
    ],
    default => [
        'Overview' => [['/school', 'dashboard', 'Dashboard'], ['/school/my-classes', 'school', 'My classes']],
        'Exams' => $soonExams,
        'Account' => [['/school/account/password', 'key', 'Password'], ['/school/account/two-factor', 'shield', 'Two-step verification']],
    ],
};
$isActive = static function (string $href) use ($currentPath): bool {
    if ($href === '/console' || $href === '/school' || $href === '/student') {
        return $currentPath === $href;
    }
    return $href !== '#' && ($currentPath === $href || str_starts_with($currentPath, $href . '/'));
};
$roleLabels = ['owner' => 'Platform owner', 'school_admin' => 'School administrator', 'teacher' => 'Teacher', 'reviewer' => 'Reviewer', 'student' => 'Student'];
$homePath = $isConsole ? '/console' : ($isStudentArea ? '/student' : '/school');
$schoolName = ($school['short_name'] ?? null) ?: ($school['name'] ?? $appName);
?>
<!doctype html>
<html lang="en">
<head>               
    <?php require __DIR__ . '/../partials/portal/head.php'; ?>
</head>
<body class="min-h-screen bg-canvas"<?= preg_match("~^/(school|console)/(bank|exams/\d+/papers)~", $currentPath) ? " data-print-protect" : "" ?>>
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[70] focus:rounded-full focus:bg-card focus:px-4 focus:py-2 focus:shadow-lg">Skip to content</a>
<?php require __DIR__ . '/../partials/portal/toasts.php'; ?>

<?php if ($impersonating): ?>
    <div class="sticky top-0 z-50 flex h-10 items-center justify-center gap-4 bg-gradient-to-r from-amber-300 via-amber-200 to-amber-300 px-4 text-[13px] font-semibold text-amber-950">
        <span class="inline-flex items-center gap-2"><?= icon('eye', 'h-4 w-4') ?> Support mode · signed in as <?= e($user['name']) ?></span>
        <form method="post" action="<?= e(url('/school/stop-impersonating')) ?>">
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <button class="rounded-full bg-amber-950 px-3 py-1 text-xs font-bold text-amber-50 transition hover:bg-black">Return to console</button>
        </form>
    </div>
<?php endif; ?>

<div class="lg:pl-[288px]">
    <!-- Sidebar -->
    <div class="fixed inset-0 z-40 bg-[rgb(12_12_29/.25)] backdrop-blur-sm lg:hidden" data-sidebar-backdrop hidden></div>
    <aside id="sidebar" class="fixed bottom-0 left-0 z-40 flex w-[288px] -translate-x-full flex-col border-r border-line bg-card transition-transform duration-300 ease-out lg:translate-x-0 <?= $impersonating ? 'top-10' : 'top-0' ?>" data-sidebar aria-label="Main navigation">
        <div class="flex h-20 items-center justify-between gap-3 px-6">
            <a href="<?= e(url($homePath)) ?>" class="group flex min-w-0 items-center gap-3">
                <?php if (!$isConsole && !empty($school['logo_path'])): ?>
                    <img src="<?= e(url('/' . $school['logo_path'])) ?>" alt="" class="h-10 w-10 shrink-0 rounded-2xl border border-line bg-white object-contain p-1">
                <?php else: ?>
                    <span class="shine grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-signature font-display text-[15px] font-bold text-brand-ink shadow-[0_10px_22px_-8px_rgb(var(--brand)/.7)] transition-transform duration-300 group-hover:rotate-[-4deg] group-hover:scale-105"><?= e($isConsole ? 'E' : initials((string) ($school['name'] ?? 'S'))) ?></span>
                <?php endif; ?>
                <span class="min-w-0">
                    <span class="block truncate font-display text-[16px] font-semibold tracking-tight text-ink"><?= e($isConsole ? $appName : $schoolName) ?></span>
                    <span class="block text-[10.5px] font-semibold uppercase tracking-[.2em] <?= $isConsole ? 'text-gold' : 'text-subtle' ?>"><?= $isConsole ? 'Owner Console' : ($isStudentArea ? 'Student portal' : 'School portal') ?></span>
                </span>
            </a>
            <button type="button" class="btn btn-ghost btn-icon lg:hidden" data-sidebar-close aria-label="Close menu"><?= icon('x') ?></button>
        </div>

        <nav class="scrollbar-thin flex-1 overflow-y-auto px-4 pb-4">
            <?php foreach ($nav as $section => $items): ?>
                <p class="nav-section"><?= e($section) ?></p>
                <ul class="space-y-1">
                    <?php foreach ($items as $item): [$href, $iconName, $label] = $item; $soon = $item[3] ?? false; ?>
                        <li>
                            <?php if ($soon): ?>
                                <span class="nav-link cursor-default opacity-60 hover:bg-transparent hover:text-muted"><?= icon($iconName) ?><span class="flex-1"><?= e($label) ?></span><span class="rounded-full bg-soft px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-subtle ring-1 ring-line">Soon</span></span>
                            <?php else: ?>
                                <a href="<?= e(url($href)) ?>" class="nav-link" <?= $isActive($href) ? 'aria-current="page"' : '' ?>><?= icon($iconName) ?><span><?= e($label) ?></span></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </nav>

        <div class="m-4 rounded-3xl border border-line bg-soft p-3">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-signature text-[13px] font-bold text-brand-ink"><?= e(initials((string) $user['name'])) ?></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-ink"><?= e($user['name']) ?></span>
                    <span class="block truncate text-xs text-muted"><?= e($roleLabels[$user['role']] ?? $user['role']) ?></span>
                </span>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                    <button class="grid h-9 w-9 place-items-center rounded-full text-muted transition hover:bg-card hover:text-ink hover:shadow-sm" aria-label="Sign out" title="Sign out"><?= icon('logout', 'h-[18px] w-[18px]') ?></button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Top bar -->
    <header class="glass sticky <?= $impersonating ? 'top-10' : 'top-0' ?> z-30 flex h-20 items-center gap-3 border-b border-line/70 px-4 sm:px-8 lg:px-10" data-topbar>
        <button type="button" class="btn btn-ghost btn-icon -ml-2 lg:hidden" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="Open menu"><?= icon('menu') ?></button>
        <div class="min-w-0">
            <p class="eyebrow hidden sm:block"><?= $isConsole ? 'Owner Console' : e($schoolName) ?></p>
            <p class="truncate font-display text-[17px] font-semibold tracking-tight text-ink"><?= e($title) ?></p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <?php if (!$isConsole && !$isStudentArea): ?>
                <form method="get" action="<?= e(url('/school/search')) ?>" class="relative hidden md:block" role="search" data-quick-search>
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 grid place-items-center text-subtle"><?= icon('search', 'h-4 w-4') ?></span>
                    <input class="input w-72 rounded-full py-2 pl-10 text-sm" type="search" name="q" placeholder="Search students or staff…" aria-label="Search students or staff" autocomplete="off" data-quick-search-input>
                    <div class="absolute right-0 top-full z-40 mt-2 hidden w-96 overflow-hidden rounded-3xl border border-line bg-card shadow-[0_30px_70px_-30px_rgb(24_22_61/.45)]" data-quick-search-results></div>
                </form>
                <a href="<?= e(url('/school/search')) ?>" class="btn btn-secondary btn-icon md:hidden" aria-label="Search"><?= icon('search', 'h-[18px] w-[18px]') ?></a>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary btn-icon hidden" data-install-app aria-label="Install the app" title="Install the app"><?= icon('download', 'h-[18px] w-[18px]') ?></button>
            <button type="button" class="btn btn-secondary btn-icon" data-theme-toggle aria-label="Toggle dark mode" title="Toggle dark mode"><?= icon('moon', 'h-[18px] w-[18px] dark:hidden') ?><?= icon('sun', 'hidden h-[18px] w-[18px] dark:block') ?></button>
        </div>
    </header>

    <main id="main" tabindex="-1" class="mx-auto w-full max-w-[1320px] px-4 py-8 focus:outline-none sm:px-8 lg:px-10 lg:py-10" data-stagger>
        <?= $content ?>
    </main>
</div>

<dialog class="w-[calc(100%-2rem)] max-w-md rounded-[28px] border border-line bg-card p-0 text-ink shadow-[0_40px_90px_-30px_rgb(24_22_61/.45)] backdrop:bg-[rgb(12_12_29/.3)] backdrop:backdrop-blur-md" data-confirm-dialog aria-labelledby="confirm-title">
    <div class="p-7">
        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-950/50 dark:text-red-400"><?= icon('alert', 'h-6 w-6') ?></span>
        <h2 id="confirm-title" class="mt-5 font-display text-xl font-semibold" data-confirm-title>Are you sure?</h2>
        <p class="mt-2 text-sm leading-6 text-muted" data-confirm-message></p>
        <div class="mt-7 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-secondary" data-confirm-cancel>Cancel</button>
            <button type="button" class="btn btn-danger" data-confirm-ok>Confirm</button>
        </div>
    </div>
</dialog>
</body>
</html>
