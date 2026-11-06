<?php
declare(strict_types=1);
/** Plain reading layout for public documents (privacy notice, terms). @var string $content @var string $title @var string $appName */
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/portal/head.php'; ?>
</head>
<body class="min-h-screen bg-canvas">
<header class="border-b border-line bg-card">
    <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-4">
        <a href="<?= e(url('/')) ?>" class="flex items-center gap-2.5 font-display text-[17px] font-semibold text-ink">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-signature"><?= icon('shield', 'h-5 w-5 text-white') ?></span><?= e($appName) ?>
        </a>
        <nav class="flex gap-4 text-sm font-medium text-muted" aria-label="Legal">
            <a class="hover:text-ink" href="<?= e(url('/privacy')) ?>">Privacy</a>
            <a class="hover:text-ink" href="<?= e(url('/terms')) ?>">Terms</a>
            <a class="hover:text-ink" href="<?= e(url('/')) ?>">Home</a>
        </nav>
    </div>
</header>
<main id="main" class="mx-auto max-w-3xl px-4 py-8 sm:py-12">
    <article class="legal"><?= $content ?></article>
</main>
<footer class="mx-auto max-w-3xl px-4 pb-10 text-center text-xs text-subtle">&copy; <?= date('Y') ?> <?= e($appName) ?></footer>
</body>
</html>
