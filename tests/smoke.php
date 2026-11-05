<?php
declare(strict_types=1);

use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Database;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Router;
use ExamCore\Core\Session;
use ExamCore\Core\Validator;
use ExamCore\Integrations\StudentIdentity;
use ExamCore\Integrations\WebsiteAdapter;
use ExamCore\Middleware\CsrfMiddleware;

$root = dirname(__DIR__);
require $root . '/bootstrap/autoload.php';

/** @throws RuntimeException */
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$phpFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$phpFileCount = 0;
foreach ($phpFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        token_get_all((string) file_get_contents($file->getPathname()), TOKEN_PARSE);
        $phpFileCount++;
    }
}

$container = new Container();
$container->set('sample', static fn (): object => new stdClass());
$check($container->get('sample') === $container->get('sample'), 'Container did not cache its service.');

$router = new Router('/examcore-cbt');
$router->group(['prefix' => '/api'], static function (Router $router): void {
    $router->group(['prefix' => '/v1'], static function (Router $router): void {
        $router->get('/students/{studentId}', static fn (Request $request, array $parameters): Response => new Response($parameters['studentId']), name: 'student.show');
    });
});
$check($router->url('student.show', ['studentId' => 42]) === '/examcore-cbt/api/v1/students/42', 'Named route URL is incorrect.');
$check($router->dispatch(new Request('GET', '/api/v1/students/42'))->content() === '42', 'Route parameter dispatch failed.');
$statusOf = static function (callable $dispatch): int {
    try {
        return $dispatch()->status();
    } catch (\ExamCore\Core\Exceptions\HttpException $exception) {
        return $exception->status();
    }
};
$check($statusOf(static fn () => $router->dispatch(new Request('POST', '/api/v1/students/42'))) === 405, 'Method-not-allowed handling failed.');
$check($statusOf(static fn () => $router->dispatch(new Request('GET', '/missing'))) === 404, 'Not-found handling failed.');

$serverBefore = $_SERVER;
$_SERVER['SCRIPT_NAME'] = '/examcore-cbt/public/index.php';
$_SERVER['REQUEST_URI'] = '/examcore-cbt/health';
$_SERVER['REQUEST_METHOD'] = 'GET';
$check(Request::fromGlobals()->path() === '/health', 'Subdirectory request path was not normalized.');
$_SERVER = $serverBefore;

$validator = new Validator();
$check($validator->validate(['name' => 'Ada'], ['name' => 'required|string|min:2']) !== null, 'Valid input was rejected.');
$check($validator->validate(['name' => ''], ['name' => 'required|string']) === null, 'Invalid input was accepted.');

$identity = new StudentIdentity('school-42', 'cbt-17', 'Test Student');
$adapter = new WebsiteAdapter(static fn (): StudentIdentity => $identity);
$check($adapter->identity() === $identity, 'Trusted integration identity was not returned.');
$check((new WebsiteAdapter(static fn (): string => 'untrusted'))->identity() === null, 'Non-identity integration value was accepted.');

try {
    (new Database(['connection' => 'mysql', 'database' => '', 'password' => 'secret']))->connection();
    throw new RuntimeException('Incomplete database configuration was accepted.');
} catch (RuntimeException $exception) {
    $check(!str_contains($exception->getMessage(), 'secret'), 'Database error exposed a secret.');
}

$sessionDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'examcore-smoke-' . bin2hex(random_bytes(6));
mkdir($sessionDirectory, 0700);
$sessionId = '';
try {
    $session = new Session(['session' => ['name' => 'examcore_smoke_' . bin2hex(random_bytes(4)), 'lifetime' => 600]], $sessionDirectory);
    $csrf = new Csrf($session);
    $middleware = new CsrfMiddleware($csrf);
    $next = static fn (Request $request): Response => new Response('accepted');
    $check($statusOf(static fn () => $middleware->process(new Request('POST', '/submit'), $next)) === 419, 'CSRF middleware accepted a missing token.');
    $token = $csrf->token();
    $check($middleware->process(new Request('POST', '/submit', [], ['_csrf_token' => $token]), $next)->content() === 'accepted', 'CSRF middleware rejected a valid token.');
    $check($csrf->verify($token), 'CSRF token must stay valid across requests (multi-tab safety).');
    $sessionId = session_id();
    $cookie = session_get_cookie_params();
    $check($cookie['httponly'] && $cookie['samesite'] === 'Lax', 'Session cookie security attributes are missing.');
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    if ($sessionId !== '') {
        @unlink($sessionDirectory . DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
    }
    @rmdir($sessionDirectory);
}

echo "Smoke checks passed; PHP syntax parsed for {$phpFileCount} files." . PHP_EOL;