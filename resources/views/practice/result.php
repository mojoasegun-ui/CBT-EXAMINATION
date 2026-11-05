<?php
declare(strict_types=1);
/** @var array $session @var list<array> $items @var list<array> $topics @var bool $learning @var int $mistakes @var array $streak */
use ExamCore\Services\PracticeService;

$cfg = json_decode((string) $session['config'], true) ?: [];
$pc = (float) $session['percent'];
$tone = $pc >= 70 ? ['text-emerald-600', 'Great work'] : ($pc >= 50 ? ['text-amber-600', 'Getting there'] : ['text-red-600', 'Keep practising']);
$answered = count(array_filter($items, static fn ($i) => $i['answer'] !== null));
$wrong = count(array_filter($items, static fn ($i) => (int) $i['is_correct'] !== 1));
$fmt = static fn ($v): string => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
$again = match ($session['mode']) {
    'topic' => ['mode' => 'topic', 'subject' => $cfg['subject'] ?? '', 'topic' => $cfg['topic'] ?? '', 'difficulty' => $cfg['difficulty'] ?? '', 'count' => $session['question_count']],
    'quick' => ['mode' => 'quick', 'subject' => $cfg['subject'] ?? '', 'count' => $session['question_count']],
    'mistakes' => ['mode' => 'mistakes', 'count' => 15],
    default => null,
};
$post = static fn (array $fields, string $label, string $class, string $ic) => '<form method="post" action="' . e(url('/practice/start')) . '"><input type="hidden" name="_csrf_token" value="' . e($GLOBALS['__pc_csrf']) . '"><input type="hidden" name="back" value="/practice">'
    . implode('', array_map(static fn ($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string) $v) . '">', array_keys($fields), $fields)) . '<button class="' . $class . '" data-submit-once>' . icon($ic) . '<span data-label>' . e($label) . '</span></button></form>';
$GLOBALS['__pc_csrf'] = $csrfToken;
?>
<section class="card overflow-hidden">
    <div class="bg-mesh border-b border-line p-6 text-center sm:p-10">
        <p class="eyebrow"><?= e($session['title']) ?></p>
        <p class="mt-4 font-display text-[56px] font-semibold leading-none tabular-nums text-ink sm:text-[72px]"><?= $fmt($pc) ?><span class="text-[28px] text-muted">%</span></p>
        <p class="mt-2 font-semibold <?= $tone[0] ?>"><?= $tone[1] ?> · <?= (int) $session['correct_count'] ?> of <?= (int) $session['question_count'] ?> correct</p>
        <div class="mx-auto mt-5 h-2.5 max-w-sm overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full bg-signature" data-width="<?= (int) round($pc) ?>"></span></div>
        <div class="mt-5 flex flex-wrap justify-center gap-2 text-sm">
            <span class="badge badge-gray py-1.5"><?= $answered ?> answered</span>
            <?php if ($streak['current'] > 0): ?><span class="badge badge-gold py-1.5"><?= icon('activity', 'h-3.5 w-3.5') ?><?= $streak['current'] ?>-day streak</span><?php endif; ?>
        </div>
    </div>
    <div class="flex flex-wrap justify-center gap-2 p-5">
        <?php if ($again): ?><?= $post($again, 'Try again (new questions)', 'btn btn-primary', 'refresh') ?><?php else: ?><a class="btn btn-primary" href="<?= e(url('/practice/mock')) ?>"><?= icon('refresh') ?>Another mock</a><?php endif; ?>
        <?php if ($mistakes > 0 && $session['mode'] !== 'mistakes'): ?><?= $post(['mode' => 'mistakes', 'count' => 15], 'Practise my mistakes (' . $mistakes . ')', 'btn btn-secondary', 'alert-circle') ?><?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('/practice/progress')) ?>"><?= icon('chart') ?>My progress</a>
        <a class="btn btn-ghost" href="<?= e(url('/practice')) ?>">Back to practice</a>
    </div>
</section>

<?php if (count($topics) > 0): ?>
<section class="card mt-6">
    <div class="card-header"><div><p class="eyebrow">By topic</p><h2 class="card-title mt-1">Where you were strong and weak</h2></div></div>
    <ul class="space-y-3 px-6 pb-6">
        <?php foreach ($topics as $t): $p = (int) round($t['correct'] * 100 / max(1, $t['total'])); ?>
            <li><div class="flex items-baseline justify-between gap-3 text-sm"><span class="min-w-0 truncate"><span class="font-medium text-ink"><?= e($t['topic']) ?></span> <span class="text-xs text-subtle"><?= e($t['subject']) ?></span></span><span class="shrink-0 tabular-nums text-muted"><?= $t['correct'] ?>/<?= $t['total'] ?></span></div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-soft"><span class="block h-full rounded-full <?= $p >= 70 ? 'bg-emerald-500' : ($p >= 50 ? 'bg-amber-400' : 'bg-red-500') ?>" data-width="<?= $p ?>"></span></div></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<section class="mt-6">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="eyebrow">Review</p><h2 class="mt-1 font-display text-xl font-semibold text-ink">Every question with its explanation</h2></div>
        <?php if ($wrong > 0): ?><label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" class="checkbox" data-filter-wrong>Only the ones I missed (<?= $wrong ?>)</label><?php endif; ?></div>
    <div class="mt-4 space-y-4">
        <?php foreach ($items as $it): $ok = (int) $it['is_correct'] === 1; $order = str_split((string) $it['option_order']);
            $text = static fn (string $key): string => (string) $it['option_' . strtolower($key)];
            $shown = static fn (?string $key): string => $key === null ? 'No answer' : chr(65 + array_search($key, $order, true)) . '. ' . $text($key); ?>
            <article class="card p-5 sm:p-6" data-item data-ok="<?= $ok ? '1' : '0' ?>">
                <div class="flex items-start justify-between gap-3"><p class="text-sm font-semibold text-muted">Question <?= (int) $it['position'] ?> · <span class="font-normal"><?= e($it['topic']) ?></span></p>
                    <span class="badge <?= $ok ? 'badge-green' : 'badge-red' ?> shrink-0"><?= $ok ? 'Correct' : ($it['answer'] === null ? 'Skipped' : 'Wrong') ?></span></div>
                <div class="xr-rich mt-3 text-ink"><?= rich_text((string) $it['body']) ?></div>
                <div class="mt-4 grid gap-2 text-sm">
                    <div class="rounded-xl bg-emerald-50 px-3 py-2 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300"><b>Correct answer:</b> <?= rich_text($shown((string) $it['correct_option'])) ?></div>
                    <?php if (!$ok): ?><div class="rounded-xl bg-red-50 px-3 py-2 text-red-700 dark:bg-red-500/10 dark:text-red-300"><b>Your answer:</b> <?= $it['answer'] === null ? 'No answer' : rich_text($shown((string) $it['answer'])) ?></div><?php endif; ?>
                </div>
                <?php if (trim((string) $it['explanation']) !== ''): ?><div class="mt-3 rounded-xl bg-soft px-3 py-2.5 text-sm leading-6 text-muted"><b class="text-ink">Why:</b> <?= rich_text((string) $it['explanation']) ?></div><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
