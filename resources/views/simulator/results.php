<?php
declare(strict_types=1);
$assetRoot = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') . '/assets' : './assets';
$homeUrl = rtrim((string) $baseUrl, '/') !== '' ? rtrim((string) $baseUrl, '/') . '/' : './';
$minutesUsed = intdiv($timeUsedSeconds, 60);
$secondsUsed = $timeUsedSeconds % 60;
$minutesLimit = intdiv($timeLimitSeconds, 60);
$secondsLimit = $timeLimitSeconds % 60;
$optionLabels = ['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'];
$questionCount = count($questions);
$correctCount = 0;
$wrongCount = 0;
$unansweredCount = 0;
foreach ($questions as $question) {
    $selected = $question['selected_option'] ?? null;
    if ($selected === null || $selected === '') {
        $unansweredCount++;
    } elseif ((string) $selected === (string) $question['correct_option']) {
        $correctCount++;
    } else {
        $wrongCount++;
    }
}
$accuracy = $questionCount > 0 ? round(($correctCount / $questionCount) * 100) : 0;
$statusClass = $passed ? 'success' : ($percent >= 35 ? 'warning' : 'danger');
$statusText = $passed ? 'Passed' : ($percent >= 35 ? 'Borderline' : 'Needs work');
$scoreTone = $passed ? 'Excellent performance' : ($percent >= 35 ? 'Close to the pass mark' : 'More practice will help');
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <?php require __DIR__ . '/../partials/pwa-tags.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1E40AF">
    <meta name="description" content="CBT exam results dashboard with detailed review and score breakdown.">
    <title>Practice results | <?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/results-dashboard.css')) ?>">
    <script defer src="<?= e(asset('js/results.js')) ?>"></script>
