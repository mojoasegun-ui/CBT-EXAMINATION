<?php
declare(strict_types=1);
/** @var int $status @var string $title @var string $message @var string $requestId @var string $baseUrl @var array<string,string>|null $debug */
$homeUrl = ($baseUrl !== '' ? $baseUrl : '') . '/';
$canRetry = in_array($status, [419, 429, 500, 503], true);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> · <?= e($appName ?? 'ExamCore') ?></title>
    <style nonce="<?= e($cspNonce ?? '') ?>">
        :root { --bg:#f8fafc; --card:#fff; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --brand:#1e40af; --brand-ink:#fff; --soft:#eff6ff; }
        @media (prefers-color-scheme: dark) { :root { --bg:#020817; --card:#0f172a; --ink:#e2e8f0; --muted:#94a3b8; --line:#1e293b; --brand:#3b82f6; --soft:#0b1a36; } }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:24px 16px; background:var(--bg); color:var(--ink);
               font:16px/1.6 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; }
        .card { width:100%; max-width:480px; background:var(--card); border:1px solid var(--line); border-radius:20px; padding:36px 28px; text-align:center;
                box-shadow:0 20px 50px -20px rgba(15,23,42,.18); }
        .code { display:inline-grid; place-items:center; min-width:84px; height:44px; padding:0 16px; border-radius:999px; background:var(--soft); color:var(--brand);
                font-weight:800; letter-spacing:.06em; }
        h1 { margin:18px 0 8px; font-size:1.6rem; line-height:1.25; }
        p { margin:0; color:var(--muted); }
        .actions { display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-top:28px; }
        a, button { min-height:46px; padding:0 20px; border-radius:12px; font:inherit; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; text-decoration:none; }
        .primary { background:var(--brand); color:var(--brand-ink); border:0; }
        .ghost { background:transparent; color:var(--ink); border:1px solid var(--line); }
        a:focus-visible, button:focus-visible { outline:3px solid var(--brand); outline-offset:2px; }
        .ref { margin-top:22px; font-size:.8rem; }
        .ref code { font-family:ui-monospace,Consolas,monospace; }
        .debug { margin-top:22px; text-align:left; font:12px/1.5 ui-monospace,Consolas,monospace; background:#1e1b4b; color:#e0e7ff; padding:14px; border-radius:12px; overflow:auto; }
    </style>
</head>
<body>
<main class="card" role="main">
    <span class="code"><?= e((string) $status) ?></span>
    <h1><?= e($title) ?></h1>
    <p><?= e($message) ?></p>
    <div class="actions">
        <?php if ($canRetry): ?><button type="button" class="primary" data-retry>Try again</button><?php endif; ?>
        <a class="<?= $canRetry ? 'ghost' : 'primary' ?>" href="<?= e($homeUrl) ?>">Go to homepage</a>
    </div>
    <?php if ($requestId !== ''): ?><p class="ref">Reference: <code><?= e($requestId) ?></code></p><?php endif; ?>
    <?php if ($debug !== null): ?>
        <pre class="debug"><?= e($debug['exception'] . "\n" . $debug['message'] . "\n" . $debug['location']) ?></pre>
    <?php endif; ?>
</main>
<script nonce="<?= e($cspNonce ?? '') ?>">document.querySelector('[data-retry]')?.addEventListener('click', () => location.reload());</script>
</body>
</html>
