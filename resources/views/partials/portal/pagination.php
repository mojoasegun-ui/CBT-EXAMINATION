<?php
declare(strict_types=1);
/** @var int $total @var int $pageNumber @var int $perPage @var array<string,string> $query */
$pages = (int) max(1, ceil($total / max(1, $perPage)));
if ($pages <= 1) {
    return;
}
$link = static fn (int $p): string => '?' . http_build_query(array_filter(($query ?? []) + ['page' => $p], static fn ($v) => $v !== '' && $v !== null));
$from = ($pageNumber - 1) * $perPage + 1;
$to = min($total, $pageNumber * $perPage);
?>
<nav class="flex items-center justify-between gap-3 border-t border-line px-5 py-3 text-sm" aria-label="Pagination">
    <p class="text-muted">Showing <span class="font-semibold text-ink"><?= $from ?>–<?= $to ?></span> of <span class="font-semibold text-ink"><?= number_format($total) ?></span></p>
    <div class="flex gap-2">
        <?php if ($pageNumber > 1): ?><a class="btn btn-secondary btn-sm" href="<?= e($link($pageNumber - 1)) ?>"><?= icon('chevron-left') ?>Previous</a><?php endif; ?>
        <?php if ($pageNumber < $pages): ?><a class="btn btn-secondary btn-sm" href="<?= e($link($pageNumber + 1)) ?>">Next<?= icon('chevron-right') ?></a><?php endif; ?>
    </div>
</nav>
