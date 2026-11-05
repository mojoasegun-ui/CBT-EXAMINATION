<?php
declare(strict_types=1);

use ExamCore\Controllers\ApiController;
use ExamCore\Core\Container;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Router;

return static function (Router $router, Container $container): void {
    $router->get('/api/health', static fn (): Response => Response::json(['status' => 'ok']), name: 'api.health');
    $router->get('/api/exams', static fn (Request $request): Response => $container->get(ApiController::class)->exams($request), name: 'api.exams');
};