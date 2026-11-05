<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows @var int $total @var int $pageNumber @var int $perPage @var string $action @var array|null $schoolFilter */
$groups = ['' => 'All activity', 'auth' => 'Sign-ins', 'school' => 'Schools', 'plan' => 'Plans', 'system' => 'System'];
$tone = static fn (string $a): string => match (true) {
    str_contains($a, 'failed') => 'badge-red',
    str_starts_with($a, 'auth') => 'badge-blue',
    str_starts_with($a, 'school.status') || str_contains($a, 'impersonate') => 'badge-amber',
    str_starts_with($a, 'system') => 'badge-gray',
    default => 'badge-brand',
};
?>
<h1 class="font-display text-2xl font-semibold text-ink">Audit log</h1>
<p class="mt-1 text-sm text-muted">Every sign-in and change on the platform: who, what, when and from where.</p>

<div class="card mt-6 overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center sm:justify-between">
        <nav class="tabs -mb-4 border-0 sm:-mb-0" aria-label="Filter">
            <?php foreach ($groups as $value => $label): ?>
                <a class="tab" href="<?= e(url('/console/audit') . '?' . http_build_query(array_filter(['action' => $value, 'school' => $schoolFilter['id'] ?? null]))) ?>" <?= $action === $value ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($schoolFilter !== null): ?>
            <a href="<?= e(url('/console/audit') . ($action !== '' ? '?action=' . rawurlencode($action) : '')) ?>" class="badge badge-brand gap-2 py-1">School: <?= e($schoolFilter['name']) ?> <?= icon('x', 'h-3.5 w-3.5') ?></a>
        <?php endif; ?>
    </div>
    <?php if ($rows === []): ?>
        <p class="px-5 py-14 text-center text-sm text-muted">Nothing recorded yet for this filter.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th scope="col">When</th><th scope="col">Who</th><th scope="col">What</th><th scope="col">School</th><th scope="col">IP address</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="whitespace-nowrap text-muted" title="<?= e(local_date((string) $row['created_at'])) ?>"><?= e(time_ago((string) $row['created_at'])) ?></td>
                        <td class="whitespace-nowrap font-medium text-ink"><?= e($row['actor_name'] ?? 'System') ?><?= $row['impersonator_id'] ? ' <span class="badge badge-amber ml-1">support</span>' : '' ?></td>
                        <td class="min-w-[18rem]">
                            <span class="badge <?= $tone((string) $row['action']) ?> mr-2 font-mono text-[10px]"><?= e($row['action']) ?></span>
                            <span class="text-ink"><?= e($row['summary']) ?></span>
                        </td>
                        <td class="whitespace-nowrap text-muted"><?= $row['school_name'] ? '<a class="hover:text-ink" href="' . e(url('/console/schools/' . (int) $row['school_id'])) . '">' . e($row['school_name']) . '</a>' : '—' ?></td>
                        <td class="whitespace-nowrap font-mono text-xs text-subtle"><?= e($row['ip'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $query = ['action' => $action, 'school' => $schoolFilter['id'] ?? '']; require __DIR__ . '/../partials/portal/pagination.php'; ?>
    <?php endif; ?>
</div>
