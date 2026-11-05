<?php
declare(strict_types=1);

namespace ExamCore\Core;

use ExamCore\Core\Exceptions\HttpException;
use ExamCore\Middleware\MiddlewareInterface;
use InvalidArgumentException;

final class Router
{
    /** @var list<array{method:string, path:string, regex:string, params:list<string>, handler:callable, middleware:list<MiddlewareInterface>, name:?string}> */
    private array $routes = [];
    /** @var list<MiddlewareInterface> */
    private array $middleware = [];
    private string $groupPrefix = '';
    /** @var list<MiddlewareInterface> */
    private array $groupMiddleware = [];

    /** @var (callable(string): object)|null Builds controllers for [Controller::class, 'method'] handlers. */
    private $resolver;

    public function __construct(private readonly string $basePath = '', ?callable $resolver = null)
    {
        $this->resolver = $resolver;
    }

    public function get(string $path, callable|array $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('GET', $path, $handler, $middleware, $name);
    }

    public function post(string $path, callable|array $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('POST', $path, $handler, $middleware, $name);
    }

    public function put(string $path, callable|array $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('PUT', $path, $handler, $middleware, $name);
    }

    public function patch(string $path, callable|array $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('PATCH', $path, $handler, $middleware, $name);
    }

    public function delete(string $path, callable|array $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add('DELETE', $path, $handler, $middleware, $name);
    }

    public function use(MiddlewareInterface ...$middleware): void
    {
        array_push($this->middleware, ...$middleware);
    }

    /** @param array{prefix?:string, middleware?:list<MiddlewareInterface>} $options */
    public function group(array $options, callable $register): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;
        $prefix = trim((string) ($options['prefix'] ?? ''), '/');
        $this->groupPrefix = rtrim($this->groupPrefix, '/') . ($prefix === '' ? '' : '/' . $prefix);
        $this->groupMiddleware = [...$this->groupMiddleware, ...($options['middleware'] ?? [])];
        try {
            $register($this);
        } finally {
            $this->groupPrefix = $previousPrefix;
            $this->groupMiddleware = $previousMiddleware;
        }
    }

    public function url(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $route) {
            if ($route['name'] !== $name) {
                continue;
            }
            $path = preg_replace_callback('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', static function (array $match) use (&$parameters): string {
                if (!array_key_exists($match[1], $parameters)) {
                    throw new InvalidArgumentException('Missing named route parameter.');
                }
                $value = rawurlencode((string) $parameters[$match[1]]);
                unset($parameters[$match[1]]);
                return $value;
            }, $route['path']);
            $query = $parameters === [] ? '' : '?' . http_build_query($parameters);
            return rtrim($this->basePath, '/') . (string) $path . $query;
        }
        throw new InvalidArgumentException('Named route was not found.');
    }

    public function dispatch(Request $request): Response
    {
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path(), $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method() && !($request->method() === 'HEAD' && $route['method'] === 'GET')) {
                continue;
            }

            $parameters = [];
            foreach ($route['params'] as $parameter) {
                $parameters[$parameter] = rawurldecode((string) ($matches[$parameter] ?? ''));
            }
            $handler = $this->resolveHandler($route['handler']);
            $destination = static function (Request $current) use ($handler, $parameters): Response {
                $response = $handler($current, $parameters);
                if (!$response instanceof Response) {
                    throw new InvalidArgumentException('Route handlers must return a Response.');
                }
                return $response;
            };
            foreach (array_reverse([...$this->middleware, ...$route['middleware']]) as $middleware) {
                $next = $destination;
                $destination = static fn (Request $current): Response => $middleware->process($current, $next);
            }
            $response = $destination($request);
            return $request->method() === 'HEAD' ? new Response('', $response->status(), $response->headers()) : $response;
        }

        if ($pathMatched) {
            throw new HttpException(405, '', ['Allow' => $this->allowedMethods($request->path())]);
        }
        throw HttpException::notFound();
    }

    /** @param list<MiddlewareInterface> $middleware */
    private function add(string $method, string $path, callable|array $handler, array $middleware, ?string $name): void
    {
        $path = '/' . trim($this->groupPrefix . '/' . trim($path, '/'), '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $path, $parameterMatches);
        $parameters = $parameterMatches[1];
        $quoted = preg_quote($path, '#');
        $regex = preg_replace('/\\\{([A-Za-z_][A-Za-z0-9_]*)\\\}/', '(?P<$1>[^/]+)', $quoted);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'regex' => '#^' . $regex . '$#D',
            'params' => $parameters,
            'handler' => $handler,
            'middleware' => [...$this->groupMiddleware, ...$middleware],
            'name' => $name,
        ];
    }

    private function resolveHandler(callable|array $handler): callable
    {
        if (is_array($handler) && isset($handler[0], $handler[1]) && is_string($handler[0]) && is_string($handler[1])) {
            if ($this->resolver === null) {
                throw new \LogicException('Router needs a resolver for controller handlers.');
            }
            $controller = ($this->resolver)($handler[0]);
            $method = $handler[1];
            return static fn (Request $request, array $parameters): mixed => $controller->{$method}($request, $parameters);
        }
        if (!is_callable($handler)) {
            throw new \InvalidArgumentException('Route handler is not callable.');
        }
        return $handler;
    }

    private function allowedMethods(string $path): string
    {
        $methods = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path)) {
                $methods[] = $route['method'];
                if ($route['method'] === 'GET') {
                    $methods[] = 'HEAD';
                }
            }
        }
        return implode(', ', array_unique($methods));
    }
}