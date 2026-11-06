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
    <script defer src="<?= e(asset('js/push.js')) ?>"></script>
</head>
<body class="min-h-screen bg-slate-50 text-ink antialiased">
<a href="#main-content" class="skip-link">Skip to content</a>
<header class="site-header sticky top-0 z-40 border-b border-white/60 bg-white/90 shadow-sm backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-2 px-3 sm:h-[72px] sm:px-6 lg:px-8">
        <a href="<?= e($homeUrl) ?>" class="brand inline-flex min-h-11 min-w-0 shrink items-center gap-2.5 rounded-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" aria-label="<?= e($appName) ?> practice home">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 shadow-md shadow-blue-900/25 sm:h-11 sm:w-11"><svg class="h-6 w-6 text-white" viewBox="0 0 40 40" aria-hidden="true"><path d="M20 6 5 13l15 7 15-7-15-7Z" fill="#fff"/><path d="M11 18v7c2.4 2.6 5.6 4 9 4s6.6-1.4 9-4v-7l-9 4.2L11 18Z" fill="#fff" opacity=".85"/><path d="M34 14v9" stroke="#fbbf24" stroke-width="2.4" stroke-linecap="round"/></svg></span>
            <span class="min-w-0 leading-tight"><span class="block truncate font-display text-[17px] font-extrabold text-ink sm:text-xl"><?= e($appName) ?></span><span class="hidden text-xs font-medium text-slate-500 min-[380px]:block"><?= e($tagline ?? 'Learn · Practice · Excel') ?></span></span>
        </a>
        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2.5">
            <button type="button" class="theme-toggle" aria-label="Toggle dark mode" data-theme-toggle><span class="theme-toggle__icon" aria-hidden="true">☀️</span></button>
            <a href="<?= e(url('/practice')) ?>" class="hidden h-10 items-center rounded-full border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:border-primary hover:text-primary lg:inline-flex">Practice Centre</a>
            <a href="<?= e(url('/student/login')) ?>" class="hidden h-10 items-center rounded-full bg-primary px-5 text-sm font-bold text-white shadow-sm transition hover:opacity-90 sm:inline-flex">Sign in</a>
            <button type="button" class="relative grid h-11 w-11 shrink-0 place-items-center rounded-full border-2 border-white bg-gradient-to-br from-blue-100 to-blue-200 text-primary shadow-md ring-1 ring-slate-200 transition active:scale-95 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" aria-label="Your practice profile" data-open-profile>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg><span class="hidden font-display text-base font-extrabold" data-avatar-letter></span><span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-success"></span>
            </button>
            <button type="button" class="grid h-11 w-11 place-items-center rounded-2xl border border-slate-200 bg-white text-ink shadow-sm transition active:scale-95 hover:border-primary hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600" aria-label="Open menu" aria-controls="practice-menu" aria-expanded="false" data-menu-toggle><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        </div>
    </div>
    <nav id="practice-menu" class="mobile-menu-panel hidden" aria-label="Main menu" data-mobile-menu>
        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
            <p class="font-display text-lg font-extrabold text-ink"><?= e($appName) ?></p>
            <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-primary hover:text-primary" aria-label="Close menu" data-menu-close><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6 18 18M18 6 6 18"/></svg></button>
        </div>
        <button type="button" class="mt-4 flex w-full items-center gap-3 rounded-2xl bg-gradient-to-r from-blue-700 to-blue-600 p-3.5 text-left text-white shadow-md" data-open-profile>
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/20"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
            <span class="min-w-0"><span class="block text-sm font-bold">Your practice profile</span><span class="block text-xs text-blue-100">Add your name so results carry it</span></span>
        </button>
        <p class="mb-1 mt-5 px-1 text-[11px] font-extrabold uppercase tracking-[.18em] text-slate-400">Practise</p>
        <div class="space-y-1">
            <a class="menu-link" href="#exam-selector" data-menu-link>Choose a practice exam</a>
            <a class="menu-link" href="<?= e(url('/practice')) ?>" data-menu-link>Practice Centre (by topic)</a>
            <a class="menu-link" href="<?= e(url('/practice/mock')) ?>" data-menu-link>JAMB mock exam</a>
            <a class="menu-link" href="#how-title" data-menu-link>How it works</a>
            <?php if ($recentAttempts !== []): ?><a class="menu-link" href="#recent-title" data-menu-link>Recent practice</a><?php endif; ?>
        </div>
        <p class="mb-1 mt-5 px-1 text-[11px] font-extrabold uppercase tracking-[.18em] text-slate-400">Sign in</p>
        <div class="space-y-1">
            <a class="menu-link" href="<?= e(url('/student/login')) ?>">Student</a>
            <a class="menu-link" href="<?= e(url('/login')) ?>">Teacher or school admin</a>
            <a class="menu-link" href="<?= e(url('/check-result')) ?>">Parent: check a result</a>
        </div>
        <p class="mt-6 rounded-2xl bg-gradient-to-r from-blue-50 to-amber-50 p-4 text-sm text-slate-600"><span class="block text-xs font-bold uppercase tracking-[.16em] text-primary">Free practice</span>No signup required. Start a new exam in seconds.</p>
    </nav>
