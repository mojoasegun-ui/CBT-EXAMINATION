<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $plans @var list<string> $states @var array<string,string> $errors @var array<string,mixed> $old */
$v = static fn (string $key, string $default = ''): string => (string) ($old[$key] ?? $default);
$invalid = static fn (string $key): string => isset($errors[$key]) ? 'input-error" aria-invalid="true" aria-describedby="' . $key . '-error' : '';
$selectedPlan = (int) ($old['plan_id'] ?? ($plans[0]['id'] ?? 0));
?>
<a href="<?= e(url('/console/schools')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Schools</a>
<h1 class="mt-3 font-display text-2xl font-semibold text-ink">Add a school</h1>
<p class="mt-1 text-sm text-muted">Creates the school’s portal and its first administrator. You will see their temporary password once on the next page.</p>

<form method="post" action="<?= e(url('/console/schools')) ?>" class="mt-6 grid gap-6 lg:grid-cols-3" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">

    <div class="space-y-6 lg:col-span-2">
        <section class="card" aria-labelledby="s-details">
            <div class="card-header"><h2 id="s-details" class="card-title">School details</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label" for="name">School name</label>
                    <input class="input <?= $invalid('name') ?>" id="name" name="name" required maxlength="160" value="<?= e($v('name')) ?>" placeholder="e.g. Kings College, Lagos" data-slug-source autofocus>
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="slug">Web address</label>
                    <div class="flex rounded-xl shadow-sm">
                        <span class="inline-flex items-center rounded-l-xl border border-r-0 border-line bg-canvas px-3 text-sm text-muted"><?= e(preg_replace('#^https?://#', '', url('/s/'))) ?></span>
                        <input class="input rounded-l-none shadow-none <?= $invalid('slug') ?>" id="slug" name="slug" maxlength="60" value="<?= e($v('slug')) ?>" placeholder="kings-college" data-slug-target pattern="[a-z0-9-]+">
                    </div>
                    <?= field_error($errors, 'slug') ?: '<p class="help">Lowercase letters, numbers and dashes. Filled in from the name.</p>' ?>
                </div>
                <div>
                    <label class="label" for="short_name">Short name <span class="label-hint">· optional</span></label>
                    <input class="input" id="short_name" name="short_name" maxlength="40" value="<?= e($v('short_name')) ?>" placeholder="KC Lagos">
                </div>
                <div>
                    <label class="label" for="state">State</label>
                    <select class="select <?= $invalid('state') ?>" id="state" name="state">
                        <option value="">Choose a state</option>
                        <?php foreach ($states as $state): ?><option <?= $v('state') === $state ? 'selected' : '' ?>><?= e($state) ?></option><?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'state') ?>
                </div>
                <div>
                    <label class="label" for="email">School email <span class="label-hint">· optional</span></label>
                    <input class="input <?= $invalid('email') ?>" id="email" name="email" type="email" value="<?= e($v('email')) ?>" placeholder="info@school.edu.ng">
                    <?= field_error($errors, 'email') ?>
                </div>
                <div>
                    <label class="label" for="phone">Phone <span class="label-hint">· optional</span></label>
                    <input class="input" id="phone" name="phone" type="tel" value="<?= e($v('phone')) ?>" placeholder="0803 000 0000">
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="address">Address <span class="label-hint">· optional</span></label>
                    <input class="input" id="address" name="address" maxlength="255" value="<?= e($v('address')) ?>">
                </div>
            </div>
        </section>

        <section class="card" aria-labelledby="s-plan">
            <div class="card-header"><h2 id="s-plan" class="card-title">Plan</h2></div>
            <fieldset class="card-body grid gap-3 sm:grid-cols-2">
                <legend class="sr-only">Choose a plan</legend>
                <?php foreach ($plans as $plan): ?>
                    <label class="relative flex cursor-pointer flex-col rounded-2xl border border-line p-4 transition hover:border-brand/60 has-[:checked]:border-brand has-[:checked]:bg-brand/5 has-[:checked]:ring-2 has-[:checked]:ring-brand/20">
                        <input type="radio" name="plan_id" value="<?= (int) $plan['id'] ?>" class="peer sr-only" <?= $selectedPlan === (int) $plan['id'] ? 'checked' : '' ?>>
                        <span class="flex items-center justify-between gap-2">
                            <span class="font-display font-semibold text-ink"><?= e($plan['name']) ?></span>
                            <span class="hidden text-brand peer-checked:block"><?= icon('check-circle', 'h-5 w-5') ?></span>
                        </span>
                        <span class="mt-1 text-sm text-muted"><?= e($plan['description']) ?></span>
                        <span class="mt-3 text-sm font-semibold text-ink">
                            <?= (int) $plan['price_per_student_kobo'] > 0 ? e(naira((int) $plan['price_per_student_kobo'])) . ' <span class="font-normal text-muted">per student / term</span>' : ($plan['code'] === 'trial' ? 'Free for 30 days' : 'Custom pricing') ?>
                        </span>
                        <span class="mt-1 text-xs text-subtle"><?= $plan['max_students'] ? 'Up to ' . number_format((int) $plan['max_students']) . ' students' : 'Unlimited students' ?> · <?= count($plan['features']) ?> features</span>
                    </label>
                <?php endforeach; ?>
                <?= field_error($errors, 'plan_id') ?>
            </fieldset>
        </section>
    </div>

    <div class="space-y-6">
        <section class="card" aria-labelledby="s-admin">
            <div class="card-header"><h2 id="s-admin" class="card-title">School administrator</h2></div>
            <div class="card-body space-y-5">
                <div>
                    <label class="label" for="admin_name">Full name</label>
                    <input class="input <?= $invalid('admin_name') ?>" id="admin_name" name="admin_name" required value="<?= e($v('admin_name')) ?>" autocomplete="off">
                    <?= field_error($errors, 'admin_name') ?>
                </div>
                <div>
                    <label class="label" for="admin_email">Email (used to sign in)</label>
                    <input class="input <?= $invalid('admin_email') ?>" id="admin_email" name="admin_email" type="email" required value="<?= e($v('admin_email')) ?>" autocomplete="off">
                    <?= field_error($errors, 'admin_email') ?>
                </div>
                <div>
                    <label class="label" for="admin_phone">Phone <span class="label-hint">· optional</span></label>
                    <input class="input" id="admin_phone" name="admin_phone" type="tel" value="<?= e($v('admin_phone')) ?>">
                </div>
                <p class="flex gap-2 rounded-xl bg-canvas p-3 text-xs text-muted"><?= icon('key', 'h-4 w-4 shrink-0 text-subtle') ?>A secure temporary password is generated. They must change it at first sign-in.</p>
            </div>
        </section>

        <section class="card" aria-labelledby="s-brand">
            <div class="card-header"><h2 id="s-brand" class="card-title">Brand colour</h2></div>
            <div class="card-body">
                <div class="flex items-center gap-3">
                    <input type="color" class="h-11 w-14 cursor-pointer rounded-xl border border-line bg-card p-1" value="<?= e($v('primary_color', '#1E40AF')) ?>" data-color-picker aria-label="Pick a colour">
                    <input class="input font-mono uppercase <?= $invalid('primary_color') ?>" name="primary_color" value="<?= e($v('primary_color', '#1E40AF')) ?>" maxlength="7" data-color-text aria-label="Colour hex code">
                </div>
                <?= field_error($errors, 'primary_color') ?>
                <div class="mt-4 flex flex-wrap gap-2" aria-label="Suggested colours">
                    <?php foreach (['#1E40AF', '#047857', '#B91C1C', '#7C3AED', '#C2410C', '#0F766E', '#BE185D', '#111827'] as $swatch): ?>
                        <button type="button" class="h-8 w-8 rounded-lg ring-1 ring-black/10 transition hover:scale-110 swatch-<?= substr($swatch, 1) ?>" data-swatch="<?= $swatch ?>" aria-label="Use <?= $swatch ?>"></button>
                    <?php endforeach; ?>
                </div>
                <style nonce="<?= e($cspNonce) ?>"><?php foreach (['1E40AF', '047857', 'B91C1C', '7C3AED', 'C2410C', '0F766E', 'BE185D', '111827'] as $s) { echo ".swatch-{$s}{background:#{$s}}"; } ?></style>
                <div class="mt-5 rounded-xl border border-line p-3" data-brand-preview>
                    <p class="text-xs font-medium text-muted">Preview</p>
                    <button type="button" class="mt-2 inline-flex min-h-10 items-center rounded-xl px-4 text-sm font-semibold" data-brand-sample tabindex="-1">Start exam</button>
                </div>
            </div>
        </section>

        <div class="flex flex-col gap-2 lg:sticky lg:top-24">
            <button type="submit" class="btn btn-primary btn-lg"><?= icon('check') ?><span data-label>Create school</span></button>
            <a href="<?= e(url('/console/schools')) ?>" class="btn btn-ghost">Cancel</a>
        </div>
    </div>
</form>
