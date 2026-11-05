<?php
declare(strict_types=1);
$assetRoot = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') . '/assets' : './assets';
$homeUrl = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') . '/' : './';
?>
<!doctype html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/pwa-tags.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <meta name="theme-color" content="#1E40AF">
    <meta name="description" content="Practice WAEC, NECO and JAMB questions with a free anonymous CBT simulator.">
    <title>Practice CBT | <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script defer src="<?= e(asset('js/selector.js')) ?>"></script>
</head>
<body class="min-h-screen bg-slate-50 text-ink antialiased">
<a href="#main-content" class="skip-link">Skip to content</a>
<header class="site-header sticky top-0 z-40 border-b border-white/60 bg-white/90 shadow-sm backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-2 px-3 sm:px-6 lg:px-8">
        <a href="<?= e($homeUrl) ?>" class="brand inline-flex min-h-11 min-w-0 shrink items-center gap-2 rounded-xl font-display text-base font-extrabold sm:gap-3 sm:text-lg text-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" aria-label="ExamCore CBT practice home">
            <svg class="h-8 w-8 shrink-0 sm:h-9 sm:w-9" viewBox="0 0 40 40" role="img" aria-label=""><path d="M5 8.5 20 2l15 6.5v13c0 8.4-6.1 13.2-15 16C11.1 34.7 5 29.9 5 21.5v-13Z" fill="#1E40AF"/><path d="M13 13h14M13 19h14M13 25h8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2.5"/></svg>
            <span>ExamCore <span class="font-medium text-primary">CBT</span></span>
        </a>
        <div class="flex shrink-0 items-center gap-1.5 sm:gap-3">
            <button type="button" class="theme-toggle" aria-label="Toggle dark mode" data-theme-toggle>
                <span class="theme-toggle__icon" aria-hidden="true">☀️</span>
            </button>
            <details class="signin-menu relative">
                <summary class="flex h-10 cursor-pointer list-none items-center gap-1.5 whitespace-nowrap rounded-full bg-primary px-3.5 text-sm font-bold text-white sm:px-4 shadow-sm transition hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 [&::-webkit-details-marker]:hidden">
                    Sign in
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <div class="absolute right-0 top-12 z-50 w-72 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                    <a href="<?= e(url('/student/login')) ?>" class="flex items-start gap-3 rounded-xl p-3 transition hover:bg-blue-50">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-blue-100 text-primary"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 10-10-5-10 5 10 5 10-5Z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></span>
                        <span><span class="block text-sm font-bold text-ink">Student</span><span class="block text-xs leading-5 text-slate-500">Write your school exams and see your results</span></span>
                    </a>
                    <a href="<?= e(url('/login')) ?>" class="flex items-start gap-3 rounded-xl p-3 transition hover:bg-blue-50">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg></span>
                        <span><span class="block text-sm font-bold text-ink">Teacher or school admin</span><span class="block text-xs leading-5 text-slate-500">Manage exams, students and results</span></span>
                    </a>
                    <a href="<?= e(url('/check-result')) ?>" class="flex items-start gap-3 rounded-xl p-3 transition hover:bg-blue-50">
                        <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-700"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg></span>
                        <span><span class="block text-sm font-bold text-ink">Parent</span><span class="block text-xs leading-5 text-slate-500">Check your child's result with the result code</span></span>
                    </a>
                </div>
            </details>
            <button type="button" class="hidden h-10 w-10 place-items-center rounded-full sm:grid border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-primary hover:bg-blue-50 hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" aria-label="Open student profile" data-open-identity-modal>
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
            </button>
            <span class="hidden items-center gap-2 text-xs font-semibold text-slate-600 sm:inline-flex"><span class="h-2 w-2 rounded-full bg-success"></span> Anonymous session</span>
            <span class="hidden rounded-full bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-[.12em] text-primary sm:inline-flex">Practice CBT</span>
            <button type="button" class="grid h-11 w-11 place-items-center rounded-xl border border-slate-200 bg-white text-ink shadow-sm transition hover:border-primary hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 sm:hidden" aria-label="Open practice menu" aria-controls="practice-menu" aria-expanded="false" data-menu-toggle><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        </div>
    </div>
    <nav id="practice-menu" class="mobile-menu-panel hidden sm:hidden" aria-label="Practice page menu" data-mobile-menu>
        <div class="mb-6 flex items-center justify-between border-b border-slate-200 pb-4">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[.22em] text-primary">Menu</p>
                <h2 class="mt-1 font-display text-xl font-bold text-ink">Explore sessions</h2>
            </div>
            <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-primary hover:text-primary" aria-label="Close menu" data-menu-close>
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6 18 18M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="space-y-2">
            <a class="menu-link" href="#exam-selector" data-menu-link>Choose a practice set</a>
            <a class="menu-link" href="#how-title" data-menu-link>How it works</a>
            <?php if ($recentAttempts !== []): ?><a class="menu-link" href="#recent-title" data-menu-link>Recent practice</a><?php endif; ?>
        </div>
        <div class="mt-6 rounded-2xl bg-gradient-to-r from-blue-50 to-amber-50 p-4">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-primary">Free practice</p>
            <p class="mt-2 text-sm text-slate-600">No signup required. Start a new exam in seconds.</p>
        </div>
    </nav>
