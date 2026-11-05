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

    public function subscribe(Request $request): Response
    {
        $uid = $this->guard->id();
        $endpoint = (string) ($request->json()['endpoint'] ?? '');
        if ($uid === null) {
            return $this->json(['error' => 'Sign in first.'], 401);
        }
        if (!PushService::publicKey() || strlen($endpoint) > 1000 || !str_starts_with($endpoint, 'https://')) {
            return $this->json(['error' => 'Notifications are not available.'], 422);
        }
        $this->notes->saveSubscription($uid, $this->guard->schoolId(), $endpoint, (string) ($request->header('user-agent') ?? ''));
        return $this->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): Response
    {
        $uid = $this->guard->id();
        if ($uid !== null) {
            $this->notes->removeSubscription($uid, (string) ($request->json()['endpoint'] ?? ''));
        }
        return $this->json(['ok' => true]);
    }

    /** A friendly "it works" alert to the person's own phones, sent right after they turn notifications on. */
    public function test(Request $request): Response
    {
        $uid = $this->guard->id();
        if ($uid !== null) {
            $this->container->get(PushService::class)->notify([$uid], $this->guard->schoolId(), 'general', 'Notifications are on', 'You will be alerted about exams, results and announcements.', '/');
        }
        return $this->json(['ok' => true]);
    }

    /** Called by the service worker after a wake-up ping; the cookie says who is asking. */
    public function pending(Request $request): Response
    {
        $uid = $this->guard->id();
        $items = $uid === null ? [] : $this->notes->takePending($uid);
        return Response::json(['items' => $items], 200, ['Cache-Control' => 'no-store']);
    }
}
