<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Controller;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\Url;
use ExamCore\Core\View;

/**
 * Base for signed-in portal pages (Owner Console, school admin, sign-in screens).
 * Supplies the shared page data and the Post/Redirect/Get helpers.
 */
abstract class PortalController extends Controller
{
    public function __construct(
        Container $container,
        View $view,
        protected readonly Guard $guard,
        protected readonly Session $session,
        protected readonly Csrf $csrf,
    ) {
        parent::__construct($container, $view);
    }

    /** @param array<string, mixed> $data */
    protected function page(Request $request, string $template, array $data = [], string $layout = 'layouts/portal', int $status = 200): Response
    {
        return $this->render($template, $data + [
            // Every school-area page gets its school for the branded shell (logo, name, colour).
            'school' => $this->guard->schoolId() !== null ? $this->service(TenantContext::class)->school() : null,
            'user' => $this->guard->user(),
            'impersonating' => $this->guard->impersonatorId() !== null,
            'csrfToken' => $this->csrf->token(),
            'flash' => $this->session->pull('flash'),
            'errors' => $this->session->pull('errors', []),
            'old' => $this->session->pull('old', []),
            'currentPath' => $request->path(),
        ], $layout, $status);
    }

    protected function flash(string $message, string $type = 'success'): void
    {
        $this->session->flash('flash', ['message' => $message, 'type' => $type]);
    }

    protected function redirectTo(string $path, ?string $message = null, string $type = 'success'): Response
    {
        if ($message !== null) {
            $this->flash($message, $type);
        }
        return Response::redirect(Url::to($path), 303);
    }

    /**
     * Send the user back to the form with field errors and their input (minus secrets).
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $input
     */
    protected function backWithErrors(string $path, array $errors, array $input = [], ?string $message = null): Response
    {
        $this->session->flash('errors', $errors);
        $this->session->flash('old', array_diff_key($input, array_flip(['password', 'password_confirmation', 'current_password', '_csrf_token', 'code'])));
        $this->flash($message ?? 'Please fix the highlighted fields.', 'error');
        return Response::redirect(Url::to($path), 303);
    }

    /** @return array<string, mixed> */
    protected function input(Request $request): array
    {
        return array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $request->allInput());
    }
}
