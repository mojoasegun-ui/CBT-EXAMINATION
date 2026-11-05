<?php
declare(strict_types=1);
/**
 * @var array<string,mixed>|null $question @var list<string> $subjectList @var list<array> $passages @var string $area @var bool $canReview
 * @var array $errors @var array $old
 */
use ExamCore\Models\BankQuestion as B;

$isEdit = $question !== null;
$canEdit ??= true;
$v = static fn (string $k, string $d = ''): string => (string) ($old[$k] ?? ($question[$k] ?? $d));
$base = '/' . $area . '/bank';
$answer = strtolower($v('correct_option', 'A'));
$ro = $canEdit ? '' : ' disabled';
$badge = ['draft' => 'badge-gray', 'review' => 'badge-amber', 'published' => 'badge-green', 'archived' => 'badge-gray'];
?>
<a href="<?= e(url($base)) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Question bank</a>
<div class="mt-3 flex flex-wrap items-center gap-3">
    <h1 class="font-display text-[30px] font-semibold text-ink"><?= $isEdit ? 'Question #' . (int) $question['id'] : 'New question' ?></h1>
    <?php if ($isEdit): ?><span class="badge badge-dot <?= $badge[$question['status']] ?>"><?= e(B::STATUSES[$question['status']]) ?></span><?php endif; ?>
</div>
<?php if ($isEdit && $question['status'] === 'draft' && $question['review_note']): ?>
    <p class="mt-3 flex gap-2 rounded-2xl border border-amber-200 bg-amber-50/70 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200"><?= icon('alert', 'h-4 w-4 shrink-0') ?>Reviewer’s note: <?= e($question['review_note']) ?></p>
<?php endif; ?>

<form method="post" action="<?= e(url($isEdit ? $base . '/' . $question['id'] : $base)) ?>" enctype="multipart/form-data" class="mt-6 grid gap-6 lg:grid-cols-3" data-submit-once data-question-form <?= $isEdit ? '' : 'data-draft="question-new"' ?> novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <div class="space-y-6 lg:col-span-2">
        <section class="card card-body space-y-5">
            <div>
                <label class="label" for="f-body">Question</label>
                <textarea class="textarea min-h-32 text-[15px] leading-7 <?= isset($errors['body']) ? 'input-error' : '' ?>" id="f-body" name="body" required <?= $ro ?> data-preview-source="body" placeholder="Type the question. Use x^2 for powers, H_2O for subscripts, **bold** for emphasis."><?= e($v('body')) ?></textarea>
                <?= field_error($errors, 'body') ?>
            </div>
            <fieldset>
                <legend class="label">Options <span class="label-hint">· pick the correct one</span></legend>
                <div class="space-y-2.5">
                    <?php foreach (['a', 'b', 'c', 'd', 'e'] as $letter): ?>
                        <label class="flex items-start gap-3 rounded-2xl border border-line p-2.5 transition has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50/60 dark:has-[:checked]:bg-emerald-950/30">
                            <input type="radio" name="correct_option" value="<?= strtoupper($letter) ?>" class="mt-3 h-4 w-4 accent-emerald-600" <?= $answer === $letter ? 'checked' : '' ?> <?= $ro ?> aria-label="Option <?= strtoupper($letter) ?> is correct">
                            <span class="mt-1.5 grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-soft text-sm font-bold text-muted"><?= strtoupper($letter) ?></span>
                            <input class="input flex-1 border-0 bg-transparent shadow-none focus:ring-0" name="option_<?= $letter ?>" value="<?= e($v('option_' . $letter)) ?>" <?= $ro ?> placeholder="<?= $letter <= 'b' ? 'Required' : 'Optional' ?>" aria-label="Option <?= strtoupper($letter) ?>" data-preview-source="option_<?= $letter ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
                <?= field_error($errors, 'option_a') . field_error($errors, 'option_b') . field_error($errors, 'option_c') . field_error($errors, 'option_d') . field_error($errors, 'option_e') . field_error($errors, 'correct_option') ?>
            </fieldset>
            <?= field('explanation', 'Explanation', $v('explanation'), $errors, ['type' => 'textarea', 'hint' => 'shown to students after results are released', 'attrs' => $ro]) ?>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="label">Image or diagram <span class="label-hint">· optional</span></p>
                    <?php if ($isEdit && $question['image_path']): ?>
                        <img src="<?= e(url('/' . $question['image_path'])) ?>" alt="Question image" class="mb-2 max-h-40 rounded-2xl border border-line bg-white object-contain p-1">
                        <label class="mb-2 flex items-center gap-2 text-xs text-muted"><input type="checkbox" class="checkbox" name="remove_image" value="1" <?= $ro ?>>Remove image</label>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-muted file:mr-3 file:rounded-full file:border-0 file:bg-soft file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink" <?= $ro ?>>
                </div>
                <?= field('passage_id', 'Linked passage', $v('passage_id'), $errors, ['type' => 'select', 'empty' => 'None', 'options' => array_column($passages, 'title', 'id'), 'hint' => 'for comprehension', 'attrs' => $ro]) ?>
            </div>
        </section>

        <section class="card overflow-hidden">
            <div class="card-header"><div><p class="eyebrow">Live preview</p><h2 class="card-title mt-1">How students will see it</h2></div></div>
            <div class="px-6 pb-6">
                <p class="text-[17px] leading-8 text-ink" data-preview="body"><?= rich_text($v('body')) ?: '<span class="text-subtle">Your question appears here…</span>' ?></p>
                <ol class="mt-4 grid gap-2">
                    <?php foreach (['a', 'b', 'c', 'd', 'e'] as $letter): ?>
                        <li class="flex items-center gap-3 rounded-2xl border border-line px-4 py-3 text-[15px] <?= $v('option_' . $letter) === '' ? 'hidden' : '' ?>" data-preview-row="option_<?= $letter ?>">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-soft text-xs font-bold text-muted"><?= strtoupper($letter) ?></span><span data-preview="option_<?= $letter ?>"><?= rich_text($v('option_' . $letter)) ?></span></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>
    </div>

    <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
        <section class="card card-body space-y-4">
            <?= field('subject', 'Subject', $v('subject', (string) ($_GET['subject'] ?? '')), $errors, ['type' => 'select', 'empty' => 'Choose', 'options' => array_combine($subjectList, $subjectList), 'required' => true, 'attrs' => $ro]) ?>
            <?= field('topic', 'Topic', $v('topic', (string) ($_GET['topic'] ?? '')), $errors, ['placeholder' => 'e.g. Quadratic equations', 'attrs' => $ro]) ?>
            <div class="grid grid-cols-2 gap-3">
                <?= field('difficulty', 'Difficulty', $v('difficulty', 'medium'), $errors, ['type' => 'select', 'options' => B::DIFFICULTIES, 'attrs' => $ro]) ?>
                <?= field('marks', 'Marks', $v('marks', '1'), $errors, ['type' => 'number', 'attrs' => ' step="0.5" min="0.5" max="100"' . $ro]) ?>
                <?= field('level', 'Level', $v('level', 'any'), $errors, ['type' => 'select', 'options' => B::LEVELS, 'attrs' => $ro]) ?>
                <?= field('exam_body', 'Exam style', $v('exam_body'), $errors, ['type' => 'select', 'options' => B::BODIES, 'attrs' => $ro]) ?>
                <?= field('year', 'Year', $v('year'), $errors, ['type' => 'number', 'hint' => 'past papers', 'attrs' => ' min="1970"' . $ro]) ?>
                <?= field('source', 'Source', $v('source', 'teacher'), $errors, ['type' => 'select', 'options' => B::SOURCES, 'attrs' => $ro]) ?>
            </div>
            <?php if (isset($errors['body']) && str_contains((string) $errors['body'], 'already exists')): ?>
                <label class="flex items-center gap-2 text-sm text-ink"><input type="checkbox" class="checkbox" name="allow_duplicate" value="1">Save anyway (intentional duplicate)</label>
            <?php endif; ?>
        </section>
        <?php if ($canEdit): ?>
            <?php if ($isEdit): ?>
                <button class="btn btn-primary btn-lg w-full"><?= icon('check') ?><span data-label>Save changes</span></button>
            <?php else: ?>
                <button name="intent" value="submit" class="btn btn-primary btn-lg w-full"><?= icon('check') ?><span data-label><?= $canReview ? 'Save and publish' : 'Save and send for review' ?></span></button>
                <button name="intent" value="draft" class="btn btn-secondary w-full">Save as draft</button>
                <label class="flex items-center justify-center gap-2 text-xs text-muted"><input type="checkbox" class="checkbox" name="after" value="another" checked>Start another question after saving</label>
            <?php endif; ?>
        <?php else: ?>
            <p class="rounded-2xl bg-soft p-4 text-sm text-muted">Published questions can only be changed by a reviewer, so results stay fair.</p>
        <?php endif; ?>
    </aside>
