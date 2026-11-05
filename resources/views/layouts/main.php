<?php
declare(strict_types=1);
$assetRoot = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') : '.';
$homeUrl = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') . '/' : './';
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/pwa-tags.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">
    <meta name="description" content="A modular computer-based testing platform foundation for schools and institutions.">
    <meta name="theme-color" content="#f8fafc">
    <title><?= e($appName) ?> | Assessment Platform</title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script defer src="<?= e(asset('js/app.js')) ?>"></script>
</head>
<body>
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="main-content" tabindex="-1">
    <?= $content ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
<div class="sr-only" id="action-status" role="status" aria-live="polite" aria-atomic="true"></div>
</body>
</html>