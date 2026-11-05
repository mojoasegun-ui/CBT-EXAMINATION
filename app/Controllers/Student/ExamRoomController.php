<?php
declare(strict_types=1);

namespace ExamCore\Controllers\Student;

use ExamCore\Controllers\PortalController as BasePortal;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;
use ExamCore\Models\ExamSitting;
use ExamCore\Models\Student;
use ExamCore\Services\ProctorService;
use ExamCore\Services\SittingService;

/** Student side of a school exam: lobby (with face check) → room → finished. */
final class ExamRoomController extends BasePortal
{
    private const VERIFY_TTL = 1800;
    private const MAX_VERIFY_TRIES = 6;
    private const MAX_PIN_TRIES = 5;

    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf,
        private readonly TenantContext $tenant,
        private readonly Student $students,
        private readonly ExamSitting $sittings,
        private readonly SittingService $service,
        private readonly ProctorService $proctor,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function lobby(Request $request, array $parameters): Response
    {
        [$student, $exam] = $this->context($parameters);
        $sitting = $this->sittings->forStudent((int) $exam['id'], (int) $student['id']);
        if ($sitting !== null && $sitting['status'] === 'in_progress' && SittingService::remaining($sitting) + SittingService::GRACE_SECONDS <= 0) {
            $this->service->submit((int) $sitting['id'], true);
            return $this->redirectTo('/student/exams/' . $exam['id'] . '/done');
        }
        if ($sitting !== null && $sitting['status'] !== 'in_progress') {
            return $this->redirectTo('/student/exams/' . $exam['id'] . '/done');
        }
        return $this->page($request, 'student/exams/lobby', [
            'area' => 'student', 'title' => $exam['title'], 'student' => $student, 'exam' => $exam,
            'sitting' => $sitting, 'window' => SittingService::window($exam),
            'verify' => $this->verification((int) $exam['id']),
            'proctor' => $this->proctorConfig($exam, $student),
            'blockedDevice' => $sitting !== null && !$this->deviceOk($exam, $sitting),
        ]);
    }

    /** Face check result from the lobby camera (JPEG + what the browser measured). */
    public function verify(Request $request, array $parameters): Response
    {
        [, $exam] = $this->context($parameters);
        if (($exam['settings']['proctoring'] ?? 'off') === 'off') {
            throw HttpException::notFound();
        }
        $key = 'xr_verify_' . $exam['id'];
        $state = (array) $this->session->get($key, []);
        $tries = (int) ($state['tries'] ?? 0) + 1;
        if ($tries > self::MAX_VERIFY_TRIES && ($state['status'] ?? '') !== 'verified') {
            return Response::json(['status' => 'mismatch', 'tries' => $tries, 'message' => 'Too many tries. Ask your invigilator to enter the PIN.'], 429);
        }
        $file = $request->file('image');
        $bytes = $file !== null && ($file['error'] ?? 1) === UPLOAD_ERR_OK ? (string) file_get_contents((string) $file['tmp_name']) : '';
        $path = $this->proctor->storeImage($this->tenant->id(), (int) $exam['id'], $bytes);
        $result = (string) $request->input('result', '');
        $faces = max(0, min(9, (int) $request->input('faces', 0)));
        $score = is_numeric($request->input('score')) ? round((float) $request->input('score'), 3) : null;
        $status = match (true) {
            $faces !== 1 => 'retry',
            $result === 'ok', $result === 'no_photo' => 'verified',
            default => 'mismatch',
        };
        $this->session->set($key, [
            'status' => $status === 'retry' ? ($state['status'] ?? 'none') : $status, 'tries' => $tries, 'at' => time(),
            'path' => $path, 'faces' => $faces, 'score' => $score, 'flag' => $result === 'no_photo' ? 'no_photo' : ($status === 'mismatch' ? 'face_mismatch' : null),
        ]);
        return Response::json(['status' => $status, 'tries' => $tries, 'pin' => ($exam['settings']['invigilator_pin'] ?? '') !== '']);
    }

