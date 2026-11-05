<?php
declare(strict_types=1);
/**
 * @var array<string,mixed> $school @var array<string,mixed>|null $admin @var list<array<string,mixed>> $plans @var list<string> $states
 * @var array{email:string,password:string}|null $credentials @var list<array<string,mixed>> $activity @var string $tab
 * @var array<string,string> $errors @var array<string,mixed> $old
 */
$v = static fn (string $key): string => (string) ($old[$key] ?? $school[$key] ?? '');
$invalid = static fn (string $key): string => isset($errors[$key]) ? 'input-error" aria-invalid="true" aria-describedby="' . $key . '-error' : '';
$statusBadge = ['trial' => 'badge-blue', 'active' => 'badge-green', 'suspended' => 'badge-amber', 'archived' => 'badge-gray'];
$base = '/console/schools/' . (int) $school['id'];
$tabs = ['details' => 'Details', 'branding' => 'Branding', 'plan' => 'Plan & limits', 'danger' => 'Status'];
$row = $school;
?>
<a href="<?= e(url('/console/schools')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Schools</a>

<div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex items-center gap-4">
        <?php $avatarSize = 'h-14 w-14'; require __DIR__ . '/../../partials/portal/school-avatar.php'; ?>
        <div class="min-w-0">
            <h1 class="truncate font-display text-2xl font-semibold text-ink"><?= e($school['name']) ?></h1>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted">
                <span class="badge badge-dot <?= $statusBadge[$school['status']] ?? 'badge-gray' ?>"><?= e(ucfirst((string) $school['status'])) ?></span>
                <span class="badge badge-brand"><?= e($school['plan_name']) ?></span>
                <?php if ($school['state']): ?><span><?= e($school['state']) ?></span><?php endif; ?>
                <span class="text-subtle">· Added <?= e(local_date((string) $school['created_at'], 'j M Y')) ?></span>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <form method="post" action="<?= e(url($base . '/reset-admin-password')) ?>" data-confirm="The administrator’s current password will stop working immediately. A new temporary password will be shown to you once." data-confirm-title="Reset administrator password?" data-confirm-ok="Reset password">
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <button class="btn btn-secondary" <?= $admin === null ? 'disabled' : '' ?>><?= icon('key') ?>Reset admin password</button>
        </form>
        <form method="post" action="<?= e(url($base . '/impersonate')) ?>">
            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
            <button class="btn btn-primary" <?= $admin === null ? 'disabled' : '' ?>><?= icon('eye') ?>Sign in as admin</button>
        </form>
    </div>
</div>

<?php if (is_array($credentials)): ?>
    <section class="mt-6 overflow-hidden rounded-2xl border border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40" aria-labelledby="cred-title">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-600 text-white"><?= icon('key', 'h-5 w-5') ?></span>
            <div class="flex-1">
                <h2 id="cred-title" class="font-display font-semibold text-emerald-950 dark:text-emerald-100">Administrator sign-in details</h2>
                <p class="mt-1 text-sm text-emerald-900/80 dark:text-emerald-200/80">Copy these now and share them securely. The password is <strong>not stored</strong> and will not be shown again. The administrator must change it at first sign-in.</p>
                <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                    <?php foreach ([['Sign-in page', url('/login')], ['Email', $credentials['email']], ['Temporary password', $credentials['password']]] as [$label, $value]): ?>
                        <div class="rounded-xl bg-white/80 p-3 ring-1 ring-emerald-200 dark:bg-emerald-950 dark:ring-emerald-800">
                            <dt class="text-xs font-medium text-emerald-800 dark:text-emerald-300"><?= e($label) ?></dt>
                            <dd class="mt-1 flex items-center justify-between gap-2">
                                <span class="truncate font-mono text-sm font-semibold text-emerald-950 dark:text-emerald-50"><?= e($value) ?></span>
                                <button type="button" class="shrink-0 rounded-lg p-1.5 text-emerald-700 hover:bg-emerald-100 dark:text-emerald-300 dark:hover:bg-emerald-900" data-copy="<?= e($value) ?>" aria-label="Copy <?= e(strtolower($label)) ?>"><?= icon('copy', 'h-4 w-4') ?></button>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
    </section>
