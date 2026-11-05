<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $assignments */
$byClass = [];
foreach ($assignments as $a) {
    $byClass[$a['class_name']][] = $a['subject_name'];
}
?>
<p class="eyebrow">Teaching</p>
<h1 class="mt-2 font-display text-[30px] font-semibold text-ink">My classes</h1>
<p class="mt-1 text-sm text-muted">The classes and subjects your administrator assigned to you. You will set questions and exams for these.</p>
<?php if ($byClass === []): ?>
    <div class="card empty mt-6"><span class="icon-tile h-14 w-14 rounded-3xl"><?= icon('school', 'h-6 w-6') ?></span><p class="font-display text-xl font-semibold text-ink">No classes assigned yet</p><p class="text-sm text-muted">Ask your school administrator to assign you to a class and subject.</p></div>
<?php else: ?>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" data-stagger>
        <?php foreach ($byClass as $className => $subjectNames): ?>
            <section class="card card-hover p-6">
                <div class="flex items-center gap-3"><span class="icon-tile"><?= icon('school') ?></span><h2 class="card-title"><?= e($className) ?></h2></div>
                <ul class="mt-5 flex flex-wrap gap-2"><?php foreach ($subjectNames as $name): ?><li class="badge badge-brand py-1.5"><?= e($name) ?></li><?php endforeach; ?></ul>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
