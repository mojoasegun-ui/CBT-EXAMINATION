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
use ExamCore\Models\Announcement;
use ExamCore\Models\SchoolClass;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;

/**
 * One announcements page for every signed-in role. What a person sees and may post is decided here from the
 * signed-in user (never from the URL), and every query is limited to that user's own school.
 */
final class AnnouncementController extends PortalController
{
    public function __construct(
        Container $container, View $view, Guard $guard, Session $session, Csrf $csrf,
        private readonly Announcement $announcements,
        private readonly SchoolClass $classes,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    private function role(): string
    {
        return (string) $this->guard->role();
    }

    private function base(): string
    {
        return match ($this->role()) {
            User::ROLE_OWNER => '/console/announcements',
            User::ROLE_STUDENT => '/student/announcements',
            default => '/school/announcements',
        };
    }

    private function canPost(): bool
    {
        return in_array($this->role(), [User::ROLE_OWNER, User::ROLE_SCHOOL_ADMIN, User::ROLE_TEACHER], true);
    }

    public function index(Request $request): Response
    {
        $role = $this->role();
        $schoolId = $this->guard->schoolId();
        $userId = (int) $this->guard->id();
        $feed = $this->announcements->feed($role, $schoolId, $userId);
        $this->announcements->markRead($userId, array_map(static fn (array $a): int => (int) $a['id'], array_filter($feed, static fn (array $a): bool => !$a['is_read'])));

        $myClasses = [];
        if ($role === User::ROLE_SCHOOL_ADMIN) {
            $myClasses = array_map(static fn (array $c): array => ['id' => $c['id'], 'name' => $c['name']], $this->classes->all((int) $schoolId, true));
        } elseif ($role === User::ROLE_TEACHER) {
            $myClasses = $this->announcements->classesOfTeacher((int) $schoolId, $userId);
        }
        $sent = $this->canPost() ? $this->announcements->sent($role === User::ROLE_OWNER ? null : $schoolId, $role === User::ROLE_TEACHER ? $userId : null) : [];

        return $this->page($request, 'announcements/index', [
            'area' => match ($role) { User::ROLE_OWNER => 'console', User::ROLE_STUDENT => 'student', default => 'school' },
            'title' => 'Announcements',
            'feed' => $feed,
            'sent' => $sent,
            'canPost' => $this->canPost(),
            'myClasses' => $myClasses,
            'audiences' => $role === User::ROLE_TEACHER ? ['students' => 'My students'] : Announcement::AUDIENCES,
            'actionBase' => $this->base(),
            'isTeacher' => $role === User::ROLE_TEACHER,
        ]);
    }

    public function store(Request $request): Response
    {
        if (!$this->canPost()) {
            throw HttpException::forbidden();
        }
        $role = $this->role();
        $schoolId = $role === User::ROLE_OWNER ? null : $this->guard->schoolId();
        $title = trim(preg_replace('/\s+/', ' ', (string) $request->input('title', '')) ?? '');
        $body = trim(str_replace("\r\n", "\n", (string) $request->input('body', '')));
        $audience = (string) $request->input('audience', 'students');
        $classId = (int) $request->input('class_id', 0);
        $old = ['title' => $title, 'body' => $body, 'audience' => $audience, 'class_id' => $classId];

        $errors = [];
        if (mb_strlen($title) < 3 || mb_strlen($title) > 160) {
            $errors['title'] = 'Enter a title of 3 to 160 characters.';
        }
        if (mb_strlen($body) < 3 || mb_strlen($body) > 3000) {
            $errors['body'] = 'Write the message (up to 3,000 characters).';
        }
        if ($role === User::ROLE_TEACHER) {
            $audience = 'students';
        }
        if (!in_array($audience, array_keys(Announcement::AUDIENCES), true)) {
            $errors['audience'] = 'Choose who should receive this.';
        }
        if ($role === User::ROLE_OWNER) {
            $classId = 0;
        } elseif ($classId > 0) {
            $allowed = $role === User::ROLE_TEACHER
                ? array_column($this->announcements->classesOfTeacher((int) $schoolId, (int) $this->guard->id()), 'id')
                : array_column($this->classes->all((int) $schoolId, true), 'id');
            if (!in_array($classId, array_map('intval', $allowed), true) || $audience !== 'students') {
                $errors['class_id'] = 'Choose one of your classes.';
            }
        } elseif ($role === User::ROLE_TEACHER) {
            $errors['class_id'] = 'Choose the class this is for.';
        }
        if ($errors !== []) {
            return $this->backWithErrors($this->base(), $errors, $old, reset($errors));
        }

        $user = (array) $this->guard->user();
        $id = $this->announcements->create([
            'school_id' => $schoolId, 'author_id' => (int) $this->guard->id(), 'author_name' => mb_substr((string) ($user['name'] ?? ''), 0, 120),
            'author_role' => $role, 'audience' => $audience, 'class_id' => $classId > 0 ? $classId : null,
            'title' => $title, 'body' => $body, 'pinned' => $request->input('pinned') === '1' && $role !== User::ROLE_TEACHER ? 1 : 0,
        ]);
        try {
            $this->container->get(\ExamCore\Services\PushService::class)->notify(
                array_values(array_diff($this->announcements->recipientIds($schoolId, $audience, $classId > 0 ? $classId : null), [(int) $this->guard->id()])),
                $schoolId, 'announcement', $title, mb_substr($body, 0, 140), '/' . ($audience === 'teachers' ? 'school' : 'student') . '/announcements'
            );
        } catch (\Throwable) {
            // A notification problem must never stop the post itself.
        }
        $this->audit->record('announcement.posted', 'Posted the announcement “' . mb_substr($title, 0, 60) . '”', 'announcement', $id, schoolId: $schoolId);
        return $this->redirectTo($this->base(), 'Announcement posted.');
    }

    public function delete(Request $request, array $parameters): Response
    {
        $id = filter_var($parameters['id'] ?? null, FILTER_VALIDATE_INT);
        $row = $id ? $this->announcements->find((int) $id) : null;
        $role = $this->role();
        $schoolId = $role === User::ROLE_OWNER ? null : $this->guard->schoolId();
        // Only posts of the viewer's own scope; a teacher may delete only their own.
        $mine = $row !== null && $this->canPost()
            && ($row['school_id'] === null ? $schoolId === null : (int) $row['school_id'] === (int) $schoolId)
            && ($role !== User::ROLE_TEACHER || (int) $row['author_id'] === (int) $this->guard->id());
        if (!$mine) {
            throw HttpException::notFound();
        }
        $this->announcements->delete((int) $row['id']);
        $this->audit->record('announcement.deleted', 'Removed the announcement “' . mb_substr((string) $row['title'], 0, 60) . '”', 'announcement', (int) $row['id'], schoolId: $schoolId);
        return $this->redirectTo($this->base(), 'Announcement removed.');
    }
}
