<?php
declare(strict_types=1);
/** Printable student record. Same data as the record page. @var array<string,mixed> $student @var list<array<string,mixed>> $timeline @var list<array<string,mixed>> $results @var array $summary @var array|null $school @var bool $isAdmin */
use ExamCore\Models\Enrolment;
?>
<article class="sheet rec-print">
    <header class="rec-head">
        <div>
            <p class="rec-school"><?= e((string) ($school['name'] ?? '')) ?></p>
            <h1>Student record</h1>
            <p class="rec-sub">Printed <?= e(local_date(gmdate('Y-m-d H:i:s'), 'j F Y')) ?></p>
        </div>
        <?php if (!empty($student['photo_path'])): ?><img class="rec-photo" src="<?= e(url('/' . $student['photo_path'])) ?>" alt="" width="96" height="96"><?php endif; ?>
    </header>
    <table class="rec-table rec-facts">
        <tr><th>Name</th><td><?= e($student['name']) ?></td><th>Admission no.</th><td><?= e((string) $student['admission_no']) ?></td></tr>
        <tr><th>Class now</th><td><?= e((string) ($student['class_name'] ?? '–')) ?></td><th>Status</th><td><?= e(ucfirst((string) $student['status'])) ?></td></tr>
        <?php if ($isAdmin): ?><tr><th>Date of birth</th><td><?= !empty($student['date_of_birth']) ? e(local_date($student['date_of_birth'] . ' 12:00:00', 'j M Y')) : '–' ?></td><th>Guardian</th><td><?= e((string) ($student['guardian_name'] ?: '–')) ?></td></tr><?php endif; ?>
    </table>

    <h2>Class history</h2>
    <table class="rec-table">
        <thead><tr><th>Session</th><th>Class</th><th>Outcome</th></tr></thead>
        <tbody><?php foreach ($timeline as $t): ?><tr><td><?= e($t['session_name']) ?></td><td><?= e($t['class_name']) ?></td><td><?= e(Enrolment::OUTCOMES[$t['outcome']] ?? $t['outcome']) ?></td></tr><?php endforeach; ?></tbody>
    </table>

    <h2>Exam results <span class="rec-note">(<?= (int) $summary['exams'] ?> exams<?= $summary['avg'] !== null ? ', average ' . (int) round($summary['avg']) . '%' : '' ?>)</span></h2>
    <table class="rec-table">
        <thead><tr><th>Session</th><th>Term</th><th>Exam</th><th>Type</th><th class="num">Score</th></tr></thead>
        <tbody>
        <?php foreach ($results as $r): ?>
            <tr><td><?= e((string) ($r['session_name'] ?? '–')) ?></td><td><?= e((string) ($r['term_name'] ?? '–')) ?></td><td><?= e((string) $r['title']) ?></td><td><?= e(ucfirst((string) $r['type'])) ?></td><td class="num"><?= $r['percent'] === null ? '–' : (int) round((float) $r['percent']) . '%' ?></td></tr>
        <?php endforeach; ?>
        <?php if ($results === []): ?><tr><td colspan="5">No exam results recorded.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <p class="rec-foot">This record was generated from the school's CBT platform. It lists every session the student has been enrolled in.</p>
</article>
