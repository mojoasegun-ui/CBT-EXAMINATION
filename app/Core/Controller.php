<?php
declare(strict_types=1);

namespace ExamCore\Core;

abstract class Controller
{
    public function __construct(protected readonly Container $container, protected readonly View $view)
    {
    }

    /** @param array<string, mixed> $data */
    protected function render(string $template, array $data = [], ?string $layout = null, int $status = 200): Response
    {
        return new Response($this->view->render($template, $data, $layout), $status);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $location, int $status = 302): Response
    {
        return Response::redirect($location, $status);
    }

    protected function service(string $id): mixed
    {
        return $this->container->get($id);
    }
}