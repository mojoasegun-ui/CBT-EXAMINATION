<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $classes @var array<int,int|string> $suggestions */
?>
<a href="<?= e(url('/school/classes')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Classes</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink">End-of-session promotion</h1>
<p class="mt-1 max-w-2xl text-sm text-muted">Move every active student up one class in a single step. We pre-filled the usual next class (JSS 1 A → JSS 2 A, SS 3 → graduated). Students who are repeating can be moved back individually afterwards.</p>
<div class="mt-4 flex gap-3 rounded-3xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
    <?= icon('alert', 'h-5 w-5 shrink-0') ?><p>Do this once, after the third term’s results are released. A backup is wise first (your platform owner can take one in seconds).</p>
</div>
<form method="post" action="<?= e(url('/school/classes/promote')) ?>" class="card mt-6 overflow-hidden" data-submit-once data-confirm="Every active student in the classes you set will move now. Graduated students can no longer sign in." data-confirm-title="Promote students now?" data-confirm-ok="Promote">
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th scope="col">From</th><th scope="col" class="text-right">Students</th><th scope="col">Move to</th></tr></thead>
            <tbody>
            <?php foreach ($classes as $class): $suggested = $suggestions[(int) $class['id']] ?? ''; ?>
                <tr>
                    <td class="font-semibold text-ink"><?= e($class['name']) ?></td>
                    <td class="text-right tabular-nums text-muted"><?= (int) $class['student_count'] ?></td>
                    <td><select name="to[<?= (int) $class['id'] ?>]" class="select py-2 text-sm sm:w-64" aria-label="Destination for <?= e($class['name']) ?>">
                        <option value="">Stay (no change)</option>
                        <option value="graduate" <?= $suggested === 'graduate' ? 'selected' : '' ?>>Graduate (leave school)</option>
                        <?php foreach ($classes as $target): if ((int) $target['id'] === (int) $class['id']) { continue; } ?>
                            <option value="<?= (int) $target['id'] ?>" <?= $suggested === (int) $target['id'] ? 'selected' : '' ?>><?= e($target['name']) ?></option>
                        <?php endforeach; ?>
                    </select></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary"><?= icon('arrow-right') ?><span data-label>Promote students</span></button></div>
</form>
