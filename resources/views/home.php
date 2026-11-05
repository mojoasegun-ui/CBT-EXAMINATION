<?php
declare(strict_types=1);
$imageRoot = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') . '/assets/images' : './assets/images';
?>
<section class="hero-section" id="home" aria-labelledby="hero-title">
    <div class="hero-orbit hero-orbit-one" aria-hidden="true"></div>
    <div class="hero-orbit hero-orbit-two" aria-hidden="true"></div>
    <div class="container hero-grid">
        <div class="hero-copy" data-reveal>
            <p class="hero-eyebrow"><span class="eyebrow-check" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="m4 10 4 4 8-8"/></svg></span>Built for schools and institutions</p>
            <h1 id="hero-title">Exams that work for <span>every learner.</span></h1>
            <p class="hero-lede">A flexible computer-based testing platform, designed to fit the way your institution teaches, assesses, and grows.</p>
            <div class="hero-actions">
                <a class="button button-primary button-large" href="#pricing">Explore pilot access <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-7-7 7 7-7 7"/></svg></a>
                <a class="button button-video" href="#contact"><span class="play-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 6 10 6-10 6V6Z"/></svg></span>Book a demo</a>
            </div>
            <div class="hero-proof" aria-label="Platform facts">
                <div><strong>PHP 8.1+</strong><span>shared-hosting ready</span></div>
                <div><strong>2 modes</strong><span>standalone or integrated</span></div>
                <div><strong>Phase 1</strong><span>foundation delivered</span></div>
            </div>
            <p class="hero-note">A pre-launch platform foundation. Product workflows are being built in later phases.</p>
        </div>
        <div class="hero-visual" data-reveal data-reveal-delay="120">
            <div class="hero-visual-label"><span class="status-pulse"></span> PRODUCT CONCEPT <span class="label-divider"></span> EXAM EXPERIENCE</div>
            <!-- TODO: Replace the illustrative SVG with an approved product capture at hero-exam.png before launch. -->
            <picture class="hero-picture">
                <source srcset="<?= e($imageRoot) ?>/hero-exam.svg 1x, <?= e($imageRoot) ?>/hero-exam.svg?scale=2 2x" type="image/svg+xml">
                <img src="<?= e($imageRoot) ?>/hero-exam.svg" srcset="<?= e($imageRoot) ?>/hero-exam.svg 1x, <?= e($imageRoot) ?>/hero-exam.svg?scale=2 2x" width="760" height="590" alt="Illustrative concept of a calm, accessible online exam interface" loading="lazy" decoding="async">
            </picture>
            <div class="floating-live-card" aria-label="Illustrative exam question preview">
                <div class="floating-card-top"><span class="live-indicator"></span><span>QUESTION PREVIEW</span><strong>12:48</strong></div>
                <p>What is the main purpose of a thesis statement?</p>
                <div class="floating-answer"><span class="answer-radio"></span> To present the central argument</div>
                <div class="floating-answer muted-answer"><span class="answer-radio"></span> To list every source</div>
                <span class="preview-caption">Illustrative only</span>
            </div>
            <div class="visual-corner-note"><span>01</span> An assessment experience shaped around focus.</div>
        </div>
    </div>
    <div class="container hero-bottom-line"><span>ASSESSMENT INFRASTRUCTURE FOR WHAT COMES NEXT</span><span aria-hidden="true">SCROLL TO EXPLORE ↓</span></div>
</section>

<section class="trust-section" aria-labelledby="trust-title">
    <div class="container trust-inner">
        <p id="trust-title">Designed for every kind of learning community</p>
        <div class="trust-logos" aria-label="Education sectors">
            <div class="trust-logo"><img src="<?= e($imageRoot) ?>/school-1.svg" alt="" width="148" height="42" loading="lazy" decoding="async"><span>Secondary schools</span></div>
            <div class="trust-logo"><img src="<?= e($imageRoot) ?>/school-2.svg" alt="" width="148" height="42" loading="lazy" decoding="async"><span>Colleges</span></div>
            <div class="trust-logo"><img src="<?= e($imageRoot) ?>/school-3.svg" alt="" width="148" height="42" loading="lazy" decoding="async"><span>Universities</span></div>
            <div class="trust-logo"><img src="<?= e($imageRoot) ?>/school-4.svg" alt="" width="148" height="42" loading="lazy" decoding="async"><span>Training teams</span></div>
            <div class="trust-logo"><img src="<?= e($imageRoot) ?>/school-5.svg" alt="" width="148" height="42" loading="lazy" decoding="async"><span>Exam boards</span></div>
            <div class="trust-logo"><img src="<?= e($imageRoot) ?>/school-6.svg" alt="" width="148" height="42" loading="lazy" decoding="async"><span>Learning centers</span></div>
        </div>
        <small>Sector illustrations, not customer logos.</small>
    </div>
