<?php
declare(strict_types=1);
/** Logo or coloured initials for a school row. @var array<string,mixed> $row @var string $cspNonce */
$avatarSize ??= 'h-10 w-10';
?>
<?php if (!empty($row['logo_path'])): ?>
    <img src="<?= e(url('/' . $row['logo_path'])) ?>" alt="" class="<?= e($avatarSize) ?> shrink-0 rounded-xl border border-line bg-white object-contain p-0.5" loading="lazy">
<?php else: ?>
    <span class="<?= e($avatarSize) ?> grid shrink-0 place-items-center rounded-xl font-display text-sm font-bold school-tint-<?= (int) $row['id'] ?>"><?= e(initials((string) $row['name'])) ?></span>
    <style nonce="<?= e($cspNonce) ?>">.school-tint-<?= (int) $row['id'] ?>{background:rgb(<?= e(hex_to_rgb_channels((string) $row['primary_color'])) ?>);color:rgb(<?= e(brand_ink((string) $row['primary_color'])) ?>)}</style>
<?php endif; ?>
