<?php
declare(strict_types=1);

use ExamCore\Controllers\SimulatorController;
use ExamCore\Core\Container;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Router;

return static function (Router $router, Container $container): void {
    $router->get('/', static fn (Request $request) => $container->get(SimulatorController::class)->index($request), name: 'simulator.home');
    $router->get('/manifest.webmanifest', static function () use ($container): Response {
        $app = $container->get('config')['app'];
        $icon = static fn (string $file, string $size, string $purpose): array => ['src' => 'assets/icons/' . $file, 'sizes' => $size, 'type' => 'image/png', 'purpose' => $purpose];
        $manifest = [
            'name' => $app['name'], 'short_name' => $app['short_name'], 'description' => 'Computer-based exams, practice and results for Nigerian schools.',
            'lang' => 'en-NG', 'dir' => 'ltr', 'start_url' => './?source=pwa', 'scope' => './', 'display' => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone'], 'orientation' => 'any', 'background_color' => '#ffffff', 'theme_color' => '#1E40AF',
            'categories' => ['education', 'productivity'],
            'icons' => [$icon('icon-192.png', '192x192', 'any'), $icon('icon-512.png', '512x512', 'any'), $icon('maskable-512.png', '512x512', 'maskable')],
            'shortcuts' => [
                ['name' => 'Student sign-in', 'short_name' => 'Students', 'url' => './student/login', 'icons' => [['src' => 'assets/icons/icon-192.png', 'sizes' => '192x192']]],
                ['name' => 'Staff sign-in', 'short_name' => 'Staff', 'url' => './login', 'icons' => [['src' => 'assets/icons/icon-192.png', 'sizes' => '192x192']]],
                ['name' => 'Free practice', 'short_name' => 'Practice', 'url' => './', 'icons' => [['src' => 'assets/icons/icon-192.png', 'sizes' => '192x192']]],
            ],
        ];
        return new Response((string) json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, ['Content-Type' => 'application/manifest+json; charset=UTF-8', 'Cache-Control' => 'public, max-age=3600']);
    }, name: 'manifest');
    $router->post('/start', static fn (Request $request) => $container->get(SimulatorController::class)->select($request), name: 'simulator.start');
    $router->post('/attempts/{token}/retake', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->retake($request, $parameters), name: 'simulator.retake');
    $router->get('/exam/{token}',static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->exam($parameters['token']), name: 'simulator.exam');
    $router->post('/exam/{token}/answer', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->saveAnswer($request, $parameters), name: 'simulator.answer');
    $router->post('/exam/{token}/flag', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->toggleFlag($request, $parameters), name: 'simulator.flag');
    $router->get('/exam/{token}/submit', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->submit($parameters['token']), name: 'simulator.submit.get');
    $router->post('/exam/{token}/submit', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->submit($parameters['token']), name: 'simulator.submit');
    $router->get('/results/{token}', static fn (Request $request, array $parameters) => $container->get(SimulatorController::class)->results($parameters['token'], $request), name: 'simulator.results');
};