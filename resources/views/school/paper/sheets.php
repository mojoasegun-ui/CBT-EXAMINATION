<?php
declare(strict_types=1);
/** OMR shading sheets as exact-millimetre SVG (A4). @var array $exam @var list<array> $sheets @var array $school */
$ms = \ExamCore\Services\PaperService::MARKER_SIZE;
$theoryMax = (int) $exam['settings']['theory_max'];
$fit = static fn (string $s, int $n): string => mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '…' : $s;
?>
<div class="print-hide card mb-6 p-5 text-sm">
    <p class="font-semibold text-ink"><?= count($sheets) ?> answer sheet<?= count($sheets) === 1 ? '' : 's' ?> · one page each</p>
    <p class="mt-1 text-muted">Print at <b>100% / Actual size</b> on plain white A4. Do not fold or staple near the black corner squares. Give each student <b>their own sheet</b> (name and QR are printed on it); the paper version (A/B…) is printed in the big box.</p>
</div>
<?php foreach ($sheets as $s): $L = $s['layout']; ?>
<svg class="omr-sheet" xmlns="http://www.w3.org/2000/svg" width="210mm" height="297mm" viewBox="0 0 210 297" font-family="Arial, Helvetica, sans-serif">
    <rect x="0" y="0" width="210" height="297" fill="#fff"/>
    <?php foreach ($L['markers'] as [$cx, $cy]): ?><rect x="<?= $cx - $ms / 2 ?>" y="<?= $cy - $ms / 2 ?>" width="<?= $ms ?>" height="<?= $ms ?>" fill="#000"/><?php endforeach; ?>

    <text x="24" y="16.5" font-size="5" font-weight="700"><?= e($fit(mb_strtoupper($school['name']), 44)) ?></text>
    <text x="24" y="23" font-size="3.6" fill="#333"><?= e($fit($exam['title'], 60)) ?> · Answer sheet</text>
    <text x="24" y="33" font-size="3" fill="#666">NAME</text><text x="38" y="33" font-size="4" font-weight="700"><?= e($fit($s['name'], 34)) ?></text>
    <text x="24" y="40" font-size="3" fill="#666">ADM. NO</text><text x="38" y="40" font-size="4" font-weight="700"><?= e($s['admission_no']) ?></text>
    <text x="24" y="47" font-size="3" fill="#666">CLASS</text><text x="38" y="47" font-size="4"><?= e($s['class_name']) ?></text>
    <rect x="128" y="27" width="22" height="22" rx="2" fill="none" stroke="#000" stroke-width="0.6"/>
    <text x="139" y="32" font-size="2.6" text-anchor="middle" fill="#666">PAPER</text>
    <text x="139" y="45.5" font-size="13" font-weight="700" text-anchor="middle"><?= e($s['version']) ?></text>
    <image x="157" y="24" width="30" height="30" href="<?= e($s['qr']) ?>"/>
    <text x="24" y="57" font-size="2.9" fill="#333">Use an HB pencil or black biro. Fill ONE bubble per question completely, like this:</text>
    <circle cx="146.5" cy="56" r="2.1" fill="#000"/>
    <text x="24" y="62" font-size="2.9" fill="#333">To change an answer, rub it out completely. Do not write on the black squares or the QR code.</text>
    <line x1="17" y1="65" x2="193" y2="65" stroke="#000" stroke-width="0.3"/>

    <?php foreach ($L['questions'] as $q): ?>
        <text x="<?= $q['x'] + 7 ?>" y="<?= $q['y'] + 1.1 ?>" font-size="3.2" font-weight="700" text-anchor="end"><?= (int) $q['n'] ?></text>
        <?php foreach ($q['letters'] as $b): ?>
            <circle cx="<?= $b['x'] ?>" cy="<?= $b['y'] ?>" r="<?= $L['r'] ?>" fill="none" stroke="#222" stroke-width="0.28"/>
            <text x="<?= $b['x'] ?>" y="<?= $b['y'] + 0.95 ?>" font-size="2.5" text-anchor="middle" fill="#888"><?= $b['l'] ?></text>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?php if ($L['theory'] !== []): ?>
        <line x1="17" y1="252" x2="193" y2="252" stroke="#000" stroke-width="0.3"/>
        <text x="24" y="257" font-size="3" font-weight="700">THEORY SCORE</text>
        <text x="24" y="261" font-size="2.6" fill="#666">(teacher only, out of <?= $theoryMax ?>)</text>
        <?php foreach ($L['theory'] as $b): ?>
            <?php if ($b['d'] === 0): ?><text x="64" y="<?= $b['y'] + 1 ?>" font-size="2.7" text-anchor="end" fill="#666"><?= $b['row'] === 't' ? 'Tens' : 'Units' ?></text><?php endif; ?>
            <circle cx="<?= $b['x'] ?>" cy="<?= $b['y'] ?>" r="<?= $L['r'] ?>" fill="none" stroke="#222" stroke-width="0.28"/>
            <text x="<?= $b['x'] ?>" y="<?= $b['y'] + 0.95 ?>" font-size="2.5" text-anchor="middle" fill="#888"><?= $b['d'] ?></text>
        <?php endforeach; ?>
    <?php endif; ?>
    <text x="105" y="276" font-size="2.5" text-anchor="middle" fill="#888">ExamCore · scan with the school's phone · <?= e($s['admission_no']) ?> · Paper <?= e($s['version']) ?></text>
</svg>
<?php endforeach; ?>
<?php if ($sheets === []): ?><div class="card empty"><p class="text-sm text-muted">No students in the selected classes.</p></div><?php endif; ?>
