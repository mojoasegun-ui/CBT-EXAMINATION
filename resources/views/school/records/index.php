<?php
declare(strict_types=1);
/**
 * Records & history list.
 * @var list<array<string,mixed>> $sessions @var int $sessionId @var list<array<string,mixed>> $classes @var array<string,mixed> $filters
 * @var list<array<string,mixed>> $rows @var int $total @var int $page @var int $pages @var bool $isAdmin
 */
use ExamCore\Models\Enrolment;

$sessionName = '';
foreach ($sessions as $s) {
    if ((int) $s['id'] === $sessionId) {
        $sessionName = (string) $s['name'];
    }
}
$pageUrl = static fn (int $p): string => url('/school/records') . '?' . http_build_query(array_filter(['session' => $sessionId, 'class' => $filters['class'] ?: null, 'q' => $filters['q'] ?: null, 'outcome' => $filters['outcome'] ?: null, 'page' => $p > 1 ? $p : null]));
$tone = ['in_progress' => 'badge-blue', 'promoted' => 'badge-green', 'repeated' => 'badge-amber', 'graduated' => 'badge-green', 'withdrawn' => 'badge-gray', 'suspended' => 'badge-amber'];
?>
<div class="pd-hello">
    <div>
        <h1>Records <b>&amp; history</b></h1>
        <p>Look up any student in any session, past or present. <?= $isAdmin ? '' : 'You see students in the classes you teach.' ?></p>
    </div>
    <?php if ($isAdmin): ?><a class="pd-pill pd-term" href="<?= e(url('/school/academic')) ?>"><?= icon('calendar', 'h-4 w-4') ?> Academic year <span class="pd-term-go">Manage</span></a><?php endif; ?>
</div>

<form method="get" class="pd-card mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.4fr)_auto] lg:items-end" role="search">
    <label><span class="label">Session</span>
        <select class="select" name="session"><?php foreach ($sessions as $s): ?><option value="<?= (int) $s['id'] ?>" <?= (int) $s['id'] === $sessionId ? 'selected' : '' ?>><?= e($s['name']) ?><?= $s['is_current'] ? ' (current)' : '' ?> · <?= (int) $s['enrolled'] ?></option><?php endforeach; ?></select></label>
    <label><span class="label">Class in that session</span>
        <select class="select" name="class"><option value="0">All classes</option><?php foreach ($classes as $c): ?><option value="<?= (int) $c['class_id'] ?>" <?= (int) $filters['class'] === (int) $c['class_id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?> (<?= (int) $c['n'] ?>)</option><?php endforeach; ?></select></label>
    <label><span class="label">How the year ended</span>
        <select class="select" name="outcome"><option value="">Any</option><?php foreach (Enrolment::OUTCOMES as $key => $label): ?><option value="<?= e($key) ?>" <?= $filters['outcome'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
    <label><span class="label">Name or admission number</span>
        <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search…" maxlength="80"></label>
    <button class="btn btn-primary"><?= icon('search', 'h-4 w-4') ?><span>Search</span></button>
</form>

<section class="pd-card mt-4" aria-labelledby="rec-title">
    <div class="pd-head"><h2 id="rec-title"><?= icon('graduation', 'h-5 w-5') ?> <?= e($sessionName ?: 'No session yet') ?></h2><span class="pd-pill pd-pill-sm"><?= (int) $total ?> student<?= $total === 1 ? '' : 's' ?></span></div>
    <?php if ($sessions === []): ?>
        <div class="pd-empty"><span class="ico"><?= icon('calendar', 'h-6 w-6') ?></span><b>No academic session yet</b><?php if ($isAdmin): ?><a class="pd-link" href="<?= e(url('/school/academic')) ?>">Create the first session <?= icon('arrow-right', 'h-3.5 w-3.5') ?></a><?php endif; ?></div>
    <?php elseif ($rows === []): ?>
        <div class="pd-empty"><span class="ico"><?= icon('search', 'h-6 w-6') ?></span><b>No students found</b><span>Try another session, class or spelling.</span></div>
    <?php else: ?>
        <ul class="pd-tl pd-records">
            <?php foreach ($rows as $r): ?>
                <li>
                    <span class="pd-av"><?= e(initials((string) $r['name'])) ?></span>
                    <a class="min-w-0 flex-1" href="<?= e(url('/school/records/' . (int) $r['student_id'])) ?>">
                        <b><?= e($r['name']) ?></b>
                        <small><?= e((string) $r['admission_no']) ?> · <?= e($r['class_name'] !== '' ? $r['class_name'] : 'No class') ?></small>
                    </a>
                    <div class="pd-act">
                        <?php if ($r['avg_percent'] !== null): ?><span class="pd-score"><?= (int) round((float) $r['avg_percent']) ?>%</span><?php endif; ?>
                        <span class="badge <?= $tone[$r['outcome']] ?? 'badge-gray' ?>"><?= e(Enrolment::OUTCOMES[$r['outcome']] ?? $r['outcome']) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($pages > 1): ?>
            <nav class="mt-4 flex items-center justify-between gap-3" aria-label="Pages">
                <a class="btn btn-secondary btn-sm <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>" href="<?= e($pageUrl(max(1, $page - 1))) ?>"><?= icon('chevron-left', 'h-4 w-4') ?>Previous</a>
                <span class="text-sm text-muted">Page <?= (int) $page ?> of <?= (int) $pages ?></span>
                <a class="btn btn-secondary btn-sm <?= $page >= $pages ? 'pointer-events-none opacity-40' : '' ?>" href="<?= e($pageUrl(min($pages, $page + 1))) ?>">Next<?= icon('chevron-right', 'h-4 w-4') ?></a>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
