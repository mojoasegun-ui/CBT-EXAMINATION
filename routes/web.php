<?php
declare(strict_types=1);

use ExamCore\Controllers\SimulatorController;
use ExamCore\Core\Container;
use ExamCore\Core\Request;
use ExamCore\Core\Router;

return static function (Router $router, Container $container): void {
    $router->get('/', static fn (Request $request) => $container->get(SimulatorController::class)->index($request), name: 'simulator.home');
    $router->post('/start', static fn (Request $request) => $container->get(SimulatorController::class)->select($request), name: 'simulator.start');
    $router->post('/attempts/{token}/retake', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->retake($request, $parameters), name: 'simulator.retake');
    $router->get('/exam/{token}',static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->exam($parameters['token']), name: 'simulator.exam');
    $router->post('/exam/{token}/answer', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->saveAnswer($request, $parameters), name: 'simulator.answer');
    $router->post('/exam/{token}/flag', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->toggleFlag($request, $parameters), name: 'simulator.flag');
    $router->get('/exam/{token}/submit', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->submit($parameters['token']), name: 'simulator.submit.get');
    $router->post('/exam/{token}/submit', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->submit($parameters['token']), name: 'simulator.submit');
    $router->get('/results/{token}', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->results($parameters['token'], $request), name: 'simulator.results');
};