</section>

<?php require __DIR__ . '/partials/hero-slider.php'; ?>

<section class="section features-section" id="features" aria-labelledby="features-title">
    <div class="container">
        <div class="section-heading centered-heading" data-reveal>
            <p class="eyebrow">Features on the roadmap</p>
            <h2 id="features-title">Thoughtful tools for the full assessment cycle.</h2>
            <p>These are planned product capabilities. ExamCore Phase 1 delivers the architectural foundation, not these examination workflows.</p>
        </div>
        <div class="features-grid">
            <article class="feature-card" data-reveal>
                <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.2 3 7.6 7.5 9.5 4.5-1.9 7.5-5.3 7.5-9.5V6L12 3Z"/><path d="m9 12 2 2 4-4"/></svg></div>
                <span class="planned-tag">Planned</span><h3>Secure exams</h3><p>Build controlled assessment flows with clear access rules and institution-led security settings.</p>
            </article>
            <article class="feature-card" data-reveal data-reveal-delay="60">
                <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h4M8 16h6"/><path d="m14 13 2 2 4-5"/></svg></div>
                <span class="planned-tag">Planned</span><h3>Instant grading</h3><p>Automate scoring for supported question types and give learners clearer feedback sooner.</p>
            </article>
            <article class="feature-card" data-reveal data-reveal-delay="120">
                <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Z"/><path d="M4 17.5A2.5 2.5 0 0 1 6.5 15H20M8 7h8M8 10.5h6"/></svg></div>
                <span class="planned-tag">Planned</span><h3>Question bank</h3><p>Organize reusable questions by subject, topic, difficulty, and curriculum objective.</p>
            </article>
            <article class="feature-card" data-reveal>
                <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12s3.2-6 9-6 9 6 9 6-3.2 6-9 6-9-6-9-6Z"/><circle cx="12" cy="12" r="2.5"/><path d="m5 4 14 16"/></svg></div>
                <span class="planned-tag">Planned</span><h3>Integrity controls</h3><p>Explore configurable exam integrity options that respect institutional policy and learner needs.</p>
            </article>
            <article class="feature-card" data-reveal data-reveal-delay="60">
                <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 5-6"/><circle cx="7" cy="15" r="1"/><circle cx="11" cy="11" r="1"/><circle cx="14" cy="13" r="1"/><circle cx="19" cy="7" r="1"/></svg></div>
                <span class="planned-tag">Planned</span><h3>Useful analytics</h3><p>Turn completion and performance data into practical insights for teaching teams.</p>
            </article>
            <article class="feature-card" data-reveal data-reveal-delay="120">
                <div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2.5 20v-1.5A4.5 4.5 0 0 1 7 14h2a4.5 4.5 0 0 1 4.5 4.5V20M14 15h2a4 4 0 0 1 4 4v1"/></svg></div>
                <span class="planned-tag">Planned</span><h3>Institution-ready</h3><p>Keep integration replaceable as a platform grows across campuses, schools, and programs.</p>
            </article>
        </div>
    </div>
</section>

<section class="section process-section" id="how-it-works" aria-labelledby="process-title">
    <div class="container process-layout">
        <div class="process-intro" data-reveal>
            <p class="eyebrow">A future-ready workflow</p>
            <h2 id="process-title">From assessment plan to meaningful insight.</h2>
            <p>The product flow is planned in three clear stages. Examination workflows are not part of the current foundation release.</p>
            <a class="text-link" href="#pricing">Discuss a pilot <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-7-7 7 7-7 7"/></svg></a>
        </div>
        <ol class="process-steps">
            <li data-reveal><span class="step-number">01</span><div><h3>Create your exam</h3><p>Shape an assessment around your course, learning goals, and delivery requirements.</p></div></li>
            <li data-reveal data-reveal-delay="90"><span class="step-number">02</span><div><h3>Students take it</h3><p>Give learners a consistent, accessible experience across the devices they use.</p></div></li>
            <li data-reveal data-reveal-delay="180"><span class="step-number">03</span><div><h3>Review results</h3><p>Help educators understand outcomes and plan the next step in learning.</p></div></li>
        </ol>
    </div>