</header>

<main id="main-content" tabindex="-1">
    <?php $heroPhoto = is_file(dirname(__DIR__, 3) . '/public/assets/images/hero-student.jpg'); ?>
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-blue-900 via-primary to-blue-700 text-white" aria-labelledby="hero-title">
        <?php if ($heroPhoto): ?><img src="<?= e($assetRoot) ?>/images/hero-student.jpg" alt="" class="hero-photo absolute inset-y-0 right-0 -z-10 h-full w-full object-cover object-right opacity-60 sm:w-3/5" fetchpriority="high"><div class="absolute inset-0 -z-10 bg-gradient-to-r from-blue-950 via-blue-900/85 to-transparent" aria-hidden="true"></div><?php endif; ?>
        <div class="hero-aurora -z-10" aria-hidden="true"><i></i><i></i><i></i></div>
        <div class="hero-grid -z-10" aria-hidden="true"></div>
        <div class="hero-bits -z-10" aria-hidden="true"><b>A</b><b>B</b><b>C</b><b>D</b><b>✓</b><b>?</b><b>+</b><b>%</b></div>
        <div class="mx-auto grid max-w-7xl items-center gap-6 px-4 pb-24 pt-9 sm:px-6 sm:pt-14 lg:grid-cols-[1.15fr_.85fr] lg:px-8 lg:pb-28 lg:pt-16">
            <div class="motion-reveal relative z-10" data-reveal>
                <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-blue-50 backdrop-blur sm:text-sm"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Free practice · no account needed</p>
                <h1 id="hero-title" class="font-display text-[2rem] font-extrabold leading-[1.1] sm:text-5xl lg:text-6xl">Practice <span class="hero-word" data-rotate="WAEC|NECO|JAMB">WAEC</span> the smart way. <span class="text-amber-300">Free, no login.</span></h1>
                <p class="mt-4 max-w-xl text-[15px] leading-7 text-blue-50 sm:text-lg">Choose a subject and year, work through one question at a time, and see your score with <span class="font-semibold text-amber-300">explanations</span> when you finish.</p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="#exam-selector" class="cta-shine inline-flex min-h-14 items-center justify-center gap-3 rounded-2xl bg-accent px-7 text-base font-extrabold text-ink shadow-lg shadow-black/20 transition active:scale-[.98] hover:bg-amber-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 5 11 7-11 7V5Z" fill="currentColor"/></svg> Choose a practice exam <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></a>
                    <a href="#how-title" class="inline-flex min-h-14 items-center justify-center gap-3 rounded-2xl border border-white/30 bg-white/10 px-6 text-base font-bold text-white backdrop-blur transition active:scale-[.98] hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 4h7a3 3 0 0 1 3 3v14a2 2 0 0 0-2-2H2V4ZM22 4h-7a3 3 0 0 0-3 3v14a2 2 0 0 1 2-2h8V4Z"/></svg> How it works</a>
                </div>
            </div>
            <div class="hero-art-wrap" aria-hidden="true">
                <div class="hero-ring"></div><div class="hero-ring hero-ring-2"></div><svg viewBox="0 0 420 360" class="hero-cap w-full drop-shadow-2xl" data-parallax xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="bkA" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient>
                        <linearGradient id="bkB" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1e3a8a"/></linearGradient>
                        <linearGradient id="capT" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#334155"/><stop offset="1" stop-color="#0f172a"/></linearGradient>
                    </defs>
                    <ellipse cx="210" cy="322" rx="170" ry="22" fill="#0b1b4d" opacity=".35"/>
                    <path d="M40 270 210 310 380 270v-34L210 276 40 236Z" fill="url(#bkB)"/><path d="M40 236 210 276 380 236 210 196Z" fill="#fff"/><path d="M60 242 210 277 360 242" fill="none" stroke="#cbd5e1" stroke-width="3"/>
                    <path d="M70 214 210 246 350 214v-30L210 216 70 184Z" fill="url(#bkA)"/><path d="M70 184 210 216 350 184 210 152Z" fill="#fff"/><path d="M86 190 210 218 334 190" fill="none" stroke="#cbd5e1" stroke-width="3"/>
                    <path d="M120 128v38c20 16 54 26 90 26s70-10 90-26v-38l-90 36-90-36Z" fill="#1e293b"/>
                    <path d="M210 52 70 106l140 54 140-54-140-54Z" fill="url(#capT)"/><path d="M210 52 70 106l140 54" fill="none" stroke="#475569" stroke-width="2"/>
                    <path d="M210 106 332 112v52" fill="none" stroke="#f59e0b" stroke-width="5" stroke-linecap="round"/><circle cx="332" cy="112" r="6" fill="#fbbf24"/><path d="M332 118v44" stroke="#f59e0b" stroke-width="9" stroke-linecap="round"/><path d="M326 160h12l4 22h-20Z" fill="#f59e0b"/>
                </svg>
                <div class="hero-chip hero-chip-a">✔ Instant marking</div><div class="hero-chip hero-chip-b">⏱ Timed like the real CBT</div><div class="hero-chip hero-chip-c">★ Explanations</div>
            </div>
        </div>
    </section>

    <section class="relative z-10 mx-auto -mt-16 max-w-7xl px-4 sm:px-6 lg:-mt-20 lg:px-8" aria-label="At a glance">
        <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
            <div class="stat-card rounded-2xl bg-white p-3 shadow-xl ring-1 ring-slate-200/70 sm:p-5"><span class="mb-2 grid h-9 w-9 place-items-center rounded-full bg-blue-600 text-white sm:h-11 sm:w-11"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.7.4-1 .9-1 1.7M12 17h.01"/></svg></span><p class="font-display text-xl font-extrabold text-ink sm:text-3xl"><span data-count="1800" data-suffix="+">1,800+</span></p><p class="text-[11px] font-medium leading-tight text-slate-500 sm:text-sm">Practice questions</p></div>
            <div class="stat-card rounded-2xl bg-white p-3 shadow-xl ring-1 ring-slate-200/70 sm:p-5"><span class="mb-2 grid h-9 w-9 place-items-center rounded-full bg-violet-600 text-white sm:h-11 sm:w-11"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg></span><p class="font-display text-xl font-extrabold text-ink sm:text-3xl"><span data-count="22">22</span></p><p class="text-[11px] font-medium leading-tight text-slate-500 sm:text-sm">Years, 2005–2026</p></div>
            <div class="stat-card rounded-2xl bg-white p-3 shadow-xl ring-1 ring-slate-200/70 sm:p-5"><span class="mb-2 grid h-9 w-9 place-items-center rounded-full bg-emerald-600 text-white sm:h-11 sm:w-11"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m4 12 5 5L20 6"/></svg></span><p class="font-display text-xl font-extrabold text-ink sm:text-3xl"><span data-count="3">3</span></p><p class="text-[11px] font-medium leading-tight text-slate-500 sm:text-sm">Exam boards</p></div>
        </div>
    </section>

