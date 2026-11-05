<?php
declare(strict_types=1);
/** Parent result checker. @var string $slug @var array $errors @var array $old */
?>
<p class="eyebrow">For parents &amp; guardians</p>
<h1 class="mt-3 font-display text-[32px] font-semibold leading-tight text-ink">Check a result</h1>
<p class="mt-3 text-[15px] leading-7 text-muted">Enter the school code, your child's admission number and the result code printed on the report card or given by the school.</p>
<form method="post" action="<?= e(url('/check-result')) ?>" class="mt-8 space-y-5" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <?= field('school', 'School code', (string) ($old['school'] ?? $slug), $errors, ['placeholder' => 'e.g. kings-college', 'attrs' => ' autocapitalize="none" autocomplete="off"', 'required' => true]) ?>
    <?= field('admission_no', 'Admission number', (string) ($old['admission_no'] ?? ''), $errors, ['placeholder' => 'e.g. KC/2024/0123', 'attrs' => ' autocapitalize="characters" autocomplete="off"', 'required' => true]) ?>
    <?= field('code', 'Result code', '', $errors, ['placeholder' => '8 letters and numbers', 'attrs' => ' autocapitalize="characters" autocomplete="off" maxlength="12"', 'required' => true]) ?>
    <button class="btn btn-primary btn-lg w-full"><?= icon('search') ?><span data-label>Show result</span></button>
</form>
