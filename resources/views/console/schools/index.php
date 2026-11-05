<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows @var int $total @var string $query @var string $status @var array<string,int> $counts */
$statusBadge = ['trial' => 'badge-blue', 'active' => 'badge-green', 'suspended' => 'badge-amber', 'archived' => 'badge-gray'];
$filters = ['' => ['All', array_sum($counts)], 'active' => ['Active', $counts['active']], 'trial' => ['Trial', $counts['trial']], 'suspended' => ['Suspended', $counts['suspended']], 'archived' => ['Archived', $counts['archived']]];
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="font-display text-2xl font-semibold text-ink">Schools</h1>
        <p class="mt-1 text-sm text-muted">Every school on the platform, its plan and its status.</p>
    </div>
    <a href="<?= e(url('/console/schools/create')) ?>" class="btn btn-primary"><?= icon('plus') ?>Add a school</a>
</div>

<div class="card mt-6 overflow-hidden">
    <div class="flex flex-col gap-3 border-b border-line p-4 lg:flex-row lg:items-center lg:justify-between">
        <nav class="tabs -mb-4 border-0 lg:-mb-0" aria-label="Filter by status">
            <?php foreach ($filters as $value => [$label, $count]): ?>
                <a href="<?= e(url('/console/schools') . '?' . http_build_query(array_filter(['status' => $value, 'q' => $query]))) ?>" class="tab inline-flex items-center gap-2" <?= $status === $value ? 'aria-current="page"' : '' ?>>
                    <?= e($label) ?> <span class="rounded-full bg-ink/5 px-2 py-0.5 text-[11px] tabular-nums text-muted"><?= $count ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <form method="get" action="<?= e(url('/console/schools')) ?>" class="relative w-full lg:w-80" role="search">
            <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-subtle"><?= icon('search', 'h-4 w-4') ?></span>
            <input class="input pl-9" type="search" name="q" value="<?= e($query) ?>" placeholder="Search name, state or email" aria-label="Search schools">
        </form>
    </div>

    <?php if ($rows === []): ?>
        <div class="empty">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-brand/10 text-brand"><?= icon($query !== '' ? 'search' : 'school', 'h-7 w-7') ?></span>
            <div>
                <p class="font-display text-lg font-semibold text-ink"><?= $query !== '' || $status !== '' ? 'No schools match' : 'No schools yet' ?></p>
                <p class="mt-1 text-sm text-muted"><?= $query !== '' || $status !== '' ? 'Try a different search or filter.' : 'Add your first school to get started.' ?></p>
            </div>
            <?php if ($query === '' && $status === ''): ?><a class="btn btn-primary" href="<?= e(url('/console/schools/create')) ?>"><?= icon('plus') ?>Add a school</a><?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Desktop table -->
        <div class="hidden overflow-x-auto md:block">
            <table class="table">
                <thead><tr><th scope="col">School</th><th scope="col">Plan</th><th scope="col">Status</th><th scope="col" class="text-right">Students</th><th scope="col" class="text-right">Staff</th><th scope="col">Added</th><th class="w-10"><span class="sr-only">Open</span></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr class="relative">
                        <td>
                            <div class="flex items-center gap-3">
                                <?php $avatarSize = 'h-9 w-9'; require __DIR__ . '/../../partials/portal/school-avatar.php'; ?>
                                <div class="min-w-0">
                                    <a href="<?= e(url('/console/schools/' . $row['id'])) ?>" class="block truncate font-semibold text-ink after:absolute after:inset-0"><?= e($row['name']) ?></a>
                                    <span class="block truncate text-xs text-muted"><?= e($row['state'] ?? '—') ?> · /<?= e($row['slug']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-brand"><?= e($row['plan_name']) ?></span></td>
                        <td><span class="badge badge-dot <?= $statusBadge[$row['status']] ?? 'badge-gray' ?>"><?= e(ucfirst((string) $row['status'])) ?></span>
                            <?php if ($row['status'] === 'trial' && $row['trial_ends_at']): ?><span class="ml-1 text-xs text-muted">ends <?= e(local_date($row['trial_ends_at'] . ' 12:00:00', 'j M')) ?></span><?php endif; ?></td>
                        <td class="text-right tabular-nums"><?= number_format((int) $row['student_count']) ?><?php if ($row['max_students']): ?><span class="text-subtle"> / <?= number_format((int) $row['max_students']) ?></span><?php endif; ?></td>
                        <td class="text-right tabular-nums"><?= number_format((int) $row['staff_count']) ?></td>
                        <td class="whitespace-nowrap text-muted"><?= e(local_date((string) $row['created_at'], 'j M Y')) ?></td>
                        <td class="text-subtle"><?= icon('chevron-right', 'h-4 w-4') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Mobile cards -->
        <ul class="divide-y divide-line md:hidden">
            <?php foreach ($rows as $row): ?>
                <li>
                    <a href="<?= e(url('/console/schools/' . $row['id'])) ?>" class="flex items-center gap-3 p-4">
                        <?php $avatarSize = 'h-10 w-10'; require __DIR__ . '/../../partials/portal/school-avatar.php'; ?>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-ink"><?= e($row['name']) ?></span>
                            <span class="mt-1 flex flex-wrap items-center gap-2">
                                <span class="badge badge-dot <?= $statusBadge[$row['status']] ?? 'badge-gray' ?>"><?= e(ucfirst((string) $row['status'])) ?></span>
                                <span class="text-xs text-muted"><?= e($row['plan_name']) ?> · <?= number_format((int) $row['student_count']) ?> students</span>
                            </span>
                        </span>
                        <?= icon('chevron-right', 'h-4 w-4 text-subtle') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php $query = ['q' => $query, 'status' => $status]; require __DIR__ . '/../../partials/portal/pagination.php'; ?>
    <?php endif; ?>
</div>
