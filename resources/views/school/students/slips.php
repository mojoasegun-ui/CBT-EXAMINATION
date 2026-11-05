<?php
declare(strict_types=1);
/** Printable A4 sign-in slips (8 per page). @var list<array<string,mixed>> $students @var array<int,string> $credentials @var array<string,mixed> $school @var string $loginUrl */
$host = preg_replace('#^https?://#', '', $loginUrl);
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <meta name="robots" content="noindex">
    <title>Sign-in slips · <?= e($school['name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/portal.css')) ?>">
    <style nonce="<?= e($cspNonce) ?>">
        :root { --brand: <?= e(hex_to_rgb_channels((string) $school['primary_color'])) ?>; }
        @page { size: A4; margin: 10mm; }
        .sheet { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .slip { border: 1px dashed #c9c9d6; padding: 14px 16px; break-inside: avoid; min-height: 64mm; }
        @media print { .no-print { display: none !important; } body { background: #fff; } .sheet { gap: 0; } }
    </style>
</head>
<body class="bg-soft">
<div class="no-print sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border-b border-line bg-card/90 px-6 py-4 backdrop-blur">
    <div><p class="font-display text-lg font-semibold text-ink">Sign-in slips</p><p class="text-sm text-muted"><?= count($students) ?> student<?= count($students) === 1 ? '' : 's' ?> · cut along the dashed lines</p></div>
    <button type="button" class="btn btn-primary" data-print><?= icon('file-text') ?>Print</button>
</div>
<main class="mx-auto max-w-[210mm] bg-white p-[6mm] shadow-sm print:p-0 print:shadow-none">
    <?php if ($students === []): ?>
        <p class="p-10 text-center text-muted">No students to print. Go back and choose a class or import.</p>
    <?php endif; ?>
    <div class="sheet">
        <?php foreach ($students as $s): $password = $credentials[(int) $s['id']] ?? null; ?>
            <article class="slip">
                <header class="flex items-center gap-2.5 border-b border-slate-100 pb-2.5">
                    <?php if ($school['logo_path']): ?><img src="<?= e(url('/' . $school['logo_path'])) ?>" alt="" class="h-9 w-9 object-contain"><?php else: ?><span class="grid h-9 w-9 place-items-center rounded-xl bg-brand text-xs font-bold text-white"><?= e(initials((string) $school['name'])) ?></span><?php endif; ?>
                    <div class="min-w-0"><p class="truncate text-[13px] font-bold text-slate-900"><?= e($school['name']) ?></p><p class="text-[10px] uppercase tracking-[.16em] text-slate-500">CBT sign-in slip</p></div>
                </header>
                <p class="mt-3 text-[15px] font-semibold text-slate-900"><?= e($s['name']) ?></p>
                <p class="text-xs text-slate-500"><?= e($s['class_name'] ?? 'No class') ?></p>
                <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-[12.5px]">
                    <dt class="text-slate-500">Website</dt><dd class="font-mono font-semibold text-slate-900"><?= e($host) ?></dd>
                    <dt class="text-slate-500">School code</dt><dd class="font-mono font-semibold text-slate-900"><?= e($school['slug']) ?></dd>
                    <dt class="text-slate-500">Admission no.</dt><dd class="font-mono font-semibold text-slate-900"><?= e($s['admission_no']) ?></dd>
                    <dt class="text-slate-500">Password</dt><dd class="font-mono font-semibold text-slate-900"><?= $password !== null ? e($password) : '<span class="font-sans font-normal text-slate-500">as given by your school</span>' ?></dd>
                </dl>
                <p class="mt-3 text-[10.5px] leading-4 text-slate-500">You will be asked to choose your own password the first time. Keep this slip private.</p>
            </article>
        <?php endforeach; ?>
    </div>
</main>
<script nonce="<?= e($cspNonce) ?>">document.querySelector('[data-print]')?.addEventListener('click', () => window.print());</script>
</body>
</html>
