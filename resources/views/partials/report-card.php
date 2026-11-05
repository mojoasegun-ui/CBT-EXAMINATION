<?php
declare(strict_types=1);
/** One A4 term report card. @var array $card */
use ExamCore\Services\ResultService;

['school' => $sc, 'student' => $st, 'term' => $tm, 'settings' => $set, 'sheet' => $sheet, 'row' => $row, 'remarks' => $rem] = $card;
$fmt = static fn (?float $v): string => $v === null ? '—' : rtrim(rtrim(number_format($v, 1), '0'), '.');
$showPos = !empty($set['show_position']);
$caW = (int) $set['ca_weight'];
?>
<article class="a4">
    <header class="rc-head flex items-center gap-4 pb-4">
        <?php if (!empty($sc['logo_path'])): ?><img src="<?= e(url('/' . $sc['logo_path'])) ?>" alt="" class="h-16 w-16 object-contain"><?php endif; ?>
        <div class="flex-1 text-center">
            <p class="rc-school font-display text-[20px] font-bold uppercase leading-tight tracking-wide"><?= e($sc['name']) ?></p>
            <?php if (!empty($sc['motto'])): ?><p class="text-[11px] italic text-slate-600">“<?= e($sc['motto']) ?>”</p><?php endif; ?>
            <p class="text-[11px] text-slate-600"><?= e(trim(($sc['address'] ?? '') . ' ' . ($sc['phone'] ?? ''))) ?></p>
            <p class="rc-pill mt-1.5 inline-block rounded px-3 py-0.5 text-[11px] font-bold uppercase tracking-[.2em]">Report sheet · <?= e($tm['name']) ?> · <?= e($tm['session_name']) ?></p>
        </div>
        <img src="<?= e($card['qr']) ?>" alt="QR code to verify this report" class="h-20 w-20">
    </header>

    <section class="mt-4 grid grid-cols-[1fr_auto] gap-4">
        <dl class="grid grid-cols-2 gap-x-6 gap-y-1.5 sm:grid-cols-3">
            <div><dt class="rc-label">Name</dt><dd class="font-semibold"><?= e($st['name']) ?></dd></div>
            <div><dt class="rc-label">Admission no</dt><dd class="font-semibold"><?= e($st['admission_no']) ?></dd></div>
            <div><dt class="rc-label">Class</dt><dd class="font-semibold"><?= e((string) $st['class_name']) ?></dd></div>
            <div><dt class="rc-label">Gender</dt><dd><?= e(ucfirst((string) ($st['gender'] ?? '—'))) ?></dd></div>
            <div><dt class="rc-label">Students in class</dt><dd><?= (int) $sheet['size'] ?></dd></div>
            <div><dt class="rc-label">Next term begins</dt><dd><?= $set['next_term_begins'] ? e(date('j M Y', strtotime($set['next_term_begins']))) : '—' ?></dd></div>
        </dl>
        <?php if (!empty($st['photo_path'])): ?><img src="<?= e(url('/' . $st['photo_path'])) ?>" alt="" class="h-24 w-20 rounded border border-slate-300 object-cover"><?php endif; ?>
    </section>

    <div class="mt-4 overflow-x-auto rc-scroll"><table class="rc-table">
        <thead><tr><th>Subject</th><th class="w-14 text-center">CA <?= $caW ?>%</th><th class="w-14 text-center">Exam <?= 100 - $caW ?>%</th><th class="w-14 text-center">Total</th><th class="w-12 text-center">Grade</th><?php if ($showPos): ?><th class="w-14 text-center">Position</th><?php endif; ?><th class="w-16 text-center">Class avg</th><th>Remark</th></tr></thead>
        <tbody>
            <?php foreach ($sheet['subjects'] as $sub): $m = $row['subjects'][$sub] ?? null; ?>
                <tr>
                    <td class="font-medium"><?= e($sub) ?></td>
                    <td class="text-center"><?= $fmt($m['ca'] ?? null) ?></td>
                    <td class="text-center"><?= $fmt($m['exam'] ?? null) ?></td>
                    <td class="text-center font-bold"><?= $fmt($m['total'] ?? null) ?></td>
                    <td class="text-center font-bold <?= ($m['total'] ?? null) !== null && !$m['pass'] ? 'text-red-600' : '' ?>"><?= ($m['total'] ?? null) !== null ? e($m['grade']) : '—' ?></td>
                    <?php if ($showPos): ?><td class="text-center"><?= e(ResultService::ordinal($m['position'] ?? null)) ?></td><?php endif; ?>
                    <td class="text-center text-slate-600"><?= $fmt($sheet['classAvg'][$sub] ?? null) ?></td>
                    <td class="text-slate-700"><?= ($m['total'] ?? null) !== null ? e($m['remark']) : 'Not taken' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($sheet['subjects'] === []): ?><tr><td colspan="8" class="py-6 text-center text-slate-500">No released results for this term yet.</td></tr><?php endif; ?>
        </tbody>
    </table></div>

    <section class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-lg border border-slate-300 p-2.5 text-center"><p class="rc-label">Subjects</p><p class="text-lg font-bold"><?= (int) ($row['count'] ?? 0) ?></p></div>
        <div class="rounded-lg border border-slate-300 p-2.5 text-center"><p class="rc-label">Total score</p><p class="text-lg font-bold"><?= $fmt($row['sum'] ?? null) ?></p></div>
        <div class="rounded-lg border border-slate-300 p-2.5 text-center"><p class="rc-label">Average</p><p class="text-lg font-bold"><?= $fmt($row['average'] ?? null) ?>%<?= ($row['average'] ?? null) !== null ? ' · ' . e($row['overall']['grade']) : '' ?></p></div>
        <div class="rounded-lg border border-slate-300 p-2.5 text-center"><p class="rc-label">Position</p><p class="text-lg font-bold"><?= $showPos ? e(ResultService::ordinal($row['position'] ?? null)) . ' <span class="text-xs font-normal text-slate-500">of ' . (int) $sheet['size'] . '</span>' : '—' ?></p></div>
    </section>

    <section class="mt-4 space-y-3">
        <div class="rounded-lg border border-slate-300 p-3"><p class="rc-label">Class teacher's comment</p><p class="mt-1 min-h-[1.5em]"><?= e($rem['teacher_comment'] ?? ResultService::autoComment($row['average'] ?? null)) ?></p></div>
        <div class="grid grid-cols-[1fr_auto] items-end gap-6 rounded-lg border border-slate-300 p-3">
            <div><p class="rc-label">Principal's comment</p><p class="mt-1 min-h-[1.5em]"><?= e($rem['principal_comment'] ?? ResultService::autoComment($row['average'] ?? null)) ?></p></div>
            <div class="w-44 text-center"><div class="h-8 border-b border-slate-500"></div><p class="mt-1 text-[11px] text-slate-600"><?= e($set['principal_name'] ?: 'Principal') ?> · signature &amp; stamp</p></div>
        </div>
    </section>

    <section class="mt-4">
        <p class="rc-label">Grading key</p>
        <p class="mt-1 text-[11px] text-slate-600"><?= e(implode(' · ', array_map(static fn ($b) => $b['grade'] . ' ' . (int) floor((float) $b['min_score']) . '–' . (int) floor((float) $b['max_score']) . ' ' . $b['remark'], $card['bands'] ?? []))) ?></p>
    </section>

    <footer class="mt-5 flex items-end justify-between gap-4 border-t border-slate-300 pt-3 text-[10.5px] text-slate-500">
        <p>Scan the QR code or visit <span class="font-mono"><?= e(preg_replace('~^https?://~', '', $card['verify'])) ?></span> to confirm this report is genuine.</p>
        <p class="shrink-0 text-right">Parent result code: <span class="font-mono font-bold text-slate-800"><?= e($card['code']) ?></span><br>Issued <?= e(date('j M Y')) ?></p>
    </footer>
</article>
