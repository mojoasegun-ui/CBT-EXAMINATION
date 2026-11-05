<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $plans @var array<string,string> $features @var array<string,string> $errors */
?>
<h1 class="font-display text-2xl font-semibold text-ink">Plans &amp; features</h1>
<p class="mt-1 text-sm text-muted">What each plan costs, how many students it allows and which features it switches on. Changes apply to every school on that plan immediately.</p>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <?php foreach ($plans as $plan): $id = (int) $plan['id']; ?>
        <form id="plan-<?= $id ?>" method="post" action="<?= e(url('/console/plans/' . $id)) ?>" class="card flex flex-col scroll-mt-24" data-submit-once>
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand/10 text-brand"><?= icon('layers', 'h-5 w-5') ?></span>
                    <div>
                        <p class="card-title"><?= e($plan['name']) ?></p>
                        <p class="text-xs text-muted"><?= (int) $plan['school_count'] ?> school<?= (int) $plan['school_count'] === 1 ? '' : 's' ?> · code <span class="font-mono"><?= e($plan['code']) ?></span></p>
                    </div>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-muted">
                    <input type="checkbox" name="is_active" value="1" class="peer sr-only" <?= $plan['is_active'] ? 'checked' : '' ?>>
                    <span class="relative h-6 w-11 rounded-full bg-ink/15 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-brand peer-checked:after:translate-x-5 peer-focus-visible:ring-4 peer-focus-visible:ring-brand/20"></span>
                    <span>Offered</span>
                </label>
            </div>
            <div class="card-body grid flex-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="name-<?= $id ?>">Name</label>
                    <input class="input <?= isset($errors['name_' . $id]) ? 'input-error' : '' ?>" id="name-<?= $id ?>" name="name" value="<?= e($plan['name']) ?>" required maxlength="80">
                    <?= field_error($errors, 'name_' . $id) ?>
                </div>
                <div>
                    <label class="label" for="price-<?= $id ?>">Price per student / term</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3.5 grid place-items-center text-muted">₦</span>
                        <input class="input pl-8 tabular-nums" id="price-<?= $id ?>" name="price" inputmode="decimal" value="<?= e((string) ((int) $plan['price_per_student_kobo'] / 100)) ?>">
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="desc-<?= $id ?>">Description</label>
                    <input class="input" id="desc-<?= $id ?>" name="description" maxlength="255" value="<?= e($plan['description']) ?>">
                </div>
                <div>
                    <label class="label" for="max-<?= $id ?>">Student limit</label>
                    <input class="input tabular-nums" id="max-<?= $id ?>" name="max_students" type="number" min="1" value="<?= e((string) ($plan['max_students'] ?? '')) ?>" placeholder="Unlimited">
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="label">Features</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <?php foreach ($features as $code => $label): ?>
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line px-3 py-2.5 text-sm transition hover:bg-canvas has-[:checked]:border-brand/40 has-[:checked]:bg-brand/5">
                                <input type="checkbox" name="features[]" value="<?= e($code) ?>" class="checkbox" <?= in_array($code, $plan['features'], true) ? 'checked' : '' ?>>
                                <span class="text-ink"><?= e($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            </div>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save <?= e($plan['name']) ?></span></button></div>
        </form>
    <?php endforeach; ?>
</div>
