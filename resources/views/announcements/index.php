<?php
declare(strict_types=1);
/**
 * Announcements page shared by every role.
 * @var list<array<string,mixed>> $feed @var list<array<string,mixed>> $sent @var bool $canPost @var list<array<string,mixed>> $myClasses
 * @var array<string,string> $audiences @var string $actionBase @var bool $isTeacher
 */
$ago = static function (string $at): string {
    $s = max(0, time() - (strtotime($at . ' UTC') ?: time()));
    return match (true) {
        $s < 90 => 'just now',
        $s < 3600 => (int) floor($s / 60) . ' min ago',
        $s < 86400 => (int) floor($s / 3600) . ' h ago',
        $s < 604800 => (int) floor($s / 86400) . ' d ago',
        default => gmdate('j M Y', strtotime($at . ' UTC') ?: time()),
    };
};
$who = static fn (array $a): string => $a['school_id'] === null ? 'Platform' : (string) $a['author_name'];
$audienceLabel = static fn (array $a): string => ($a['audience'] === 'students' ? ($a['class_name'] ? 'Class ' . $a['class_name'] : 'All students') : ($a['audience'] === 'teachers' ? 'Teachers and staff' : 'Everyone'));
?>
<div class="grid gap-6 <?= $canPost ? 'xl:grid-cols-[minmax(0,1fr)_380px]' : '' ?>">
    <section aria-labelledby="ann-feed">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 id="ann-feed" class="font-display text-xl font-semibold text-ink">For you</h2>
            <span class="badge badge-gray"><?= count($feed) ?></span>
        </div>
        <?php if ($feed === []): ?>
            <div class="card empty"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('mail', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">Nothing new</p><p class="text-sm text-muted">Notices from your school appear here.</p></div>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($feed as $a): ?>
                    <li class="card p-5 <?= $a['is_read'] ? '' : 'ring-2 ring-brand/30' ?>">
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if (!$a['is_read']): ?><span class="badge badge-brand">New</span><?php endif; ?>
                            <?php if ($a['pinned']): ?><span class="badge badge-gray">Pinned</span><?php endif; ?>
                            <span class="text-xs text-muted"><?= e($who($a)) ?> · <?= e($ago((string) $a['created_at'])) ?></span>
                        </div>
                        <h3 class="mt-2 font-display text-lg font-semibold text-ink"><?= e($a['title']) ?></h3>
                        <p class="mt-1 whitespace-pre-line break-words text-sm leading-7 text-ink"><?= e($a['body']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($canPost && $sent !== []): ?>
            <div class="mb-4 mt-10 flex items-center justify-between gap-3"><h2 class="font-display text-xl font-semibold text-ink"><?= $isTeacher ? 'Posted by you' : 'Posted' ?></h2><span class="badge badge-gray"><?= count($sent) ?></span></div>
            <ul class="space-y-3">
                <?php foreach ($sent as $a): ?>
                    <li class="card flex items-start gap-3 p-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink"><?= e($a['title']) ?></p>
                            <p class="text-xs text-muted">To <?= e($audienceLabel($a)) ?> · <?= e($ago((string) $a['created_at'])) ?> · by <?= e($a['author_name']) ?></p>
                        </div>
                        <form method="post" action="<?= e(url($actionBase . '/' . (int) $a['id'] . '/delete')) ?>">
                            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                            <button class="btn btn-ghost btn-icon" type="submit" aria-label="Remove this announcement" data-confirm="Remove this announcement for everyone?"><?= icon('trash', 'h-4 w-4') ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($canPost): ?>
        <aside>
            <form method="post" action="<?= e(url($actionBase)) ?>" class="card sticky top-28 space-y-4 p-5" novalidate>
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <p class="card-title">Post an announcement</p>
                <?php if ($isTeacher && $myClasses === []): ?>
                    <p class="rounded-2xl bg-soft p-4 text-sm text-muted">You are not set as a class teacher yet. Ask your school administrator to assign you a class, then you can post to its students.</p>
                <?php else: ?>
                    <div>
                        <label class="label" for="ann-title">Title</label>
                        <input class="input <?= isset($errors['title']) ? 'input-error' : '' ?>" id="ann-title" name="title" maxlength="160" required value="<?= e((string) ($old['title'] ?? '')) ?>" placeholder="e.g. Mock exam starts Monday">
                        <?= field_error($errors, 'title') ?>
                    </div>
                    <div>
                        <label class="label" for="ann-body">Message</label>
                        <textarea class="textarea min-h-32 <?= isset($errors['body']) ? 'input-error' : '' ?>" id="ann-body" name="body" maxlength="3000" required placeholder="Write the notice…"><?= e((string) ($old['body'] ?? '')) ?></textarea>
                        <?= field_error($errors, 'body') ?>
                    </div>
                    <?php if (count($audiences) > 1): ?>
                        <div>
                            <label class="label" for="ann-aud">Send to</label>
                            <select class="select" id="ann-aud" name="audience">
                                <?php foreach ($audiences as $key => $label): ?><option value="<?= e($key) ?>" <?= ($old['audience'] ?? 'students') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <?php if ($myClasses !== []): ?>
                        <div>
                            <label class="label" for="ann-class"><?= $isTeacher ? 'Class' : 'Class (students only)' ?></label>
                            <select class="select <?= isset($errors['class_id']) ? 'input-error' : '' ?>" id="ann-class" name="class_id">
                                <option value="0"><?= $isTeacher ? 'Choose a class' : 'All classes' ?></option>
                                <?php foreach ($myClasses as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) ($old['class_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                            </select>
                            <?= field_error($errors, 'class_id') ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!$isTeacher): ?>
                        <label class="flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="pinned" value="1" class="checkbox"> Pin to the top</label>
                    <?php endif; ?>
                    <button class="btn btn-primary w-full" type="submit"><?= icon('mail', 'h-4 w-4') ?><span>Post announcement</span></button>
                <?php endif; ?>
            </form>
        </aside>
    <?php endif; ?>
</div>