    /** Invigilator PIN admits a student whose face check failed (or who has no camera). */
    public function approve(Request $request, array $parameters): Response
    {
        [, $exam] = $this->context($parameters);
        $pin = (string) ($exam['settings']['invigilator_pin'] ?? '');
        $key = 'xr_verify_' . $exam['id'];
        $state = (array) $this->session->get($key, []);
        $pinTries = (int) ($state['pin_tries'] ?? 0) + 1;
        if ($pin === '' || $pinTries > self::MAX_PIN_TRIES) {
            return $this->redirectTo('/student/exams/' . $exam['id'], 'PIN entry is locked. Ask the exam officer for help.', 'error');
        }
        if (!hash_equals($pin, preg_replace('/\D/', '', (string) $request->input('pin', '')) ?? '')) {
            $this->session->set($key, ['pin_tries' => $pinTries] + $state);
            return $this->redirectTo('/student/exams/' . $exam['id'], 'That PIN is not correct.', 'error');
        }
        $this->session->set($key, ['status' => 'approved', 'at' => time(), 'pin_tries' => $pinTries,
            'path' => $state['path'] ?? null, 'faces' => $state['faces'] ?? null, 'score' => $state['score'] ?? null, 'flag' => $state['flag'] ?? 'pin'] + $state);
        return $this->redirectTo('/student/exams/' . $exam['id'], 'Approved by the invigilator. You can start now.');
    }

    public function start(Request $request, array $parameters): Response
    {
        [$student, $exam] = $this->context($parameters);
        $back = '/student/exams/' . $exam['id'];
        $existing = $this->sittings->forStudent((int) $exam['id'], (int) $student['id']);
        $s = $exam['settings'];
        if ($existing === null) {
            if ($request->input('agree') !== '1') {
                return $this->redirectTo($back, 'Tick the box to confirm you have read the rules.', 'error');
            }
            $check = $this->verification((int) $exam['id']);
            if (($s['proctoring'] ?? 'off') !== 'off' && !in_array($check['status'] ?? '', ['verified', 'approved'], true)) {
                return $this->redirectTo($back, 'Complete the face check first.', 'error');
            }
            $seat = mb_substr(trim((string) $request->input('seat_no', '')), 0, 12);
            if (!empty($s['ask_seat']) && $seat === '') {
                return $this->redirectTo($back, 'Enter your seat or computer number.', 'error');
            }
        } elseif (!$this->deviceOk($exam, $existing)) {
            return $this->redirectTo($back, 'This exam is open on another device. Ask your invigilator to move you to this one.', 'error');
        }
        try {
            $sitting = $this->service->start($this->tenant->id(), $exam, (int) $student['id'], (string) $request->input('access_code', ''), $request->ip(), (string) $request->header('user-agent'));
        } catch (HttpException $e) {
            if ($e->status() !== 422) {
                throw $e;
            }
            return $this->redirectTo($back, $e->getMessage(), 'error');
        }
        if ($existing === null) {
            $check = $this->verification((int) $exam['id']);
            $faceStatus = $check['status'] === 'approved' ? 'approved' : 'verified';
            if (!empty($check['path'])) {
                $this->proctor->attachVerification((int) $sitting['id'], ['status' => $faceStatus] + $check);
            } elseif ($faceStatus === 'approved') {
                $this->sittings->finish((int) $sitting['id'], ['face_status' => 'approved']);
            }
            if ($faceStatus === 'approved') {
                $this->sittings->logEvents((int) $sitting['id'], [['type' => 'pin_approved', 'detail' => 'Admitted with the invigilator PIN']]);
            }
            if (!empty($s['ask_seat'])) {
                $this->sittings->finish((int) $sitting['id'], ['seat_no' => $seat ?? null]);
            }
            $this->session->remove('xr_verify_' . $exam['id']);
            $this->bindDevice($sitting);
        }
        if ($sitting['status'] !== 'in_progress') {
            return $this->redirectTo($back . '/done');
        }
        return $this->redirectTo($back . '/room');
    }

