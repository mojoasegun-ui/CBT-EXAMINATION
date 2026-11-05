<?php
declare(strict_types=1);
/** @var string $subject @var array $info @var array<string,array> $mine @var list<int> $counts */
use ExamCore\Services\PracticeService;

$back = '/practice/subject/' . PracticeService::slug($subject);
?>
<a href="<?= e(url('/practice')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>All subjects</a>
<div class="mt-3"><p class="eyebrow">Practise</p><h1 class="mt-1 font-display text-[28px] font-semibold text-ink sm:text-[34px]"><?= e($subject) ?></h1>
    <p class="mt-1 text-sm text-muted"><?= count($info['topics']) ?> syllabus topics · <?= number_format($info['total']) ?> questions</p></div>

<form method="post" action="<?= e(url('/practice/start')) ?>" class="mt-6" data-submit-once>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="mode" value="topic"><input type="hidden" name="subject" value="<?= e($subject) ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
    <div class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-end">
        <label class="sm:w-44"><span class="label">Questions</span><select class="select" name="count"><?php foreach ($counts as $c): ?><option value="<?= $c ?>" <?= $c === 10 ? 'selected' : '' ?>><?= $c ?> questions</option><?php endforeach; ?></select></label>
        <label class="sm:w-44"><span class="label">Difficulty</span><select class="select" name="difficulty"><option value="">Mixed</option><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option></select></label>
        <button class="btn btn-primary sm:ml-auto" name="topic" value=""><?= icon('sparkles') ?><span data-label>Mixed practice</span></button>
    </div>

    <ul class="card mt-5 divide-y divide-line overflow-hidden">
        <?php foreach ($info['topics'] as $topic => $t): $m = $mine[$topic] ?? null; $a = $m && $m['attempts'] > 0 ? (int) round($m['correct'] * 100 / $m['attempts']) : null; ?>
            <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink"><?= e((string) $topic) ?></p>
                    <p class="mt-0.5 text-xs text-muted"><?= $t['n'] ?> questions · <?= $t['easy'] ?> easy · <?= $t['medium'] ?> medium · <?= $t['hard'] ?> hard<?= $a !== null ? ' · you: ' . $a . '% of ' . $m['attempts'] : '' ?></p>
                    <?php if ($a !== null): ?><div class="mt-2 h-1.5 max-w-xs overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full <?= $a >= 70 ? 'bg-emerald-500' : ($a >= 50 ? 'bg-amber-400' : 'bg-red-500') ?>" data-width="<?= $a ?>"></span></div><?php endif; ?>
                </div>
                <button class="btn btn-secondary btn-sm shrink-0" name="topic" value="<?= e((string) $topic) ?>"><?= icon('arrow-right') ?>Practise</button>
            </li>
        <?php endforeach; ?>
    </ul>
</form>
