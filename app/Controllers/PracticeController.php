<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\View;
use ExamCore\Models\Practice;
use ExamCore\Models\Student;
use ExamCore\Models\User;
use ExamCore\Services\PracticeService;

/**
 * The Practice Centre: topic practice, quick sessions, mistakes quizzes, mock exams, progress and a school
 * leaderboard. Open to everyone; progress is kept against the signed-in student, or against this device
 * (a random cookie) for visitors.
 */
final class PracticeController extends PortalController
{
    private const LAYOUT = 'layouts/practice';

    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf,
        private readonly PracticeService $service,
        private readonly Practice $practice,
        private readonly Student $students,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function home(Request $request): Response
    {
        $owner = $this->owner();
        $catalog = $this->practice->catalog();
        $progress = $this->service->progress($owner['key'], $catalog);
        $bySubject = array_column($progress['subjects'], null, 'subject');
        return $this->page($request, 'practice/home', [
            'title' => 'Practice Centre', 'catalog' => $catalog, 'progress' => $progress, 'bySubject' => $bySubject,
            'open' => $this->practice->openSession($owner['key']), 'signedIn' => $owner['student_id'] !== null,
        ], self::LAYOUT);
    }

    public function subject(Request $request, array $parameters): Response
    {
        $owner = $this->owner();
        $catalog = $this->practice->catalog();
        $subject = PracticeService::subjectBySlug($catalog, (string) ($parameters['slug'] ?? '')) ?? throw HttpException::notFound('That subject has no practice questions yet.');
        $mine = [];
        foreach ($this->practice->topicStats($owner['key']) as $s) {
            if ($s['subject'] === $subject) {
                $mine[$s['topic']] = $s;
            }
        }
        return $this->page($request, 'practice/subject', [
            'title' => $subject . ' practice', 'subject' => $subject, 'info' => $catalog[$subject], 'mine' => $mine, 'counts' => PracticeService::COUNTS,
        ], self::LAYOUT);
    }

    public function mock(Request $request): Response
    {
        $catalog = $this->practice->catalog();
        return $this->page($request, 'practice/mock', ['title' => 'Mock exam', 'catalog' => $catalog, 'english' => isset($catalog['English Language'])], self::LAYOUT);
    }

    public function start(Request $request): Response
    {
        $owner = $this->owner();
        $in = $this->input($request);
        $mode = (string) ($in['mode'] ?? 'topic');
        try {
            $session = $this->service->start($owner, $mode, $in);
        } catch (HttpException $e) {
            if ($e->status() !== 422) {
                throw $e;
            }
            $back = $mode === 'mock' ? '/practice/mock' : $this->safeBack((string) ($in['back'] ?? ''));
            return $this->redirectTo($back, $e->getMessage(), 'error');
        }
        return Response::redirect(url('/practice/s/' . $session['token']), 303);
    }

    public function run(Request $request, array $parameters): Response
    {
        $session = $this->mine($parameters);
        if ($session['status'] === 'submitted') {
            return Response::redirect(url('/practice/s/' . $session['token'] . '/result'), 303);
        }
        $remaining = PracticeService::remaining($session);
        if ($remaining !== null && $remaining + PracticeService::GRACE_SECONDS <= 0) {
            $this->service->submit($session);
            return Response::redirect(url('/practice/s/' . $session['token'] . '/result'), 303);
        }
        $payload = $this->service->payload($session) + ['csrf' => $this->csrf->token()];
        return $this->page($request, 'practice/run', ['title' => $session['title'], 'payload' => $payload, 'session' => $session], 'layouts/practice-run');
    }

    public function sync(Request $request, array $parameters): Response
    {
        $session = $this->mine($parameters);
        $body = $request->json();
        $result = $this->service->sync($session, is_array($body['answers'] ?? null) ? $body['answers'] : []);
        if ($result['status'] === 'in_progress' && $result['deadline'] !== null && $result['deadline'] + PracticeService::GRACE_SECONDS < time()) {
            $this->service->submit($session);
            $result['status'] = 'submitted';
        }
        return Response::json($result);
    }