</section>

<section class="section why-section" id="why-examcore" aria-labelledby="why-title">
    <div class="container why-grid">
        <div class="why-visual" data-reveal>
            <div class="why-image-frame">
                <!-- TODO: Replace this architecture illustration with an approved product or institution image before launch. -->
                <img src="<?= e($imageRoot) ?>/why-examcore.svg" width="620" height="500" alt="Illustration of ExamCore connecting a school website, a testing platform, and learners" loading="lazy" decoding="async">
                <span class="why-visual-label">INDEPENDENT BY DESIGN</span>
            </div>
            <div class="why-note"><span class="why-note-mark">EC</span><p><strong>Built to fit your environment.</strong><br>Not the other way around.</p></div>
        </div>
        <div class="why-copy" data-reveal data-reveal-delay="100">
            <p class="eyebrow">Why ExamCore</p>
            <h2 id="why-title">A Solid foundation, ready to meet your institution where it is.</h2>
            <p class="why-lede">A testing platform should complement your school’s systems. ExamCore is being built to stay modular, understandable, and practical to host.</p>
            <ul class="check-list">
                <li><span aria-hidden="true"><svg viewBox="0 0 20 20"><path d="m4 10 4 4 8-8"/></svg></span><div><strong>Integrates by contract</strong><small>Trusted identity adapters avoid assumptions about your website’s sessions or database schema.</small></div></li>
                <li><span aria-hidden="true"><svg viewBox="0 0 20 20"><path d="m4 10 4 4 8-8"/></svg></span><div><strong>Works with shared hosting</strong><small>PHP and MySQL foundations without Redis, workers, or a Node.js server runtime.</small></div></li>
                <li><span aria-hidden="true"><svg viewBox="0 0 20 20"><path d="m4 10 4 4 8-8"/></svg></span><div><strong>Security starts at the core</strong><small>Session, CSRF, validation, prepared-statement, and default-deny authorization foundations.</small></div></li>
                <li><span aria-hidden="true"><svg viewBox="0 0 20 20"><path d="m4 10 4 4 8-8"/></svg></span><div><strong>Designed to extend</strong><small>Independent modules keep future assessment features replaceable and maintainable.</small></div></li>
            </ul>
            <a class="button button-primary" href="#contact">Talk about integration <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-7-7 7 7-7 7"/></svg></a>
        </div>
    </div>
</section>

<section class="section testimonials-section" aria-labelledby="testimonials-title">
    <div class="container">
        <div class="section-heading centered-heading" data-reveal>
            <p class="eyebrow">Partner voices</p>
            <h2 id="testimonials-title">Built with educators, not assumptions.</h2>
            <p>We will publish feedback only after a real institution has used the platform and approved its quote.</p>
        </div>
        <div class="testimonials-grid">
            <?php foreach ([['IA', 'Institution A', 'Assessment lead'], ['IB', 'Institution B', 'Teaching and learning'], ['IC', 'Institution C', 'IT and systems']] as [$initials, $institution, $role]): ?>
                <article class="testimonial-card" data-reveal>
                    <div class="testimonial-top"><span class="avatar-placeholder" aria-hidden="true"><?= e($initials) ?></span><div><h3><?= e($institution) ?></h3><p><?= e($role) ?></p></div></div>
                    <div class="rating-placeholder" aria-label="Rating placeholder, not a customer rating"><span aria-hidden="true">☆ ☆ ☆ ☆ ☆</span><small>RATING PENDING</small></div>
                    <blockquote>Verified partner feedback will be added here after a completed pilot and written approval.</blockquote>
                    <span class="testimonial-status">PRE-LAUNCH PLACEHOLDER</span>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section pricing-section" id="pricing" aria-labelledby="pricing-title">
    <div class="container">
        <div class="section-heading centered-heading" data-reveal>
            <p class="eyebrow">Pilot and plans</p>
            <h2 id="pricing-title">Find the right starting point.</h2>
            <p>Pricing and plan inclusions will be set after institution pilots. No paid plan or self-serve trial is available in this foundation release.</p>
        </div>
        <div class="pricing-grid">
            <article class="pricing-card" data-reveal>
                <p class="plan-name">Free trial</p><p class="plan-price">Preview <span>guided access</span></p>
                <p class="plan-description">Explore the product direction with the ExamCore team.</p>
                <a class="button button-outline" href="#contact">Ask about a preview</a>
                <ul><li>Product walkthrough</li><li>Foundation architecture overview</li><li>Roadmap conversation</li><li>Integration questions</li><li>No payment or account setup</li></ul>
            </article>
            <article class="pricing-card pricing-featured" data-reveal data-reveal-delay="70">
                <span class="popular-badge">Pilot partner</span>
                <p class="plan-name">Institution</p><p class="plan-price">Let's scope it <span>with your team</span></p>
                <p class="plan-description">Shape a pilot around your institution’s needs and systems.</p>
                <a class="button button-primary" href="#contact">Discuss a pilot</a>
                <ul><li>Institution discovery session</li><li>Integration requirements review</li><li>Phased delivery planning</li><li>Hosting and deployment discussion</li><li>Pilot scope agreed together</li></ul>
            </article>
            <article class="pricing-card" data-reveal data-reveal-delay="140">
                <p class="plan-name">Enterprise</p><p class="plan-price">Let's talk <span>custom scope</span></p>
                <p class="plan-description">Plan for complex programs, campuses, and integration needs.</p>
                <a class="button button-outline" href="#contact">Contact the team</a>
                <ul><li>Multi-program discovery</li><li>Identity and data boundaries</li><li>Deployment requirements</li><li>Security review planning</li><li>Roadmap conversation</li></ul>
            </article>
        </div>
        <p class="pricing-footnote">All plan names are exploratory. Availability, features, security reviews, and prices are subject to product development and agreement.</p>
    </div>