</head>
<body>
    <header class="topbar">
        <div class="site-shell topbar-inner">
            <a href="<?= e($homeUrl) ?>" class="brand" aria-label="Go back to the homepage">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 40 40" width="18" height="18" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 9.5L20 2L35 9.5V20.5C35 27.6 29.3 33 20 36C10.7 33 5 27.6 5 20.5V9.5Z" fill="white" opacity=".2"/>
                        <path d="M11 14H29M11 20H29M11 26H23" stroke="white" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="brand-title">ExamCore <strong>CBT</strong></span>
            </a>

            <nav class="topbar-nav" aria-label="Result page navigation">
                <a href="#overview">Overview</a>
                <a href="#performance">Performance</a>
                <a href="#review">Review answers</a>
                <a href="#insights">Insights</a>
            </nav>

            <div class="topbar-actions">
                <button type="button" class="pill-toggle" data-theme-toggle aria-label="Toggle dark mode">☀️</button>
                <a href="<?= e($homeUrl) ?>" class="secondary-action">Practice again</a>
                <button type="button" class="mobile-menu-button" data-mobile-menu-button aria-label="Open menu" aria-expanded="false">☰</button>
            </div>
        </div>
    </header>

    <div class="mobile-panel" data-mobile-panel aria-label="Mobile menu">
        <div class="mobile-panel-header">
            <div>
                <div class="panel-kicker mobile-menu-heading">Menu</div>
                <div class="panel-title mobile-menu-title">Explore</div>
            </div>
            <button type="button" class="pill-toggle" data-mobile-menu-close aria-label="Close menu">✕</button>
        </div>
        <a href="#overview">Overview <span>→</span></a>
        <a href="#performance">Performance <span>→</span></a>
        <a href="#review">Review answers <span>→</span></a>
        <a href="#insights">Insights <span>→</span></a>
    </div>

    <main class="site-shell">
        <?php if ($submitted || $timeExpired): ?>
            <div class="panel submit-banner">
                <div class="panel-body submit-banner-body">
                    <div class="status-pill success submit-banner-status">✓ Submitted</div>
                    <div class="submit-banner-meta">
                        <div class="submit-banner-copy">
                            <strong class="submit-banner-title">Your exam has been submitted successfully.</strong>
                            <span class="submit-banner-text">Your score, explanations, and review dashboard are ready below.</span>
                        </div>
                        <?php if ($timeExpired): ?>
                            <span class="status-pill warning">Time expired</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <section id="overview" class="results-hero" data-reveal>
            <div class="results-hero-grid">
                <div>
                    <div class="badge"><span class="badge-dot" aria-hidden="true"></span> Exam submitted</div>
                    <h1 class="hero-title">Hello <?= e((string) ($attempt['student_name'] ?? 'Student')) ?>!</h1>
                    <p class="hero-subtitle">You have successfully completed <?= e((string) $examCount) ?> exam<?= $examCount > 1 ? 's' : '' ?> and your score is ready below.</p>
                    <p class="hero-subtitle mt-2">A copy of this result has been sent to <?= e((string) ($attempt['student_email'] ?? 'your email address')) ?>.</p>
                    <p class="hero-subtitle"><?= e($scoreTone) ?> — <?= e($passed ? 'You met the pass standard for this CBT exercise.' : 'Review the explanation panel below to improve your weak areas and retake with confidence.') ?></p>

                    <div class="hero-metrics">
                        <div class="metric-card">
                            <span class="metric-label">Score</span>
                            <span class="metric-value"><?= e(number_format((float) $score, 0)) ?>/<?= e(number_format((float) $total, 0)) ?></span>
                        </div>
                        <div class="metric-card">
                            <span class="metric-label">Accuracy</span>
                            <span class="metric-value"><?= e((string) $accuracy) ?>%</span>
                        </div>
                        <div class="metric-card">
                            <span class="metric-label">Time</span>
                            <span class="metric-value"><?= sprintf('%02dm %02ds', $minutesUsed, $secondsUsed) ?></span>
                        </div>
                    </div>
                </div>

                <div class="score-panel">
                    <div class="score-ring" aria-label="Score ring representing the current exam result">
                        <div class="score-ring-inner">
                            <div>
                                <span class="score-ring-value"><?= e((string) $percent) ?>%</span>
                                <span class="score-ring-text"><?= $passed ? 'Pass' : 'Retry' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="dashboard-grid">
            <section id="performance" class="panel" data-reveal>
                <div class="panel-head">
                    <div>
                        <div class="panel-kicker">Performance</div>
                        <h2 class="panel-title">Exam summary dashboard</h2>
                    </div>
                    <span class="status-pill <?= $statusClass ?>"><?= $statusText ?></span>
                </div>
                <div class="panel-body">
                    <div class="stat-grid">
                        <div class="stat-card">
                            <span class="label">Correct</span>
                            <span class="value value-success"><?= e((string) $correctCount) ?></span>
                        </div>
                        <div class="stat-card">
                            <span class="label">Wrong</span>
                            <span class="value value-danger"><?= e((string) $wrongCount) ?></span>
                        </div>
                        <div class="stat-card">
                            <span class="label">Unanswered</span>
                            <span class="value value-warning"><?= e((string) $unansweredCount) ?></span>
                        </div>
                        <div class="stat-card">
                            <span class="label">Pass mark</span>
                            <span class="value value-primary">50%</span>
                        </div>
                    </div>

                    <div class="insight-list" id="insights">
                        <div class="insight-item">
                            <div class="insight-icon">✓</div>
                            <div>
                                <strong><?= $passed ? 'You passed this exam' : 'You are close to passing' ?></strong>
                                <small><?= $passed ? 'Your score is above the required benchmark.' : 'A few more correct answers would move you above the pass mark.' ?></small>
                            </div>
                        </div>
                        <div class="insight-item">
                            <div class="insight-icon">⏱</div>
                            <div>
                                <strong>Time usage</strong>
                                <small><?= sprintf('%02dm %02ds', $minutesUsed, $secondsUsed) ?> used out of <?= sprintf('%02dm %02ds', $minutesLimit, $secondsLimit) ?> total.</small>
                            </div>
                        </div>
                        <div class="insight-item">
                            <div class="insight-icon">◎</div>
                            <div>
                                <strong>Exam focus</strong>
                                <small><?= e($wrongCount > 0 ? 'Your mistakes are concentrated in a few questions; the review below highlights them.' : 'Excellent work — your answers were accurate across the paper.') ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="panel" data-reveal>
                <div class="panel-head">
                    <div>
                        <div class="panel-kicker">Progress</div>
                        <h2 class="panel-title panel-title-small">Your performance</h2>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="summary-box">
                        <div class="summary-header">
                            <span class="summary-label">Result</span>
                            <span class="status-pill <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>
                        <div class="summary-score"><?= e((string) $percent) ?>%</div>
                        <div class="progress-track">
                            <div class="progress-bar" data-progress="<?= max(6, min(100, (int) $percent)) ?>"></div>
                        </div>
                        <p class="summary-copy"><?= e($scoreTone) ?>. Review the answer breakdown to understand the exact areas to improve.</p>
                    </div>

                    <div class="review-toolbar review-toolbar-compact">
                        <form action="<?= e($baseUrl) ?>/start" method="post" class="retake-form">
                            <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="exam_id" value="<?= e((string) $attempt['exam_id']) ?>">
                            <button type="submit" class="primary-action">Retake exam</button>
                        </form>
                        <a href="<?= e($homeUrl) ?>" class="small-action">New subject</a>
                    </div>
                </div>
            </aside>
        </div>

        <section id="review" class="panel review-panel-shell" data-reveal>
            <div class="panel-head">
                <div>
                    <div class="panel-kicker">Answer review</div>
                    <h2 class="panel-title">Detailed question analysis</h2>
                </div>
            </div>
            <div class="panel-body">
                <div class="review-toolbar" aria-label="Review view switcher">
                    <button type="button" class="review-toggle is-active" data-review-tab="all">All questions</button>
                    <button type="button" class="review-toggle" data-review-tab="incorrect">Incorrect</button>
                    <button type="button" class="review-toggle" data-review-tab="correct">Correct</button>
                </div>

                <div class="review-panels">
                    <div class="review-panel is-visible" data-review-panel="all">
                        <ol class="review-list">
                            <?php foreach ($questions as $index => $question): ?>
                                <?php
                                $selected = $question['selected_option'] ?? null;
                                $correctOption = (string) $question['correct_option'];
                                $isCorrect = (bool) $question['is_correct'];
                                $optionMap = ['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'];
                                $selectedText = is_string($selected) && isset($optionMap[$selected]) ? $question[$optionMap[$selected]] : 'No answer';
                                $correctText = $question[$optionMap[$correctOption]] ?? '';
                                ?>
                                <li class="review-item">
                                    <div class="review-item-head">
                                        <span class="review-number"><?= e((string) ($index + 1)) ?></span>
                                        <div class="review-copy">
                                            <h4><?= e($question['question_text']) ?></h4>
                                            <div class="review-choices">
                                                <?php foreach (['A', 'B', 'C', 'D'] as $label): ?>
                                                    <?php
                                                    $choiceKey = $optionMap[$label] ?? null;
                                                    $text = $choiceKey !== null ? $question[$choiceKey] : '';
                                                    $choiceClass = '';
                                                    if ((string) $label === (string) $correctOption) {
                                                        $choiceClass = 'is-correct';
                                                    }
                                                    if (is_string($selected) && (string) $label === (string) $selected && (string) $selected !== (string) $correctOption) {
                                                        $choiceClass = 'is-selected';
                                                    }
                                                    ?>
                                                    <div class="choice <?= e($choiceClass) ?>">
                                                        <span class="choice-letter"><?= e($label) ?></span>
                                                        <span><?= e($text) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="explanation"><strong>Explanation:</strong> <?= e($question['explanation']) ?></div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>

                    <div class="review-panel" data-review-panel="incorrect">
                        <ol class="review-list">
                            <?php foreach ($questions as $question): ?>
                                <?php if ((bool) $question['is_correct']) continue; ?>
                                <?php
                                $selected = $question['selected_option'] ?? null;
                                $correctOption = (string) $question['correct_option'];
                                $optionMap = ['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'];
                                ?>
                                <li class="review-item">
                                    <div class="review-item-head">
                                        <span class="review-number">!</span>
                                        <div class="review-copy">
                                            <h4><?= e($question['question_text']) ?></h4>
                                            <div class="review-choices">
                                                <?php foreach (['A', 'B', 'C', 'D'] as $label): ?>
                                                    <?php $text = $optionMap[$label] !== null ? $question[$optionMap[$label]] : ''; ?>
                                                    <div class="choice <?= (string) $label === (string) $correctOption ? 'is-correct' : '' ?> <?= is_string($selected) && (string) $label === (string) $selected && (string) $label !== (string) $correctOption ? 'is-selected' : '' ?>">
                                                        <span class="choice-letter"><?= e($label) ?></span>
                                                        <span><?= e($text) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="explanation"><strong>Correct answer:</strong> <?= e($question[$optionMap[$correctOption] ?? 'option_a']) ?>. <?= e($question['explanation']) ?></div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>

                    <div class="review-panel" data-review-panel="correct">
                        <ol class="review-list">
                            <?php foreach ($questions as $question): ?>
                                <?php if (!(bool) $question['is_correct']) continue; ?>
                                <li class="review-item">
                                    <div class="review-item-head">
                                        <span class="review-number">✓</span>
                                        <div class="review-copy">
                                            <h4><?= e($question['question_text']) ?></h4>
                                            <div class="explanation"><strong>Reasoning:</strong> <?= e($question['explanation']) ?></div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <footer class="footer">
            <div class="footer-inner">
                <div>ExamCore CBT • Premium practice dashboard</div>
                <div>Built for confident revision, score tracking, and better exam performance.</div>
            </div>
        </footer>
    </main>
</body>
</html>