</form>

<?php if ($isEdit): ?>
    <section class="card mt-6 card-body">
        <p class="eyebrow">Workflow</p>
        <div class="mt-4 flex flex-wrap items-start gap-2">
            <?php
            $actions = [];
            if ($question['status'] === 'draft' && $canEdit) { $actions[] = ['submit', $canReview ? 'Send to review queue' : 'Send for review', 'btn-secondary']; }
            if ($canReview && in_array($question['status'], ['draft', 'review'], true)) { $actions[] = ['approve', 'Approve & publish', 'btn-primary']; }
            if ($canReview && $question['status'] !== 'archived') { $actions[] = ['archive', 'Archive', 'btn-ghost']; }
            if ($canReview && $question['status'] === 'archived') { $actions[] = ['restore', 'Restore as draft', 'btn-secondary']; }
            foreach ($actions as [$action, $label, $class]): ?>
                <form method="post" action="<?= e(url($base . '/' . $question['id'] . '/status')) ?>"><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="<?= $action ?>"><button class="btn <?= $class ?>"><?= e($label) ?></button></form>
            <?php endforeach; ?>
            <?php if ($canReview && in_array($question['status'], ['review', 'published'], true)): ?>
                <form method="post" action="<?= e(url($base . '/' . $question['id'] . '/status')) ?>" class="flex flex-1 gap-2 sm:min-w-[22rem]"><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="reject">
                    <input class="input flex-1 py-2" name="note" maxlength="255" placeholder="What should the author fix?" required aria-label="Note to the author"><button class="btn btn-danger-soft">Send back</button></form>
            <?php endif; ?>
            <?php if ($question['status'] !== 'published' && $canEdit): ?>
                <form method="post" action="<?= e(url($base . '/' . $question['id'] . '/delete')) ?>" class="ml-auto" data-confirm="This question will be removed permanently." data-confirm-title="Delete question #<?= (int) $question['id'] ?>?" data-confirm-ok="Delete"><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-ghost text-red-600"><?= icon('trash') ?>Delete</button></form>
            <?php endif; ?>
        </div>
        <p class="mt-4 text-xs text-muted">Written by <?= e($question['author_name'] ?? '—') ?> · updated <?= e(time_ago((string) $question['updated_at'])) ?><?= $question['reviewer_name'] ? ' · reviewed by ' . e($question['reviewer_name']) : '' ?></p>
    </section>
<?php endif; ?>
