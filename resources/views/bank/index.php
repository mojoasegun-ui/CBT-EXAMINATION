<?php
declare(strict_types=1);
/** @var list<array> $rows @var int $total @var array $filters @var list<array> $health @var list<string> $subjectList @var int $inReview @var bool $canReview @var string $area */
$badge = ['draft' => 'badge-gray', 'review' => 'badge-amber', 'published' => 'badge-green', 'archived' => 'badge-gray'];
$base = '/' . $area . '/bank';
$query = array_filter(['q' => $filters['q'], 'subject' => $filters['subject'], 'status' => $filters['status'], 'difficulty' => $filters['difficulty'], 'mine' => $filters['author'] ? '1' : null]);
?>
<div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div><p class="eyebrow"><?= $area === 'console' ? 'Platform' : 'Exams' ?></p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink"><?= $area === 'console' ? 'Platform question bank' : 'Question bank' ?></h1>
        <p class="mt-1 text-sm text-muted"><?= $area === 'console' ? 'Curated questions schools on Pro and Enterprise plans can copy into their banks.' : 'Write, import and review questions. Only published questions can be used in exams.' ?></p></div>
    <div class="flex flex-wrap gap-2">
        <?php if ($area === 'school'): ?><a href="<?= e(url('/school/bank/platform')) ?>" class="btn btn-secondary"><?= icon('library') ?>Platform bank</a><?php endif; ?>
        <a href="<?= e(url($base . '/passages')) ?>" class="btn btn-secondary"><?= icon('book') ?>Passages</a>
        <a href="<?= e(url($base . '/import')) ?>" class="btn btn-secondary"><?= icon('upload') ?>Import</a>
        <a href="<?= e(url($base . '/create')) ?>" class="btn btn-primary"><?= icon('plus') ?>New question</a>
    </div>
</div>

<?php if ($health !== []): ?>
    <section class="mt-6 flex gap-3 overflow-x-auto pb-2" aria-label="Bank health by subject">
        <?php foreach ($health as $h): $low = $h['published'] < 40; ?>
            <a href="<?= e(url($base . '?subject=' . rawurlencode($h['subject']))) ?>" class="card card-hover min-w-[13rem] shrink-0 p-4 <?= $filters['subject'] === $h['subject'] ? 'ring-2 ring-brand/40' : '' ?>">
                <p class="truncate text-sm font-semibold text-ink"><?= e($h['subject']) ?></p>
                <p class="mt-2 font-display text-2xl font-semibold tabular-nums text-ink"><?= $h['published'] ?><span class="text-sm font-normal text-muted"> published</span></p>
                <p class="mt-1 text-xs <?= $low ? 'text-amber-600' : 'text-muted' ?>"><?= $low ? 'Add more for varied exams' : 'Healthy' ?> · <?= $h['review'] ?> in review · <?= $h['draft'] ?> drafts</p>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if ($canReview && $inReview > 0 && $filters['status'] !== 'review'): ?>
    <a href="<?= e(url($base . '?status=review')) ?>" class="mt-4 flex items-center gap-3 rounded-3xl border border-amber-200 bg-amber-50/70 p-4 text-sm font-semibold text-amber-900 transition hover:bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
        <?= icon('list-checks', 'h-5 w-5') ?><?= $inReview ?> question<?= $inReview === 1 ? '' : 's' ?> waiting for your review<span class="ml-auto"><?= icon('arrow-right', 'h-4 w-4') ?></span></a>
<?php endif; ?>

<div class="card mt-6 overflow-hidden">
    <form method="get" action="<?= e(url($base)) ?>" class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_.9fr_.9fr_auto]" data-auto-filter>
        <label class="relative sm:col-span-2 lg:col-span-1"><span class="sr-only">Search questions</span>
            <span class="pointer-events-none absolute inset-y-0 left-3.5 grid place-items-center text-subtle"><?= icon('search', 'h-4 w-4') ?></span>
            <input class="input pl-10" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search question text"></label>
        <select class="select" name="subject" aria-label="Subject"><option value="">All subjects</option><?php foreach ($subjectList as $s): ?><option <?= $filters['subject'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
        <select class="select" name="status" aria-label="Status"><option value="">Any status</option><?php foreach (\ExamCore\Models\BankQuestion::STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
        <select class="select" name="difficulty" aria-label="Difficulty"><option value="">Any difficulty</option><?php foreach (\ExamCore\Models\BankQuestion::DIFFICULTIES as $k => $l): ?><option value="<?= $k ?>" <?= $filters['difficulty'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
        <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" class="checkbox" name="mine" value="1" <?= $filters['author'] ? 'checked' : '' ?> data-auto-submit>Mine</label>
    </form>
    <?php if ($rows === []): ?>
        <div class="empty"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('library', 'h-6 w-6') ?></span>
            <div><p class="font-display text-xl font-semibold text-ink"><?= $query ? 'No questions match' : 'Your bank is empty' ?></p><p class="mt-1 text-sm text-muted">Write questions one by one or import a whole set from Excel.</p></div>
            <div class="flex gap-2"><a class="btn btn-primary" href="<?= e(url($base . '/create')) ?>"><?= icon('plus') ?>New question</a><a class="btn btn-secondary" href="<?= e(url($base . '/import')) ?>"><?= icon('upload') ?>Import</a></div></div>
    <?php else: ?>
        <ul class="divide-y divide-line">
            <?php foreach ($rows as $q): ?>
                <li><a href="<?= e(url($base . '/' . $q['id'])) ?>" class="block px-6 py-4 transition hover:bg-soft">
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="badge badge-dot <?= $badge[$q['status']] ?>"><?= e(\ExamCore\Models\BankQuestion::STATUSES[$q['status']]) ?></span>
                        <span class="font-semibold text-ink"><?= e($q['subject']) ?></span><?php if ($q['topic']): ?><span class="text-muted">· <?= e($q['topic']) ?></span><?php endif; ?>
                        <span class="text-subtle">· <?= e(ucfirst((string) $q['difficulty'])) ?><?= $q['exam_body'] ? ' · ' . e($q['exam_body']) . ($q['year'] ? ' ' . (int) $q['year'] : '') : '' ?></span>
                        <span class="ml-auto text-subtle">#<?= (int) $q['id'] ?> · <?= e($q['author_name'] ?? '') ?> · <?= e(time_ago((string) $q['updated_at'])) ?></span>
                    </div>
                    <p class="mt-2 line-clamp-2 text-[15px] leading-7 text-ink"><?= rich_text($q['body']) ?></p>
                    <?php if ($q['status'] === 'draft' && $q['review_note']): ?><p class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400">Reviewer: <?= e($q['review_note']) ?></p><?php endif; ?>
                    <?php if ((int) $q['times_answered'] >= 20): $pct = (int) round($q['times_correct'] / $q['times_answered'] * 100); ?>
                        <p class="mt-1 text-xs <?= $pct < 15 || $pct > 95 ? 'text-amber-700 dark:text-amber-400' : 'text-muted' ?>"><?= $pct ?>% of <?= (int) $q['times_answered'] ?> students answer correctly<?= $pct < 15 ? ' · check the answer key' : ($pct > 95 ? ' · may be too easy' : '') ?></p>
                    <?php endif; ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
        <?php $query = $query; require __DIR__ . '/../partials/portal/pagination.php'; ?>
    <?php endif; ?>
</div>
