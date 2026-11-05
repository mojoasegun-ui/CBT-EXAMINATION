<?php
declare(strict_types=1);

namespace ExamCore\Controllers;

use ExamCore\Core\Container;
use ExamCore\Core\Controller;
use ExamCore\Core\Csrf;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\View;

final class HomeController extends Controller
{
    public function __construct(Container $container, View $view)
    {
        parent::__construct($container, $view);
    }

    public function index(Request $request): Response
    {
        return $this->render('home', [
            'appName' => $this->service('config')['app']['name'],
            'baseUrl' => $this->service('base_url'),
            'csrfToken' => $this->service(Csrf::class)->token(),
            'version' => $this->service('config')['app']['version'],
        ], 'layouts/main');
    }
}