<?php
declare(strict_types=1);
/** @var list<array> $terms @var array|null $term @var list<array> $classes @var int $classId @var array|null $sheet @var array $settings */
use ExamCore\Services\ResultService;

$fmt = static fn ($v): string => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
$q = static fn (array $extra = []) => http_build_query(['term' => $term['id'] ?? null, 'class' => $classId] + $extra);
?>
<div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div><p class="eyebrow">Results</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Term results &amp; report cards</h1>
        <p class="mt-1 text-sm text-muted">Every exam of the term is combined per subject: tests and CA make the CA score, terminal exams make the exam score.</p></div>
    <form method="get" class="flex flex-wrap gap-2">
        <select class="select w-auto" name="term" aria-label="Term" data-submit-on-change><?php foreach ($terms as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) ($term['id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name'] . ' · ' . $t['session_name']) ?></option><?php endforeach; ?></select>
        <select class="select w-auto" name="class" aria-label="Class" data-submit-on-change><?php foreach ($classes as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $classId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
    </form>
</div>

<?php if ($sheet === null || $term === null): ?>
    <div class="card empty mt-6"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('chart', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">Set up a term and classes first</p><p class="text-sm text-muted">Results appear once students have written exams this term.</p></div>
<?php else: ?>
<div class="mt-6 flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= e(url('/school/results/report-cards?' . $q())) ?>"><?= icon('file-text') ?>Print all report cards</a>
    <a class="btn btn-secondary" href="<?= e(url('/school/results/export?' . $q())) ?>"><?= icon('download') ?>Broadsheet (Excel)</a>
</div>

<section class="card mt-4 overflow-hidden">
    <div class="card-header"><div><p class="eyebrow">Broadsheet</p><h2 class="card-title mt-1"><?= count($sheet['students']) ?> students · <?= count($sheet['subjects']) ?> subjects</h2></div><span class="text-xs text-muted">CA <?= (int) $settings['ca_weight'] ?>% · Exam <?= 100 - (int) $settings['ca_weight'] ?>%</span></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-y border-line bg-soft/60 text-left text-xs font-semibold uppercase tracking-wider text-subtle">
                <tr><th class="sticky left-0 bg-soft px-5 py-3">Pos.</th><th class="px-3 py-3">Student</th><?php foreach ($sheet['subjects'] as $sub): ?><th class="px-3 py-3 text-right" title="<?= e($sub) ?>"><?= e(mb_strimwidth($sub, 0, 14, '…')) ?></th><?php endforeach; ?><th class="px-3 py-3 text-right">Average</th><th class="px-3 py-3">Grade</th><th class="px-5 py-3 text-right">Report</th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($sheet['students'] as $s): ?>
                    <tr>
                        <td class="sticky left-0 bg-card px-5 py-3 font-semibold tabular-nums text-ink"><?= e(ResultService::ordinal($s['position'])) ?></td>
                        <td class="px-3 py-3"><p class="whitespace-nowrap font-medium text-ink"><?= e($s['name']) ?></p><p class="text-xs text-muted"><?= e($s['admission_no']) ?></p></td>
                        <?php foreach ($sheet['subjects'] as $sub): $m = $s['subjects'][$sub]; ?>
                            <td class="px-3 py-3 text-right tabular-nums <?= $m['total'] !== null && !$m['pass'] ? 'text-red-600' : 'text-ink' ?>"><?= $fmt($m['total']) ?><?php if ($m['total'] !== null): ?><span class="ml-1 text-[11px] text-muted"><?= e($m['grade']) ?></span><?php endif; ?></td>
                        <?php endforeach; ?>
                        <td class="px-3 py-3 text-right font-semibold tabular-nums text-ink"><?= $fmt($s['average']) ?><?= $s['average'] !== null ? '%' : '' ?></td>
                        <td class="px-3 py-3"><?= $s['average'] !== null ? '<span class="badge ' . ($s['overall']['pass'] ? 'badge-green' : 'badge-red') . '">' . e($s['overall']['grade']) . '</span>' : '<span class="text-subtle">—</span>' ?></td>
                        <td class="px-5 py-3 text-right"><a class="btn btn-ghost btn-sm" href="<?= e(url('/school/results/students/' . $s['id'] . '?term=' . $term['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($sheet['students'] === []): ?><tr><td colspan="99" class="px-5 py-12 text-center text-muted">No active students in this class.</td></tr><?php endif; ?>
            </tbody>
            <?php if ($sheet['subjects']): ?>
            <tfoot class="border-t border-line bg-soft/40 text-xs text-muted"><tr><td class="sticky left-0 bg-soft px-5 py-3" colspan="2">Class average</td><?php foreach ($sheet['subjects'] as $sub): ?><td class="px-3 py-3 text-right tabular-nums"><?= $fmt($sheet['classAvg'][$sub] ?? null) ?></td><?php endforeach; ?><td colspan="3"></td></tr></tfoot>
            <?php endif; ?>
        </table>
    </div>
</section>

<details class="card mt-6">
    <summary class="flex cursor-pointer items-center justify-between px-6 py-5"><span><span class="eyebrow block">Settings</span><span class="card-title mt-1 block">Report card settings</span></span><?= icon('chevron-down', 'h-4 w-4 text-muted') ?></summary>
    <form method="post" action="<?= e(url('/school/results/settings')) ?>" class="grid gap-4 border-t border-line p-6 sm:grid-cols-2 lg:grid-cols-4" data-submit-once>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="term" value="<?= (int) $term['id'] ?>"><input type="hidden" name="class" value="<?= $classId ?>">
        <?= field('ca_weight', 'CA weight %', (string) $settings['ca_weight'], [], ['type' => 'number', 'hint' => 'exam = 100 − CA', 'attrs' => ' min="0" max="100"']) ?>
        <?= field('principal_name', "Principal's name", (string) $settings['principal_name'], [], ['placeholder' => 'Mrs. A. Bello']) ?>
        <?= field('next_term_begins', 'Next term begins', (string) $settings['next_term_begins'], [], ['type' => 'date']) ?>
        <label class="flex items-center gap-2.5 self-end pb-3 text-sm"><input type="checkbox" class="checkbox" name="show_position" value="1" <?= !empty($settings['show_position']) ? 'checked' : '' ?>>Show positions on report cards</label>
        <div class="sm:col-span-2 lg:col-span-4 flex justify-end"><button class="btn btn-primary btn-sm"><?= icon('check') ?><span data-label>Save settings</span></button></div>
    </form>
</details>
<?php endif; ?>
