<?php
declare(strict_types=1);
/** Distraction-free shell for the exam room. @var string $content @var array<string,mixed> $school */
$brandColor = null; // one platform colour for every role
?>
<!doctype html>
<html lang="en" class="exam-shell">
<head>
    <?php require __DIR__ . '/../partials/portal/head.php'; ?>
    <script defer src="<?= e(asset('js/proctor.js')) ?>"></script>
    <script defer src="<?= e(asset('js/exam-room.js')) ?>"></script>
</head>
<body class="min-h-screen select-none bg-soft" data-exam-protect>
<?= $content ?>
<div class="print-block" aria-hidden="true">Printing this exam is not allowed.</div>
</body>
</html>
