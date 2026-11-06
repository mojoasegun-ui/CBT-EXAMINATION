<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\View;
use ExamCore\Models\PasswordReset;
use ExamCore\Services\PasswordRecoveryService;

/** "Forgot password" for school staff. The answer to a request never reveals whether the email has an account. */
final class PasswordResetController extends PortalController
{
    public function __construct(Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, private readonly PasswordRecoveryService $recovery)
    {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function forgotForm(Request $request): Response
    {
        return $this->page($request, 'auth/forgot', ['title' => 'Reset your password', 'area' => 'school', 'sent' => $request->query('sent') === '1'], 'layouts/signin');
    }

    public function forgotSend(Request $request): Response
    {
        $email = mb_substr(trim((string) $request->input('email', '')), 0, 190);
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->backWithErrors('/forgot-password', ['email' => 'Enter the email address you use to sign in.'], ['email' => $email], 'Enter a valid email address.');
        }
        try {
            $this->recovery->request($email, $request->ip());
        } catch (\Throwable $exception) {
            // Mail trouble must not tell a visitor anything about the account; it is logged by the mailer/queue layer.
            $this->container->get(\ExamCore\Core\Logger::class)->log('error', 'Password reset email failed.', ['message' => $exception->getMessage()]);
        }
        return $this->redirectTo('/forgot-password?sent=1', 'If that email belongs to a school staff account, a reset link is on its way. Check your inbox and spam folder.');
    }

    public function resetForm(Request $request, array $parameters): Response
    {
        $token = (string) ($parameters['token'] ?? '');
        $valid = $this->recovery->inspect($token) !== null;
        return $this->page($request, 'auth/reset', ['title' => 'Choose a new password', 'area' => 'school', 'token' => $token, 'valid' => $valid], 'layouts/signin', $valid ? 200 : 410);
    }

    public function resetSave(Request $request, array $parameters): Response
    {
        $token = (string) ($parameters['token'] ?? '');
        if (!PasswordReset::wellFormed($token)) {
            return $this->redirectTo('/forgot-password', 'That link is not valid. Request a new one.', 'error');
        }
        $error = $this->recovery->reset($token, (string) $request->input('password', ''), (string) $request->input('password_confirmation', ''));
        if ($error !== null) {
            return $this->backWithErrors('/reset-password/' . $token, ['password' => $error], [], $error);
        }
        return $this->redirectTo('/login', 'Password updated. Sign in with your new password.');
    }
}
