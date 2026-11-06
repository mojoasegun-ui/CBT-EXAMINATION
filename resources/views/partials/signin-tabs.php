<?php
declare(strict_types=1);
/** Role tabs shared by the three sign-in pages. @var string $mode student|staff|parent */
$tabs = [
    'student' => ['Student', 'graduation', '/student/login'],
    'staff' => ['Teacher', 'users', '/login'],
    'parent' => ['Parent', 'user', '/check-result'],
];
?>
<nav class="si-tabs" aria-label="Choose how you sign in">
    <?php foreach ($tabs as $key => [$label, $iconName, $href]): ?>
        <a href="<?= e(url($href)) ?>" class="si-tab" <?= $key === $mode ? 'aria-current="page"' : '' ?>><?= icon($iconName, 'h-[18px] w-[18px]') ?><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
</nav>
