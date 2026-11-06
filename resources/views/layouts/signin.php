<?php
declare(strict_types=1);
/**
 * Sign-in shell for students, teachers/staff and parents (the Owner Console keeps its own layout).
 * Desktop: photo/brand panel on the left, white card on the right. Phone: the picture sits centred and animated
 * behind the headline, with the card sliding up over it.
 * @var string $content @var string $title @var string $appName
 */
$photo = is_file(dirname(__DIR__, 3) . '/public/assets/images/login-student.png') ? 'images/login-student.png' : (is_file(dirname(__DIR__, 3) . '/public/assets/images/login-student.jpg') ? 'images/login-student.jpg' : null);
$cap = '<svg viewBox="0 0 420 360" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs><linearGradient id="sA" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient><linearGradient id="sB" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1e3a8a"/></linearGradient><linearGradient id="sT" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#334155"/><stop offset="1" stop-color="#0f172a"/></linearGradient></defs><ellipse cx="210" cy="322" rx="170" ry="22" fill="#020a2e" opacity=".35"/><path d="M40 270 210 310 380 270v-34L210 276 40 236Z" fill="url(#sB)"/><path d="M40 236 210 276 380 236 210 196Z" fill="#fff"/><path d="M70 214 210 246 350 214v-30L210 216 70 184Z" fill="url(#sA)"/><path d="M70 184 210 216 350 184 210 152Z" fill="#fff"/><path d="M120 128v38c20 16 54 26 90 26s70-10 90-26v-38l-90 36-90-36Z" fill="#1e293b"/><path d="M210 52 70 106l140 54 140-54-140-54Z" fill="url(#sT)"/><path d="M210 106 332 112v52" fill="none" stroke="#f59e0b" stroke-width="5" stroke-linecap="round"/><circle cx="332" cy="112" r="6" fill="#fbbf24"/><path d="M332 118v44" stroke="#f59e0b" stroke-width="9" stroke-linecap="round"/><path d="M326 160h12l4 22h-20Z" fill="#f59e0b"/></svg>';
$mark = '<svg viewBox="0 0 40 40" aria-hidden="true"><path d="M20 6 5 13l15 7 15-7-15-7Z" fill="#fff"/><path d="M11 18v7c2.4 2.6 5.6 4 9 4s6.6-1.4 9-4v-7l-9 4.2L11 18Z" fill="#fff" opacity=".85"/><path d="M34 14v9" stroke="#fbbf24" stroke-width="2.4" stroke-linecap="round"/></svg>';
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/portal/head.php'; ?>
</head>
<body class="si-body">
<?php require __DIR__ . '/../partials/portal/toasts.php'; ?>
<div class="si-wrap">
    <section class="si-hero" aria-label="<?= e($appName) ?>">
        <div class="si-bg" aria-hidden="true"><i></i><i></i><i></i></div>
        <div class="si-grid" aria-hidden="true"></div>
        <div class="si-figure" aria-hidden="true">
            <div class="si-ring"></div><div class="si-ring si-ring-2"></div>
            <?php if ($photo !== null): ?><img src="<?= e(asset($photo)) ?>" alt="" class="si-photo" width="640" height="760"><?php else: ?><div class="si-art"><?= $cap ?></div><?php endif; ?>
            <span class="si-chip si-chip-a">Instant marking</span><span class="si-chip si-chip-b">Timed like the real CBT</span>
        </div>
        <header class="si-brand">
            <a href="<?= e(url('/')) ?>" class="si-brand-link" aria-label="<?= e($appName) ?> home">
                <span class="si-logo"><?= $mark ?></span>
                <span><span class="si-brand-name"><?= e($appName) ?></span><span class="si-brand-sub">Smart Assessment Platform</span></span>
            </a>
            <span class="si-tag">Assess <b>&bull;</b> Monitor <b>&bull;</b> Excel</span>
        </header>
        <div class="si-copy">
            <p class="si-welcome">Welcome to</p>
            <h1 class="si-title"><?= e($appName) ?></h1>
            <p class="si-lead">A modern, secure and powerful platform for Computer Based Testing.</p>
        </div>
        <ul class="si-features">
            <li><span><?= icon('shield', 'h-5 w-5') ?></span><div><b>Secure &amp; Reliable</b><small>Your data is always protected</small></div></li>
            <li><span><?= icon('sparkles', 'h-5 w-5') ?></span><div><b>Fast &amp; Seamless</b><small>Smooth on any phone or computer</small></div></li>
            <li><span><?= icon('chart', 'h-5 w-5') ?></span><div><b>Comprehensive Analytics</b><small>Track progress with clear results</small></div></li>
        </ul>
        <div class="si-trust">
            <div><b>1,800+</b><small>Practice questions</small></div>
            <div><b>22</b><small>Years of practice sets</small></div>
            <div><b>3</b><small>Exam boards</small></div>
        </div>
    </section>

    <main class="si-card-wrap" id="main">
        <div class="si-card">
            <div class="si-card-head">
                <span class="si-card-logo"><svg viewBox="0 0 40 40" aria-hidden="true"><path d="M20 6 5 13l15 7 15-7-15-7Z" fill="#1e40af"/><path d="M11 18v7c2.4 2.6 5.6 4 9 4s6.6-1.4 9-4v-7l-9 4.2L11 18Z" fill="#1e40af" opacity=".8"/><path d="M34 14v9" stroke="#f59e0b" stroke-width="2.4" stroke-linecap="round"/></svg></span>
                <p class="si-card-name"><?= e($appName) ?></p>
            </div>
            <?= $content ?>
        </div>
        <p class="si-foot"><?= icon('lock', 'h-3.5 w-3.5') ?> Secure sign-in &middot; <a href="<?= e(url('/privacy')) ?>">Privacy</a> &middot; <a href="<?= e(url('/terms')) ?>">Terms</a></p>
    </main>
</div>
</body>
</html>
