<?php
declare(strict_types=1);
/** @var array $exam @var array $paper @var list<array> $rows @var int $total @var list<int> $selected @var list<string> $topics @var string $q @var string $topic */
$base = '/school/exams/' . $exam['id'] . '/papers/' . $paper['id'] . '/questions';
?>
<a href="<?= e(url('/school/exams/' . $exam['id'])) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?><?= e($exam['title']) ?></a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">Choose <?= e($paper['subject']) ?> questions</h1>
<p class="mt-1 text-sm text-muted">Tick <?= (int) $paper['question_count'] ?> published questions. Selected: <span class="font-semibold text-ink" data-pick-count><?= count($selected) ?></span></p>
<form method="get" action="<?= e(url($base)) ?>" class="mt-4 flex flex-wrap gap-2">
    <input class="input max-w-sm" type="search" name="q" value="<?= e($q) ?>" placeholder="Search" aria-label="Search">
    <select class="select max-w-xs" name="topic" aria-label="Topic" data-submit-on-change><option value="">All topics</option><?php foreach ($topics as $t): ?><option <?= $topic === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select>
    <button class="btn btn-secondary">Filter</button>
</form>
<form method="post" action="<?= e(url($base)) ?>" class="card mt-4 overflow-hidden" data-submit-once>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <?php foreach (array_diff($selected, array_column($rows, 'id')) as $keep): ?><input type="hidden" name="ids[]" value="<?= (int) $keep ?>"><?php endforeach; ?>
    <ul class="divide-y divide-line">
        <?php foreach ($rows as $row): ?>
            <li><label class="flex cursor-pointer gap-4 px-6 py-4 hover:bg-soft has-[:checked]:bg-brand/[.04]"><input type="checkbox" class="checkbox mt-1.5" name="ids[]" value="<?= (int) $row['id'] ?>" <?= in_array((int) $row['id'], $selected, true) ? 'checked' : '' ?> data-pick>
                <span><span class="text-xs text-muted"><?= e($row['topic'] ?? 'No topic') ?> · <?= e(ucfirst((string) $row['difficulty'])) ?></span><span class="mt-1 block text-[15px] leading-7 text-ink"><?= rich_text($row['body']) ?></span></span></label></li>
        <?php endforeach; ?>
        <?php if ($rows === []): ?><li class="px-6 py-12 text-center text-sm text-muted">No published <?= e($paper['subject']) ?> questions match. Publish some in the question bank first.</li><?php endif; ?>
    </ul>
    <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save selection</span></button></div>
</form>
