<?php
declare(strict_types=1);
$navigation = [
    ['Home', '#home'],
    ['Features', '#features'],
    ['How it works', '#how-it-works'],
    ['Pricing', '#pricing'],
    ['FAQ', '#faq'],
    ['Contact', '#contact'],
];
?>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header" data-site-header>
    <div class="container header-inner">
        <a class="brand" href="<?= e($homeUrl) ?>" aria-label="ExamCore CBT home">
            <svg class="brand-mark" viewBox="0 0 40 40" role="img" aria-label="">
                <path d="M5 8.5 20 2l15 6.5v13c0 8.4-6.1 13.2-15 16C11.1 34.7 5 29.9 5 21.5v-13Z" fill="currentColor"/>
                <path d="M13 13h14M13 19h14M13 25h8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2.5"/>
            </svg>
            <span>ExamCore <span class="brand-light">CBT</span></span>
        </a>

        <nav class="desktop-nav" aria-label="Main navigation">
            <?php foreach ($navigation as [$label, $href]): ?>
                <a href="<?= e($href) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <a class="button button-quiet sign-in-link" href="#contact" data-coming-soon="The sign-in portal will be available in a later release.">Sign in</a>
            <a class="button button-amber header-cta" href="#pricing">Get started</a>
            <button class="icon-button menu-toggle" type="button" aria-label="Open navigation menu" aria-controls="mobile-menu" aria-expanded="false" data-menu-open>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    <div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-labelledby="mobile-menu-title" hidden>
        <div class="mobile-menu-top">
            <span class="brand" id="mobile-menu-title">
                <svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true"><path d="M5 8.5 20 2l15 6.5v13c0 8.4-6.1 13.2-15 16C11.1 34.7 5 29.9 5 21.5v-13Z" fill="currentColor"/><path d="M13 13h14M13 19h14M13 25h8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2.5"/></svg>
                <span>ExamCore <span class="brand-light">CBT</span></span>
            </span>
            <button class="icon-button menu-close" type="button" aria-label="Close navigation menu" data-menu-close>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <nav class="mobile-nav" aria-label="Mobile navigation">
            <?php foreach ($navigation as [$label, $href]): ?>
                <a href="<?= e($href) ?>" data-menu-link><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="mobile-menu-actions">
            <a class="button button-outline" href="#contact" data-menu-link data-coming-soon="The sign-in portal will be available in a later release.">Sign in</a>
            <a class="button button-amber" href="#pricing" data-menu-link>Get started</a>
        </div>
        <p class="mobile-menu-note">A foundation for secure, flexible assessments.</p>
    </div>
</header>