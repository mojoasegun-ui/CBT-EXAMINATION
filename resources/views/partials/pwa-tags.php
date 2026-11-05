<?php
declare(strict_types=1);
/** PWA head tags for the public pages (portal pages include them via partials/portal/head.php). */
?>
<link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('icons/favicon-32.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('icons/apple-touch-icon.png')) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <script defer src="<?= e(asset('js/pwa.js')) ?>"></script>
