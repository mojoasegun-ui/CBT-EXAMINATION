<?php
declare(strict_types=1);
/** Theory score entry for a class list. @var array $exam @var list<array> $roster @var int $max */
$fmt = static fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
?>
<a href="<?= e(url('/school/exams/' . $exam['id'] . '/paper')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Paper exam</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">Theory scores</h1>
<p class="mt-1 text-sm text-muted"><?= e($exam['title']) ?> · out of <b><?= $max ?></b>. Scan the objective sheet first; then type the theory mark. Totals update straight away.</p>
<form method="post" class="card mt-6 overflow-hidden" data-submit-once>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <ul class="divide-y divide-line">
        <?php foreach ($roster as $i => $r): $ready = $r['sitting_id'] !== null && $r['mode'] === 'paper'; ?>
            <li class="flex items-center gap-4 px-5 py-3">
                <span class="w-6 text-right text-xs tabular-nums text-subtle"><?= $i + 1 ?></span>
                <div class="min-w-0 flex-1"><p class="truncate font-medium text-ink"><?= e($r['name']) ?></p><p class="text-xs text-muted"><?= e($r['admission_no']) ?> · <?= e($r['class_name']) ?><?= $ready ? ' · objectives ' . (int) $r['score'] . '/' . (int) $r['question_count'] : '' ?></p></div>
                <?php if ($ready): ?>
                    <input class="input w-24 text-center tabular-nums" type="number" inputmode="decimal" step="0.5" min="0" max="<?= $max ?>" name="theory[<?= (int) $r['student_id'] ?>]" value="<?= e($fmt($r['theory_score'])) ?>" aria-label="Theory score for <?= e($r['name']) ?>">
                <?php else: ?>
                    <span class="w-24 text-center text-xs text-subtle">not scanned</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="sticky bottom-0 flex justify-end border-t border-line bg-card/95 px-5 py-4 backdrop-blur"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save theory scores</span></button></div>
</form>
