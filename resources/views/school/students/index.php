<?php
declare(strict_types=1);
/**
 * @var list<array<string,mixed>> $rows @var int $total @var int $pageNumber @var int $perPage @var array<string,mixed> $filters @var string $sort
 * @var list<array<string,mixed>> $classes @var list<array<string,mixed>> $departments @var int|null $remaining @var int $activeCount
 */
$statusBadge = ['active' => 'badge-green', 'suspended' => 'badge-amber', 'graduated' => 'badge-blue', 'withdrawn' => 'badge-gray'];
$query = array_filter([
    'q' => $filters['q'], 'class' => $filters['class_id'] === 0 ? 'none' : $filters['class_id'], 'department' => $filters['department_id'],
    'status' => $filters['status'] !== 'active' ? $filters['status'] : null, 'gender' => $filters['gender'] ?: null, 'sort' => $sort !== 'name' ? $sort : null,
], static fn ($v) => $v !== null && $v !== '');
$exportQuery = http_build_query($query);
$isFiltered = array_diff_key($query, ['sort' => 1]) !== [];
$capacity = $remaining === null ? null : $activeCount + $remaining;
?>
<div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="eyebrow">People</p>
        <h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Students</h1>
        <p class="mt-1 text-sm text-muted"><?= number_format($activeCount) ?> active<?= $capacity !== null ? ' of ' . number_format($capacity) . ' seats on your plan' : '' ?>.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/school/students/import')) ?>" class="btn btn-secondary"><?= icon('upload') ?>Import</a>
        <div class="relative" data-menu>
            <button type="button" class="btn btn-secondary" data-menu-button aria-expanded="false"><?= icon('download') ?>Export<?= icon('chevron-down') ?></button>
            <div class="absolute right-0 z-20 mt-2 hidden w-56 overflow-hidden rounded-2xl border border-line bg-card p-1.5 shadow-[0_24px_60px_-24px_rgb(24_22_61/.4)]" data-menu-panel>
                <a class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-soft" href="<?= e(url('/school/students/export') . '?' . $exportQuery) ?>"><?= icon('file-text', 'h-4 w-4 text-subtle') ?>Excel (.xlsx)</a>
                <a class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-soft" href="<?= e(url('/school/students/export') . '?' . http_build_query($query + ['format' => 'csv'])) ?>"><?= icon('file-text', 'h-4 w-4 text-subtle') ?>CSV</a>
                <a class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-soft" href="<?= e(url('/school/students/slips') . '?' . $exportQuery) ?>" target="_blank" rel="noopener"><?= icon('key', 'h-4 w-4 text-subtle') ?>Print sign-in slips</a>
            </div>
        </div>
        <a href="<?= e(url('/school/students/create')) ?>" class="btn btn-primary"><?= icon('plus') ?>Enrol student</a>
    </div>
</div>

<?php if ($capacity !== null && $capacity > 0): $pct = min(100, (int) round($activeCount / $capacity * 100)); ?>
    <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-soft ring-1 ring-line" role="img" aria-label="<?= $pct ?>% of plan seats used">
        <div class="h-full rounded-full <?= $pct >= 90 ? 'bg-amber-500' : 'bg-signature' ?> seats-bar"></div>
    </div>
    <style nonce="<?= e($cspNonce) ?>">.seats-bar{width:<?= $pct ?>%}</style>
<?php endif; ?>

