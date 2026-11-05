<?php
declare(strict_types=1);
/** Distraction-free shell for a running practice session. @var string $content @var string $title */
$brandColor = null;
?>
<!doctype html>
<html lang="en" class="exam-shell">
<head>
    <?php require __DIR__ . '/../partials/portal/head.php'; ?>
    <script defer src="<?= e(asset('js/practice.js')) ?>"></script>
</head>
<body class="min-h-screen bg-soft">
<?= $content ?>
</body>
</html>
