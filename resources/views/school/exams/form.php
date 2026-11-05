<?php
declare(strict_types=1);
/** @var array<string,mixed> $exam @var list<array> $classOptions @var list<string> $subjectNames @var array<string,int> $bankHealth @var array $errors @var array $old */
use ExamCore\Models\SchoolExam;

$v = static fn (string $k, string $d = '') => $old[$k] ?? ($exam[$k] ?? $d);
$on = static fn (string $k): string => (!empty($old) ? isset($old[$k]) : !empty($exam['settings'][$k])) ? 'checked' : '';
$papers = $old['papers'] ?? $exam['papers'];
$papers = array_values($papers);
while (count($papers) < 4) {
    $papers[] = ['subject' => '', 'mode' => 'random', 'question_count' => 20, 'topics' => [], 'difficulty' => ''];
}
$chosenClasses = array_map('intval', (array) ($old['classes'] ?? $exam['class_ids']));
$action = $exam['id'] ? url('/school/exams/' . $exam['id']) : url('/school/exams');
?>
<a href="<?= e(url('/school/exams')) ?>" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink"><?= icon('arrow-left', 'h-4 w-4') ?>Exams</a>
<h1 class="mt-3 font-display text-[30px] font-semibold text-ink"><?= $exam['id'] ? 'Edit exam' : 'New exam' ?></h1>
<form method="post" action="<?= e($action) ?>" class="mt-6 grid gap-6 lg:grid-cols-3" data-submit-once novalidate>
    <input type="hidden" name="_csrf_token" value="<?= e($csrfToken) ?>">
    <div class="space-y-6 lg:col-span-2">
        <section class="card card-body grid gap-5 sm:grid-cols-2">
            <?= field('title', 'Title', (string) $v('title'), $errors, ['class' => 'sm:col-span-2', 'required' => true, 'placeholder' => 'SS 2 First Term Mathematics Test']) ?>
            <?= field('type', 'Type', (string) $v('type', 'test'), $errors, ['type' => 'select', 'options' => SchoolExam::TYPES]) ?>
            <?= field('duration_minutes', 'Duration (minutes)', (string) $v('duration_minutes', '40'), $errors, ['type' => 'number', 'attrs' => ' min="5" max="360"']) ?>
            <?= field('starts_at', 'Opens', (string) $v('starts_at'), $errors, ['type' => 'datetime-local', 'hint' => 'earliest start']) ?>
            <?= field('ends_at', 'Closes', (string) $v('ends_at'), $errors, ['type' => 'datetime-local', 'hint' => 'last moment to finish']) ?>
            <?= field('instructions', 'Instructions for students', (string) $v('instructions'), $errors, ['type' => 'textarea', 'class' => 'sm:col-span-2']) ?>
        </section>

        <section class="card">
            <div class="card-header"><div><p class="eyebrow">Papers</p><h2 class="card-title mt-1">Subjects in this sitting</h2></div><span class="text-xs text-muted">Up to 4 (JAMB style)</span></div>
            <?= field_error($errors, 'papers') ?>
            <div class="divide-y divide-line border-t border-line">
                <?php foreach ($papers as $i => $paper): $topics = is_array($paper['topics'] ?? null) ? implode(', ', $paper['topics']) : (string) ($paper['topics'] ?? ''); ?>
                    <div class="grid gap-3 p-5 sm:grid-cols-[1.4fr_1fr_.7fr]">
                        <label><span class="label">Subject <?= $i + 1 ?></span>
                            <select class="select" name="papers[<?= $i ?>][subject]"><option value=""><?= $i === 0 ? 'Choose a subject' : 'None' ?></option>
                                <?php foreach ($subjectNames as $name): ?><option value="<?= e($name) ?>" <?= ($paper['subject'] ?? '') === $name ? 'selected' : '' ?>><?= e($name) ?> (<?= (int) ($bankHealth[$name] ?? 0) ?> published)</option><?php endforeach; ?>
                            </select></label>
                        <label><span class="label">Questions</span>
                            <select class="select" name="papers[<?= $i ?>][mode]"><option value="random" <?= ($paper['mode'] ?? '') !== 'fixed' ? 'selected' : '' ?>>Random from bank</option><option value="fixed" <?= ($paper['mode'] ?? '') === 'fixed' ? 'selected' : '' ?>>Hand-picked</option></select></label>
                        <label><span class="label">How many</span><input class="input" type="number" min="1" max="200" name="papers[<?= $i ?>][question_count]" value="<?= (int) ($paper['question_count'] ?? 20) ?>"></label>
                        <label class="sm:col-span-2"><span class="label">Only these topics <span class="label-hint">· optional, comma-separated</span></span><input class="input" name="papers[<?= $i ?>][topics]" value="<?= e($topics) ?>" placeholder="Indices, Algebra"></label>
                        <label><span class="label">Difficulty</span><select class="select" name="papers[<?= $i ?>][difficulty]"><option value="">Mixed</option><?php foreach (\ExamCore\Models\BankQuestion::DIFFICULTIES as $k => $l): ?><option value="<?= $k ?>" <?= ($paper['difficulty'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="px-5 pb-5 text-xs text-muted">“Random from bank” gives every student a different set of questions, which makes copying hard. Keep at least twice as many published questions as you ask for.</p>
        </section>
    </div>

    <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
        <section class="card card-body">
            <p class="label">Classes</p>
            <?= field_error($errors, 'classes') ?>
            <div class="mt-1 grid max-h-64 grid-cols-2 gap-2 overflow-y-auto">
                <?php foreach ($classOptions as $c): ?>
                    <label class="flex items-center gap-2 rounded-xl border border-line px-3 py-2 text-sm has-[:checked]:border-brand/40 has-[:checked]:bg-brand/5"><input type="checkbox" class="checkbox" name="classes[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $chosenClasses, true) ? 'checked' : '' ?>><?= e($c['name']) ?></label>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="card card-body space-y-3 text-sm">
            <p class="label">Settings</p>
            <?php foreach (['shuffle_questions' => 'Shuffle question order', 'shuffle_options' => 'Shuffle options A–D', 'allow_back' => 'Allow going back to earlier questions', 'calculator' => 'Show on-screen calculator', 'show_review' => 'Let students review answers after results'] as $key => $label): ?>
                <label class="flex items-center gap-2.5"><input type="checkbox" class="checkbox" name="<?= $key ?>" value="1" <?= $on($key) ?>><?= e($label) ?></label>
            <?php endforeach; ?>
            <?= field('result_release', 'Release results', (string) ($old['result_release'] ?? $exam['settings']['result_release']), [], ['type' => 'select', 'options' => ['instant' => 'Immediately after submitting', 'after_window' => 'When the exam window closes', 'manual' => 'When I release them']]) ?>
            <div class="grid grid-cols-2 gap-3">
                <?= field('pass_mark', 'Pass mark %', (string) ($old['pass_mark'] ?? $exam['settings']['pass_mark']), [], ['type' => 'number', 'attrs' => ' min="0" max="100"']) ?>
                <?= field('access_code', 'Access code', (string) ($old['access_code'] ?? $exam['settings']['access_code']), [], ['hint' => 'optional', 'placeholder' => 'e.g. MATHS7']) ?>
            </div>
        </section>
        <section class="card card-body space-y-3 text-sm">
            <p class="label flex items-center gap-2"><?= icon('shield', 'h-4 w-4 text-brand') ?>Exam security</p>
            <?php $sv = static fn (string $k) => $old[$k] ?? $exam['settings'][$k]; ?>
            <?= field('proctoring', 'Camera check', (string) $sv('proctoring'), [], ['type' => 'select', 'options' => SchoolExam::PROCTORING]) ?>
            <div class="grid grid-cols-2 gap-3">
                <?= field('snapshot_minutes', 'Photo every (min)', (string) $sv('snapshot_minutes'), [], ['type' => 'number', 'attrs' => ' min="1" max="30"']) ?>
                <?= field('max_warnings', 'Auto-submit after', (string) $sv('max_warnings'), [], ['type' => 'number', 'hint' => 'warnings · 0 = never', 'attrs' => ' min="0" max="50"']) ?>
            </div>
            <?php foreach (['single_device' => 'One device only (blocks signing in on a second phone)', 'require_fullscreen' => 'Require full screen (computers)', 'ask_seat' => 'Ask for seat / computer number'] as $key => $label): ?>
                <label class="flex items-center gap-2.5"><input type="checkbox" class="checkbox" name="<?= $key ?>" value="1" <?= $on($key) ?>><?= e($label) ?></label>
            <?php endforeach; ?>
            <?= field('invigilator_pin', 'Invigilator PIN', (string) $sv('invigilator_pin'), [], ['hint' => '4–8 digits · lets a supervisor admit a student whose face check failed', 'attrs' => ' inputmode="numeric" autocomplete="off" maxlength="8"']) ?>
        </section>
        <button class="btn btn-primary btn-lg w-full"><?= icon('check') ?><span data-label>Save draft</span></button>
    </aside>
</form>