<?php if (\ExamCore\Services\PushService::publicKey() !== ''): ?>
    <section class="relative z-10 mx-auto mt-6 hidden max-w-7xl items-center gap-4 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-slate-200/70 sm:p-5 lg:mx-auto" data-push-banner data-push-guest="1" data-push-key="<?= e(\ExamCore\Services\PushService::publicKey()) ?>" data-push-base="<?= e(url('/push')) ?>" aria-label="Phone notifications" style="width:calc(100% - 2rem)">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-blue-100 text-primary"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg></span>
        <div class="min-w-0 flex-1">
            <p class="font-display text-[15px] font-bold text-ink" data-push-title>Get a daily practice reminder</p>
            <p class="mt-0.5 text-sm leading-6 text-slate-600" data-push-text>Turn on alerts and we will nudge you to practise, protect your streak and tell you about new question sets.</p>
        </div>
        <button type="button" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl bg-primary px-4 text-sm font-bold text-white shadow-sm transition hover:opacity-90 disabled:opacity-60" data-push-enable>Turn on</button>
        <button type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-slate-500 transition hover:bg-slate-100" data-push-dismiss aria-label="Not now"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6 18 18M18 6 6 18"/></svg></button>
    </section>
    <?php endif; ?>

    <section id="exam-selector" class="relative z-10 mx-auto mt-6 max-w-7xl px-4 pb-14 sm:px-6 lg:px-8" aria-labelledby="selector-title">
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
            <form action="<?= e($baseUrl) ?>/start" method="post" id="selector-form" data-selector-form data-api-url="<?= e($baseUrl) ?>/api/exams"<?php if (!empty($knownProfile)): ?> data-known-name="<?= e($knownProfile['name']) ?>" data-known-email="<?= e($knownProfile['email']) ?>"<?php endif; ?>>
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
                                <p class="mt-3 border-t border-slate-200 pt-3 text-xs leading-5 text-slate-500">Each year is a practice series of original questions written to the exam syllabus. They are not the real past papers; the year is only a label to help you keep track.</p>
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
                    <span class="mt-5 block text-xs font-extrabold uppercase tracking-widest text-primary">Step 01</span><h3 class="mt-2 font-display text-xl font-bold">Choose a set</h3><p class="mt-2 text-sm leading-6 text-muted">Pick an exam type, a subject, and a year. Every series uses original questions written to the exam syllabus.</p>
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
                <a href="<?= e($homeUrl) ?>" class="brand inline-flex items-center gap-3 text-lg font-extrabold text-white" aria-label="<?= e($appName) ?> home">
                    <svg class="h-9 w-9" viewBox="0 0 40 40" role="img" aria-label=""><path d="M5 8.5 20 2l15 6.5v13c0 8.4-6.1 13.2-15 16C11.1 34.7 5 29.9 5 21.5v-13Z" fill="#1E40AF"/><path d="M13 13h14M13 19h14M13 25h8" fill="none" stroke="#fff" stroke-linecap="round" stroke-width="2.5"/></svg>
                    <span><?= e($appName) ?></span>
                </a>
                <p class="mt-4 max-w-sm text-sm leading-7 text-slate-200">Premium CBT practice for JAMB, WAEC and NECO students. Train with realistic subject sets, timed exercises, and instant feedback.</p>
            </div>
            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-[.18em] text-amber-300">Practice</h3>
                <ul class="mt-4 space-y-3 text-sm text-slate-200">
                    <li><a href="#exam-selector" class="hover:text-amber-300">Choose exam</a></li>
                    <li><a href="#how-title" class="hover:text-amber-300">How it works</a></li>
                    <li><a href="#recent-title" class="hover:text-amber-300">Recent scores</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-[.18em] text-amber-300">Boards</h3>
                <ul class="mt-4 space-y-3 text-sm text-slate-200">
                    <li>JAMB</li>
                    <li>WAEC</li>
                    <li>NECO</li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-extrabold uppercase tracking-[.18em] text-amber-300">Support</h3>
                <ul class="mt-4 space-y-3 text-sm text-slate-200">
                    <li>Anonymous exam mode</li>
                    <li>Instant explanations</li>
                    <li>Subject-by-subject practice</li>
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col gap-3 border-t border-white/15 pt-6 text-sm text-slate-200 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; <?= date('Y') ?> <?= e($appName) ?>. All rights reserved. <a class="underline hover:text-amber-300" href="<?= e(url('/privacy')) ?>">Privacy</a> &middot; <a class="underline hover:text-amber-300" href="<?= e(url('/terms')) ?>">Terms</a></p>
            <p>Powered by <span class="font-semibold text-white"><?= e($appName) ?></span></p>
        </div>
    </div>
</footer>
<div class="launch" data-launch hidden aria-live="polite" role="status">
    <div class="launch__card">
        <div class="launch__ring"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 10-10-5-10 5 10 5 10-5Z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
        <p class="launch__hello" data-launch-hello>Get ready</p>
        <p class="launch__title" data-launch-title>Preparing your exam</p>
        <ul class="launch__steps">
            <li data-step>Setting up your questions</li>
            <li data-step>Shuffling the options</li>
            <li data-step>Starting your timer</li>
        </ul>
        <div class="launch__bar"><span></span></div>
    </div>
</div>
</body>
</html>