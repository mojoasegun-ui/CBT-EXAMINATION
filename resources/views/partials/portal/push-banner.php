<?php
declare(strict_types=1);
/** "Get alerts on this phone" card. Hidden until push.js decides it is useful (supported, not yet on, not blocked). */
$pushKey = \ExamCore\Services\PushService::publicKey();
$pushText ??= 'Hear about new exams, results and school announcements the moment they are posted, even when the app is closed.';
$pushTitle ??= 'Get alerts on this phone';
if ($pushKey === '') {
    return;
}
?>
<section class="card mb-6 hidden items-center gap-4 p-4 sm:p-5" data-push-banner data-push-guest="<?= !empty($pushGuest) ? '1' : '0' ?>" data-push-key="<?= e($pushKey) ?>" data-push-base="<?= e(url('/push')) ?>" aria-label="Phone notifications">
    <span class="icon-tile h-12 w-12 shrink-0 rounded-2xl"><?= icon('bell', 'h-6 w-6') ?></span>
    <div class="min-w-0 flex-1">
        <p class="font-display text-[15px] font-semibold text-ink" data-push-title><?= e($pushTitle) ?></p>
        <p class="mt-0.5 text-sm leading-6 text-muted" data-push-text><?= e($pushText) ?></p>
    </div>
    <button type="button" class="btn btn-primary shrink-0" data-push-enable><?= icon('bell', 'h-4 w-4') ?><span>Turn on</span></button>
    <button type="button" class="btn btn-ghost btn-icon shrink-0" data-push-dismiss aria-label="Not now"><?= icon('x', 'h-4 w-4') ?></button>
</section>
