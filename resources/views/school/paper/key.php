<?php
declare(strict_types=1);
/** Marking scheme for every version. @var array $exam @var array<string, list<array>> $keys @var array $school */
?>
<article class="a4">
    <header class="border-b-2 border-slate-800 pb-3 text-center">
        <p class="font-display text-lg font-bold uppercase"><?= e($school['name']) ?></p>
        <p class="text-sm font-semibold">Marking scheme · <?= e($exam['title']) ?></p>
        <p class="text-xs text-slate-500">Confidential — for teachers only. Scanned sheets are marked automatically; use this to mark by hand if needed.</p>
    </header>
    <div class="mt-4 grid gap-6 <?= count($keys) > 1 ? 'grid-cols-2' : '' ?>">
        <?php foreach ($keys as $v => $rows): ?>
            <section>
                <p class="mb-2 font-display text-base font-bold">Paper <?= e($v) ?></p>
                <div class="grid grid-cols-5 gap-x-2 gap-y-1 text-[12px]">
                    <?php foreach ($rows as $r): ?><span class="tabular-nums"><b><?= (int) $r['n'] ?>.</b> <?= e((string) $r['answer']) ?></span><?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</article>
