<?php
declare(strict_types=1);
/** One or many report cards. @var list<array> $cards @var bool $editable @var array|null $term */
?>
<?php if ($editable && $cards !== []): $card = $cards[0]; ?>
    <form method="post" action="<?= e(url('/school/results/students/' . $card['student']['id'] . '/remarks')) ?>" class="print-hide card mb-6 p-5" data-submit-once>
        <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="term" value="<?= (int) $term['id'] ?>">
        <p class="card-title">Comments for this report card</p>
        <p class="mt-1 text-xs text-muted">Leave empty to use the automatic comment based on the average.</p>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label><span class="label">Class teacher's comment</span><textarea class="textarea" name="teacher_comment" rows="2" maxlength="400" placeholder="<?= e(\ExamCore\Services\ResultService::autoComment($card['row']['average'] ?? null)) ?>"><?= e((string) $card['remarks']['teacher_comment']) ?></textarea></label>
            <label><span class="label">Principal's comment</span><textarea class="textarea" name="principal_comment" rows="2" maxlength="400" placeholder="<?= e(\ExamCore\Services\ResultService::autoComment($card['row']['average'] ?? null)) ?>"><?= e((string) $card['remarks']['principal_comment']) ?></textarea></label>
        </div>
        <div class="mt-4 flex justify-end"><button class="btn btn-primary btn-sm"><?= icon('check') ?><span data-label>Save comments</span></button></div>
    </form>
<?php endif; ?>
<?php foreach ($cards as $card): ?>
    <?php require __DIR__ . '/../../partials/report-card.php'; ?>
<?php endforeach; ?>
<?php if ($cards === []): ?>
    <div class="card empty"><p class="font-display text-xl font-semibold text-ink">No report cards</p><p class="text-sm text-muted">There are no students or no term selected.</p></div>
<?php endif; ?>
