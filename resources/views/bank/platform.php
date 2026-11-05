<?php
declare(strict_types=1);
/** @var list<array> $rows @var int $total @var array $filters @var list<string> $subjectList */
$query = array_filter(['q' => $filters['q'], 'subject' => $filters['subject']]);
?>
<a href="<?= e(url('/school/bank')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Your question bank</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">Platform question bank</h1>
<p class="mt-1 text-sm text-muted">Curated, reviewed questions. Copy any into your bank; they arrive as drafts so your reviewers stay in control.</p>
<form method="post" action="<?= e(url('/school/bank/platform/copy')) ?>" class="card mt-6 overflow-hidden" data-submit-once>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center">
        <div class="flex flex-1 gap-3" data-platform-filter>
            <input class="input" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search" form="platform-filter" aria-label="Search">
            <select class="select sm:w-56" name="subject" form="platform-filter" aria-label="Subject" data-submit-on-change><option value="">All subjects</option><?php foreach ($subjectList as $s): ?><option <?= $filters['subject'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
        </div>
        <button class="btn btn-primary" <?= $rows === [] ? 'disabled' : '' ?>><?= icon('copy') ?><span data-label>Copy selected</span></button>
    </div>
    <?php if ($rows === []): ?><p class="px-6 py-14 text-center text-sm text-muted">No platform questions match.</p><?php else: ?>
        <ul class="divide-y divide-line">
            <?php foreach ($rows as $q): ?>
                <li><label class="flex cursor-pointer gap-4 px-6 py-4 hover:bg-soft"><input type="checkbox" class="checkbox mt-1.5" name="ids[]" value="<?= (int) $q['id'] ?>">
                    <span class="min-w-0"><span class="text-xs font-semibold text-ink"><?= e($q['subject']) ?></span><span class="text-xs text-muted"><?= $q['topic'] ? ' · ' . e($q['topic']) : '' ?> · <?= e(ucfirst((string) $q['difficulty'])) ?></span>
                        <span class="mt-1 block text-[15px] leading-7 text-ink"><?= rich_text($q['body']) ?></span></span></label></li>
            <?php endforeach; ?>
        </ul>
        <?php require __DIR__ . '/../partials/portal/pagination.php'; ?>
    <?php endif; ?>
</form>
<form id="platform-filter" method="get" action="<?= e(url('/school/bank/platform')) ?>" hidden></form>
