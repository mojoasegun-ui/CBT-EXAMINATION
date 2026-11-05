<?php
declare(strict_types=1);

namespace ExamCore\Middleware;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Url;
use ExamCore\Models\School;
use ExamCore\Services\Auth\TwoFactorService;

/**
 * Gate for signed-in areas.
 *
 *  - not signed in        → redirect to the area's sign-in page (401 JSON for fetch calls)
 *  - wrong role           → 403
 *  - temporary password   → must set a new one first
 *  - owner without 2FA    → must enable it first (when required)
 *  - school suspended     → explanatory page (owners impersonating may still enter)
 */
final class Authenticate implements MiddlewareInterface
{
    /** @param list<string> $roles */
    public function __construct(
        private readonly Guard $guard,
        private readonly array $roles,
        private readonly string $loginPath,
        private readonly ?TwoFactorService $twoFactor = null,
        private readonly bool $requireTwoFactor = false,
        private readonly ?School $schools = null,
        private readonly string $area = '',
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $user = $this->guard->user();
        if ($user === null) {
            if ($request->wantsJson()) {
                throw new HttpException(401, 'Please sign in again.');
            }
            $target = $request->method() === 'GET' ? '?next=' . rawurlencode($request->path()) : '';
            return Response::redirect(Url::to($this->loginPath) . $target, 303);
        }
        if (!in_array($user['role'], $this->roles, true)) {
            // Signed in, but with another kind of account (e.g. owner opening a school page):
            // offer the right sign-in instead of a dead-end 403.
            if ($request->method() === 'GET' && !$request->wantsJson()) {
                return Response::redirect(Url::to($this->loginPath) . '?switch=1&next=' . rawurlencode($request->path()), 303);
            }
            throw HttpException::forbidden();
        }

        $area = '/' . ($this->area !== '' ? $this->area : (str_starts_with($this->loginPath, '/console') ? 'console' : 'school'));
        $exempt = [$area . '/account/password', $area . '/account/two-factor', $area . '/logout', '/logout', '/school/stop-impersonating'];
        if (!in_array($request->path(), $exempt, true)) {
            if ((int) $user['must_change_password'] === 1 && $this->guard->impersonatorId() === null) {
                return Response::redirect(Url::to($area . '/account/password'), 303);
            }
            if ($this->requireTwoFactor && $this->twoFactor !== null && !$this->twoFactor->isEnabled($user)) {
                return Response::redirect(Url::to($area . '/account/two-factor'), 303);
            }
        }

        if ($user['school_id'] !== null && $this->schools !== null && $this->guard->impersonatorId() === null) {
            $school = $this->schools->find((int) $user['school_id']);
            if ($school === null || in_array($school['status'], ['suspended', 'archived'], true)) {
                if (!in_array($request->path(), $exempt, true)) {
                    throw new HttpException(403, $school !== null && $school['status'] === 'suspended'
                        ? 'This school’s account is suspended. Please contact the platform administrator to restore access.'
                        : 'This school account is no longer active.');
                }
            }
        }
        return $next($request);
    }
}
