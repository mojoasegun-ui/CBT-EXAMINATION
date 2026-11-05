<?php
declare(strict_types=1);

namespace ExamCore\Controllers\Console;

use ExamCore\Controllers\PortalController;
use ExamCore\Core\Auth\Guard;
use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Session;
use ExamCore\Core\View;
use ExamCore\Models\Plan;
use ExamCore\Services\AuditService;

final class PlanController extends PortalController
{
    public function __construct(Container $container, View $view, Guard $guard, Session $session, Csrf $csrf, private readonly Plan $plans, private readonly AuditService $audit)
    {
        parent::__construct($container, $view, $guard, $session, $csrf);
    }

    public function index(Request $request): Response
    {
        return $this->page($request, 'console/plans', [
            'area' => 'console',
            'title' => 'Plans & features',
            'plans' => $this->plans->all(),
            'features' => Plan::FEATURES,
        ]);
    }

    public function update(Request $request, array $parameters): Response
    {
        $plan = $this->plans->find((int) ($parameters['id'] ?? 0)) ?? throw HttpException::notFound('Plan not found.');
        $name = trim((string) $request->input('name', ''));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
            return $this->backWithErrors('/console/plans#plan-' . $plan['id'], ['name_' . $plan['id'] => 'Enter a plan name.']);
        }
        $price = (float) str_replace([',', '₦', ' '], '', (string) $request->input('price', '0'));
        $max = trim((string) $request->input('max_students', ''));
        $features = $request->input('features', []);
        $this->plans->update(
            (int) $plan['id'],
            $name,
            mb_substr(trim((string) $request->input('description', '')), 0, 255),
            (int) round(max(0, $price) * 100),
            $max === '' ? null : max(1, (int) $max),
            is_array($features) ? array_map('strval', $features) : [],
            $request->input('is_active') === '1',
        );
        $this->audit->record('plan.updated', 'Updated the ' . $name . ' plan', 'plan', (int) $plan['id']);
        return $this->redirectTo('/console/plans#plan-' . $plan['id'], $name . ' plan saved.');
    }
}
