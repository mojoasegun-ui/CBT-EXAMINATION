<?php
declare(strict_types=1);
/** @var array<string,string> $saved @var array<string,string> $defaults @var array $errors @var array $old */
$val = static fn (string $k): string => (string) ($old[$k] ?? $saved[$k] ?? '');
?>
<div class="pd-hello">
    <div>
        <h1>Brand <b>&amp; name</b></h1>
        <p>Change the product name once. It updates every page, email and the installable app.</p>
    </div>
</div>

<div class="pd-grid two">
    <form method="post" action="<?= e(url('/console/branding')) ?>" class="pd-card space-y-4" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="pd-head"><h2><?= icon('palette', 'h-5 w-5') ?> Product details</h2></div>
        <?= field('product_name', 'Product name', $val('product_name') !== '' ? $val('product_name') : $defaults['product_name'], $errors, ['required' => true, 'hint' => '2 to 40 characters', 'attrs' => ' maxlength="40"']) ?>
        <?= field('short_name', 'Short name', $val('short_name'), $errors, ['placeholder' => $defaults['short_name'], 'hint' => 'label under the app icon, up to 14 characters', 'attrs' => ' maxlength="14"']) ?>
        <?= field('tagline', 'Tagline', $val('tagline'), $errors, ['placeholder' => $defaults['tagline'], 'hint' => 'one short line under the name', 'attrs' => ' maxlength="80"']) ?>
        <?= field('support_email', 'Support email', $val('support_email'), $errors, ['type' => 'email', 'placeholder' => 'support@yourdomain.ng', 'hint' => 'shown on the privacy notice and terms']) ?>
        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button class="btn btn-primary"><?= icon('check', 'h-4 w-4') ?><span data-label>Save</span></button>
            <span class="text-xs text-muted">Leave a field empty to use the default.</span>
        </div>
    </form>

    <section class="pd-card" aria-labelledby="pv-title">
        <div class="pd-head"><h2 id="pv-title"><?= icon('eye', 'h-5 w-5') ?> Right now</h2></div>
        <dl class="pd-dl">
            <div><dt>Name shown</dt><dd><?= e($appName) ?></dd></div>
            <div><dt>Short name</dt><dd><?= e($saved['short_name'] ?? $defaults['short_name']) ?></dd></div>
            <div><dt>Tagline</dt><dd><?= e($saved['tagline'] ?? $defaults['tagline']) ?></dd></div>
            <div><dt>Support email</dt><dd><?= e($saved['support_email'] ?? 'not set') ?></dd></div>
        </dl>
        <p class="mt-4 text-sm leading-6 text-muted">Already installed apps keep their old label until they are reinstalled. Everything else changes immediately for everyone.</p>
        <form method="post" action="<?= e(url('/console/branding/reset')) ?>" class="mt-4" data-submit-once data-confirm="Restore the original name and details?">
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <button class="btn btn-secondary"><?= icon('refresh', 'h-4 w-4') ?><span data-label>Restore the original name</span></button>
        </form>
    </section>
</div>
