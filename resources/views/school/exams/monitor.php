<?php
declare(strict_types=1);
/** @var array $exam @var string $state @var list<array> $roster */
$count = static fn (string $s): int => count(array_filter($roster, static fn ($r) => $r['state'] === $s));
$scores = array_values(array_filter(array_column($roster, 'score'), static fn ($v) => $v !== null));
$avg = $scores ? round(array_sum($scores) / count($scores), 1) : null;
$labels = ['not_started' => ['badge-gray', 'Not started'], 'writing' => ['badge-green', 'Writing'], 'away' => ['badge-amber', 'Away / offline'], 'submitted' => ['badge-blue', 'Submitted']];
$isAdmin = ($user['role'] ?? '') === 'school_admin';
$trustBadge = ['clean' => ['badge-green', 'Clean'], 'check' => ['badge-amber', 'Check'], 'suspicious' => ['badge-red', 'Suspicious']];
$released = !empty($exam['results_released_at']);
$form = static fn (array $r, string $action, string $label, string $class, array $extra = [], string $confirm = '') =>
    '<form method="post" action="' . e(url('/school/exams/' . $exam['id'] . '/sittings/' . $r['sitting_id'])) . '"' . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '" data-confirm-ok="' . e($label) . '"' : '') . '>'
    . '<input type="hidden" name="_csrf_token" value="' . e($csrfToken) . '"><input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="name" value="' . e($r['name']) . '">'
    . implode('', array_map(static fn ($k, $v) => '<input type="hidden" name="' . e($k) . '" value="' . e((string) $v) . '">', array_keys($extra), $extra))
    . '<button class="' . $class . '">' . e($label) . '</button></form>';
?>
<a href="<?= e(url('/school/exams/' . $exam['id'])) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?><?= e($exam['title']) ?></a>
<div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="eyebrow">Live monitor</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink"><?= e($exam['title']) ?></h1>
        <p class="mt-1 text-sm text-muted" data-monitor-status><?= $state === 'live' ? 'Exam is open now · refreshes every 15 seconds' : ($state === 'upcoming' ? 'Opens ' . e(local_date($exam['starts_at'], 'D j M, g:i a')) : 'Exam window closed') ?></p></div>
    <?php if (!$released && ($exam['settings']['result_release'] ?? '') !== 'instant' && $count('submitted') > 0): ?>
        <form method="post" action="<?= e(url('/school/exams/' . $exam['id'] . '/action')) ?>" data-confirm="Students will see their scores straight away." data-confirm-ok="Release results">
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="action" value="release">
            <button class="btn btn-primary"><?= icon('chart') ?>Release results</button>
        </form>
    <?php elseif ($released): ?><span class="badge badge-green">Results released</span><?php endif; ?>
</div>

<div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5" data-stagger>
    <?php foreach (['not_started' => 'Not started', 'writing' => 'Writing now', 'away' => 'Away / offline', 'submitted' => 'Submitted'] as $k => $l): ?>
        <div class="card p-4"><p class="text-xs text-muted"><?= $l ?></p><p class="mt-1 font-display text-2xl font-semibold tabular-nums text-ink" data-count="<?= $k ?>"><?= $count($k) ?></p></div>
    <?php endforeach; ?>
    <div class="card col-span-2 p-4 sm:col-span-1"><p class="text-xs text-muted">Average score</p><p class="mt-1 font-display text-2xl font-semibold tabular-nums text-ink" data-avg><?= $avg !== null ? $avg . '%' : '—' ?></p></div>
</div>

