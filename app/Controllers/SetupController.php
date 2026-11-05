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
use ExamCore\Models\User;
use ExamCore\Services\AuditService;
use ExamCore\Services\Auth\PasswordPolicy;

/**
 * One-time creation of the first Owner account on hosts without shell access.
 *
 * Works only while no owner exists AND the request carries SETUP_TOKEN from .env,
 * so a freshly uploaded site cannot be claimed by a stranger who finds it first.
 * With shell access, prefer: php cbt owner:create
 */
final class SetupController extends PortalController
{
    public function __construct(
        Container $container,
        View $view,
        Guard $guard,
        Session $session,
        Csrf $csrf,
        private readonly User $users,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function form(Request $request): Response
    {
        $this->guardAccess((string) $request->query('token', ''));
        return $this->page($request, 'auth/setup', ['title' => 'Set up your platform', 'token' => (string) $request->query('token')], 'layouts/auth');
    }

    public function store(Request $request): Response
    {
        $token = (string) $request->input('token', '');
        $this->guardAccess($token);
        $input = $this->input($request);
        $errors = [];
        if (mb_strlen((string) ($input['name'] ?? '')) < 2) {
            $errors['name'] = 'Enter your full name.';
        }
        if (filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Enter a valid email address.';
        }
        $password = (string) $request->input('password', '');
        $passwordError = PasswordPolicy::validate($password, 10, (string) ($input['name'] ?? ''), (string) ($input['email'] ?? ''));
        if ($passwordError !== null) {
            $errors['password'] = $passwordError;
        } elseif ($password !== (string) $request->input('password_confirmation', '')) {
            $errors['password_confirmation'] = 'The passwords do not match.';
        }
        if ($errors !== []) {
            return $this->backWithErrors('/setup?token=' . rawurlencode($token), $errors, $input);
        }

        $id = $this->users->create([
            'role' => User::ROLE_OWNER,
            'name' => (string) $input['name'],
            'email' => (string) $input['email'],
            'password_hash' => PasswordPolicy::hash($password),
        ]);
        $owner = $this->users->find($id);
        $this->guard->login($owner);
        $this->audit->record('platform.setup', 'Created the first Owner account', 'user', $id);
        return $this->redirectTo('/console/account/two-factor', 'Welcome! Protect your account with two-step verification to finish setup.');
    }

    private function guardAccess(string $token): void
    {
        $expected = (string) (env('SETUP_TOKEN') ?: '');
        // 404 rather than 403: do not advertise that a setup page exists.
        if ($this->users->countOwners() > 0 || strlen($expected) < 16 || !hash_equals($expected, $token)) {
            throw HttpException::notFound();
        }
    }
}
