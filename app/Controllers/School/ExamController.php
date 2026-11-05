<?php
declare(strict_types=1);

namespace ExamCore\Controllers\School;

use DateTimeImmutable;
use DateTimeZone;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\AcademicCalendar;
use ExamCore\Models\BankQuestion;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\SchoolExam;
use ExamCore\Models\SchoolSubject;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;

/** Exam builder & scheduling for school admins and teachers. */
final class ExamController extends SchoolController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, TenantContext $tenant,
        private readonly SchoolExam $exams,
        private readonly SchoolClass $classes,
        private readonly SchoolSubject $subjects,
        private readonly BankQuestion $bank,
        private readonly AcademicCalendar $calendar,
        private readonly AuditService $audit,
        private readonly \ExamCore\Models\ExamSitting $sittings,
        private readonly \ExamCore\Services\SittingService $sittingService,
        private readonly \ExamCore\Services\ProctorService $proctor,
        private readonly \ExamCore\Models\Student $students,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf, $tenant);
    }

    public function index(Request $request): Response
    {
        $tab = in_array($request->query('tab'), ['draft', 'upcoming', 'live', 'past'], true) ? (string) $request->query('tab') : 'upcoming';
        $author = $this->isAdmin() ? null : (int) $this->guard->id();
        $counts = [];
        foreach (['live', 'upcoming', 'draft', 'past'] as $t) {
            $counts[$t] = count($this->exams->list($this->schoolId(), $t, $author));
        }
        return $this->page($request, 'school/exams/index', $this->base('Exams & schedule') + ['tab' => $tab, 'counts' => $counts, 'rows' => $this->exams->list($this->schoolId(), $tab, $author)]);
    }

    public function create(Request $request): Response
    {
        $start = (new DateTimeImmutable('tomorrow 09:00'));
        return $this->page($request, 'school/exams/form', $this->base('New exam') + $this->formData() + ['exam' => [
            'title' => '', 'type' => 'test', 'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_at' => $start->modify('+2 hours')->format('Y-m-d\TH:i'),
            'duration_minutes' => 40, 'instructions' => "Answer all questions.\nYour answers save automatically.\nThe exam submits itself when time runs out.", 'settings' => SchoolExam::DEFAULT_SETTINGS,
            'papers' => [], 'class_ids' => [], 'id' => null,
        ]]);
    }

    public function store(Request $request): Response
    {
        return $this->persist($request, null);
    }

    public function edit(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        if ($exam['status'] !== 'draft') {
            return $this->redirectTo('/school/exams/' . $exam['id'], 'Unpublish the exam before editing it.', 'warning');
        }
        $exam['starts_at'] = $this->local($exam['starts_at']);
        $exam['ends_at'] = $this->local($exam['ends_at']);
        return $this->page($request, 'school/exams/form', $this->base('Edit exam') + $this->formData() + ['exam' => $exam]);
    }

    public function update(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        if ($exam['status'] !== 'draft') {
            return $this->redirectTo('/school/exams/' . $exam['id'], 'Unpublish the exam before editing it.', 'warning');
        }
        return $this->persist($request, (int) $exam['id']);
    }

    public function show(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        return $this->page($request, 'school/exams/show', $this->base($exam['title']) + [
            'exam' => $exam, 'checks' => $this->readiness($exam), 'state' => $this->state($exam),
            'classNames' => array_column(array_filter($this->classes->all($this->schoolId()), static fn ($c) => in_array((int) $c['id'], $exam['class_ids'], true)), 'student_count', 'name'),
        ]);
    }

    public function action(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        $id = (int) $exam['id'];
        switch ((string) $request->input('action')) {
            case 'publish':
                $failed = array_filter($this->readiness($exam), static fn (array $c): bool => !$c['ok']);
                if ($failed !== []) {
                    return $this->redirectTo('/school/exams/' . $id, 'Fix the items marked in red before publishing.', 'error');
                }
                $this->exams->setStatus($this->schoolId(), $id, 'published');
                $this->audit->record('exam.published', 'Published “' . $exam['title'] . '”', 'exam', $id, schoolId: $this->schoolId());
                return $this->redirectTo('/school/exams/' . $id, 'Published. Students in the selected classes can now see it.');
            case 'unpublish':
                if ($this->state($exam) !== 'upcoming') {
                    return $this->redirectTo('/school/exams/' . $id, 'Only exams that have not started can be unpublished.', 'error');
                }
                $this->exams->setStatus($this->schoolId(), $id, 'draft');
                return $this->redirectTo('/school/exams/' . $id . '/edit', 'Back to draft. Students can no longer see it.', 'warning');
            case 'cancel':
                $this->exams->setStatus($this->schoolId(), $id, 'cancelled');
                $this->audit->record('exam.cancelled', 'Cancelled “' . $exam['title'] . '”', 'exam', $id, schoolId: $this->schoolId());
                return $this->redirectTo('/school/exams?tab=past', 'Exam cancelled.', 'warning');
            case 'release':
                $this->exams->releaseResults($this->schoolId(), $id);
                $this->audit->record('exam.results_released', 'Released results of “' . $exam['title'] . '”', 'exam', $id, schoolId: $this->schoolId());
                return $this->redirectTo('/school/exams/' . $id . '/monitor', 'Results released. Students can now see their scores.');
            case 'duplicate':
                $copy = $exam;
                $copy['title'] = mb_substr('Copy of ' . $exam['title'], 0, 160);
                $newId = $this->exams->save($this->schoolId(), null, (int) $this->guard->id(), $copy, $exam['papers'], $exam['class_ids']);
                foreach ($this->exams->find($this->schoolId(), $newId)['papers'] as $i => $paper) {
                    $this->exams->setFixedQuestions((int) $paper['id'], $this->exams->fixedQuestionIds((int) $exam['papers'][$i]['id']));
                }
                return $this->redirectTo('/school/exams/' . $newId . '/edit', 'Copied as a new draft. Adjust the dates and publish.');
        }
        return $this->redirectTo('/school/exams/' . $id, 'Unknown action.', 'error');
    }

    /** Hand-pick questions for a fixed paper. */
    public function pick(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        $paper = $this->paper($exam, $parameters);
        $result = $this->bank->search($this->schoolId(), ['subject' => $paper['subject'], 'status' => 'published', 'q' => (string) $request->query('q', ''), 'topic' => (string) $request->query('topic', '')], 1, 100);
        return $this->page($request, 'school/exams/pick', $this->base('Choose questions') + [
            'exam' => $exam, 'paper' => $paper, 'rows' => $result['rows'], 'total' => $result['total'], 'selected' => $this->exams->fixedQuestionIds((int) $paper['id']),
            'topics' => $this->bank->topics($this->schoolId(), $paper['subject']), 'q' => (string) $request->query('q', ''), 'topic' => (string) $request->query('topic', ''),
        ]);
    }

    public function savePick(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        $paper = $this->paper($exam, $parameters);
        $ids = [];
        foreach (is_array($request->input('ids')) ? $request->input('ids') : [] as $qid) {
            $q = $this->bank->find($this->schoolId(), (int) $qid);
            if ($q !== null && $q['status'] === 'published' && $q['subject'] === $paper['subject']) {
                $ids[] = (int) $qid;
            }
        }
        $this->exams->setFixedQuestions((int) $paper['id'], $ids);
        return $this->redirectTo('/school/exams/' . $exam['id'], count($ids) . ' question(s) chosen for ' . $paper['subject'] . '.');
    }

    // ---- Internals -----------------------------------------------------------------------------

    private function persist(Request $request, ?int $id): Response
    {
        $in = $this->input($request);
        $errors = [];
        $title = mb_substr(trim((string) ($in['title'] ?? '')), 0, 160);
        if (mb_strlen($title) < 3) {
            $errors['title'] = 'Give the exam a title, e.g. “SS 2 First Term Mathematics Test”.';
        }
        $starts = $this->utc((string) ($in['starts_at'] ?? ''));
        $ends = $this->utc((string) ($in['ends_at'] ?? ''));
        $duration = (int) ($in['duration_minutes'] ?? 0);
        if ($starts === null || $ends === null || $ends <= $starts) {
            $errors['ends_at'] = 'The window must end after it starts.';
        }
        if ($duration < 5 || $duration > 360) {
            $errors['duration_minutes'] = 'Duration must be between 5 and 360 minutes.';
        } elseif ($starts !== null && $ends !== null && strtotime($ends . ' UTC') - strtotime($starts . ' UTC') < $duration * 60) {
            $errors['duration_minutes'] = 'The window is shorter than the duration; students would not have enough time.';
        }
        $allowedClasses = array_column($this->allowedClasses(), 'id');
        $classIds = array_values(array_intersect(array_map('intval', is_array($in['classes'] ?? null) ? $in['classes'] : []), array_map('intval', $allowedClasses)));
        if ($classIds === []) {
            $errors['classes'] = 'Choose at least one class' . ($this->isAdmin() ? '.' : ' that you teach.');
        }
        $subjectNames = array_column($this->subjects->all($this->schoolId(), true), 'name');
        $papers = [];
        foreach (is_array($in['papers'] ?? null) ? $in['papers'] : [] as $paper) {
            $subject = (string) ($paper['subject'] ?? '');
            if ($subject === '' || !in_array($subject, $subjectNames, true) || isset($papers[$subject])) {
                continue;
            }
            $papers[$subject] = [
                'subject' => $subject, 'mode' => ($paper['mode'] ?? '') === 'fixed' ? 'fixed' : 'random',
                'question_count' => max(1, min(200, (int) ($paper['question_count'] ?? 20))),
                'topics' => array_values(array_filter(array_map('trim', explode(',', (string) ($paper['topics'] ?? ''))))),
                'difficulty' => array_key_exists((string) ($paper['difficulty'] ?? ''), BankQuestion::DIFFICULTIES) ? (string) $paper['difficulty'] : '',
            ];
        }
        if ($papers === []) {
            $errors['papers'] = 'Add at least one subject paper.';
        }
        if (count($papers) > 4) {
            $errors['papers'] = 'Use at most 4 subjects in one sitting (JAMB style).';
        }
        if ($errors !== []) {
            return $this->backWithErrors($id === null ? '/school/exams/create' : '/school/exams/' . $id . '/edit', $errors, $in);
        }
        $current = $this->calendar->current($this->schoolId());
        $settings = [
            'shuffle_questions' => isset($in['shuffle_questions']), 'shuffle_options' => isset($in['shuffle_options']), 'allow_back' => isset($in['allow_back']),
            'calculator' => isset($in['calculator']), 'show_review' => isset($in['show_review']),
            'result_release' => in_array($in['result_release'] ?? '', ['instant', 'after_window', 'manual'], true) ? $in['result_release'] : 'after_window',
            'pass_mark' => max(0, min(100, (int) ($in['pass_mark'] ?? 50))),
            'access_code' => mb_substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($in['access_code'] ?? '')) ?? ''), 0, 12),
            'proctoring' => in_array($in['proctoring'] ?? '', ['off', 'presence', 'match'], true) ? $in['proctoring'] : 'off',
            'snapshot_minutes' => max(1, min(30, (int) ($in['snapshot_minutes'] ?? 5))),
            'require_fullscreen' => isset($in['require_fullscreen']), 'single_device' => isset($in['single_device']), 'ask_seat' => isset($in['ask_seat']),
            'max_warnings' => max(0, min(50, (int) ($in['max_warnings'] ?? 0))),
            'invigilator_pin' => mb_substr(preg_replace('/\D/', '', (string) ($in['invigilator_pin'] ?? '')) ?? '', 0, 8),
            'delivery' => ($in['delivery'] ?? '') === 'paper' ? 'paper' : 'cbt',
            'versions' => max(1, min(4, (int) ($in['versions'] ?? 2))),
            'theory' => mb_substr(trim(str_replace("\r", '', (string) ($in['theory'] ?? ''))), 0, 6000),
            'theory_max' => max(0, min(100, (int) ($in['theory_max'] ?? 0))),
        ];
        if ($settings['delivery'] === 'paper') {
            if (array_sum(array_column($papers, 'question_count')) > 100) {
                $errors['papers'] = 'A shading sheet holds at most 100 objective questions in total.';
            }
            if ($settings['theory_max'] > 0 && count($papers) > 1) {
                $errors['theory_max'] = 'Theory marks are only supported when the paper has one subject.';
            }
            if ($errors !== []) {
                return $this->backWithErrors($id === null ? '/school/exams/create' : '/school/exams/' . $id . '/edit', $errors, $in);
            }
        }
        $id = $this->exams->save($this->schoolId(), $id, (int) $this->guard->id(), [
            'term_id' => $current['term']['id'] ?? null, 'title' => $title, 'type' => array_key_exists($in['type'] ?? '', SchoolExam::TYPES) ? $in['type'] : 'test',
            'starts_at' => $starts, 'ends_at' => $ends, 'duration_minutes' => $duration, 'instructions' => mb_substr(trim((string) ($in['instructions'] ?? '')), 0, 3000) ?: null, 'settings' => $settings,
        ], array_values($papers), $classIds);
        $this->audit->record('exam.saved', 'Saved exam “' . $title . '”', 'exam', $id, schoolId: $this->schoolId());
        return $this->redirectTo('/school/exams/' . $id, 'Saved as a draft. Check readiness, then publish.');
    }

    /** @return list<array{label: string, ok: bool, detail: string}> */
    private function readiness(array $exam): array
    {
        $checks = [];
        $checks[] = ['label' => 'Starts in the future', 'ok' => strtotime($exam['starts_at'] . ' UTC') > time(), 'detail' => 'Opens ' . local_date($exam['starts_at'])];
        $checks[] = ['label' => 'Classes assigned', 'ok' => $exam['class_ids'] !== [], 'detail' => count($exam['class_ids']) . ' class(es)'];
        if (($exam['settings']['proctoring'] ?? 'off') === 'match' && $exam['class_ids'] !== []) {
            [$total, $missing] = $this->exams->photoGaps($this->schoolId(), $exam['class_ids']);
            // Advisory only: students without a photo are still checked for a face, just not matched.
            $checks[] = ['label' => 'Student photos for face matching', 'ok' => true, 'detail' => $missing === 0 ? "All {$total} students have photos" : "{$missing} of {$total} have no photo (face presence only)"];
        }
        if (($exam['settings']['proctoring'] ?? 'off') !== 'off' && ($exam['settings']['invigilator_pin'] ?? '') === '') {
            $checks[] = ['label' => 'Invigilator PIN', 'ok' => true, 'detail' => 'Not set · a student whose camera fails cannot be admitted'];
        }
        foreach ($exam['papers'] as $paper) {
            if ($paper['mode'] === 'fixed') {
                $ok = (int) $paper['fixed_count'] >= (int) $paper['question_count'];
                $checks[] = ['label' => $paper['subject'] . ': questions chosen', 'ok' => $ok, 'detail' => $paper['fixed_count'] . ' of ' . $paper['question_count'] . ' chosen'];
            } else {
                $available = $this->exams->availableCount($this->schoolId(), $paper['subject'], $paper['topics'], $paper['difficulty']);
                $checks[] = ['label' => $paper['subject'] . ': enough published questions', 'ok' => $available >= (int) $paper['question_count'],
                    'detail' => $available . ' available for ' . $paper['question_count'] . ' per student' . ($available >= (int) $paper['question_count'] * 2 ? ' · good variety' : '')];
            }
        }
        return $checks;
    }

    private function state(array $exam): string
    {
        if ($exam['status'] !== 'published') {
            return $exam['status'];
        }
        $now = time();
        return $now < strtotime($exam['starts_at'] . ' UTC') ? 'upcoming' : ($now <= strtotime($exam['ends_at'] . ' UTC') ? 'live' : 'closed');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return ['classOptions' => $this->allowedClasses(), 'subjectNames' => array_column($this->subjects->all($this->schoolId(), true), 'name'),
            'bankHealth' => array_column($this->bank->health($this->schoolId()), 'published', 'subject')];
    }

    /** @return list<array<string, mixed>> admins: every class; teachers: classes they teach or form-teach */
    private function allowedClasses(): array
    {
        $all = $this->classes->all($this->schoolId(), true);
        if ($this->isAdmin()) {
            return $all;
        }
        $mine = array_column($this->subjects->forTeacher($this->schoolId(), (int) $this->guard->id()), 'class_id');
        return array_values(array_filter($all, fn ($c) => in_array($c['id'], $mine, false) || (int) $c['class_teacher_id'] === (int) $this->guard->id()));
    }

    private function isAdmin(): bool
    {
        return $this->guard->role() === User::ROLE_SCHOOL_ADMIN;
    }

    private function utc(string $local): ?string
    {
        try {
            return $local === '' ? null : (new DateTimeImmutable($local))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
    }

    private function local(string $utc): string
    {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d\TH:i');
    }

    /** @return array<string, mixed> */
    /** Live monitor: who is writing, who has submitted, warnings, and invigilator actions. */
    public function monitor(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        return $this->page($request, 'school/exams/monitor', [
            'title' => 'Monitor · ' . $exam['title'], 'exam' => $exam, 'state' => $this->state($exam),
            'roster' => $this->monitorRows((int) $exam['id']),
        ]);
    }

    public function monitorData(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        return Response::json(['state' => $this->state($exam), 'rows' => $this->monitorRows((int) $exam['id'])]);
    }

    public function sitting(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        $sitting = $this->sittings->find((int) ($parameters['sitting'] ?? 0));
        if ($sitting === null || (int) $sitting['exam_id'] !== (int) $exam['id'] || (int) $sitting['school_id'] !== $this->schoolId()) {
            throw HttpException::notFound('That sitting could not be found.');
        }
        $back = '/school/exams/' . $exam['id'] . '/monitor';
        $who = mb_substr((string) ($request->input('name') ?? 'student'), 0, 80);
        switch ((string) $request->input('action')) {
            case 'add_time':
                $minutes = max(1, min(120, (int) $request->input('minutes', 5)));
                if ($sitting['status'] !== 'in_progress') {
                    return $this->redirectTo($back, 'That student has already submitted.', 'error');
                }
                $this->sittings->extend((int) $sitting['id'], $minutes);
                $this->audit->record('sitting.time_added', "Added {$minutes} min for {$who} in “{$exam['title']}”", 'exam', (int) $exam['id'], schoolId: $this->schoolId());
                return $this->redirectTo($back, "Added {$minutes} minute(s). The student's timer updates within 30 seconds.");
            case 'submit':
                $this->sittingService->submit((int) $sitting['id'], true);
                $this->audit->record('sitting.force_submitted', "Submitted for {$who} in “{$exam['title']}”", 'exam', (int) $exam['id'], schoolId: $this->schoolId());
                return $this->redirectTo($back, 'Submitted and marked.');
            case 'release_device':
                $this->sittings->setDevice((int) $sitting['id'], null);
                $this->audit->record('sitting.device_released', "Allowed {$who} to continue on a new device in “{$exam['title']}”", 'exam', (int) $exam['id'], schoolId: $this->schoolId());
                return $this->redirectTo($back, 'Done. The student can now continue on the next device they sign in with.');
            case 'reset':
                if (!$this->isAdmin()) {
                    throw HttpException::forbidden('Only an administrator can let a student restart.');
                }
                $this->sittings->delete((int) $sitting['id']);
                $this->audit->record('sitting.reset', "Reset {$who}'s sitting in “{$exam['title']}”", 'exam', (int) $exam['id'], schoolId: $this->schoolId());
                return $this->redirectTo($back, 'Cleared. The student can start again with a new paper.', 'warning');
        }
        return $this->redirectTo($back, 'Unknown action.', 'error');
    }

    /** Integrity report for one student's sitting: identity check, alerts, snapshots, timeline. */
    public function report(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        $sitting = $this->ownedSitting($exam, $parameters);
        $student = $this->students->find($this->schoolId(), (int) $sitting['student_id']);
        $snapshots = $this->sittings->snapshots((int) $sitting['id']);
        $timeline = [];
        foreach ($this->sittings->events((int) $sitting['id'], 300) as $e) {
            $timeline[] = ['at' => $e['created_at'], 'type' => $e['type'], 'detail' => $e['detail'], 'snap' => null];
        }
        foreach ($snapshots as $snap) {
            $timeline[] = ['at' => $snap['created_at'], 'type' => $snap['kind'] === 'verify' ? 'face_check' : ($snap['flag'] ?? 'snapshot'), 'detail' => null, 'snap' => $snap];
        }
        usort($timeline, static fn ($x, $y) => strcmp((string) $y['at'], (string) $x['at']));
        return $this->page($request, 'school/exams/report', [
            'title' => 'Integrity report · ' . ($student['name'] ?? 'Student'), 'exam' => $exam, 'sitting' => $sitting, 'student' => $student,
            'snapshots' => $snapshots, 'timeline' => $timeline, 'trust' => \ExamCore\Services\ProctorService::trust($sitting),
        ]);
    }

    /** Snapshots are stored outside the web root and only streamed to staff of this school. */
    public function snapshotImage(Request $request, array $parameters): Response
    {
        $exam = $this->find($parameters);
        $sitting = $this->ownedSitting($exam, $parameters);
        $snap = $this->sittings->snapshot((int) $sitting['id'], (int) ($parameters['snap'] ?? 0)) ?? throw HttpException::notFound();
        $file = $this->proctor->path((string) $snap['path']) ?? throw HttpException::notFound('This snapshot has been deleted.');
        return new Response((string) file_get_contents($file), 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function ownedSitting(array $exam, array $parameters): array
    {
        $sitting = $this->sittings->find((int) ($parameters['sitting'] ?? 0));
        if ($sitting === null || (int) $sitting['exam_id'] !== (int) $exam['id'] || (int) $sitting['school_id'] !== $this->schoolId()) {
            throw HttpException::notFound('That sitting could not be found.');
        }
        return $sitting;
    }

    /** @return list<array<string, mixed>> */
    private function monitorRows(int $examId): array
    {
        $now = time();
        return array_map(static function (array $r) use ($now): array {
            $state = match (true) {
                $r['sitting_id'] === null => 'not_started',
                $r['status'] !== 'in_progress' => 'submitted',
                $r['last_seen_at'] !== null && $now - strtotime($r['last_seen_at'] . ' UTC') <= 75 => 'writing',
                default => 'away',
            };
            return [
                'student_id' => (int) $r['student_id'], 'sitting_id' => $r['sitting_id'] !== null ? (int) $r['sitting_id'] : null,
                'name' => (string) $r['name'], 'admission_no' => (string) $r['admission_no'], 'class_name' => (string) $r['class_name'],
                'state' => $state, 'answered' => (int) $r['answered_count'], 'total' => (int) $r['question_count'],
                'left' => $r['deadline_at'] !== null && $state !== 'submitted' ? max(0, strtotime($r['deadline_at'] . ' UTC') - $now) : null,
                'score' => $r['percent'] !== null ? (float) $r['percent'] : null, 'warnings' => (int) $r['warnings'], 'extra' => (int) $r['extra_minutes'],
                'last_seen' => $r['last_seen_at'] !== null ? time_ago((string) $r['last_seen_at']) : null,
                'flags' => (int) $r['flags'], 'seat' => $r['seat_no'], 'face' => $r['face_status'], 'device_locked' => (bool) $r['device_locked'],
                'trust' => $r['sitting_id'] !== null ? \ExamCore\Services\ProctorService::trust($r)['level'] : null,
            ];
        }, $this->sittings->roster($this->schoolId(), $examId));
    }

    private function find(array $parameters): array
    {
        $exam = $this->exams->find($this->schoolId(), $this->idParam($parameters)) ?? throw HttpException::notFound('That exam could not be found.');
        if (!$this->isAdmin() && (int) $exam['created_by'] !== (int) $this->guard->id()) {
            throw HttpException::forbidden('Only the teacher who created this exam or an administrator can manage it.');
        }
        return $exam;
    }

    /** @return array<string, mixed> */
    private function paper(array $exam, array $parameters): array
    {
        foreach ($exam['papers'] as $paper) {
            if ((int) $paper['id'] === (int) ($parameters['paper'] ?? 0) && $paper['mode'] === 'fixed') {
                return $paper;
            }
        }
        throw HttpException::notFound('That paper does not use hand-picked questions.');
    }
}
