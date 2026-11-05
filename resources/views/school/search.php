<?php
declare(strict_types=1);
/** @var string $q @var array{rows:list<array>,total:int} $students @var list<array> $staff */
?>
<p class="eyebrow">Search</p>
<form method="get" action="<?= e(url('/school/search')) ?>" class="relative mt-3 max-w-2xl" role="search">
    <span class="pointer-events-none absolute inset-y-0 left-4 grid place-items-center text-subtle"><?= icon('search', 'h-5 w-5') ?></span>
    <input class="input rounded-full py-4 pl-12 text-lg" type="search" name="q" value="<?= e($q) ?>" placeholder="Search students or staff" autofocus aria-label="Search">
</form>
<?php if ($q !== ''): ?>
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="card overflow-hidden"><div class="card-header"><h2 class="card-title">Students</h2><span class="badge badge-gray"><?= (int) $students['total'] ?></span></div>
            <?php if ($students['rows'] === []): ?><p class="px-6 pb-6 text-sm text-muted">No students match “<?= e($q) ?>”.</p><?php else: ?>
                <ul class="space-y-1 px-3 pb-3"><?php foreach ($students['rows'] as $s): ?>
                    <li><a href="<?= e(url('/school/students/' . $s['id'])) ?>" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 hover:bg-soft"><span class="grid h-10 w-10 place-items-center rounded-full bg-brand/[.08] text-[13px] font-semibold text-brand"><?= e(initials((string) $s['name'])) ?></span>
                        <span class="min-w-0"><span class="block truncate font-semibold text-ink"><?= e($s['name']) ?></span><span class="block text-xs text-muted"><?= e($s['admission_no']) ?> · <?= e($s['class_name'] ?? 'No class') ?> · <?= e(ucfirst((string) $s['status'])) ?></span></span></a></li>
                <?php endforeach; ?></ul>
                <?php if ($students['total'] > count($students['rows'])): ?><a class="link block px-6 pb-5 text-sm" href="<?= e(url('/school/students?status=all&q=' . rawurlencode($q))) ?>">See all <?= (int) $students['total'] ?> results</a><?php endif; ?>
            <?php endif; ?></section>
        <section class="card overflow-hidden"><div class="card-header"><h2 class="card-title">Staff</h2><span class="badge badge-gray"><?= count($staff) ?></span></div>
            <?php if ($staff === []): ?><p class="px-6 pb-6 text-sm text-muted">No staff match “<?= e($q) ?>”.</p><?php else: ?>
                <ul class="space-y-1 px-3 pb-3"><?php foreach ($staff as $t): ?>
                    <li><a href="<?= e(url('/school/teachers/' . $t['id'])) ?>" class="flex items-center gap-3 rounded-2xl px-3 py-2.5 hover:bg-soft"><span class="grid h-10 w-10 place-items-center rounded-full bg-signature text-[13px] font-bold text-brand-ink"><?= e(initials((string) $t['name'])) ?></span>
                        <span class="min-w-0"><span class="block truncate font-semibold text-ink"><?= e($t['name']) ?></span><span class="block truncate text-xs text-muted"><?= e($t['email']) ?></span></span></a></li>
                <?php endforeach; ?></ul>
            <?php endif; ?></section>
    </div>
<?php endif; ?>