    public function room(Request $request, array $parameters): Response
    {
        [$student, $exam] = $this->context($parameters);
        $sitting = $this->sittings->forStudent((int) $exam['id'], (int) $student['id']);
        if ($sitting === null) {
            return $this->redirectTo('/student/exams/' . $exam['id']);
        }
        if ($sitting['status'] !== 'in_progress' || SittingService::remaining($sitting) + SittingService::GRACE_SECONDS <= 0) {
            $this->service->submit((int) $sitting['id'], true);
            return $this->redirectTo('/student/exams/' . $exam['id'] . '/done');
        }
        if (!$this->deviceOk($exam, $sitting)) {
            return $this->redirectTo('/student/exams/' . $exam['id']);
        }
        $this->sittings->logEvents((int) $sitting['id'], [['type' => 'resumed', 'detail' => mb_substr($request->ip(), 0, 45)]]);
        $payload = $this->service->payload($exam, $sitting);
        $payload['proctor'] = $this->proctorConfig($exam, $student);
        $payload['warnings'] = (int) $sitting['warnings'];
        $payload['maxWarnings'] = (int) ($exam['settings']['max_warnings'] ?? 0);
        $payload['requireFullscreen'] = !empty($exam['settings']['require_fullscreen']);
        return $this->page($request, 'student/exams/room', [
            'area' => 'student', 'title' => $exam['title'], 'student' => $student, 'exam' => $exam, 'payload' => $payload,
        ], 'layouts/exam');
    }

    /** JSON autosave + heartbeat. */
    public function sync(Request $request, array $parameters): Response
    {
        [$exam, $sitting] = $this->active($parameters);
        $body = $request->json();
        $result = $this->service->sync($sitting, is_array($body['answers'] ?? null) ? $body['answers'] : [], is_array($body['events'] ?? null) ? $body['events'] : [],
            (int) ($body['position'] ?? 1), (int) ($exam['settings']['max_warnings'] ?? 0));
        if ($result['status'] === 'in_progress' && $result['deadline'] + SittingService::GRACE_SECONDS < time()) {
            $this->service->submit((int) $sitting['id'], true);
            $result['status'] = 'auto_submitted';
        }
        return Response::json($result);
    }

    /** Camera snapshot during the exam. */
    public function snapshot(Request $request, array $parameters): Response
    {
        [$exam, $sitting] = $this->active($parameters);
        if (($exam['settings']['proctoring'] ?? 'off') === 'off' || $sitting['status'] !== 'in_progress') {
            return Response::json(['status' => $sitting['status'], 'stored' => false]);
        }
        $flag = (string) $request->input('flag', '');
        $since = $this->proctor->secondsSinceLastSnapshot((int) $sitting['id']);
        if ($since !== null && $since < ($flag !== '' ? 20 : 45)) {
            return Response::json(['status' => 'in_progress', 'stored' => false, 'warnings' => (int) $sitting['warnings']]);
        }
        $file = $request->file('image');
        $bytes = $file !== null && ($file['error'] ?? 1) === UPLOAD_ERR_OK ? (string) file_get_contents((string) $file['tmp_name']) : '';
        $path = $this->proctor->storeImage($this->tenant->id(), (int) $exam['id'], $bytes);
        $faces = is_numeric($request->input('faces')) ? max(0, min(9, (int) $request->input('faces'))) : null;
        $score = is_numeric($request->input('score')) ? round((float) $request->input('score'), 3) : null;
        $result = $this->proctor->snapshot($sitting, $path, $faces, $score, $flag !== '' ? $flag : null);
        $status = 'in_progress';
        $max = (int) ($exam['settings']['max_warnings'] ?? 0);
        if ($max > 0 && $result['warnings'] >= $max) {
            $this->sittings->logEvents((int) $sitting['id'], [['type' => 'auto_submitted', 'detail' => 'Warning limit reached']]);
            $status = (string) $this->service->submit((int) $sitting['id'], true)['status'];
        }
        return Response::json(['status' => $status, 'stored' => true, 'warnings' => $result['warnings']]);
    }

    public function submit(Request $request, array $parameters): Response
    {
        [, $sitting] = $this->active($parameters);
        $body = $request->json();
        if (is_array($body['answers'] ?? null)) {
            $this->service->sync($sitting, $body['answers'], [], (int) ($body['position'] ?? 1)); // last unsaved answers first
        }
        $this->service->submit((int) $sitting['id'], (bool) ($body['auto'] ?? false));
        $done = url('/student/exams/' . $sitting['exam_id'] . '/done');
        return $request->wantsJson() ? Response::json(['status' => 'submitted', 'redirect' => $done]) : Response::redirect($done, 303);
    }

