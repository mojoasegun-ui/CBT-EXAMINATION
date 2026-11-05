<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use ExamCore\Core\Container;
use ExamCore\Core\Controller;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\Validator;
use ExamCore\Core\View;
use ExamCore\Models\Exam;
use ExamCore\Services\AttemptService;
use ExamCore\Services\Exceptions\AttemptException;
use ExamCore\Services\ExamService;

final class SimulatorController extends Controller
{
    /** Friendly messages for ?error= codes on the selector. */
    private const NOTICES = [
        'selection' => 'Choose an exam type, subject and paper before starting.',
        'identity' => 'Enter your name (2–80 characters) and a valid email address to continue.',
        AttemptException::UNAVAILABLE => 'That exam is not available right now. Please choose another one.',
        AttemptException::TOO_MANY_PAPERS => 'You can combine at most ' . AttemptService::MAX_PAPERS_PER_SITTING . ' subjects in one sitting.',
        AttemptException::NOT_FOUND => 'We could not find that attempt. It may have been started in another browser.',
    ];

    public function __construct(
        Container $container,
        View $view,
        private readonly ExamService $examService,
        private readonly AttemptService $attemptService,
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly Exam $examModel,
    ) {
        parent::__construct($container, $view);
    }

    public function index(Request $request): Response
    {
        $sessionToken = $this->sessionToken();
        $recent = array_map(function (array $attempt) use ($sessionToken): array {
            $attempt['token'] = $this->attemptToken((int) $attempt['attempt_id'], $sessionToken);
            return $attempt;
        }, $this->examModel->listRecentForSession($sessionToken, 5));
        $errorCode = $request->query('error');
        // Details this browser already gave on an earlier attempt, so nobody retypes them (the page also remembers them locally).
        $known = null;
        foreach ($recent as $attempt) {
            if (trim((string) ($attempt['student_name'] ?? '')) !== '' && trim((string) ($attempt['student_email'] ?? '')) !== '') {
                $known = ['name' => (string) $attempt['student_name'], 'email' => (string) $attempt['student_email']];
                break;
            }
        }

        return $this->render('simulator/selector', [
            'knownProfile' => $known,
            'appName' => $this->service('config')['app']['name'],
            'baseUrl' => $this->baseUrl(),
            'csrfToken' => $this->csrf->token(),
            'examTypes' => $this->examService->listExamTypes(),
            'recentAttempts' => $recent,
            'notice' => is_string($errorCode) ? (self::NOTICES[$errorCode] ?? null) : null,
            'maxPapers' => AttemptService::MAX_PAPERS_PER_SITTING,
        ]);
    }

    public function select(Request $request): Response
    {
        $examIds = $this->normaliseExamIds($request->input('exam_ids') ?? []);
        $primary = filter_var($request->input('exam_id'), FILTER_VALIDATE_INT);
        if ($primary !== false && $primary > 0) {
            array_unshift($examIds, (int) $primary);
            $examIds = array_values(array_unique($examIds));
        }
        if ($examIds === []) {
            return $this->backToSelector('selection');
        }

        $studentName = trim((string) ($request->input('student_name') ?? ''));
        $studentEmail = trim((string) ($request->input('student_email') ?? ''));
        $valid = (new Validator())->validate(
            ['student_name' => $studentName, 'student_email' => $studentEmail],
            ['student_name' => 'required|string|min:2|max:80', 'student_email' => 'required|email|max:180'],
        );
        if ($valid === null) {
            return $this->backToSelector('identity');
        }

        return $this->startAndRedirect($examIds, $studentName, $studentEmail);
    }

