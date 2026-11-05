<?php
declare(strict_types=1);
/** Print shell for result slips and report cards: A4 pages on screen, clean output on paper. @var string $content */
$school ??= null;
$brandColor = null; // one platform colour for every role
$back ??= null;
// Printed documents carry the school's colour; the app itself stays platform blue. Platform blue when no valid colour.
$schoolHex = (string) ($school['primary_color'] ?? '');
$schoolHex = preg_match('/^#[0-9a-fA-F]{6}$/', $schoolHex) ? $schoolHex : '#1E40AF';
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/portal/head.php'; ?>
    <style nonce="<?= e($cspNonce) ?>">:root{--school:<?= e(hex_to_rgb_channels($schoolHex)) ?>;--school-ink:<?= e(brand_ink($schoolHex)) ?>;--school-text:<?= e(brand_readable($schoolHex)) ?>}</style>
</head>
<body class="print-shell min-h-screen bg-soft">
<?php require __DIR__ . '/../partials/portal/toasts.php'; ?>
<div class="print-toolbar sticky top-0 z-30 border-b border-line bg-card/90 backdrop-blur-xl">
    <div class="mx-auto flex max-w-[210mm] items-center justify-between gap-3 px-4 py-3">
        <a href="<?= e($back ?? 'javascript:history.back()') ?>" class="btn btn-ghost btn-sm" data-back><?= icon('arrow-left', 'h-4 w-4') ?>Back</a>
        <p class="hidden truncate text-sm font-semibold text-ink sm:block"><?= e($title) ?></p>
        <button type="button" class="btn btn-primary btn-sm" data-print><?= icon('download', 'h-4 w-4') ?>Print / Save as PDF</button>
    </div>
</div>
<main class="mx-auto max-w-[210mm] px-2 py-6 sm:px-4"><?= $content ?></main>
<script nonce="<?= e($cspNonce) ?>">
    document.querySelector("[data-print]")?.addEventListener("click", () => window.print());
    const back = document.querySelector("[data-back]");
    if (back && back.getAttribute("href").startsWith("javascript")) { back.setAttribute("href", "#"); back.addEventListener("click", (e) => { e.preventDefault(); history.back(); }); }
</script>
</body>
</html>
