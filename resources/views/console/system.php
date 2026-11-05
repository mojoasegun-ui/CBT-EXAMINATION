<?php
declare(strict_types=1);
/**
 * @var list<array{label:string,value:string,ok:bool}> $checks @var list<array{migration:string,batch:?int,ran_at:?string}> $migrations
 * @var array{pending:int,reserved:int,failed:int} $queue @var list<array<string,mixed>> $failedJobs @var list<array{name:string,size:int,time:int}> $backups
 */
$pending = array_values(array_filter($migrations, static fn (array $m): bool => $m['batch'] === null));
$problems = count(array_filter($checks, static fn (array $c): bool => !$c['ok']));
$kb = static fn (int $bytes): string => $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024, 1) . ' KB';
?>
<h1 class="font-display text-2xl font-semibold text-ink">System</h1>
<p class="mt-1 text-sm text-muted">Health checks, database updates, background jobs and backups.</p>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2" aria-labelledby="checks-title">
        <div class="card-header">
            <h2 id="checks-title" class="card-title">Health checks</h2>
            <span class="badge <?= $problems === 0 ? 'badge-green' : 'badge-amber' ?>"><?= $problems === 0 ? 'All good' : $problems . ' need' . ($problems === 1 ? 's' : '') . ' attention' ?></span>
        </div>
        <ul class="grid divide-y divide-line sm:grid-cols-2 sm:divide-y-0">
            <?php foreach ($checks as $check): ?>
                <li class="flex items-center gap-3 border-line px-5 py-3.5 sm:border-b">
                    <span class="<?= $check['ok'] ? 'text-emerald-500' : 'text-amber-500' ?>"><?= icon($check['ok'] ? 'check-circle' : 'alert', 'h-[18px] w-[18px]', $check['ok'] ? 'OK' : 'Needs attention') ?></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs text-muted"><?= e($check['label']) ?></span>
                        <span class="block truncate text-sm font-medium text-ink"><?= e($check['value']) ?></span>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="card" aria-labelledby="updates-title">
        <div class="card-header"><h2 id="updates-title" class="card-title">Database updates</h2></div>
        <div class="card-body">
            <?php if ($pending === []): ?>
                <div class="flex items-center gap-3"><span class="text-emerald-500"><?= icon('check-circle', 'h-6 w-6') ?></span><div><p class="font-semibold text-ink">Up to date</p><p class="text-sm text-muted"><?= count($migrations) ?> updates applied</p></div></div>
            <?php else: ?>
                <p class="font-semibold text-ink"><?= count($pending) ?> update<?= count($pending) === 1 ? '' : 's' ?> ready</p>
                <ul class="mt-2 space-y-1 text-xs text-muted"><?php foreach ($pending as $m): ?><li class="truncate font-mono"><?= e($m['migration']) ?></li><?php endforeach; ?></ul>
                <form method="post" action="<?= e(url('/console/system/migrate')) ?>" class="mt-4" data-submit-once data-confirm="A full backup is taken first. Updates usually take a few seconds; avoid running them during a live exam." data-confirm-title="Apply database updates?" data-confirm-ok="Back up and update">
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                    <button class="btn btn-primary w-full"><?= icon('refresh') ?><span data-label>Back up &amp; apply updates</span></button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="card overflow-hidden" aria-labelledby="queue-title">
        <div class="card-header">
            <h2 id="queue-title" class="card-title">Background jobs</h2>
            <span class="text-sm text-muted"><?= $queue['pending'] ?> waiting · <?= $queue['reserved'] ?> running</span>
        </div>
        <?php if ($failedJobs === []): ?>
            <div class="flex items-center gap-3 px-5 py-6"><span class="text-emerald-500"><?= icon('check-circle', 'h-5 w-5') ?></span><p class="text-sm text-muted">No failed jobs. Emails and other background work are running normally.</p></div>
        <?php else: ?>
            <ul class="divide-y divide-line">
                <?php foreach ($failedJobs as $job): ?>
                    <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink"><span class="font-mono"><?= e($job['type']) ?></span> <span class="font-normal text-subtle">#<?= (int) $job['id'] ?> · <?= e(time_ago((string) $job['failed_at'])) ?></span></p>
                            <p class="mt-0.5 truncate text-xs text-red-600 dark:text-red-400" title="<?= e($job['last_error']) ?>"><?= e($job['last_error']) ?></p>
                        </div>
                        <div class="flex gap-2">
                            <form method="post" action="<?= e(url('/console/system/jobs/' . (int) $job['id'] . '/retry')) ?>"><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-secondary btn-sm"><?= icon('refresh') ?>Retry</button></form>
                            <form method="post" action="<?= e(url('/console/system/jobs/' . (int) $job['id'] . '/delete')) ?>"><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-ghost btn-sm" aria-label="Delete job <?= (int) $job['id'] ?>"><?= icon('trash') ?></button></form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="card overflow-hidden" aria-labelledby="backup-title">
        <div class="card-header">
            <h2 id="backup-title" class="card-title">Backups</h2>
            <form method="post" action="<?= e(url('/console/system/backup')) ?>" data-submit-once>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <button class="btn btn-secondary btn-sm"><?= icon('download') ?><span data-label>Back up now</span></button>
            </form>
        </div>
        <?php if ($backups === []): ?>
            <p class="px-5 py-6 text-sm text-muted">No backups yet. Take one now, and download a copy to keep somewhere other than this server.</p>
        <?php else: ?>
            <ul class="divide-y divide-line">
                <?php foreach ($backups as $backup): ?>
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="text-subtle"><?= icon('database', 'h-[18px] w-[18px]') ?></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-mono text-sm text-ink"><?= e($backup['name']) ?></span>
                            <span class="block text-xs text-muted"><?= e($kb($backup['size'])) ?> · <?= e(time_ago(gmdate('Y-m-d H:i:s', $backup['time']))) ?></span>
                        </span>
                        <a class="btn btn-ghost btn-sm" href="<?= e(url('/console/system/backups/' . rawurlencode($backup['name']))) ?>" aria-label="Download <?= e($backup['name']) ?>"><?= icon('download') ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
