<?php
declare(strict_types=1);

namespace ExamCore\Tests\Unit;

use ExamCore\Core\Container;
use ExamCore\Core\Csrf;
use ExamCore\Core\Database;
use ExamCore\Core\ErrorHandler;
use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Core\Logger;
use ExamCore\Core\Request;
use ExamCore\Core\Response;
use ExamCore\Core\Router;
use ExamCore\Core\Session;
use ExamCore\Core\Validator;  
use ExamCore\Middleware\CsrfMiddleware;       
use PHPUnit\Framework\TestCase;
use RuntimeException;
         
final class FoundationTest extends TestCase
{
    private string $sessionDirectory = '';
    private string $sessionId = '';

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if ($this->sessionDirectory !== '') {
            if ($this->sessionId !== '') {
                @unlink($this->sessionDirectory . DIRECTORY_SEPARATOR . 'sess_' . $this->sessionId);
            }
            @rmdir($this->sessionDirectory);
        }
        parent::tearDown();
    }

    public function testFallbackAutoloaderResolvesCoreClasses(): void
    {
        self::assertTrue(class_exists(Database::class));
        self::assertTrue(class_exists(Router::class));
    }

    public function testDatabaseRejectsMissingConfigurationWithoutLeakingSecrets(): void
    {
        $database = new Database(['connection' => 'mysql', 'database' => '', 'password' => 'never-expose-this']);
        try {
            $database->connection();
            self::fail('Expected missing database configuration to be rejected.');
        } catch (RuntimeException $exception) {
            self::assertStringNotContainsString('never-expose-this', $exception->getMessage());
        }
    }

    public function testRouterMatchesParametersAndGeneratesSubdirectoryUrls(): void
    {
        $router = new Router('/examcore-cbt');
        $router->get('/students/{studentId}', static fn (Request $request, array $parameters): Response => new Response($parameters['studentId']), name: 'students.show');

        self::assertSame('/examcore-cbt/students/42', $router->url('students.show', ['studentId' => 42]));
        self::assertSame('42', $router->dispatch(new Request('GET', '/students/42'))->content());
    }

    public function testRouterDistinguishesNotFoundAndMethodNotAllowed(): void
    {
        $router = new Router();
        $router->get('/status', static fn (): Response => new Response('ok'));

        self::assertSame(404, $this->httpStatus(static fn () => $router->dispatch(new Request('GET', '/missing'))));
        self::assertSame(405, $this->httpStatus(static fn () => $router->dispatch(new Request('POST', '/status'))));
    }

    /** Status of a response, or of the HttpException thrown while producing it. */
    private function httpStatus(callable $produce): int
    {
        try {
            return $produce()->status();
        } catch (HttpException $exception) {
            return $exception->status();
        }
    }

    public function testContainerCachesFactoryInstances(): void
    {
        $container = new Container();
        $container->set('service', static fn (): object => new \stdClass());
        self::assertSame($container->get('service'), $container->get('service'));
    }

    public function testValidatorReturnsCleanDataOrFieldErrors(): void
    {
        $validator = new Validator();
        self::assertSame(['name' => 'Ada'], $validator->validate(['name' => 'Ada'], ['name' => 'required|string|min:2']));
        self::assertNull($validator->validate(['name' => ''], ['name' => 'required|string']));
        self::assertArrayHasKey('name', $validator->errors());
    }

    public function testCsrfTokensAreRandomVerifiedAndRotated(): void
    {
        $this->sessionDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'examcore-session-' . bin2hex(random_bytes(6));
        mkdir($this->sessionDirectory, 0700);
        $session = new Session(['session' => ['name' => 'examcore_test_' . bin2hex(random_bytes(4)), 'lifetime' => 600]], $this->sessionDirectory);
        $csrf = new Csrf($session);
        $token = $csrf->token();
        $this->sessionId = session_id();

        self::assertSame(64, strlen($token));
        self::assertTrue($csrf->verify($token));
        self::assertFalse($csrf->verify('invalid'));
        self::assertNotSame($token, $csrf->rotate());
        self::assertFalse($csrf->verify($token));
    }

    public function testSessionCookiesUseHttpOnlyAndSameSite(): void
    {
        $this->sessionDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'examcore-session-' . bin2hex(random_bytes(6));
        mkdir($this->sessionDirectory, 0700);
        $session = new Session(['session' => ['name' => 'examcore_test_' . bin2hex(random_bytes(4)), 'lifetime' => 600]], $this->sessionDirectory);
        $session->get('_test');
        $this->sessionId = session_id();
        $cookie = session_get_cookie_params();

        self::assertTrue($cookie['httponly']);
        self::assertSame('Lax', $cookie['samesite']);
    }

    public function testCsrfMiddlewareRejectsMissingTokenAndAcceptsValidToken(): void
    {
        $this->sessionDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'examcore-session-' . bin2hex(random_bytes(6));
        mkdir($this->sessionDirectory, 0700);
        $session = new Session(['session' => ['name' => 'examcore_test_' . bin2hex(random_bytes(4)), 'lifetime' => 600]], $this->sessionDirectory);
        $csrf = new Csrf($session);
        $middleware = new CsrfMiddleware($csrf);
        $next = static fn (Request $request): Response => new Response('passed');

        self::assertSame(419, $this->httpStatus(static fn () => $middleware->process(new Request('POST', '/submit'), $next)));
        $token = $csrf->token();
        self::assertSame('passed', $middleware->process(new Request('POST', '/submit', [], ['_csrf_token' => $token]), $next)->content());
        // The token survives the request so a second tab (or the Back button) keeps working.
        self::assertTrue($csrf->verify($token));
        self::assertSame('passed', $middleware->process(new Request('POST', '/submit', [], ['_csrf_token' => $token]), $next)->content());
        $this->sessionId = session_id();
    }

    public function testErrorHandlerResponseDoesNotExposeExceptionMessage(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'examcore-log-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $logger = new Logger($directory . '/app.log');
        $response = (new ErrorHandler($logger, false))->response(new RuntimeException('private database detail'));
        $log = (string) file_get_contents($directory . '/app.log');
        @unlink($directory . '/app.log');
        @rmdir($directory);

        self::assertSame(500, $response->status());
        // Users never see internals...
        self::assertStringNotContainsString('private database detail', $response->content());
        // ...but the private server log keeps them, or failures cannot be diagnosed.
        self::assertStringContainsString('private database detail', $log);
    }

    public function testLoggerRedactsSecretsAndTraceOmitsArguments(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'examcore-log-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $logger = new Logger($directory . '/app.log');
        $logger->log('error', 'Login failed.', ['password' => 'hunter2', 'nested' => ['api_token' => 'abc123'], 'user' => 'ada']);
        $leaky = static fn (string $secret) => throw new RuntimeException('boom');
        try {
            $leaky('s3cret-argument');
        } catch (RuntimeException $exception) {
            (new ErrorHandler($logger, false))->report($exception);
        }
        $log = (string) file_get_contents($directory . '/app.log');
        @unlink($directory . '/app.log');
        @rmdir($directory);

        self::assertStringNotContainsString('hunter2', $log);
        self::assertStringNotContainsString('abc123', $log);
        self::assertStringNotContainsString('s3cret-argument', $log);
        self::assertStringContainsString('ada', $log);
    }

    public function testErrorResponsesMatchWhatTheClientExpects(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'examcore-log-' . bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $handler = new ErrorHandler(new Logger($directory . '/app.log'), false);
        $api = $handler->response(HttpException::notFound(), new Request('GET', '/api/exams'), 'req1');
        $page = $handler->response(HttpException::notFound(), new Request('GET', '/exam/x', [], [], ['accept' => 'text/html']), 'req2');
        @unlink($directory . '/app.log');
        @rmdir($directory);

        self::assertSame(404, $api->status());
        self::assertStringContainsString('application/json', $api->headers()['Content-Type'] ?? '');
        self::assertSame('req1', json_decode($api->content(), true)['request_id'] ?? null);
        self::assertSame(404, $page->status());
        self::assertStringContainsString('text/html', $page->headers()['Content-Type'] ?? '');
    }
}