</header>

<main id="main-content" tabindex="-1">
    <section class="relative isolate min-h-[440px] overflow-hidden bg-primary text-white sm:min-h-[470px]" aria-labelledby="hero-title">
        <div class="absolute inset-0 -z-10 overflow-hidden hero-visual-shell" role="img" aria-label="Illustration of a student practicing a computer-based exam">
            <svg class="hero-visual-svg h-full w-full" viewBox="0 0 1600 620" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="heroWash" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#1E40AF"/><stop offset=".62" stop-color="#1D4ED8"/><stop offset="1" stop-color="#172554"/></linearGradient>
                    <linearGradient id="screen" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#fff"/><stop offset="1" stop-color="#eff6ff"/></linearGradient>
                    <pattern id="dots" width="26" height="26" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.3" fill="#fff" opacity=".15"/></pattern>
                </defs>
                <path fill="url(#heroWash)" d="M0 0h1600v620H0z"/><path fill="url(#dots)" d="M0 0h1600v620H0z"/>
                <circle cx="1310" cy="170" r="260" fill="#60a5fa" opacity=".18"/><circle cx="1490" cy="500" r="330" fill="#f59e0b" opacity=".16"/>
                <path d="M850 620c90-180 240-240 470-210s250 120 280 210H850Z" fill="#0f172a" opacity=".24"/>
                <g class="hero-screen-card" transform="translate(930 104) rotate(3 250 190)">
                    <rect x="8" y="10" width="500" height="340" rx="25" fill="#0b1c56" opacity=".3"/>
                    <rect width="500" height="340" rx="24" fill="#dbeafe"/><rect x="12" y="12" width="476" height="316" rx="17" fill="url(#screen)"/>
                    <path d="M12 30a18 18 0 0 1 18-18h440a18 18 0 0 1 18 18v34H12V30Z" fill="#f1f5f9"/>
                    <circle cx="36" cy="35" r="5" fill="#f87171"/><circle cx="54" cy="35" r="5" fill="#fbbf24"/><circle cx="72" cy="35" r="5" fill="#34d399"/>
                    <rect x="34" y="86" width="286" height="17" rx="8" fill="#0f172a"/><rect x="34" y="119" width="390" height="10" rx="5" fill="#cbd5e1"/><rect x="34" y="139" width="320" height="10" rx="5" fill="#cbd5e1"/>
                    <g font-family="Arial,sans-serif" font-size="13" font-weight="700" text-anchor="middle"><circle cx="49" cy="190" r="15" fill="#dbeafe"/><text x="49" y="195" fill="#1e40af">A</text><circle cx="49" cy="230" r="15" fill="#1e40af"/><text x="49" y="235" fill="#fff">B</text><circle cx="49" cy="270" r="15" fill="#dbeafe"/><text x="49" y="275" fill="#1e40af">C</text><circle cx="49" cy="310" r="15" fill="#dbeafe"/><text x="49" y="315" fill="#1e40af">D</text></g>
                    <rect x="80" y="181" width="330" height="9" rx="4" fill="#94a3b8"/><rect x="80" y="221" width="290" height="9" rx="4" fill="#64748b"/><rect x="80" y="261" width="350" height="9" rx="4" fill="#94a3b8"/><rect x="80" y="301" width="230" height="9" rx="4" fill="#94a3b8"/>
                    <path d="M-36 350h572l-44 35H8l-44-35Z" fill="#cbd5e1"/><path d="M180 356h136l-13 18h-110l-13-18Z" fill="#94a3b8"/>
                </g>
                <g class="hero-timer-card" transform="translate(1322 285)"><rect width="176" height="92" rx="19" fill="#fff"/><circle cx="27" cy="27" r="7" fill="#10b981"/><text x="46" y="31" font-family="Arial,sans-serif" font-size="11" fill="#64748b" font-weight="700">PRACTICE SESSION</text><text x="21" y="65" font-family="Arial,sans-serif" font-size="23" fill="#0f172a" font-weight="700">08:42</text><path d="M115 62h38" stroke="#f59e0b" stroke-width="6" stroke-linecap="round"/></g>
            </svg>
        </div>
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-blue-950/95 via-primary/90 to-blue-950/75 lg:to-primary/10" aria-hidden="true"></div>
        <div class="mx-auto min-h-[440px] max-w-7xl px-4 py-14 sm:min-h-[470px] sm:px-6 lg:px-8">
            <div class="hero-layout">
                <div class="max-w-2xl motion-reveal" data-reveal>
                    <p class="hero-pill mb-5 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-blue-50 backdrop-blur"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Free practice · no account needed</p>
                    <h1 id="hero-title" class="max-w-2xl font-display text-4xl font-extrabold leading-[1.08] text-white sm:text-5xl lg:text-6xl">Practice WAEC, NECO &amp; JAMB CBT. <span class="text-amber-300">Free, no login required.</span></h1>
                    <p class="mt-5 max-w-xl text-base leading-7 text-blue-50 sm:text-lg">Choose a subject and year, work through one question at a time, and see your score with explanations when you finish.</p>
                    <div class="hero-metrics mt-8 grid max-w-xl gap-3 sm:grid-cols-3">
                        <div><span>1400+</span><small>Practice questions</small></div>
                        <div><span>18</span><small>Subjects</small></div>
                        <div><span>3</span><small>Exam boards</small></div>
                    </div>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#exam-selector" class="hero-main-action inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-accent px-6 py-3 font-bold text-ink shadow-lg transition duration-200 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Choose a practice exam <span aria-hidden="true">↓</span></a>
                        <a href="#how-title" class="hero-secondary-action inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/5 px-5 py-3 font-semibold text-white backdrop-blur-sm transition duration-200 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">How it works</a>
                    </div>
                </div>

                <div class="hero-visual-stack motion-reveal" data-reveal aria-hidden="true">
                    <div class="hero-float-card hero-float-card-1">
                        <div class="hero-float-label">Performance</div>
                        <div class="hero-float-score">89%</div>
                        <div class="hero-float-bars">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    <div class="hero-float-card hero-float-card-2">
                        <div class="hero-float-label">Active boards</div>
                        <div class="hero-float-row"><span>JAMB</span><strong>2024</strong></div>
                        <div class="hero-float-row"><span>WAEC</span><strong>2023</strong></div>
                        <div class="hero-float-row"><span>NECO</span><strong>2022</strong></div>
                    </div>
                    <div class="hero-float-badge">
                        <span class="hero-float-badge__dot"></span>
                        3D study mode
                    </div>
                </div>
            </div>
        </div>
        <div class="pointer-events-none absolute bottom-0 right-[5%] hidden h-12 w-72 rounded-t-full bg-white/10 blur-2xl lg:block" aria-hidden="true"></div>
    </section>

    <section id="exam-selector" class="relative z-10 mx-auto -mt-7 max-w-7xl px-4 pb-14 sm:px-6 lg:px-8" aria-labelledby="selector-title">
        <div class="motion-reveal rounded-2xl bg-white p-5 shadow-xl ring-1 ring-slate-200/70 sm:p-8 lg:p-10" data-reveal>
            <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-sm font-extrabold uppercase tracking-[.15em] text-primary">Your practice session</p><h2 id="selector-title" class="mt-2 font-display text-2xl font-bold text-ink sm:text-3xl">Build your exam in three steps</h2></div>
                <p class="inline-flex items-center gap-2 text-sm text-muted"><svg class="h-4 w-4 text-success" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>No signup. Your progress stays in this browser session.</p>
            </div>
            <?php if ($notice !== null): ?><p class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert" tabindex="-1" data-notice><?= e($notice) ?></p><?php endif; ?>
            <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-amber-50 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-primary">Selection summary</p>
                    <p class="mt-1 text-sm text-slate-600">Build your CBT stack for the day.</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-200">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-success"></span>
                    <span data-selection-count>0 exam selected</span>
                </div>
            </div>
            <div class="mb-6 flex flex-wrap gap-2" data-selection-list aria-live="polite"></div>
            <form action="<?= e($baseUrl) ?>/start" method="post" id="selector-form" data-selector-form data-api-url="<?= e($baseUrl) ?>/api/exams">
                <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="exam_id" id="exam-id" value="">
                <input type="hidden" name="student_name" id="student-name-hidden" value="">
                <input type="hidden" name="student_email" id="student-email-hidden" value="">
                <div class="grid gap-8 lg:grid-cols-2" data-selector-grid>
                    <fieldset>
                        <legend class="mb-4 flex items-center gap-3 font-display text-lg font-bold text-ink"><span class="grid h-8 w-8 place-items-center rounded-full bg-blue-100 text-sm font-extrabold text-primary">01</span> Exam type</legend>
                        <div class="grid grid-cols-3 gap-2" role="group" aria-label="Choose an exam type" data-exam-types>
                            <?php foreach ($examTypes as $type): ?>
                                <button type="button" class="min-h-12 rounded-xl border border-slate-200 bg-white px-3 font-bold text-slate-700 transition duration-150 hover:border-primary hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" data-exam-type="<?= e($type['slug']) ?>" aria-pressed="false"><?= e($type['name']) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <p class="mt-3 text-sm text-muted" data-type-status>Select an exam type to see subjects.</p>
                    </fieldset>

                    <fieldset>
                        <legend class="mb-4 flex items-center gap-3 font-display text-lg font-bold text-ink"><span class="grid h-8 w-8 place-items-center rounded-full bg-blue-100 text-sm font-extrabold text-primary">02</span> Subject</legend>
                        <label class="relative mb-3 block"><span class="sr-only">Search subjects</span><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm text-ink placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:bg-slate-100" type="search" placeholder="Search subjects" autocomplete="off" data-subject-search disabled></label>
                        <p class="mb-2 text-xs font-semibold text-muted" data-subject-count aria-live="polite">Select an exam type to load subjects.</p>
                        <button type="button" class="mb-3 min-h-10 rounded-lg px-2 text-sm font-bold text-primary underline decoration-primary/30 underline-offset-4 hover:text-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" data-subject-browse hidden>Browse all subjects</button>
                        <div class="grid max-h-[28rem] grid-cols-2 gap-2 overflow-y-auto pr-1 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3" data-subjects aria-live="polite">
                            <p class="col-span-full rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-muted">Choose an exam type first.</p>
                        </div>
                    </fieldset>

                    <div class="hidden" data-year-step>
                        <fieldset>
                            <legend class="mb-4 flex items-center gap-3 font-display text-lg font-bold text-ink"><span class="grid h-8 w-8 place-items-center rounded-full bg-blue-100 text-sm font-extrabold text-primary">03</span> Year</legend>
                            <label for="exam-choice" class="mb-2 block text-sm font-semibold text-slate-700">Available practice sets</label>
                            <select id="exam-choice" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-ink shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-slate-100" disabled>
                                <option value="">Choose a subject first</option>
                            </select>
                            <div class="mt-5 rounded-xl bg-slate-50 p-4" aria-live="polite">
                                <p class="font-semibold text-ink" data-exam-meta>Choose a set to see its format.</p>
                                <p class="mt-1 text-sm text-muted" data-exam-description>Available practice sets show their question count and time limit here.</p>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="mt-8 flex flex-col gap-4 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-lg text-sm text-muted">Original practice material for learning; not official examination papers or past questions.</p>
                    <button type="button" class="inline-flex min-h-14 items-center justify-center gap-3 rounded-xl bg-accent px-8 py-4 text-lg font-extrabold text-ink shadow-sm transition duration-200 hover:bg-amber-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500 disabled:shadow-none" data-start-button disabled data-open-identity-modal>Start exam <span aria-hidden="true">→</span></button>
                </div>
                <p class="sr-only" role="status" aria-live="polite" data-selector-status></p>

            </form>
        </div>
    </section>

    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4" data-identity-modal aria-modal="true" role="dialog">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-200">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-[.18em] text-primary">Student profile</p>
                    <h3 class="mt-2 font-display text-2xl font-bold text-ink">Start your exam</h3>
                </div>
                <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-slate-600 hover:bg-slate-50" data-close-identity-modal aria-label="Close identity form">✕</button>
            </div>
            <p class="mt-3 text-sm text-slate-600">Add your first name and email so we can personalize your result and send a copy of the score.</p>
            <div class="mt-5 space-y-4">
                <label class="block text-sm font-semibold text-slate-700">
                    First name
                    <input type="text" id="identity-name" class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-ink placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="e.g. Ada" required>
                </label>
                <label class="block text-sm font-semibold text-slate-700">
                    Email address
                    <input type="email" id="identity-email" class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-ink placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" placeholder="ada@example.com" required>
                </label>
            </div>
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" class="min-h-11 rounded-xl border border-slate-300 px-4 font-bold text-slate-700 hover:bg-slate-50" data-close-identity-modal>Cancel</button>
                <button type="submit" form="selector-form" class="min-h-11 rounded-xl bg-primary px-5 font-bold text-white hover:bg-primary-600" data-submit-identity>Continue to exam</button>
            </div>
        </div>
    </div>

    <section class="border-y border-slate-200 bg-white py-14 sm:py-18" aria-labelledby="how-title">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="motion-reveal mx-auto max-w-2xl text-center" data-reveal><p class="text-sm font-extrabold uppercase tracking-[.15em] text-primary">Simple by design</p><h2 id="how-title" class="mt-3 font-display text-3xl font-bold text-ink">How the CBT works</h2><p class="mt-3 text-muted">A familiar exam flow, followed by feedback you can use.</p></div>
            <ol class="how-grid motion-stagger mt-10 grid gap-5 md:grid-cols-3">
                <li class="how-card motion-reveal rounded-3xl border border-slate-200 p-6 shadow-[0_20px_45px_rgba(15,23,42,0.08)]" data-reveal>
                    <svg class="h-16 w-16 text-primary" viewBox="0 0 64 64" role="img" aria-label="Select exam illustration"><rect x="7" y="10" width="50" height="44" rx="9" fill="#dbeafe"/><rect x="15" y="18" width="34" height="7" rx="3.5" fill="#1e40af"/><circle cx="21" cy="36" r="5" fill="#f59e0b"/><path d="M32 36h12M16 46h32" stroke="#64748b" stroke-width="3" stroke-linecap="round"/></svg>
                    <span class="mt-5 block text-xs font-extrabold uppercase tracking-widest text-primary">Step 01</span><h3 class="mt-2 font-display text-xl font-bold">Choose a set</h3><p class="mt-2 text-sm leading-6 text-muted">Pick an exam type, a subject, and an available year curated from real school-exam formats.</p>
                </li>
                <li class="how-card motion-reveal rounded-3xl border border-slate-200 p-6 shadow-[0_20px_45px_rgba(15,23,42,0.08)]" data-reveal>
                    <svg class="h-16 w-16 text-primary" viewBox="0 0 64 64" role="img" aria-label="Answer practice question illustration"><rect x="5" y="10" width="54" height="42" rx="8" fill="#dbeafe"/><path d="M13 19h25M13 27h38" stroke="#64748b" stroke-width="3" stroke-linecap="round"/><circle cx="18" cy="38" r="5" fill="#1e40af"/><path d="M29 38h20" stroke="#64748b" stroke-width="3" stroke-linecap="round"/><path d="M24 55h16" stroke="#f59e0b" stroke-width="4" stroke-linecap="round"/></svg>
                    <span class="mt-5 block text-xs font-extrabold uppercase tracking-widest text-primary">Step 02</span><h3 class="mt-2 font-display text-xl font-bold">Take the exam</h3><p class="mt-2 text-sm leading-6 text-muted">Work through one question at a time with a focused CBT experience and timed pacing.</p>
                </li>
                <li class="how-card motion-reveal rounded-3xl border border-slate-200 p-6 shadow-[0_20px_45px_rgba(15,23,42,0.08)]" data-reveal>
                    <svg class="h-16 w-16 text-primary" viewBox="0 0 64 64" role="img" aria-label="Review score illustration"><rect x="8" y="8" width="48" height="48" rx="12" fill="#dbeafe"/><path d="M18 42V31M28 42V23M38 42V17M48 42V27" stroke="#1e40af" stroke-width="5" stroke-linecap="round"/><path d="m17 48 10-7 9 2 12-15" fill="none" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="mt-5 block text-xs font-extrabold uppercase tracking-widest text-primary">Step 03</span><h3 class="mt-2 font-display text-xl font-bold">See your score</h3><p class="mt-2 text-sm leading-6 text-muted">Review correct answers and explanations immediately to sharpen weak areas.</p>
                </li>
            </ol>
        </div>
    </section>

    <?php if ($recentAttempts !== []): ?>
        <section class="py-14 sm:py-18" aria-labelledby="recent-title">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-extrabold uppercase tracking-[.15em] text-primary">This browser session</p><h2 id="recent-title" class="mt-2 font-display text-2xl font-bold text-ink">Your recent practice</h2></div><p class="text-sm text-muted">Only visible in this anonymous session.</p></div>
                <ul class="mt-6 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($recentAttempts as $recent): ?>
                        <li class="flex flex-col justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:flex-row sm:items-center">
                            <div><p class="font-bold text-ink"><?= e($recent['title']) ?></p><p class="mt-1 text-sm text-muted"><?= e(ucfirst((string) $recent['status'])) ?><?= $recent['score'] !== null ? ' · ' . e($recent['score']) . ' / ' . e($recent['total']) : '' ?></p></div>
                            <?php if ($recent['status'] === 'in_progress'): ?>
                                <a class="inline-flex min-h-11 items-center justify-center rounded-xl border border-primary px-4 font-bold text-primary hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" href="<?= e($baseUrl) ?>/exam/<?= e($recent['token']) ?>">Resume</a>
                            <?php else: ?>
                                <div class="flex gap-2"><a class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 font-bold text-ink hover:bg-slate-50" href="<?= e($baseUrl) ?>/results/<?= e($recent['token']) ?>">Review</a><form action="<?= e($baseUrl) ?>/attempts/<?= e($recent['token']) ?>/retake" method="post"><input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>"><button class="min-h-11 rounded-xl bg-primary px-4 font-bold text-white hover:bg-primary-600" type="submit">Retake</button></form></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    <?php endif; ?>
