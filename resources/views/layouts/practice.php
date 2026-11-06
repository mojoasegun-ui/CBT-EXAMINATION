<?php
declare(strict_types=1);
/** Practice Centre shell: platform design, for visitors and signed-in students. @var string $content @var string $title @var array|null $user @var string $currentPath */
$brandColor = null;
$student = ($user['role'] ?? '') === 'student';
$path = $currentPath ?? '';
$nav = [['/practice', 'Practice', 'sparkles'], ['/practice/mock', 'Mock exam', 'clock'], ['/practice/progress', 'Progress', 'chart']];
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/portal/head.php'; ?>
    <script defer src="<?= e(asset('js/practice.js')) ?>"></script>
</head>
<body class="min-h-screen bg-canvas">
<?php require __DIR__ . '/../partials/portal/toasts.php'; ?>
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-card focus:px-3 focus:py-2 focus:text-sm focus:font-semibold focus:text-ink focus:shadow-lg">Skip to content</a>
<header class="sticky top-0 z-40 border-b border-line bg-card/90 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4">
        <a href="<?= e(url('/practice')) ?>" class="group flex min-w-0 items-center gap-2.5" aria-label="Practice Centre home">
            <span class="shine grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-signature shadow-[0_8px_18px_-8px_rgb(var(--brand)/.7)]"><?php $markClass = 'h-[18px] w-[18px]'; require __DIR__ . '/../partials/brand-mark.php'; ?></span>
            <span class="truncate font-display text-[16px] font-semibold tracking-tight text-ink">Practice Centre</span>
        </a>
        <div class="flex shrink-0 items-center gap-2">
            <button type="button" class="btn btn-secondary btn-icon" data-theme-toggle aria-label="Toggle dark mode"><?= icon('moon', 'h-4 w-4 dark:hidden') ?><?= icon('sun', 'hidden h-4 w-4 dark:block') ?></button>
            <?php if ($student): ?>
                <a href="<?= e(url('/student')) ?>" class="btn btn-secondary btn-sm"><?= icon('dashboard', 'h-4 w-4') ?><span class="hidden sm:inline">My dashboard</span><span class="sm:hidden">Dashboard</span></a>
            <?php else: ?>
                <a href="<?= e(url('/student/login')) ?>" class="btn btn-primary btn-sm">Sign in</a>
            <?php endif; ?>
        </div>
    </div>
    <nav class="mx-auto max-w-6xl px-4 pb-3" aria-label="Practice sections">
        <div class="tabs">
            <?php foreach ($nav as [$href, $label, $ic]): $on = $path === $href || ($href === '/practice' && str_starts_with($path, '/practice/subject')); ?>
                <a href="<?= e(url($href)) ?>" class="tab inline-flex items-center gap-1.5" <?= $on ? 'aria-current="page"' : '' ?>><?= icon($ic, 'h-4 w-4') ?><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
<main id="main" class="mx-auto max-w-6xl px-4 py-6 sm:py-8"><?php if (empty($user)) { $pushTitle = 'Get a daily practice reminder'; $pushText = 'Turn on alerts and we will nudge you to practise, protect your streak and tell you about new question sets.'; } require __DIR__ . '/../partials/portal/push-banner.php'; ?><?= $content ?></main>
<footer class="mx-auto max-w-6xl px-4 pb-10 pt-2 text-center text-xs text-subtle">
    Free practice from the JAMB, WAEC and NECO syllabus topics. <a class="font-medium text-brand hover:underline" href="<?= e(url('/')) ?>">ExamCore home</a>
</footer>
</body>
</html>
