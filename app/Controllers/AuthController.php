<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\Url;
use ExamCore\Core\View;
use ExamCore\Models\User;
use ExamCore\Services\AuditService;
use ExamCore\Services\Auth\AuthService;
use ExamCore\Services\Auth\TwoFactorService;

final class AuthController extends PortalController
{
    private const CHOICES = '_login_choices';

    public function __construct(
        Container $container,
        View $view,
        Guard $guard,
        Session $session,
        Csrf $csrf,
        private readonly AuthService $auth,
        private readonly TwoFactorService $twoFactor,
        private readonly AuditService $audit,
        private readonly User $users,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    // ---- Owner Console -------------------------------------------------------------

    public function consoleForm(Request $request): Response
    {
        if ($this->guard->role() === User::ROLE_OWNER) {
            return $this->redirectTo('/console');
        }
        return $this->page($request, 'auth/login', [
            'area' => 'console',
            'title' => 'Owner Console',
            'subtitle' => 'Restricted area. Platform administrators only.',
            'action' => url('/console/login'),
            'next' => $request->query('next'),
        ], 'layouts/auth');
    }

    public function consoleLogin(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $result = $this->auth->attemptOwner($email, (string) $request->input('password', ''), $request->ip());
        return $this->afterAttempt($result, '/console/login', $email, (string) $request->input('next', ''), '/console');
    }

    // ---- School staff ----------------------------------------------------------------

    public function schoolForm(Request $request): Response
    {
        if (in_array($this->guard->role(), User::STAFF_ROLES, true)) {
            return $this->redirectTo('/school');
        }
        return $this->page($request, 'auth/login', [
            'area' => 'school',
            'title' => 'Sign in to your school',
            'subtitle' => 'For school administrators, teachers and reviewers.',
            'action' => url('/login'),
            'next' => $request->query('next'),
        ], 'layouts/signin');
    }

    public function schoolLogin(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        [$result, $choices] = $this->auth->attemptStaff($email, (string) $request->input('password', ''), $request->ip());
        if ($result['status'] === 'choose') {
            $this->session->set(self::CHOICES, ['ids' => array_map(static fn (array $u): int => (int) $u['id'], $choices), 'at' => time()]);
            return $this->redirectTo('/login/choose');
        }
        return $this->afterAttempt($result, '/login', $email, (string) $request->input('next', ''), '/school');
    }

    public function chooseForm(Request $request): Response
    {
        $choices = $this->pendingChoices();
        if ($choices === []) {
            return $this->redirectTo('/login', 'Please sign in again.', 'error');
        }
        return $this->page($request, 'auth/choose-school', ['choices' => $choices, 'title' => 'Choose a school'], 'layouts/auth');
    }

    public function choose(Request $request): Response
    {
        $choices = $this->pendingChoices();
        $id = (int) $request->input('user_id', 0);
        $user = null;
        foreach ($choices as $choice) {
            if ((int) $choice['id'] === $id) {
                $user = $choice;
            }
        }
        $this->session->remove(self::CHOICES);
        if ($user === null) {
            return $this->redirectTo('/login', 'Please sign in again.', 'error');
        }
        $result = $this->auth->signIn($user, null, $request->ip(), 'school');
        return $this->afterAttempt($result, '/login', (string) $user['email'], '', '/school');
    }

    // ---- Two-factor challenge (both areas) ---------------------------------------------

    public function twoFactorForm(Request $request): Response
    {
        $user = $this->guard->pendingTwoFactorUser();
        if ($user === null) {
            return $this->redirectTo('/login', 'Your sign-in timed out. Please start again.', 'error');
        }
        return $this->page($request, 'auth/two-factor', [
            'title' => 'Two-step verification',
            'area' => $user['role'] === User::ROLE_OWNER ? 'console' : 'school',
            'next' => $request->query('next'),
        ], 'layouts/auth');
    }

    public function twoFactor(Request $request): Response
    {
        $user = $this->guard->pendingTwoFactorUser();
        if ($user === null) {
            return $this->redirectTo('/login', 'Your sign-in timed out. Please start again.', 'error');
        }
        $home = $user['role'] === User::ROLE_OWNER ? '/console' : '/school';
        if (!$this->twoFactor->verify($user, (string) $request->input('code', ''))) {
            $this->users->recordFailedLogin((int) $user['id'], AuthService::LOCK_AFTER, AuthService::LOCK_SECONDS);
            $this->audit->record('auth.2fa_failed', 'Wrong two-step code', 'user', (int) $user['id'], [], $user['school_id'] === null ? null : (int) $user['school_id']);
            return $this->redirectTo('/login/two-factor', 'That code is not right. Check the time on your phone and try again.', 'error');
        }
        $this->auth->finish($user, $request->ip(), $home === '/console' ? 'console' : 'school');
        return Response::redirect(Url::safeNext($request->input('next'), $home), 303);
    }

    public function logout(Request $request): Response
    {
        $wasOwner = $this->guard->role() === User::ROLE_OWNER || $this->guard->impersonatorId() !== null;
        $wasStudent = $this->guard->role() === User::ROLE_STUDENT;
        if ($this->guard->check()) {
            $this->audit->record('auth.logout', 'Signed out', 'user', $this->guard->id());
        }
        $this->guard->logout();
        return $this->redirectTo($wasOwner ? '/console/login' : ($wasStudent ? '/student/login' : '/login'), 'You have been signed out.');
    }

    /** @param array{status: string, minutes?: int} $result */
    private function afterAttempt(array $result, string $formPath, string $email, string $next, string $home): Response
    {
        $nextQuery = $next !== '' ? '?next=' . rawurlencode($next) : '';
        return match ($result['status']) {
            'ok' => Response::redirect(Url::safeNext($next !== '' ? $next : null, $home), 303),
            'two_factor' => $this->redirectTo('/login/two-factor' . $nextQuery),
            'locked' => $this->backWithErrors($formPath . $nextQuery, ['email' => ' '], ['email' => $email],
                sprintf('Too many failed attempts. For your security this account is locked for %d more minute%s.', $result['minutes'] ?? 15, ($result['minutes'] ?? 15) === 1 ? '' : 's')),
            default => $this->backWithErrors($formPath . $nextQuery, ['email' => ' ', 'password' => ' '], ['email' => $email], 'The email or password is incorrect.'),
        };
    }

    /** @return list<array<string, mixed>> */
    private function pendingChoices(): array
    {
        $state = $this->session->get(self::CHOICES);
        if (!is_array($state) || time() - (int) ($state['at'] ?? 0) > 300) {
            return [];
        }
        $choices = [];
        foreach ($state['ids'] ?? [] as $id) {
            foreach ($this->users->findStaffByEmail((string) ($this->users->find((int) $id)['email'] ?? '')) as $account) {
                if ((int) $account['id'] === (int) $id && $account['status'] === 'active') {
                    $choices[] = $account;
                }
            }
        }
        return $choices;
    }
}