    /** Start a fresh attempt on the same papers, reusing the identity stored on the old attempt. */
    public function retake(Request $request, array $parameters): Response
    {
        $sessionToken = $this->sessionToken();
        $attemptId = $this->resolveAttemptId((string) ($parameters['token'] ?? ''), $sessionToken);
        if ($attemptId === null) {
            return $this->backToSelector(AttemptException::NOT_FOUND);
        }
        try {
            $previous = $this->attemptService->findOwned($attemptId, $sessionToken);
        } catch (AttemptException $exception) {
            return $this->backToSelector($exception->reason());
        }
        $name = trim((string) ($previous['student_name'] ?? ''));
        $email = trim((string) ($previous['student_email'] ?? ''));
        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->backToSelector('identity');
        }
        return $this->startAndRedirect($this->attemptService->examIds($previous), $name, $email);
    }

    public function exam(string $token): Response
    {
        $sessionToken = $this->sessionToken();
        $attemptId = $this->requireAttemptId($token, $sessionToken);
        try {
            $runner = $this->attemptService->getRunnerData($attemptId, $sessionToken);
        } catch (AttemptException $exception) {
            throw HttpException::notFound($exception->getMessage());
        }
        if ($runner['attempt']['status'] !== 'in_progress') {
            return Response::redirect($this->baseUrl() . '/results/' . $token, 303);
        }
        if ($runner['remaining_seconds'] <= 0) {
            $this->attemptService->submitAttempt($attemptId, $sessionToken);
            return Response::redirect($this->baseUrl() . '/results/' . $token . '?expired=1', 303);
        }

        return $this->render('simulator/exam', [
            'appName' => $this->service('config')['app']['name'],
            'baseUrl' => $this->baseUrl(),
            'attemptToken' => $token,
            'csrfToken' => $this->csrf->token(),
            'attempt' => $runner['attempt'],
            'questions' => $runner['questions'],
            'answers' => $runner['answers'],
            'flaggedQuestions' => $runner['flagged_questions'],
            'remainingSeconds' => $runner['remaining_seconds'],
        ]);
    }

    /** @param array<string, string> $parameters */
    public function saveAnswer(Request $request, array $parameters): Response
    {
        $payload = $request->json();
        $questionId = filter_var($payload['question_id'] ?? null, FILTER_VALIDATE_INT);
        $option = strtoupper((string) ($payload['selected_option'] ?? ''));
        if ($questionId === false || $questionId < 1 || !in_array($option, ['A', 'B', 'C', 'D'], true)) {
            return Response::json(['error' => 'Answer could not be saved.'], 422);
        }
        return $this->autosave($parameters, fn (int $attemptId, string $sessionToken): array
            => $this->attemptService->saveAnswer($attemptId, $questionId, $option, $sessionToken), ['saved' => true]);
    }

    /** @param array<string, string> $parameters */
    public function toggleFlag(Request $request, array $parameters): Response
    {
        $payload = $request->json();
        $questionId = filter_var($payload['question_id'] ?? null, FILTER_VALIDATE_INT);
        $flagged = (bool) ($payload['flagged'] ?? false);
        if ($questionId === false || $questionId < 1) {
            return Response::json(['error' => 'Flag could not be updated.'], 422);
        }
        return $this->autosave($parameters, fn (int $attemptId, string $sessionToken): array
            => $this->attemptService->setFlaggedQuestion($attemptId, $questionId, $flagged, $sessionToken), ['saved' => true, 'flagged' => $flagged]);
    }

    public function submit(string $token): Response
    {
        $sessionToken = $this->sessionToken();
        $attemptId = $this->requireAttemptId($token, $sessionToken);
        try {
            // Idempotent; the result email is queued inside the same transaction.
            $this->attemptService->submitAttempt($attemptId, $sessionToken);
        } catch (AttemptException $exception) {
            throw HttpException::notFound($exception->getMessage());
        }
        return Response::redirect($this->baseUrl() . '/results/' . $token . '?submitted=1', 303);
    }

    public function results(string $token, Request $request): Response
    {
        $sessionToken = $this->sessionToken();
        $attemptId = $this->requireAttemptId($token, $sessionToken);
        try {
            $review = $this->attemptService->getReview($attemptId, $sessionToken);
        } catch (AttemptException $exception) {
            throw HttpException::notFound($exception->getMessage());
        }

        $attempt = $review['attempt'];
        $score = (float) ($attempt['score'] ?? 0);
        $total = (float) $attempt['total'];
        $percent = $total > 0 ? round(($score / $total) * 100) : 0;
        $startedAt = new DateTimeImmutable((string) $attempt['started_at'], new DateTimeZone('UTC'));
        $submittedAt = new DateTimeImmutable((string) $attempt['submitted_at'], new DateTimeZone('UTC'));
        $durationSeconds = max(0, $submittedAt->getTimestamp() - $startedAt->getTimestamp());
        $timeLimitSeconds = (int) $attempt['duration_minutes'] * 60;

        return $this->render('simulator/results', [
            'appName' => $this->service('config')['app']['name'],
            'baseUrl' => $this->baseUrl(),
            'attemptToken' => $token,
            'csrfToken' => $this->csrf->token(),
            'attempt' => $attempt,
            'questions' => $review['questions'],
            'score' => $score,
            'total' => $total,
            'percent' => $percent,
            'passed' => $percent >= $review['pass_mark_percent'],
            'timeUsedSeconds' => min($durationSeconds, $timeLimitSeconds),
            'timeLimitSeconds' => $timeLimitSeconds,
            'timeExpired' => $request->query('expired') === '1' || $attempt['status'] === 'timed_out',
            'submitted' => $request->query('submitted') === '1',
            'examCount' => count($this->attemptService->examIds($attempt)),
        ]);
    }

    /** @param list<int> $examIds */
    private function startAndRedirect(array $examIds, string $name, string $email): Response
    {
        $sessionToken = $this->sessionToken();
        try {
            $attempt = $this->attemptService->startAttempt(
                examId: $examIds[0],
                sessionToken: $sessionToken,
                studentName: $name,
                studentEmail: $email,
                accessToken: bin2hex(random_bytes(32)),
                selectedExamIds: array_slice($examIds, 1),
            );
        } catch (AttemptException $exception) {
            return $this->backToSelector($exception->reason());
        }
        return Response::redirect($this->baseUrl() . '/exam/' . $this->attemptToken($attempt['attempt_id'], $sessionToken), 303);
    }

    /**
     * @param array<string, string> $parameters
     * @param callable(int, string): array{saved: bool, expired: bool} $write
     * @param array<string, mixed> $success
     */
    private function autosave(array $parameters, callable $write, array $success): Response
    {
        $sessionToken = $this->sessionToken();
        $token = (string) ($parameters['token'] ?? '');
        $attemptId = $this->resolveAttemptId($token, $sessionToken);
        if ($attemptId === null) {
            return Response::json(['error' => 'This exam attempt could not be found.'], 404);
        }
        try {
            $result = $write($attemptId, $sessionToken);
        } catch (AttemptException $exception) {
            if ($exception->reason() === AttemptException::ALREADY_SUBMITTED) {
                return Response::json(['error' => $exception->getMessage(), 'submitted' => true, 'result_url' => $this->baseUrl() . '/results/' . $token], 409);
            }
            return Response::json(['error' => $exception->getMessage()], 422);
        }
        if ($result['expired']) {
            $this->attemptService->submitAttempt($attemptId, $sessionToken);
            return Response::json(['expired' => true, 'result_url' => $this->baseUrl() . '/results/' . $token], 409);
        }
        return Response::json($success);
    }

    private function backToSelector(string $errorCode): Response
    {
        return Response::redirect($this->baseUrl() . '/?error=' . rawurlencode($errorCode) . '#exam-selector', 303);
    }

    private function sessionToken(): string
    {
        $token = $this->session->get('practice_session_token');
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $token = bin2hex(random_bytes(32));
            $this->session->set('practice_session_token', $token);
        }
        return $token;
    }

    private function attemptToken(int $attemptId, string $sessionToken): string
    {
        return $attemptId . '-' . hash_hmac('sha256', (string) $attemptId, $sessionToken);
    }

    private function requireAttemptId(string $token, string $sessionToken): int
    {
        return $this->resolveAttemptId($token, $sessionToken)
            ?? throw HttpException::notFound('This exam attempt could not be found. It may belong to another browser session.');
    }

    private function resolveAttemptId(string $token, string $sessionToken): ?int
    {
        if (!preg_match('/^([1-9][0-9]{0,18})-([a-f0-9]{64})$/', $token, $matches)) {
            return null;
        }
        $attemptId = filter_var($matches[1], FILTER_VALIDATE_INT);
        if ($attemptId === false || !hash_equals($this->attemptToken($attemptId, $sessionToken), $token)) {
            return null;
        }
        return $attemptId;
    }

    /** @return list<int> in the order given, without duplicates */
    private function normaliseExamIds(mixed $input): array
    {
        $values = [];
        foreach (is_array($input) ? $input : [$input] as $item) {
            $number = is_scalar($item) ? filter_var((string) $item, FILTER_VALIDATE_INT) : false;
            if ($number !== false && $number > 0) {
                $values[] = (int) $number;
            }
        }
        return array_values(array_unique($values));
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->service('base_url'), '/');
    }
}