</section>

<section class="section faq-section" id="faq" aria-labelledby="faq-title">
    <div class="container faq-layout">
        <div class="faq-intro" data-reveal>
            <p class="eyebrow">Frequently asked questions</p>
            <h2 id="faq-title">A clear answer, even at this stage.</h2>
            <p>ExamCore is in its foundation phase. Here is what is ready and what is still ahead.</p>
            <a class="text-link" href="#contact">Have another question? <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-7-7 7 7-7 7"/></svg></a>
        </div>
        <div class="faq-list" data-reveal data-reveal-delay="90">
            <details class="faq-item"><summary>Is my data secure on these App!?</summary><div class="faq-answer"><div><p>Phase 1 includes secure-session, CSRF, prepared-statement, and default-deny authorization foundations. Production security depends on deployment and future feature reviews; ExamCore does not claim an independent security certification.</p></div></div></details>
            <details class="faq-item"><summary>Can I import existing Questions?</summary><div class="faq-answer"><div><p>Question banks and import workflows are not part of the foundation release. Import options will be evaluated during a later product phase.</p></div></div></details>
            <details class="faq-item"><summary>Does ExamCore work on Mobile?</summary><div class="faq-answer"><div><div><p>This public site is responsive. The student examination experience has not been built yet and will need its own device and accessibility testing.</p></div></div></div></details>
            <details class="faq-item"><summary>Do you support Multi-tenancy?</summary><div class="faq-answer"><div><p>The architecture is modular and integration-ready, but tenant isolation and multi-tenant operations are not implemented. Those requirements need explicit design and testing.</p></div></div></details>
            <details class="faq-item"><summary>What about Proctoring?</summary><div class="faq-answer"><div><p>Proctoring is not implemented. Any future integrity controls will need institution policy, privacy, accessibility, and legal review.</p></div></div></details>
            <details class="faq-item"><summary>How do I migrate from another platform?</summary><div class="faq-answer"><div><p>No migration utility exists in Phase 1. We can document your current identity and data needs as part of a future pilot discussion; no host database is modified by this foundation.</p></div></div></details>
        </div>
    </div>
</section>

<section class="final-cta" aria-labelledby="final-cta-title">
    <div class="cta-pattern" aria-hidden="true"></div>
    <div class="container final-cta-inner" data-reveal>
        <div><p class="eyebrow eyebrow-on-blue">The next phase starts with a conversation</p><h2 id="final-cta-title">Ready to shape your assessment future?</h2><p>Tell us what your institution needs. We’ll start with the people, systems, and constraints you already have.</p></div>
        <div class="final-cta-actions"><a class="button button-white" href="#contact">Talk to the team <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h15m-7-7 7 7-7 7"/></svg></a><a class="button button-blue-outline" href="#showcase">Explore the roadmap</a></div>
    </div>
</section>