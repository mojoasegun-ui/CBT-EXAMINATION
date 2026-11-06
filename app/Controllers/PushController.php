<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Controller;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\View;
use ExamCore\Models\Notification;
use ExamCore\Services\PushService;

/** Phone-notification plumbing: register a device, remove it, and hand the service worker what to show. */
final class PushController extends Controller
{
    public function __construct(Container $container, View $view, private readonly Guard $guard, private readonly Notification $notes)
    {
        parent::__construct($container, $view);
    }

    /** Signed-in account, or the anonymous practice cookie (created here if this visitor has none yet). */
    private function guestKey(bool $create): ?string
    {
        $token = (string) ($_COOKIE['ec_guest'] ?? '');
        if (preg_match('/^[a-f0-9]{24}$/', $token)) {
            return $token;
        }
        if (!$create) {
            return null;
        }
        $token = bin2hex(random_bytes(12));
        $params = session_get_cookie_params();
        setcookie('ec_guest', $token, ['expires' => time() + 400 * 86400, 'path' => $params['path'] ?: '/', 'secure' => (bool) $params['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        $_COOKIE['ec_guest'] = $token;
        return $token;
    }

    public function subscribe(Request $request): Response
    {
        $uid = $this->guard->id();
        $body = $request->json();
        $endpoint = (string) ($body['endpoint'] ?? '');
        $p256dh = (string) ($body['keys']['p256dh'] ?? '');
        $auth = (string) ($body['keys']['auth'] ?? '');
        $name = trim(preg_replace('/[^\p{L}\p{N} .\'-]+/u', '', (string) ($body['name'] ?? '')) ?? '');
        $name = $name !== '' ? mb_substr((string) preg_split('/\s+/', $name)[0], 0, 60) : null;
        if (!PushService::publicKey() || strlen($endpoint) > 1000 || !str_starts_with($endpoint, 'https://')) {
            return $this->json(['error' => 'Notifications are not available.'], 422);
        }
        // A signed-in person keeps their account link and also the visitor id, so earlier practice reminders keep working.
        $this->notes->saveSubscription($uid, $uid !== null ? $this->guard->schoolId() : null, $this->guestKey(true), $endpoint, (string) ($request->header('user-agent') ?? ''), $p256dh !== '' && strlen($p256dh) <= 100 ? $p256dh : null, $auth !== '' && strlen($auth) <= 40 ? $auth : null, $uid === null ? $name : null);
        return $this->json(['ok' => true]);
    }

    /** One link that works for everyone: takes the signed-in person to their own announcements page. */
    public function announcements(Request $request): Response
    {
        $target = match ($this->guard->role()) {
            'owner' => '/console/announcements',
            'student' => '/student/announcements',
            null => '/',
            default => '/school/announcements',
        };
        return Response::redirect(\ExamCore\Core\Url::to($target), 302);
    }

    public function unsubscribe(Request $request): Response
    {
        $this->notes->removeSubscription($this->guard->id(), $this->guestKey(false), (string) ($request->json()['endpoint'] ?? ''));
        return $this->json(['ok' => true]);
    }

    /** A friendly "it works" alert to the person's own phone, sent right after they turn notifications on. */
    public function test(Request $request): Response
    {
        $uid = $this->guard->id();
        $push = $this->container->get(PushService::class);
        if ($uid !== null) {
            $push->notify([$uid], $this->guard->schoolId(), 'general', 'Notifications are on', 'You will be alerted about exams, results and announcements.', '/');
        } elseif (($guest = $this->guestKey(false)) !== null) {
            $push->notifyGuest($guest, 'general', 'Alerts are on', 'We will remind you to practise and tell you about new question sets.', '/practice');
        }
        return $this->json(['ok' => true]);
    }

    /** Called by the service worker after a wake-up ping; the cookies say who is asking. */
    public function pending(Request $request): Response
    {
        $uid = $this->guard->id();
        $items = $this->notes->takePending($uid, $uid === null ? $this->guestKey(false) : null);
        return Response::json(['items' => $items], 200, ['Cache-Control' => 'no-store']);
    }
}
