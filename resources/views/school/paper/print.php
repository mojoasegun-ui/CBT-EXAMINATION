<?php
declare(strict_types=1);
/** Printable question paper(s). @var array $exam @var array<string, list<array>> $papers @var list<string> $theory @var array $school @var string $classes */
$subjects = array_column($exam['papers'], 'subject', 'id');
$theoryMax = (int) $exam['settings']['theory_max'];
?>
<div class="print-hide card mb-6 flex flex-wrap items-center justify-between gap-3 p-5 text-sm">
    <p class="text-muted">Print <b>double-sided</b> (front and back) to save paper. Page numbers are added automatically.</p>
    <div class="flex flex-wrap gap-2">
        <?php foreach (array_slice(\ExamCore\Services\PaperService::LETTERS, 0, (int) $exam['settings']['versions']) as $v): ?><a class="btn btn-secondary btn-sm" href="?version=<?= $v ?>">Paper <?= $v ?></a><?php endforeach; ?>
        <a class="btn btn-ghost btn-sm" href="?version=all">All versions</a>
    </div>
</div>
<?php foreach ($papers as $version => $questions): $lastPaper = null; $lastPassage = null; ?>
<article class="qp">
    <header class="qp-head">
        <?php if (!empty($school['logo_path'])): ?><img src="<?= e(url('/' . $school['logo_path'])) ?>" alt="" class="h-14 w-14 object-contain"><?php endif; ?>
        <div class="flex-1 text-center">
            <p class="qp-school"><?= e($school['name']) ?></p>
            <p class="qp-title"><?= e($exam['title']) ?></p>
            <p class="qp-meta"><?= e(implode(' · ', array_column($exam['papers'], 'subject'))) ?> · <?= e($classes) ?> · Time: <?= (int) $exam['duration_minutes'] ?> minutes</p>
        </div>
        <div class="qp-version"><span>PAPER</span><?= e($version) ?></div>
    </header>
    <div class="qp-instructions">
        <p><b>Instructions:</b> <?= trim((string) $exam['instructions']) !== '' ? e((string) $exam['instructions']) : 'Answer all questions.' ?></p>
        <p>Section A: shade your answers on the answer sheet with an HB pencil. Check that the paper letter on your answer sheet is <b><?= e($version) ?></b>.<?= $theory ? ' Section B: answer in your answer booklet.' : '' ?></p>
    </div>
    <h2 class="qp-section">Section A · Objectives (<?= count($questions) ?> questions)</h2>
    <ol class="qp-objectives">
        <?php foreach ($questions as $q): ?>
            <?php if (count($subjects) > 1 && $q['paper_id'] !== $lastPaper): $lastPaper = $q['paper_id']; ?><li class="qp-subject"><?= e($subjects[$q['paper_id']] ?? '') ?></li><?php endif; ?>
            <?php if ($q['passage_id'] && $q['passage_id'] !== $lastPassage): $lastPassage = $q['passage_id']; ?><li class="qp-passage"><b><?= e((string) $q['passage_title']) ?></b><div><?= rich_text((string) $q['passage_body']) ?></div></li><?php endif; ?>
            <li class="qp-q">
                <p><span class="qp-n"><?= (int) $q['n'] ?>.</span> <?= rich_text($q['body']) ?></p>
                <?php if ($q['image']): ?><img src="<?= e(url('/' . ltrim((string) $q['image'], '/'))) ?>" alt="" class="qp-img"><?php endif; ?>
                <ol class="qp-options"><?php foreach ($q['options'] as $o): ?><li><b><?= $o['letter'] ?>.</b> <?= rich_text($o['text']) ?></li><?php endforeach; ?></ol>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php if ($theory): ?>
        <h2 class="qp-section qp-break">Section B · Theory<?= $theoryMax ? ' (' . $theoryMax . ' marks)' : '' ?></h2>
        <ol class="qp-theory"><?php foreach ($theory as $i => $t): ?><li><span class="qp-n"><?= $i + 1 ?>.</span> <?= nl2br(e($t)) ?></li><?php endforeach; ?></ol>
    <?php endif; ?>
    <p class="qp-end">— End of paper <?= e($version) ?> —</p>
</article>
<?php endforeach; ?>
