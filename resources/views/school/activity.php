<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows @var int $total @var int $pageNumber @var int $perPage */
?>
<p class="eyebrow">School</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Activity log</h1>
<p class="mt-1 text-sm text-muted">Every sign-in and change in your school, with who did it and when.</p>
<div class="card mt-6 overflow-hidden">
    <?php if ($rows === []): ?><p class="px-6 py-14 text-center text-sm text-muted">Nothing recorded yet.</p><?php else: ?>
        <ol class="divide-y divide-line">
            <?php foreach ($rows as $row): ?>
                <li class="flex gap-4 px-6 py-4">
                    <span class="mt-1 grid h-[19px] w-[19px] shrink-0 place-items-center rounded-full bg-brand/10"><span class="h-2 w-2 rounded-full bg-signature"></span></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-ink"><span class="font-semibold"><?= e($row['actor_name'] ?? 'System') ?></span> <?= e(lcfirst((string) $row['summary'])) ?><?= $row['impersonator_id'] ? ' <span class="badge badge-amber ml-1">platform support</span>' : '' ?></p>
                        <p class="mt-0.5 text-xs text-subtle" title="<?= e(local_date((string) $row['created_at'])) ?>"><?= e(time_ago((string) $row['created_at'])) ?> · <span class="font-mono"><?= e($row['ip'] ?? '') ?></span></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php $query = []; require __DIR__ . '/../partials/portal/pagination.php'; ?>
    <?php endif; ?>
</div>
