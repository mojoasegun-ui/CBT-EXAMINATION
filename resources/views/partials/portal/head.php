<?php
declare(strict_types=1);
/** @var string $title @var string $appName @var string $cspNonce @var string $csrfToken @var string|null $brandColor */
$brandColor ??= null;
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e($csrfToken) ?>">
<meta name="theme-color" content="#ffffff">
<title><?= e($title) ?> · <?= e($appName) ?></title>
<link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('icons/favicon-32.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(asset('icons/apple-touch-icon.png')) ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= e($appName) ?>">
<link rel="preload" href="<?= e(asset('fonts/inter-var.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/portal.css')) ?>">
<?php if ($brandColor !== null): ?>
<style nonce="<?= e($cspNonce) ?>">:root{--brand:<?= e(hex_to_rgb_channels($brandColor)) ?>;--brand-2:<?= e(brand_tint($brandColor)) ?>;--brand-ink:<?= e(brand_ink($brandColor)) ?>}</style>
<?php endif; ?>
<script nonce="<?= e($cspNonce) ?>">
    (function () {
        // Pure white by default; dark only when the user chooses it with the toggle.
        document.documentElement.classList.add('js');
        try {
            document.documentElement.dataset.theme = localStorage.getItem('examcore-theme') === 'dark' ? 'dark' : 'light';
        } catch (e) { document.documentElement.dataset.theme = 'light'; }
    })();
</script>
<script defer src="<?= e(asset('js/portal.js')) ?>"></script>
<script defer src="<?= e(asset('js/pwa.js')) ?>"></script>