<section class="card mt-6 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
            <thead class="border-b border-line bg-soft/60 text-left text-xs font-semibold uppercase tracking-wider text-subtle">
                <tr><th class="px-5 py-3">Student</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Progress</th><th class="px-3 py-3">Time left</th><th class="px-3 py-3">Integrity</th><th class="px-3 py-3">Score</th><th class="px-5 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-line" data-roster>
                <?php foreach ($roster as $r): ?>
                    <tr data-student="<?= $r['student_id'] ?>" data-sitting="<?= (int) $r['sitting_id'] ?>">
                        <td class="px-5 py-3"><p class="font-medium text-ink"><?= e($r['name']) ?></p><p class="text-xs text-muted"><?= e($r['admission_no']) ?> · <?= e($r['class_name']) ?><?= $r['seat'] ? ' · Seat ' . e($r['seat']) : '' ?></p></td>
                        <td class="px-3 py-3"><span class="badge badge-dot <?= $labels[$r['state']][0] ?>" data-cell="state"><?= $labels[$r['state']][1] ?></span></td>
                        <td class="px-3 py-3 tabular-nums text-muted" data-cell="progress"><?= $r['total'] ? $r['answered'] . '/' . $r['total'] : '—' ?></td>
                        <td class="px-3 py-3 tabular-nums text-muted" data-cell="left"><?= $r['left'] !== null ? intdiv($r['left'], 60) . 'm ' . ($r['left'] % 60) . 's' : '—' ?><?= $r['extra'] ? ' <span class="text-xs text-brand">+' . $r['extra'] . '</span>' : '' ?></td>
                        <td class="px-3 py-3"><?php if ($r['trust'] !== null): ?><a href="<?= e(url('/school/exams/' . $exam['id'] . '/sittings/' . $r['sitting_id'])) ?>" class="inline-flex items-center gap-2 hover:underline"><span class="badge <?= $trustBadge[$r['trust']][0] ?>" data-cell="trust"><?= $trustBadge[$r['trust']][1] ?></span><span class="text-xs text-muted" data-cell="warnings"><?= $r['warnings'] ?> warn<?= $r['flags'] ? ' · ' . $r['flags'] . ' cam' : '' ?></span></a><?php else: ?><span class="text-subtle">—</span><?php endif; ?></td>
                        <td class="px-3 py-3 font-semibold tabular-nums text-ink" data-cell="score"><?= $r['score'] !== null ? rtrim(rtrim(number_format($r['score'], 1), '0'), '.') . '%' : '—' ?></td>
                        <td class="px-5 py-3">
                            <?php if ($r['sitting_id'] !== null): ?>
                                <div class="flex justify-end gap-1.5">
                                    <?php if ($r['state'] !== 'submitted'): ?>
                                        <?= $form($r, 'add_time', '+5 min', 'btn btn-secondary btn-sm', ['minutes' => 5]) ?>
                                        <?= $form($r, 'add_time', '+15', 'btn btn-ghost btn-sm', ['minutes' => 15]) ?>
                                        <?php if (!empty($exam['settings']['single_device']) && $r['device_locked']): ?><?= $form($r, 'release_device', 'New device', 'btn btn-ghost btn-sm', [], 'Let ' . $r['name'] . ' continue on another phone or computer? Their answers are kept.') ?><?php endif; ?>
                                        <?= $form($r, 'submit', 'Submit', 'btn btn-danger-soft btn-sm', [], 'Submit and mark ' . $r['name'] . '’s paper now?') ?>
                                    <?php endif; ?>
                                    <?php if ($isAdmin): ?><?= $form($r, 'reset', 'Reset', 'btn btn-ghost btn-sm', [], 'Delete ' . $r['name'] . '’s sitting? Their answers are lost and they can start again.') ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($roster === []): ?><tr><td colspan="7" class="px-5 py-12 text-center text-muted">No active students in the selected classes.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<script nonce="<?= e($cspNonce) ?>">
(() => {
    const url = <?= json_encode(url('/school/exams/' . $exam['id'] . '/monitor/data')) ?>;
    const labels = <?= json_encode($labels) ?>;
    const trust = <?= json_encode($trustBadge) ?>;
    const known = new Set([...document.querySelectorAll("[data-roster] tr")].map((tr) => tr.dataset.sitting));
    const fmt = (s) => `${Math.floor(s / 60)}m ${s % 60}s`;
    async function refresh() {
        if (document.hidden || document.querySelector("dialog[open]")) return;
        try {
            const res = await fetch(url, { headers: { Accept: "application/json" }, credentials: "same-origin" });
            if (!res.ok) return;
            const data = await res.json();
            if (data.rows.some((r) => !known.has(String(r.sitting_id ?? 0)))) { location.reload(); return; }
            const counts = { not_started: 0, writing: 0, away: 0, submitted: 0 };
            const scores = [];
            data.rows.forEach((r) => {
                counts[r.state]++;
                if (r.score !== null) scores.push(r.score);
                const tr = document.querySelector(`[data-roster] tr[data-student="${r.student_id}"]`);
                if (!tr) return;
                const st = tr.querySelector('[data-cell="state"]');
                st.className = `badge badge-dot ${labels[r.state][0]}`;
                st.textContent = labels[r.state][1];
                tr.querySelector('[data-cell="progress"]').textContent = r.total ? `${r.answered}/${r.total}` : "—";
                tr.querySelector('[data-cell="left"]').textContent = r.left !== null ? fmt(r.left) : "—";
                const w = tr.querySelector('[data-cell="warnings"]');
                if (w) w.textContent = `${r.warnings} warn` + (r.flags ? ` · ${r.flags} cam` : "");
                const tb = tr.querySelector('[data-cell="trust"]');
                if (tb && r.trust) { tb.className = `badge ${trust[r.trust][0]}`; tb.textContent = trust[r.trust][1]; }
                tr.querySelector('[data-cell="score"]').textContent = r.score !== null ? `${Math.round(r.score * 10) / 10}%` : "—";
            });
            Object.entries(counts).forEach(([k, v]) => { const el = document.querySelector(`[data-count="${k}"]`); if (el) el.textContent = v; });
            document.querySelector("[data-avg]").textContent = scores.length ? `${Math.round(scores.reduce((a, b) => a + b, 0) / scores.length * 10) / 10}%` : "—";
        } catch { /* try again next time */ }
    }
    setInterval(refresh, 15000);
})();
</script>
