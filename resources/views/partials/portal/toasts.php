<?php
declare(strict_types=1);
/** @var array{message: string, type: string}|null $flash */
$styles = [
    'success' => ['check-circle', 'text-emerald-500'],
    'error' => ['alert-circle', 'text-red-500'],
    'warning' => ['alert', 'text-amber-500'],
    'info' => ['info', 'text-sky-500'],
];
?>
<div class="pointer-events-none fixed inset-x-0 top-3 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:pr-6" data-toasts aria-live="polite" aria-atomic="true">
    <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
        <?php [$iconName, $iconColor] = $styles[$flash['type'] ?? 'success'] ?? $styles['info']; ?>
        <div class="pointer-events-auto flex w-full max-w-md animate-toast-in items-start gap-3 rounded-2xl border border-line bg-card p-4 shadow-lift" role="<?= ($flash['type'] ?? '') === 'error' ? 'alert' : 'status' ?>" data-toast>
            <span class="mt-0.5 <?= e($iconColor) ?>"><?= icon($iconName, 'h-5 w-5') ?></span>
            <p class="flex-1 text-sm font-medium text-ink"><?= e($flash['message']) ?></p>
            <button type="button" class="-m-1 rounded-lg p-1 text-subtle hover:text-ink" data-toast-close aria-label="Dismiss"><?= icon('x', 'h-4 w-4') ?></button>
        </div>
    <?php endif; ?>
</div>
