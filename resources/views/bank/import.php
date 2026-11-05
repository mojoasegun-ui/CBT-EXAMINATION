<?php
declare(strict_types=1);
/** @var list<string> $subjectList @var array|null $result @var string $area @var bool $canReview */
use ExamCore\Models\BankQuestion as B;

$base = '/' . $area . '/bank';
?>
<a href="<?= e(url($base)) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Question bank</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">Import questions</h1>
<p class="mt-1 max-w-2xl text-sm text-muted">One question per row. Every row is checked; duplicates already in the bank are skipped automatically. Up to <?= number_format(\ExamCore\Services\QuestionBankService::MAX_IMPORT) ?> questions per file.</p>
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <form method="post" action="<?= e(url($base . '/import')) ?>" enctype="multipart/form-data" class="card lg:col-span-2" data-submit-once>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="space-y-5 p-6">
            <label class="group flex cursor-pointer flex-col items-center justify-center rounded-[28px] border-2 border-dashed border-line bg-soft px-6 py-12 text-center transition hover:border-brand/50" data-dropzone>
                <span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('upload', 'h-6 w-6') ?></span>
                <span class="mt-4 font-display text-lg font-semibold text-ink" data-dropzone-label>Drop your Excel or CSV file here</span>
                <span class="mt-1 text-sm text-muted">or tap to choose · .xlsx or .csv</span>
                <input type="file" name="file" accept=".xlsx,.csv" class="sr-only" required data-dropzone-input>
            </label>
            <div class="grid gap-4 sm:grid-cols-3">
                <?= field('subject', 'Default subject', '', [], ['type' => 'select', 'empty' => 'Use the Subject column', 'options' => array_combine($subjectList, $subjectList)]) ?>
                <?= field('status', 'Import as', 'review', [], ['type' => 'select', 'options' => ['draft' => 'Drafts', 'review' => 'Sent for review'] + ($canReview ? ['published' => 'Published'] : [])]) ?>
                <?= field('source', 'Source', 'teacher', [], ['type' => 'select', 'options' => B::SOURCES]) ?>
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?= e(url($base . '/import/template')) ?>" class="btn btn-ghost"><?= icon('download') ?>Download Excel template</a>
                <button class="btn btn-primary btn-lg"><?= icon('upload') ?><span data-label>Import questions</span></button>
            </div>
        </div>
    </form>
    <aside class="card card-body space-y-3 text-sm text-muted">
        <p class="eyebrow">Columns</p>
        <p><span class="font-semibold text-ink">Question, Option A, Option B, Answer</span> are required. Option C–E, Explanation, Subject, Topic, Difficulty (easy/medium/hard), Level (jss/ss/primary), Exam Body, Year and Marks are optional.</p>
        <p>Answer is the letter (A–E). Write powers as <span class="font-mono text-ink">x^2</span> and subscripts as <span class="font-mono text-ink">H_2O</span>.</p>
        <p>Only “Past question (licensed)” should be used for real WAEC/NECO/JAMB papers you have the right to use.</p>
    </aside>
</div>
<?php if (is_array($result) && $result['errors'] !== []): ?>
    <section class="card mt-6 overflow-hidden">
        <div class="card-header"><div><p class="eyebrow text-red-600 dark:text-red-400">Needs fixing</p><h2 class="card-title mt-1"><?= count($result['errors']) ?> row(s) were not imported</h2></div></div>
        <ul class="max-h-96 divide-y divide-line overflow-y-auto">
            <?php foreach (array_slice($result['errors'], 0, 300) as $error): ?>
                <li class="flex gap-4 px-6 py-3"><span class="w-14 shrink-0 text-xs font-semibold tabular-nums text-subtle">Row <?= (int) $error['row'] ?></span>
                    <ul class="text-xs text-red-600 dark:text-red-400"><?php foreach ($error['messages'] as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