</main>
<footer class="site-footer">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[1.3fr_0.7fr_0.7fr_1fr]">
            <div>
                <a href="<?= e($homeUrl) ?>" class="brand inline-flex items-center gap-3 text-lg font-extrabold text-ink" aria-label="ExamCore CBT home">
                    <svg class="h-9 w-9" viewBox="0 0 40 40" role="img" aria-label=""><path d="M5 8.5 20 2l15 6.5v13c0 8.4-6.1 13.2-15 16C11.1 34.7 5 29.9 5 21.5v-13Z" fill="#1E40AF"/><path d="M13 13h14M13 19h14M13 25h8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2.5"/></svg>
                    <span>ExamCore <span class="font-medium text-primary">CBT</span></span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-7 text-muted">Premium CBT practice for JAMB, WAEC and NECO students. Train with realistic subject sets, timed exercises, and instant feedback.</p>
            </div>
            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-[.18em] text-primary">Practice</h3>
                <ul class="mt-4 space-y-3 text-sm text-muted">
                    <li><a href="#exam-selector" class="hover:text-primary">Choose exam</a></li>
                    <li><a href="#how-title" class="hover:text-primary">How it works</a></li>
                    <li><a href="#recent-title" class="hover:text-primary">Recent scores</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-[.18em] text-primary">Boards</h3>
                <ul class="mt-4 space-y-3 text-sm text-muted">
                    <li>JAMB</li>
                    <li>WAEC</li>
                    <li>NECO</li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-[.18em] text-primary">Support</h3>
                <ul class="mt-4 space-y-3 text-sm text-muted">
                    <li>Anonymous exam mode</li>
                    <li>Instant explanations</li>
                    <li>Subject-by-subject practice</li>
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col gap-3 border-t border-slate-200 pt-6 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; <?= date('Y') ?> <?= e($appName) ?>. All rights reserved.</p>
            <p>Powered by <span class="font-semibold text-ink">ExamCore CBT</span></p>
        </div>
    </div>
</footer>
</body>
</html>