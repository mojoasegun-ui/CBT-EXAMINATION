<?php
declare(strict_types=1);
/** @var array<string,mixed> $school @var list<string> $states @var array $errors @var array $old */
$v = static fn (string $k): string => (string) ($old[$k] ?? $school[$k] ?? '');
?>
<div><p class="eyebrow">School</p><h1 class="mt-2 font-display text-[30px] font-semibold text-ink">Profile &amp; branding</h1>
    <p class="mt-1 text-sm text-muted">Shown on your portal, students’ screens and result slips. The school name and web address are managed by your platform provider.</p></div>
<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <form method="post" action="<?= e(url('/school/settings')) ?>" class="card lg:col-span-2" data-submit-once novalidate>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="card-header"><h2 class="card-title">School details</h2></div>
        <div class="grid gap-5 px-6 pb-6 sm:grid-cols-2">
            <div class="sm:col-span-2"><p class="label">School name</p><p class="rounded-2xl border border-line bg-soft px-4 py-3 text-[15px] text-ink"><?= e($school['name']) ?> <span class="text-xs text-subtle">· code <span class="font-mono"><?= e($school['slug']) ?></span></span></p></div>
            <?= field('short_name', 'Short name', $v('short_name'), $errors, ['hint' => 'used in admission numbers', 'placeholder' => 'KCL']) ?>
            <?= field('state', 'State', $v('state'), $errors, ['type' => 'select', 'empty' => 'Choose', 'options' => array_combine($states, $states)]) ?>
            <?= field('motto', 'Motto', $v('motto'), $errors, ['class' => 'sm:col-span-2', 'placeholder' => 'Knowledge and Integrity']) ?>
            <?= field('email', 'School email', $v('email'), $errors, ['type' => 'email']) ?>
            <?= field('phone', 'Phone', $v('phone'), $errors, ['type' => 'tel']) ?>
            <?= field('address', 'Address', $v('address'), $errors, ['class' => 'sm:col-span-2']) ?>
            <div class="sm:col-span-2">
                <p class="label">School colour <span class="label-hint">· used on report cards, result slips, question papers and flyers</span></p>
                <div class="flex max-w-sm items-center gap-3">
                    <input type="color" class="h-12 w-14 cursor-pointer rounded-2xl border border-line bg-card p-1" value="<?= e($v('primary_color')) ?>" data-color-picker aria-label="Pick a colour">
                    <input class="input font-mono uppercase" name="primary_color" value="<?= e($v('primary_color')) ?>" maxlength="7" data-color-text aria-label="Colour hex code">
                    <button type="button" class="inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold" data-brand-sample tabindex="-1">Preview</button>
                </div>
                <?= field_error($errors, 'primary_color') ?>
            </div>
        </div>
        <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save profile</span></button></div>
    </form>
    <form method="post" action="<?= e(url('/school/settings/logo')) ?>" enctype="multipart/form-data" class="card self-start" data-submit-once>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
        <div class="card-header"><h2 class="card-title">Logo</h2></div>
        <div class="px-6 pb-6">
            <div class="grid aspect-square w-full place-items-center overflow-hidden rounded-3xl border border-dashed border-line bg-soft" data-logo-preview>
                <?php if ($school['logo_path']): ?><img src="<?= e(url('/' . $school['logo_path'])) ?>" alt="Current logo" class="h-full w-full object-contain p-6"><?php else: ?><?= icon('upload', 'h-8 w-8 text-subtle') ?><?php endif; ?>
            </div>
            <p class="mt-3 text-xs text-muted">PNG, JPG or WebP up to 1 MB. Square with a transparent background looks best.</p>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <label class="btn btn-secondary btn-sm cursor-pointer"><?= icon('upload') ?>Choose<input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only" data-logo-input required></label>
                <button class="btn btn-primary btn-sm"><span data-label>Upload</span></button>
            </div>
            <p class="mt-2 text-xs text-muted" data-logo-name></p>
        </div>
    </form>
</div>