    public function submit(Request $request, array $parameters): Response
    {
        $session = $this->mine($parameters);
        $body = $request->json();
        $this->service->submit($session, is_array($body['answers'] ?? null) ? $body['answers'] : []);
        $to = url('/practice/s/' . $session['token'] . '/result');
        return $request->wantsJson() ? Response::json(['status' => 'submitted', 'redirect' => $to]) : Response::redirect($to, 303);
    }

    public function result(Request $request, array $parameters): Response
    {
        $session = $this->mine($parameters);
        if ($session['status'] !== 'submitted') {
            $remaining = PracticeService::remaining($session);
            if ($remaining !== null && $remaining + PracticeService::GRACE_SECONDS <= 0) {
                $this->service->submit($session);
                $session = $this->mine($parameters);
            } else {
                return Response::redirect(url('/practice/s/' . $session['token']), 303);
            }
        }
        $owner = $this->owner();
        $data = $this->service->result($session);
        return $this->page($request, 'practice/result', [
            'title' => 'Result · ' . $session['title'], 'session' => $session, 'items' => $data['items'], 'topics' => $data['topics'],
            'learning' => PracticeService::isLearning($session), 'mistakes' => $this->practice->mistakeCount($owner['key']),
            'streak' => PracticeService::streak($this->practice->days($owner['key'], 90), PracticeService::today()),
        ], self::LAYOUT);
    }

    public function progress(Request $request): Response
    {
        $owner = $this->owner();
        $catalog = $this->practice->catalog();
        $board = null;
        if ($owner['student_id'] !== null && $owner['school_id'] !== null) {
            $student = $this->students->find($owner['school_id'], $owner['student_id']);
            $board = [
                'school' => $this->service->leaderboard($owner['school_id'], $owner['student_id'], null),
                'class' => $student && $student['class_id'] ? $this->service->leaderboard($owner['school_id'], $owner['student_id'], (int) $student['class_id']) : null,
                'className' => $student['class_name'] ?? '',
            ];
        }
        return $this->page($request, 'practice/progress', [
            'title' => 'My progress', 'progress' => $this->service->progress($owner['key'], $catalog), 'catalog' => $catalog, 'board' => $board, 'signedIn' => $owner['student_id'] !== null,
        ], self::LAYOUT);
    }

    // ---- Helpers --------------------------------------------------------------------------------------

    /** @return array{key:string, student_id:?int, school_id:?int} */
    private function owner(): array
    {
        if ($this->guard->role() === User::ROLE_STUDENT && $this->guard->id() !== null) {
            return ['key' => 's:' . $this->guard->id(), 'student_id' => (int) $this->guard->id(), 'school_id' => $this->guard->schoolId()];
        }
        $token = (string) ($_COOKIE['ec_guest'] ?? '');
        if (!preg_match('/^[a-f0-9]{24}$/', $token)) {
            $token = bin2hex(random_bytes(12));
            $params = session_get_cookie_params();
            setcookie('ec_guest', $token, ['expires' => time() + 400 * 86400, 'path' => $params['path'] ?: '/', 'secure' => (bool) $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
            $_COOKIE['ec_guest'] = $token;
        }
        return ['key' => 'g:' . $token, 'student_id' => null, 'school_id' => null];
    }

    /** @return array<string, mixed> */
    private function mine(array $parameters): array
    {
        $token = (string) ($parameters['token'] ?? '');
        $session = preg_match('/^[a-f0-9]{32}$/', $token) ? $this->practice->byToken($token) : null;
        if ($session === null || !hash_equals((string) $session['owner_key'], $this->owner()['key'])) {
            throw HttpException::notFound('That practice session could not be found.');
        }
        return $session;
    }

    private function safeBack(string $back): string
    {
        return preg_match('~^/practice(/subject/[a-z0-9-]+)?$~', $back) === 1 ? $back : '/practice';
    }
}
