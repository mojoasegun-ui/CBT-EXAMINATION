<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $bands */
$rows = $bands;
while (count($rows) < 10) {
    $rows[] = ['grade' => '', 'min_score' => '', 'max_score' => '', 'remark' => '', 'is_pass' => 1];
}
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">Academics</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Grading scale</h1>
        <p class="mt-1 text-sm text-muted">How percentage scores become grades on result slips. Defaults follow WAEC/NECO (A1–F9).</p></div>
    <form method="post" action="<?= e(url('/school/grading/reset')) ?>" data-confirm="Your custom grades will be replaced by the standard A1–F9 scale." data-confirm-title="Restore the standard scale?" data-confirm-ok="Restore">
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="btn btn-secondary"><?= icon('refresh') ?>Restore standard</button></form>
</div>
<form method="post" action="<?= e(url('/school/grading')) ?>" class="card mt-6 overflow-hidden" data-submit-once>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th scope="col">Grade</th><th scope="col">From %</th><th scope="col">To %</th><th scope="col">Remark</th><th scope="col" class="text-center">Pass</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $band): ?>
                <tr>
                    <td><input class="input w-20 py-2 text-center font-semibold uppercase" name="grade[<?= $i ?>]" value="<?= e($band['grade']) ?>" maxlength="4" aria-label="Grade <?= $i + 1 ?>"></td>
                    <td><input class="input w-24 py-2 tabular-nums" name="min[<?= $i ?>]" value="<?= e($band['min_score'] === '' ? '' : rtrim(rtrim((string) $band['min_score'], '0'), '.')) ?>" inputmode="decimal" aria-label="Minimum score"></td>
                    <td><input class="input w-24 py-2 tabular-nums" name="max[<?= $i ?>]" value="<?= e($band['max_score'] === '' ? '' : rtrim(rtrim((string) $band['max_score'], '0'), '.')) ?>" inputmode="decimal" aria-label="Maximum score"></td>
                    <td><input class="input min-w-[10rem] py-2" name="remark[<?= $i ?>]" value="<?= e($band['remark']) ?>" maxlength="40" aria-label="Remark"></td>
                    <td class="text-center"><input type="checkbox" class="checkbox" name="pass[<?= $i ?>]" value="1" <?= $band['is_pass'] ? 'checked' : '' ?> aria-label="Counts as a pass"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="flex flex-col gap-3 border-t border-line px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-muted">Leave a row’s grade empty to remove it. Bands must not overlap.</p>
        <button class="btn btn-primary"><?= icon('check') ?><span data-label>Save grading scale</span></button>
    </div>
</form>