<?php endif; ?>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <nav class="tabs" aria-label="School settings">
            <?php foreach ($tabs as $key => $label): ?>
                <a class="tab" href="<?= e(url($base . '?tab=' . $key)) ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="card mt-5">
        <?php if ($tab === 'details'): ?>
            <form method="post" action="<?= e(url($base)) ?>" class="card-body grid gap-5 sm:grid-cols-2" data-submit-once novalidate>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="tab" value="details">
                <div class="sm:col-span-2">
                    <label class="label" for="name">School name</label>
                    <input class="input <?= $invalid('name') ?>" id="name" name="name" required maxlength="160" value="<?= e($v('name')) ?>">
                    <?= field_error($errors, 'name') ?>
                </div>
                <div>
                    <label class="label" for="short_name">Short name</label>
                    <input class="input" id="short_name" name="short_name" maxlength="40" value="<?= e($v('short_name')) ?>">
                </div>
                <div>
                    <label class="label" for="slug">Web address</label>
                    <input class="input <?= $invalid('slug') ?>" id="slug" name="slug" maxlength="60" value="<?= e($v('slug')) ?>">
                    <?= field_error($errors, 'slug') ?>
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="motto">Motto <span class="label-hint">· shown on result slips</span></label>
                    <input class="input" id="motto" name="motto" maxlength="160" value="<?= e($v('motto')) ?>">
                </div>
                <div>
                    <label class="label" for="email">School email</label>
                    <input class="input <?= $invalid('email') ?>" id="email" name="email" type="email" value="<?= e($v('email')) ?>">
                    <?= field_error($errors, 'email') ?>
                </div>
                <div>
                    <label class="label" for="phone">Phone</label>
                    <input class="input" id="phone" name="phone" type="tel" value="<?= e($v('phone')) ?>">
                </div>
                <div>
                    <label class="label" for="state">State</label>
                    <select class="select" id="state" name="state">
                        <option value="">Choose a state</option>
                        <?php foreach ($states as $state): ?><option <?= $v('state') === $state ? 'selected' : '' ?>><?= e($state) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label" for="address">Address</label>
                    <input class="input" id="address" name="address" maxlength="255" value="<?= e($v('address')) ?>">
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="notes">Private notes <span class="label-hint">· only you can see these</span></label>
                    <textarea class="textarea" id="notes" name="notes" rows="3" placeholder="Contract details, contact person, payment reminders…"><?= e($v('notes')) ?></textarea>
                </div>
                <div class="flex justify-end sm:col-span-2"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save details</span></button></div>
            </form>

        <?php elseif ($tab === 'branding'): ?>
            <div class="card-body space-y-8">
                <form method="post" action="<?= e(url($base . '/logo')) ?>" enctype="multipart/form-data" class="flex flex-col gap-5 sm:flex-row sm:items-center" data-submit-once>
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                    <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-2xl border border-dashed border-line bg-canvas" data-logo-preview>
                        <?php if ($school['logo_path']): ?><img src="<?= e(url('/' . $school['logo_path'])) ?>" alt="Current logo" class="h-full w-full object-contain p-1"><?php else: ?><?= icon('upload', 'h-7 w-7 text-subtle') ?><?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-ink">School logo</p>
                        <p class="mt-1 text-sm text-muted">PNG, JPG or WebP, up to 1 MB. A square logo on a transparent background looks best.</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <label class="btn btn-secondary btn-sm cursor-pointer"><?= icon('upload') ?>Choose image<input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only" data-logo-input required></label>
                            <button class="btn btn-primary btn-sm"><span data-label>Upload</span></button>
                            <span class="text-xs text-muted" data-logo-name></span>
                        </div>
                    </div>
                </form>
                <div class="divider"></div>
                <form method="post" action="<?= e(url($base)) ?>" data-submit-once novalidate>
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="tab" value="branding">
                    <p class="font-semibold text-ink">Brand colour</p>
                    <p class="mt-1 text-sm text-muted">Used for buttons and highlights across the school’s portal and exam screens. Text colour adjusts automatically for readability.</p>
                    <div class="mt-4 flex max-w-sm items-center gap-3">
                        <input type="color" class="h-11 w-14 cursor-pointer rounded-xl border border-line bg-card p-1" value="<?= e($v('primary_color')) ?>" data-color-picker aria-label="Pick a colour">
                        <input class="input font-mono uppercase <?= $invalid('primary_color') ?>" name="primary_color" value="<?= e($v('primary_color')) ?>" maxlength="7" data-color-text aria-label="Colour hex code">
                    </div>
                    <?= field_error($errors, 'primary_color') ?>
                    <div class="mt-4 flex items-center gap-3" data-brand-preview>
                        <button type="button" class="inline-flex min-h-10 items-center rounded-xl px-4 text-sm font-semibold" data-brand-sample tabindex="-1">Start exam</button>
                        <span class="text-xs text-muted">Live preview</span>
                    </div>
                    <div class="mt-6 flex justify-end"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save colour</span></button></div>
                </form>
                <div class="divider"></div>
                <form method="post" action="<?= e(url($base)) ?>" data-submit-once novalidate>
                    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="tab" value="branding">
                    <label class="label" for="custom_domain">Custom domain <span class="label-hint">· optional, Enterprise</span></label>
                    <input class="input max-w-md <?= $invalid('custom_domain') ?>" id="custom_domain" name="custom_domain" value="<?= e($v('custom_domain')) ?>" placeholder="cbt.yourschool.ng">
                    <?= field_error($errors, 'custom_domain') ?: '<p class="help">The school points this domain’s DNS at your server. Leave blank to use the platform address.</p>' ?>
                    <div class="mt-4 flex justify-end"><button class="btn btn-secondary"><span data-label>Save domain</span></button></div>
                </form>
            </div>

        <?php elseif ($tab === 'plan'): ?>
            <form method="post" action="<?= e(url($base)) ?>" class="card-body space-y-6" data-submit-once novalidate>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="tab" value="plan">
                <fieldset class="grid gap-3 sm:grid-cols-2">
                    <legend class="label">Plan</legend>
                    <?php foreach ($plans as $plan): ?>
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line p-4 transition hover:border-brand/60 has-[:checked]:border-brand has-[:checked]:bg-brand/5">
                            <input type="radio" name="plan_id" value="<?= (int) $plan['id'] ?>" class="mt-1 accent-[rgb(var(--brand))]" <?= (int) $v('plan_id') === (int) $plan['id'] ? 'checked' : '' ?>>
                            <span>
                                <span class="block font-semibold text-ink"><?= e($plan['name']) ?><?= !$plan['is_active'] ? ' <span class="badge badge-gray ml-1">Hidden</span>' : '' ?></span>
                                <span class="block text-xs text-muted"><?= $plan['max_students'] ? 'Up to ' . number_format((int) $plan['max_students']) . ' students' : 'Unlimited students' ?> · <?= count($plan['features']) ?> features</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label" for="max_students_override">Student limit override</label>
                        <input class="input" id="max_students_override" name="max_students_override" type="number" min="1" value="<?= e($v('max_students_override')) ?>" placeholder="Use plan limit">
                        <p class="help">Leave blank to use the plan’s limit.</p>
                    </div>
                    <div>
                        <p class="label">Current limit</p>
                        <p class="text-2xl font-semibold tabular-nums text-ink"><?= $school['max_students'] ? number_format((int) $school['max_students']) : 'Unlimited' ?></p>
                        <?php if ($school['status'] === 'trial' && $school['trial_ends_at']): ?><p class="help">Trial ends <?= e(local_date($school['trial_ends_at'] . ' 12:00:00', 'j F Y')) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="flex justify-end"><button class="btn btn-primary"><?= icon('check') ?><span data-label>Save plan</span></button></div>
            </form>

        <?php else: ?>
            <div class="card-body space-y-4">
                <p class="text-sm text-muted">Suspending stops the school’s staff and students from signing in. Nothing is deleted, and you can reactivate at any time.</p>
                <?php foreach ([
                    'active' => ['Activate', 'Staff and students can use the portal.', 'btn-primary', 'check-circle'],
                    'trial' => ['Move to trial', 'Treat as a trial school.', 'btn-secondary', 'clock'],
                    'suspended' => ['Suspend', 'Block sign-in, for example for unpaid invoices.', 'btn-danger-soft', 'alert'],
                    'archived' => ['Archive', 'The school has left the platform. Data is kept for records.', 'btn-danger-soft', 'trash'],
                ] as $status => [$label, $description, $btn, $iconName]): ?>
                    <?php if ($school['status'] === $status) { continue; } ?>
                    <form method="post" action="<?= e(url($base . '/status')) ?>" class="flex flex-col gap-3 rounded-2xl border border-line p-4 sm:flex-row sm:items-center sm:justify-between"
                          <?= in_array($status, ['suspended', 'archived'], true) ? 'data-confirm="' . e($description) . ' Signed-in users will be signed out on their next click." data-confirm-title="' . e($label) . ' ' . e($school['name']) . '?" data-confirm-ok="' . e($label) . '"' : '' ?>>
                        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="status" value="<?= $status ?>">
                        <div>
                            <p class="font-semibold text-ink"><?= e($label) ?></p>
                            <p class="text-sm text-muted"><?= e($description) ?></p>
                            <?php if (in_array($status, ['suspended', 'archived'], true)): ?>
                                <input class="input mt-2 max-w-sm" name="reason" maxlength="200" placeholder="Reason (saved in the audit log)">
                            <?php endif; ?>
                        </div>
                        <button class="btn <?= $btn ?> shrink-0"><?= icon($iconName) ?><?= e($label) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <aside class="space-y-6">
        <section class="card" aria-labelledby="admin-title">
            <div class="card-header"><h2 id="admin-title" class="card-title">Administrator</h2></div>
            <?php if ($admin === null): ?>
                <p class="card-body text-sm text-muted">No administrator account.</p>
            <?php else: ?>
                <div class="card-body">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-brand/10 font-semibold text-brand"><?= e(initials((string) $admin['name'])) ?></span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink"><?= e($admin['name']) ?></p>
                            <p class="truncate text-sm text-muted"><?= e($admin['email']) ?></p>
                        </div>
                    </div>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-muted">Last sign-in</dt><dd class="text-ink"><?= e(time_ago($admin['last_login_at'])) ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-muted">Password</dt><dd><?= (int) $admin['must_change_password'] === 1 ? '<span class="badge badge-amber">Temporary</span>' : '<span class="badge badge-green">Set</span>' ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-muted">Two-step</dt><dd><?= $admin['two_factor_confirmed_at'] ? '<span class="badge badge-green">On</span>' : '<span class="badge badge-gray">Off</span>' ?></dd></div>
                        <?php if ($admin['locked_until'] && strtotime($admin['locked_until'] . ' UTC') > time()): ?>
                            <div class="flex justify-between gap-3"><dt class="text-muted">Status</dt><dd><span class="badge badge-red">Locked out</span></dd></div>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endif; ?>
        </section>

        <section class="card" aria-labelledby="log-title">
            <div class="card-header"><h2 id="log-title" class="card-title">Activity</h2><a class="link text-sm" href="<?= e(url('/console/audit?school=' . (int) $school['id'])) ?>">All</a></div>
            <?php if ($activity === []): ?>
                <p class="card-body text-sm text-muted">No activity yet.</p>
            <?php else: ?>
                <ul class="divide-y divide-line">
                    <?php foreach ($activity as $event): ?>
                        <li class="px-5 py-3">
                            <p class="text-sm text-ink"><?= e($event['summary']) ?></p>
                            <p class="mt-0.5 text-xs text-subtle"><?= e($event['actor_name'] ?? 'System') ?><?= $event['impersonator_id'] ? ' (via support)' : '' ?> · <?= e(time_ago((string) $event['created_at'])) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </aside>
</div>