    public function done(Request $request, array $parameters): Response
    {
        [$student, $exam] = $this->context($parameters);
        $sitting = $this->sittings->forStudent((int) $exam['id'], (int) $student['id']);
        if ($sitting === null || $sitting['status'] === 'in_progress') {
            return $this->redirectTo('/student/exams/' . $exam['id']);
        }
        return $this->page($request, 'student/exams/done', [
            'area' => 'student', 'title' => 'Exam submitted', 'student' => $student, 'exam' => $exam, 'sitting' => $sitting,
            'showScore' => SittingService::resultsVisible($exam, $sitting),
            'breakdown' => json_decode((string) $sitting['breakdown'], true) ?: [],
        ]);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    private function context(array $parameters): array
    {
        $student = $this->students->find($this->tenant->id(), (int) $this->guard->id()) ?? throw HttpException::forbidden();
        if ($student['status'] !== 'active') {
            throw HttpException::forbidden('Your student account is not active. Please see the school office.');
        }
        $exam = $this->service->examFor($this->tenant->id(), (int) ($parameters['id'] ?? 0), $student['class_id'] !== null ? (int) $student['class_id'] : null)
            ?? throw HttpException::notFound('That exam is not available to you.');
        return [$student, $exam];
    }

    /** Exam + this student's sitting, on the device that owns it. @return array{0: array, 1: array} */
    private function active(array $parameters): array
    {
        [$student, $exam] = $this->context($parameters);
        $sitting = $this->sittings->forStudent((int) $exam['id'], (int) $student['id']) ?? throw HttpException::notFound('Sitting not found.');
        if (!$this->deviceOk($exam, $sitting)) {
            throw new HttpException(409, 'This exam is open on another device. Ask your invigilator to move you to this one.');
        }
        return [$exam, $sitting];
    }

    private function verification(int $examId): array
    {
        $state = (array) $this->session->get('xr_verify_' . $examId, []);
        if (($state['at'] ?? 0) < time() - self::VERIFY_TTL) {
            $state['status'] = 'none';
        }
        return $state + ['status' => 'none', 'tries' => 0, 'path' => null, 'faces' => null, 'score' => null, 'flag' => null];
    }

    private function proctorConfig(array $exam, array $student): array
    {
        $s = $exam['settings'];
        $mode = (string) ($s['proctoring'] ?? 'off');
        return [
            'mode' => $mode,
            'every' => max(60, (int) ($s['snapshot_minutes'] ?? 5) * 60),
            'models' => url('/assets/vendor/face-api/model'),
            'lib' => asset('vendor/face-api/face-api.js'),
            'photo' => $mode === 'match' && !empty($student['photo_path']) ? url('/' . ltrim((string) $student['photo_path'], '/')) : null,
            'verifyUrl' => url('/student/exams/' . $exam['id'] . '/verify'),
            'snapshotUrl' => url('/student/exams/' . $exam['id'] . '/snapshot'),
        ];
    }

    private static function deviceCookie(int $sittingId): string
    {
        return 'xrd' . $sittingId;
    }

    private function deviceOk(array $exam, array $sitting): bool
    {
        if (empty($exam['settings']['single_device'])) {
            return true;
        }
        if ($sitting['device_hash'] === null) {
            $this->bindDevice($sitting); // released by the invigilator: this device takes over
            return true;
        }
        $token = (string) ($_COOKIE[self::deviceCookie((int) $sitting['id'])] ?? '');
        return $token !== '' && hash_equals((string) $sitting['device_hash'], hash('sha256', $token));
    }

    private function bindDevice(array $sitting): void
    {
        $token = bin2hex(random_bytes(24));
        $params = session_get_cookie_params();
        setcookie(self::deviceCookie((int) $sitting['id']), $token, [
            'expires' => time() + 7 * 86400, 'path' => $params['path'] ?: '/', 'secure' => (bool) $params['secure'], 'httponly' => true, 'samesite' => 'Lax',
        ]);
        $_COOKIE[self::deviceCookie((int) $sitting['id'])] = $token;
        $this->sittings->setDevice((int) $sitting['id'], hash('sha256', $token));
    }
}
