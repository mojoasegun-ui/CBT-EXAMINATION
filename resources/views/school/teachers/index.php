<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $staff @var string $query @var array<string,string> $roles @var array|null $credentials */
$roleBadge = ['school_admin' => 'badge-brand', 'teacher' => 'badge-blue', 'reviewer' => 'badge-gold'];
$roleShort = ['school_admin' => 'Administrator', 'teacher' => 'Teacher', 'reviewer' => 'Reviewer'];
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">People</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Teachers &amp; staff</h1>
        <p class="mt-1 text-sm text-muted"><?= count($staff) ?> account<?= count($staff) === 1 ? '' : 's' ?>. Teachers write questions and see their classes; reviewers approve questions.</p></div>
    <a href="<?= e(url('/school/teachers/create')) ?>" class="btn btn-primary"><?= icon('plus') ?>Add staff</a>
</div>

<?php if (is_array($credentials)): require __DIR__ . '/../../partials/portal/credentials.php'; endif; ?>

<div class="card mt-6 overflow-hidden">
    <form method="get" action="<?= e(url('/school/teachers')) ?>" class="border-b border-line p-4" role="search">
        <label class="relative block max-w-md"><span class="sr-only">Search staff</span>
            <span class="pointer-events-none absolute inset-y-0 left-3.5 grid place-items-center text-subtle"><?= icon('search', 'h-4 w-4') ?></span>
            <input class="input pl-10" type="search" name="q" value="<?= e($query) ?>" placeholder="Name, email or staff number"></label>
    </form>
    <?php if ($staff === []): ?>
        <div class="empty"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('users', 'h-6 w-6') ?></span>
            <div><p class="font-display text-xl font-semibold text-ink"><?= $query !== '' ? 'No staff match' : 'No teachers yet' ?></p><p class="mt-1 text-sm text-muted">Add teachers so you can assign them to classes and subjects.</p></div>
            <a class="btn btn-primary" href="<?= e(url('/school/teachers/create')) ?>"><?= icon('plus') ?>Add a teacher</a></div>
    <?php else: ?>
        <ul class="grid gap-px bg-line sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($staff as $t): ?>
                <li class="bg-card"><a href="<?= e(url('/school/teachers/' . $t['id'])) ?>" class="group flex h-full items-start gap-4 p-5 transition hover:bg-soft">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-signature text-sm font-bold text-brand-ink <?= $t['status'] !== 'active' ? 'opacity-40 grayscale' : '' ?>"><?= e(initials((string) $t['name'])) ?></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-ink"><?= e(trim(($t['title'] ? $t['title'] . ' ' : '') . $t['name'])) ?></span>
                        <span class="block truncate text-sm text-muted"><?= e($t['email']) ?></span>
                        <span class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="badge <?= $roleBadge[$t['role']] ?? 'badge-gray' ?>"><?= e($roleShort[$t['role']] ?? $t['role']) ?></span>
                            <?php if ($t['status'] !== 'active'): ?><span class="badge badge-gray">Deactivated</span><?php endif; ?>
                            <?php if ((int) $t['assignment_count'] > 0): ?><span class="badge badge-gray"><?= (int) $t['assignment_count'] ?> subject class<?= (int) $t['assignment_count'] === 1 ? '' : 'es' ?></span><?php endif; ?>
                            <?php if ((int) $t['form_class_count'] > 0): ?><span class="badge badge-green">Class teacher</span><?php endif; ?>
                        </span>
                    </span>
                    <span class="text-subtle transition group-hover:translate-x-0.5 group-hover:text-ink"><?= icon('chevron-right', 'h-4 w-4') ?></span>
                </a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
