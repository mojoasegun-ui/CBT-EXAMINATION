<?php
declare(strict_types=1);
?>
<footer class="site-footer" id="contact">
    <div class="container footer-grid">
        <div class="footer-brand-column">
            <a class="brand footer-brand" href="<?= e($homeUrl) ?>" aria-label="ExamCore CBT home">
                <svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true"><path d="M5 8.5 20 2l15 6.5v13c0 8.4-6.1 13.2-15 16C11.1 34.7 5 29.9 5 21.5v-13Z" fill="currentColor"/><path d="M13 13h14M13 19h14M13 25h8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2.5"/></svg>
                <span>ExamCore <span class="brand-light">CBT</span></span>
            </a>
            <p class="footer-tagline">A thoughtful foundation for institutions building better assessment experiences.</p>
            <div class="social-links" aria-label="Social profiles coming soon">
                <a href="#contact" aria-label="LinkedIn profile coming soon" data-coming-soon="ExamCore social profiles will be announced later."><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9v10M5 5.5v.1M10 19v-6a4 4 0 0 1 8 0v6M10 10v9"/></svg></a>
                <a href="#contact" aria-label="YouTube channel coming soon" data-coming-soon="ExamCore social profiles will be announced later."><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8.2a2.5 2.5 0 0 0-1.8-1.8C17.6 6 12 6 12 6s-5.6 0-7.2.4A2.5 2.5 0 0 0 3 8.2 26 26 0 0 0 2.6 12c0 1.3.1 2.6.4 3.8a2.5 2.5 0 0 0 1.8 1.8c1.6.4 7.2.4 7.2.4s5.6 0 7.2-.4a2.5 2.5 0 0 0 1.8-1.8c.3-1.2.4-2.5.4-3.8s-.1-2.6-.4-3.8Z"/><path d="m10 9.5 5 2.5-5 2.5v-5Z"/></svg></a>
                <a href="#contact" aria-label="X profile coming soon" data-coming-soon="ExamCore social profiles will be announced later."><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 4 14 16M19 4 5 20M6 4h4l8 16h-4L6 4Z"/></svg></a>
            </div>
        </div>

        <div class="footer-column">
            <h2>Product</h2>
            <a href="#features">Features</a>
            <a href="#pricing">Pricing</a>
            <a href="#why-examcore">Security</a>
            <a href="#showcase">Roadmap</a>
        </div>
        <div class="footer-column">
            <h2>Company</h2>
            <a href="#why-examcore">About ExamCore</a>
            <a href="#contact" data-coming-soon="Careers information will be published later.">Careers</a>
            <a href="#showcase">Product updates</a>
            <a href="#contact">Contact</a>
        </div>
        <div class="footer-column footer-legal-column">
            <h2>Legal &amp; updates</h2>
            <a href="#contact" data-coming-soon="Legal documents will be published before launch.">Privacy</a>
            <a href="#contact" data-coming-soon="Legal documents will be published before launch.">Terms</a>
            <a href="#contact" data-coming-soon="Legal documents will be published before launch.">DPA</a>
            <a href="<?= e(rtrim((string) $baseUrl, '/') . '/health') ?>">System status</a>
            <form class="newsletter-form" action="#" method="post" data-newsletter>
                <!-- TODO: Connect newsletter signup to an approved backend endpoint. -->
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken ?? '') ?>">
                <label for="newsletter-email">Product notes, occasionally</label>
                <div class="newsletter-controls">
                    <input id="newsletter-email" name="email" type="email" autocomplete="email" placeholder="you@school.edu" required>
                    <button class="icon-button newsletter-submit" type="submit" aria-label="Join product updates">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15M13 5l7 7-7 7"/></svg>
                    </button>
                </div>
                <p class="newsletter-status" data-newsletter-status aria-live="polite"></p>
            </form>
        </div>
    </div>
    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= e($appName) ?>. All rights reserved.</p>
        <p>Made for education, built to integrate.</p>
        <button class="language-switcher" type="button" disabled aria-label="Language selector coming soon">English <span aria-hidden="true">⌄</span></button>
    </div>
</footer>