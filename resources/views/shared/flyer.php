<?php
declare(strict_types=1);
/** Printable flyer. @var string $layout @var string $headline @var string $sub @var list<string> $steps @var string $qr @var string $target @var string $contact @var array|null $school @var string $appName */
use ExamCore\Controllers\FlyerController;

$copies = ['a4' => 1, 'a5' => 2, 'cards' => 8][$layout];
$sheetClass = ['a4' => 'flyer-a4', 'a5' => 'flyer-a5', 'cards' => 'flyer-cards'][$layout]; // literal names so Tailwind keeps them
$short = preg_replace('~^https?://~', '', rtrim($target, '/'));
$short = preg_replace('~\?source=flyer$~', '', $short);
$brand = $school['name'] ?? $appName;
?>
<form method="get" class="print-hide card mb-6 grid gap-4 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
    <?= field('headline', 'Headline', $headline, [], ['attrs' => ' maxlength="90"']) ?>
    <?= field('contact', 'Phone / WhatsApp (optional)', $contact, [], ['placeholder' => '0803 000 0000', 'attrs' => ' maxlength="80"']) ?>
    <div class="flex gap-2">
        <select class="select" name="layout" aria-label="Size"><?php foreach (FlyerController::LAYOUTS as $k => $l): ?><option value="<?= $k ?>" <?= $layout === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
        <button class="btn btn-secondary">Update</button>
    </div>
</form>

<div class="flyer-sheet <?= $sheetClass ?>">
    <?php for ($i = 0; $i < $copies; $i++): ?>
        <article class="flyer">
            <div class="flyer-band"></div>
            <header class="flex items-center gap-3">
                <?php if (!empty($school['logo_path'])): ?><img src="<?= e(url('/' . $school['logo_path'])) ?>" alt="" class="flyer-logo object-contain"><?php else: ?><img src="<?= e(asset('icons/icon-192.png')) ?>" alt="" class="flyer-logo rounded-xl"><?php endif; ?>
                <p class="flyer-brand"><?= e($brand) ?></p>
            </header>
            <h1 class="flyer-title"><?= e($headline) ?></h1>
            <?php if ($layout !== 'cards'): ?><p class="flyer-sub"><?= e($sub) ?></p><?php endif; ?>
            <div class="flyer-body">
                <img src="<?= e($qr) ?>" alt="QR code: <?= e($short) ?>" class="flyer-qr">
                <?php if ($layout !== 'cards'): ?>
                    <ol class="flyer-steps">
                        <?php foreach ($steps as $n => $step): ?><li><span><?= $n + 1 ?></span><?= e($step) ?></li><?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
            <p class="flyer-url">Scan or visit <b><?= e($short) ?></b></p>
            <?php if ($contact !== ''): ?><p class="flyer-contact">Enquiries: <?= e($contact) ?></p><?php endif; ?>
            <p class="flyer-foot">Works on any phone · Add it to your home screen like an app</p>
        </article>
    <?php endfor; ?>
</div>
