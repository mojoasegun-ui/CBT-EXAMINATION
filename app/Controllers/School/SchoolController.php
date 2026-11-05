<?php
declare(strict_types=1);

namespace ExamCore\Controllers\School;

use ExamCore\Controllers\PortalController;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Session;
use ExamCore\Core\TenantContext;
use ExamCore\Core\View;

/** Base for school-portal controllers: every action works on the signed-in user's school only. */
abstract class SchoolController extends PortalController
{
    public function __construct(Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, protected readonly TenantContext $tenant)
    {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    protected function schoolId(): int
    {
        return $this->tenant->id();
    }

    /** Positive integer route parameter, or 404. */
    protected function idParam(array $parameters, string $key = 'id'): int
    {
        $id = filter_var($parameters[$key] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            throw HttpException::notFound();
        }
        return $id;
    }

    /** @return array<string, mixed> common data for school pages */
    protected function base(string $title): array
    {
        return ['area' => 'school', 'title' => $title];
    }
}
