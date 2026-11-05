<?php
declare(strict_types=1);
/** @var string $content @var string $title @var string $appName */
$area ??= 'school';
$isConsole = $area === 'console';
$slides = $isConsole
    ? [
        ['exam-screen', 'Every school, one elegant console', 'Onboard schools in minutes, brand their portals and manage plans from a single place.'],
        ['face-verify', 'Security that never sleeps', 'Two-step verification, account lockout and a complete audit trail of every action.'],
        ['results-insight', 'Insight across the platform', 'Live activity, health checks, backups and updates without touching a server.'],
    ]
    : [
        ['exam-screen', 'A real CBT experience', 'Timed papers, a question palette and instant autosave, just like the JAMB and WAEC screens.'],
        ['face-verify', 'Exams students can trust', 'Identity checks, tab-switch detection and proctoring reports keep every result honest.'],
        ['results-insight', 'Results that teach', 'Scores, topic breakdowns and class broadsheets the moment results are released.'],
    ];
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/portal/head.php'; ?>
</head>
<body class="min-h-screen bg-canvas">
<?php require __DIR__ . '/../partials/portal/toasts.php'; ?>
<div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
    <!-- Form -->
    <main class="relative flex flex-col px-5 py-8 sm:px-10 lg:px-16">
        <header class="flex items-center justify-between">
            <a href="<?= e(url('/')) ?>" class="group flex items-center gap-3">
                <span class="shine grid h-10 w-10 place-items-center rounded-2xl bg-signature font-display text-[15px] font-bold text-white shadow-[0_10px_22px_-8px_rgb(var(--brand)/.7)] transition-transform duration-300 group-hover:rotate-[-4deg]">E</span>
                <span class="font-display text-[17px] font-semibold tracking-tight text-ink"><?= e($appName) ?></span>
            </a>
            <button type="button" class="btn btn-secondary btn-icon" data-theme-toggle aria-label="Toggle dark mode"><?= icon('moon', 'h-4 w-4 dark:hidden') ?><?= icon('sun', 'hidden h-4 w-4 dark:block') ?></button>
        </header>

        <div class="flex flex-1 items-center justify-center py-12">
            <div class="w-full max-w-[400px]" data-stagger>
                <?= $content ?>
            </div>
        </div>

        <footer class="flex flex-wrap items-center justify-between gap-3 text-xs text-subtle">
            <span class="inline-flex items-center gap-1.5"><?= icon('lock', 'h-3.5 w-3.5') ?> Secure sign-in · accounts lock after repeated failed attempts</span>
            <span>&copy; <?= date('Y') ?> <?= e($appName) ?></span>
        </footer>
    </main>

    <!-- Showcase slider -->
    <aside class="relative hidden overflow-hidden p-4 lg:block" aria-label="Product highlights">
        <div class="bg-mesh relative flex h-full flex-col overflow-hidden rounded-[36px] border border-line" data-slider>
            <div class="bg-dots pointer-events-none absolute inset-0 opacity-60 [mask-image:radial-gradient(70%_60%_at_50%_40%,black,transparent)]"></div>
            <div class="relative flex items-center justify-between px-10 pt-9">
                <span class="inline-flex items-center gap-2 rounded-full border border-line bg-card/80 px-3.5 py-1.5 text-xs font-semibold text-ink shadow-sm backdrop-blur">
                    <span class="text-gold"><?= icon($isConsole ? 'shield' : 'sparkles', 'h-3.5 w-3.5') ?></span><?= $isConsole ? 'Owner Console' : 'Built for Nigerian schools' ?>
                </span>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-secondary btn-icon bg-card/80 backdrop-blur" data-slide-prev aria-label="Previous slide"><?= icon('chevron-left') ?></button>
                    <button type="button" class="btn btn-secondary btn-icon bg-card/80 backdrop-blur" data-slide-next aria-label="Next slide"><?= icon('chevron-right') ?></button>
                </div>
            </div>

            <div class="relative flex flex-1 items-center justify-center px-10">
                <?php foreach ($slides as $i => [$art]): ?>
                    <img src="<?= e(asset('images/art/' . $art . '.svg')) ?>" alt="" width="560" height="420"
                         class="absolute w-[min(92%,600px)] transition-all duration-700 ease-out <?= $i === 0 ? 'opacity-100' : 'pointer-events-none translate-y-6 scale-95 opacity-0' ?>"
                         data-slide-art="<?= $i ?>" <?= $i === 0 ? '' : 'loading="lazy"' ?>>
                <?php endforeach; ?>
            </div>

            <div class="relative px-10 pb-10">
                <div class="relative min-h-[132px]" aria-live="polite">
                    <?php foreach ($slides as $i => [, $heading, $text]): ?>
                        <div class="absolute inset-x-0 top-0 transition-all duration-500 <?= $i === 0 ? 'opacity-100' : 'pointer-events-none translate-y-3 opacity-0' ?>" data-slide-text="<?= $i ?>" <?= $i === 0 ? '' : 'aria-hidden="true"' ?>>
                            <h2 class="max-w-lg font-display text-[34px] font-semibold leading-[1.1] text-ink"><?= e($heading) ?></h2>
                            <p class="mt-3 max-w-md text-[15px] leading-7 text-muted"><?= e($text) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-6 flex items-center gap-2" role="tablist" aria-label="Choose slide">
                    <?php foreach ($slides as $i => $_): ?>
                        <button type="button" role="tab" class="relative h-1.5 overflow-hidden rounded-full bg-ink/10 transition-all duration-500 <?= $i === 0 ? 'w-10' : 'w-4' ?>" data-slide-dot="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                            <span class="absolute inset-y-0 left-0 w-0 rounded-full bg-signature" data-slide-progress></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </aside>
</div>
</body>
</html>
