<?php
declare(strict_types=1);
/** No-JavaScript fallback: each page load imports one batch and continues. @var array{processed:int,total:int,created:int} $progress @var int $batchId */
$pct = $progress['total'] > 0 ? (int) floor($progress['processed'] / $progress['total'] * 100) : 0;
?>
<style nonce="<?= e($cspNonce) ?>">.import-bar{width:<?= $pct ?>%}</style>
<div class="mx-auto max-w-xl py-10 text-center">
    <span class="icon-tile mx-auto h-14 w-14 rounded-3xl"><?= icon('upload', 'h-6 w-6') ?></span>
    <h1 class="mt-6 font-display text-[28px] font-semibold text-ink">Importing students…</h1>
    <p class="mt-2 text-muted"><?= number_format($progress['processed']) ?> of <?= number_format($progress['total']) ?> processed · <?= number_format($progress['created']) ?> added</p>
    <div class="mt-6 h-2.5 overflow-hidden rounded-full bg-soft ring-1 ring-line"><div class="import-bar h-full rounded-full bg-signature"></div></div>
    <form method="post" action="<?= e(url('/school/students/import/' . $batchId . '/process')) ?>" class="mt-8" data-auto-continue>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <button class="btn btn-primary btn-lg"><span data-label>Continue import</span></button>
    </form>
</div>
