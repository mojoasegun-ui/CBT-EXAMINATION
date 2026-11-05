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
use ExamCore\Models\User;
use ExamCore\Services\AuditService;
use ExamCore\Services\Auth\AuthService;
use ExamCore\Services\Auth\TwoFactorService;

/** Password and two-step verification for the signed-in user, in either portal. */
final class AccountController extends PortalController
{
    public function __construct(
        Container $container,
        View $view,
        Guard $guard,
        Session $session,
        Csrf $csrf,
        private readonly AuthService $auth,
        private readonly TwoFactorService $twoFactor,
        private readonly AuditService $audit,
    ) {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function passwordForm(Request $request): Response
    {
        $user = $this->guard->user();
        return $this->page($request, 'account/password', [
            'area' => $this->area($request),
            'title' => 'Password',
            'forced' => (int) ($user['must_change_password'] ?? 0) === 1,
        ]);
    }

    public function password(Request $request): Response
    {
        $area = $this->area($request);
        $user = $this->guard->user();
        $forced = (int) ($user['must_change_password'] ?? 0) === 1;
        $error = $this->auth->changePassword(
            (int) $user['id'],
            (string) $request->input('current_password', ''),
            (string) $request->input('password', ''),
            (string) $request->input('password_confirmation', ''),
        );
        if ($error !== null) {
            return $this->backWithErrors('/' . $area . '/account/password', ['password' => $error], [], $error);
        }
        return $this->redirectTo($forced ? '/' . $area : '/' . $area . '/account/password', 'Your password has been updated.');
    }

    public function twoFactorForm(Request $request): Response
    {
        $user = $this->guard->user();
        $enabled = $this->twoFactor->isEnabled($user);
        return $this->page($request, 'account/two-factor', [
            'area' => $this->area($request),
            'title' => 'Two-step verification',
            'enabled' => $enabled,
            'setup' => $enabled ? null : $this->twoFactor->pendingSetup($user),
            'recoveryCodes' => $this->session->pull('recovery_codes'),
            'remainingCodes' => $enabled ? $this->twoFactor->remainingRecoveryCodes($user) : 0,
            'required' => $user['role'] === User::ROLE_OWNER && (bool) ($this->service('config')['app']['console_require_2fa'] ?? false),
        ]);
    }

    public function enableTwoFactor(Request $request): Response
    {
        $area = $this->area($request);
        $codes = $this->twoFactor->confirmSetup($this->guard->user(), (string) $request->input('code', ''));
        if ($codes === null) {
            return $this->backWithErrors('/' . $area . '/account/two-factor', ['code' => 'That code did not match. Enter the 6 digits currently shown in your app.']);
        }
        $this->session->flash('recovery_codes', $codes);
        $this->audit->record('auth.2fa_enabled', 'Turned on two-step verification', 'user', $this->guard->id());
        return $this->redirectTo('/' . $area . '/account/two-factor', 'Two-step verification is on. Save your recovery codes now.');
    }

    public function disableTwoFactor(Request $request): Response
    {
        $area = $this->area($request);
        $user = $this->guard->user();
        if ($user['role'] === User::ROLE_OWNER && (bool) ($this->service('config')['app']['console_require_2fa'] ?? false)) {
            return $this->redirectTo('/' . $area . '/account/two-factor', 'Two-step verification is required for the Owner Console and cannot be turned off.', 'error');
        }
        if (!password_verify((string) $request->input('current_password', ''), (string) $user['password_hash'])) {
            return $this->backWithErrors('/' . $area . '/account/two-factor', ['current_password' => 'Your password is incorrect.']);
        }
        $this->twoFactor->disable($user);
        $this->audit->record('auth.2fa_disabled', 'Turned off two-step verification', 'user', (int) $user['id']);
        return $this->redirectTo('/' . $area . '/account/two-factor', 'Two-step verification has been turned off.', 'warning');
    }

    private function area(Request $request): string
    {
        foreach (['console', 'student'] as $area) {
            if (str_starts_with($request->path(), '/' . $area)) {
                return $area;
            }
        }
        return 'school';
    }
}
