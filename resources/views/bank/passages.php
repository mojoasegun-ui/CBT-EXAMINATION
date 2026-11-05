<?php
declare(strict_types=1);
/** @var list<array> $passages @var list<string> $subjectList @var string $area @var array $old */
$base = '/' . $area . '/bank';
?>
<a href="<?= e(url($base)) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Question bank</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">Passages</h1>
<p class="mt-1 text-sm text-muted">Comprehension texts, poems, case studies or data shared by several questions. Students see the passage beside each linked question.</p>
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <?php foreach ($passages as $p): ?>
            <details class="card group">
                <summary class="flex cursor-pointer list-none items-center gap-4 p-5">
                    <span class="icon-soft"><?= icon('book') ?></span>
                    <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-ink"><?= e($p['title']) ?></span><span class="block text-xs text-muted"><?= e($p['subject']) ?> · <?= (int) $p['question_count'] ?> linked question(s) · <?= number_format(str_word_count((string) $p['body'])) ?> words</span></span>
                    <span class="text-subtle transition group-open:rotate-180"><?= icon('chevron-down', 'h-4 w-4') ?></span>
                </summary>
                <form method="post" action="<?= e(url($base . '/passages')) ?>" class="space-y-4 border-t border-line p-5" data-submit-once>
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <div class="grid gap-4 sm:grid-cols-2"><?= field('subject', 'Subject', (string) $p['subject'], [], ['type' => 'select', 'options' => array_combine($subjectList, $subjectList)]) ?><?= field('title', 'Title', (string) $p['title'], []) ?></div>
                    <?= field('body', 'Text', (string) $p['body'], [], ['type' => 'textarea', 'attrs' => ' rows="10"']) ?>
                    <div class="flex justify-end"><button class="btn btn-primary btn-sm"><span data-label>Save passage</span></button></div>
                </form>
            </details>
        <?php endforeach; ?>
        <?php if ($passages === []): ?><div class="card empty"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('book', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">No passages yet</p><p class="text-sm text-muted">Add one, then link questions to it.</p></div><?php endif; ?>
    </div>
    <form method="post" action="<?= e(url($base . '/passages')) ?>" class="card self-start" data-submit-once>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="card-header"><div><p class="eyebrow">New</p><h2 class="card-title mt-1">Add a passage</h2></div></div>
        <div class="space-y-4 px-6 pb-6">
            <?= field('subject', 'Subject', (string) ($old['subject'] ?? 'English Language'), [], ['type' => 'select', 'options' => array_combine($subjectList, $subjectList)]) ?>
            <?= field('title', 'Title', (string) ($old['title'] ?? ''), [], ['placeholder' => 'Passage 1: The Harmattan']) ?>
            <?= field('body', 'Text', (string) ($old['body'] ?? ''), [], ['type' => 'textarea', 'attrs' => ' rows="8"']) ?>
            <button class="btn btn-primary w-full"><?= icon('plus') ?><span data-label>Add passage</span></button>
        </div>
    </form>
</div>