<div class="card mt-6 overflow-hidden">
    <form method="get" action="<?= e(url('/school/students')) ?>" class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1fr_.8fr_.9fr_auto]" data-auto-filter>
        <label class="relative sm:col-span-2 lg:col-span-1">
            <span class="sr-only">Search</span>
            <span class="pointer-events-none absolute inset-y-0 left-3.5 grid place-items-center text-subtle"><?= icon('search', 'h-4 w-4') ?></span>
            <input class="input pl-10" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, admission no. or parent phone">
        </label>
        <select class="select" name="class" aria-label="Class">
            <option value="">All classes</option>
            <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= (int) $filters['class_id'] === (int) $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?> (<?= (int) $class['student_count'] ?>)</option><?php endforeach; ?>
            <option value="none" <?= $filters['class_id'] === 0 ? 'selected' : '' ?>>No class assigned</option>
        </select>
        <select class="select" name="department" aria-label="Department">
            <option value="">All departments</option>
            <?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>" <?= (int) $filters['department_id'] === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
        </select>
        <select class="select" name="gender" aria-label="Gender">
            <option value="">Any gender</option><option value="female" <?= $filters['gender'] === 'female' ? 'selected' : '' ?>>Girls</option><option value="male" <?= $filters['gender'] === 'male' ? 'selected' : '' ?>>Boys</option>
        </select>
        <select class="select" name="status" aria-label="Status">
            <?php foreach (['active' => 'Active', 'suspended' => 'Suspended', 'graduated' => 'Graduated', 'withdrawn' => 'Withdrawn', 'all' => 'All statuses'] as $v => $l): ?><option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select>
        <div class="flex gap-2">
            <button class="btn btn-secondary flex-1 lg:flex-none">Filter</button>
            <?php if ($isFiltered): ?><a class="btn btn-ghost" href="<?= e(url('/school/students')) ?>" aria-label="Clear filters"><?= icon('x') ?></a><?php endif; ?>
        </div>
    </form>

    <?php if ($rows === []): ?>
        <div class="empty">
            <img src="<?= e(asset('images/art/exam-screen.svg')) ?>" alt="" class="float-slow w-52" width="560" height="420" loading="lazy">
            <div>
                <p class="font-display text-xl font-semibold text-ink"><?= $isFiltered ? 'No students match these filters' : 'No students yet' ?></p>
                <p class="mt-1 max-w-sm text-sm text-muted"><?= $isFiltered ? 'Try a different search or clear the filters.' : 'Import your class lists from Excel in one go, or enrol students one at a time.' ?></p>
            </div>
            <?php if (!$isFiltered): ?>
                <div class="flex flex-wrap justify-center gap-2">
                    <a class="btn btn-primary" href="<?= e(url('/school/students/import')) ?>"><?= icon('upload') ?>Import from Excel</a>
                    <a class="btn btn-secondary" href="<?= e(url('/school/students/create')) ?>"><?= icon('plus') ?>Enrol one student</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="flex items-center justify-between gap-3 px-6 pt-4 text-xs text-muted">
            <span><?= number_format($total) ?> student<?= $total === 1 ? '' : 's' ?></span>
            <span class="flex items-center gap-1">Sort:
                <?php foreach (['name' => 'Name', 'admission' => 'Admission no.', 'class' => 'Class', 'newest' => 'Newest'] as $key => $label): ?>
                    <a class="rounded-full px-2 py-1 <?= $sort === $key ? 'bg-soft font-semibold text-ink' : 'hover:text-ink' ?>" href="<?= e(url('/school/students') . '?' . http_build_query(['sort' => $key] + $query)) ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </span>
        </div>
        <div class="hidden overflow-x-auto md:block">
            <table class="table mt-2">
                <thead><tr><th scope="col">Student</th><th scope="col">Admission no.</th><th scope="col">Class</th><th scope="col">Guardian</th><th scope="col">Status</th><th scope="col">Last sign-in</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $s): ?>
                    <tr class="relative">
                        <td>
                            <div class="flex items-center gap-3">
                                <?php if ($s['photo_path']): ?><img src="<?= e(url('/' . $s['photo_path'])) ?>" alt="" class="h-10 w-10 rounded-full object-cover ring-2 ring-card" loading="lazy">
                                <?php else: ?><span class="grid h-10 w-10 place-items-center rounded-full bg-brand/[.08] text-[13px] font-semibold text-brand"><?= e(initials((string) $s['name'])) ?></span><?php endif; ?>
                                <a href="<?= e(url('/school/students/' . $s['id'])) ?>" class="font-semibold text-ink after:absolute after:inset-0"><?= e($s['name']) ?></a>
                            </div>
                        </td>
                        <td class="font-mono text-[13px] text-muted"><?= e($s['admission_no']) ?></td>
                        <td><?= $s['class_name'] ? e($s['class_name']) : '<span class="text-subtle">—</span>' ?></td>
                        <td class="text-muted"><?= e($s['guardian_phone'] ?? '—') ?></td>
                        <td><span class="badge badge-dot <?= $statusBadge[$s['status']] ?? 'badge-gray' ?>"><?= e(ucfirst((string) $s['status'])) ?></span></td>
                        <td class="whitespace-nowrap text-muted"><?= e(time_ago($s['last_login_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <ul class="divide-y divide-line md:hidden">
            <?php foreach ($rows as $s): ?>
                <li><a href="<?= e(url('/school/students/' . $s['id'])) ?>" class="flex items-center gap-3 px-4 py-3.5">
                    <?php if ($s['photo_path']): ?><img src="<?= e(url('/' . $s['photo_path'])) ?>" alt="" class="h-11 w-11 rounded-full object-cover" loading="lazy"><?php else: ?><span class="grid h-11 w-11 place-items-center rounded-full bg-brand/[.08] text-sm font-semibold text-brand"><?= e(initials((string) $s['name'])) ?></span><?php endif; ?>
                    <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-ink"><?= e($s['name']) ?></span><span class="block truncate text-xs text-muted"><?= e($s['admission_no']) ?> · <?= e($s['class_name'] ?? 'No class') ?></span></span>
                    <?= icon('chevron-right', 'h-4 w-4 text-subtle') ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
        <?php require __DIR__ . '/../../partials/portal/pagination.php'; ?>
    <?php endif; ?>
</div>
