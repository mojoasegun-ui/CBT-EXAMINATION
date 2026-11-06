<?php
declare(strict_types=1);

namespace ExamCore\Core;

use ExamCore\Contracts\IntegrationInterface;
use ExamCore\Core\Database\Migrator;
use ExamCore\Core\Mail\Mailer;
use ExamCore\Core\Queue\Queue;
use ExamCore\Core\Queue\Worker;
use ExamCore\Integrations\StandaloneAdapter;
use ExamCore\Integrations\WebsiteAdapter;
use ExamCore\Jobs\SendAttemptResultEmail;
use ExamCore\Middleware\CsrfMiddleware;
use ExamCore\Middleware\RateLimitMiddleware;
use ExamCore\Models\Attempt;
use ExamCore\Models\AttemptAnswer;
use ExamCore\Models\Exam;
use ExamCore\Models\Plan;
use ExamCore\Models\Question;
use ExamCore\Models\School;
use ExamCore\Models\User;
use ExamCore\Services\AttemptService;
use ExamCore\Services\AuditService;
use ExamCore\Services\Auth\TwoFactorService;
use ExamCore\Services\BackupService;
use ExamCore\Services\EmailService;
use ExamCore\Services\SchoolService;
use ExamCore\Services\SchoolSetupService;
use ExamCore\Models\ImportBatch;
use Throwable;

final class Application
{
    private function __construct(
        private readonly array $config,
        private readonly Container $container,
        private readonly Router $router,
        private readonly ErrorHandler $errorHandler,
        private readonly string $cspNonce,
    ) {
    }

    /** @param array<string, array<string, mixed>> $config */
    public static function boot(string $root, array $config, Container $container): self
    {
        $storage = $root . '/storage';
        $production = ($config['app']['environment'] ?? 'production') === 'production';
        $logger = new Logger($storage . '/logs/application.log', $production ? 'info' : 'debug');
        $errorHandler = new ErrorHandler($logger, !$production && (bool) ($config['app']['debug'] ?? false));
        $errorHandler->register();

        $baseUrl = self::resolveBaseUrl($config);
        $cspNonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
        $view = new View($root . '/resources/views');
        $view->share('cspNonce', $cspNonce);
        $view->share('appName', (string) ($config['app']['name'] ?? 'ExamCore CBT'));
        $errorHandler->useView($view, $baseUrl);
        Asset::configure($baseUrl, $root . '/public');
        Url::configure($baseUrl);

        $container->instance('config', $config);
        $container->instance('base_url', $baseUrl);
        $container->instance('root', $root);
        $container->instance(Logger::class, $logger);
        $container->instance(View::class, $view);
        $container->instance(Database::class, new Database($config['database'] ?? []));
        $container->instance(Session::class, new Session($config['security'] ?? [], $storage . '/sessions'));
        self::registerServices($container, $config, $root);

        $router = new Router(self::routerBasePath($config, $baseUrl), static fn (string $class): object => $container->get($class));
        $router->use(new CsrfMiddleware($container->get(Csrf::class)));
        $rateLimit = $config['security']['rate_limit'] ?? [];
        $router->use(new RateLimitMiddleware(
            $storage . '/cache',
            $rateLimit['buckets'] ?? [],
            $rateLimit['default'] ?? ['limit' => 180, 'window' => 60],
            (string) ($config['security']['session']['name'] ?? 'examcore_session'),
        ));
        foreach (['web.php', 'api.php', 'portal.php'] as $routeFile) {
            $register = require $root . '/routes/' . $routeFile;
            $register($router, $container);
        }

        return new self($config, $container, $router, $errorHandler, $cspNonce);
    }

    public function run(Request $request): Response
    {
        $requestId = bin2hex(random_bytes(8));
        try {
            $response = $this->router->dispatch($request);
        } catch (Throwable $exception) {
            $response = $this->errorHandler->response($exception, $request, $requestId);
        }

        $nonce = $this->cspNonce;
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(self), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'nonce-{$nonce}'; "
                . "img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; manifest-src 'self'; worker-src 'self'; "
                . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
            'Cache-Control' => 'no-store',
            'X-Request-Id' => $requestId,
        ];
        if ($this->isHttps()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }
        return new Response($response->content(), $response->status(), array_merge($response->headers(), $headers));
    }

    /**
     * Post-response work for hosts without cron. The session lock is released first so the
     * student's next click is never blocked behind maintenance work.
     */
    public function terminate(): void
    {
        if (!($this->config['app']['scheduler_web_trigger'] ?? true)) {
            return;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        ignore_user_abort(true);
        try {
            $this->container->get(Scheduler::class)->tick();
        } catch (Throwable $exception) {
            $this->errorHandler->report($exception);
        }
    }

    public function container(): Container
    {
        return $this->container;
    }

    /** @param array<string, array<string, mixed>> $config */
    private static function registerServices(Container $container, array $config, string $root): void
    {
        $appName = (string) ($config['app']['name'] ?? 'ExamCore CBT');
        $passMark = (float) ($config['app']['pass_mark_percent'] ?? 50);

        $container->set(Auth::class, static fn (Container $s): Auth => new Auth($s->get(Session::class), $config['permissions'] ?? []));
        $container->set(Queue::class, static fn (Container $s): Queue => new Queue($s->get(Database::class)));
        $container->set(Mailer::class, static fn (): Mailer => new Mailer($config['mail'] ?? []));
        $container->set(Migrator::class, static fn (Container $s): Migrator => new Migrator($s->get(Database::class), $root . '/database/migrations'));

        $container->set(AttemptService::class, static fn (Container $s): AttemptService => new AttemptService(
            $s->get(Database::class), $s->get(Exam::class), $s->get(Question::class), $s->get(Attempt::class), $s->get(AttemptAnswer::class),
            $s->get(Queue::class), $passMark,
        ));
        $container->set(EmailService::class, static fn (Container $s): EmailService => new EmailService($s->get(Mailer::class), $appName, $passMark));
        $container->set(Crypto::class, static fn (): Crypto => new Crypto((string) ($config['security']['app_key'] ?? '')));
        $container->set(TwoFactorService::class, static fn (Container $s): TwoFactorService => new TwoFactorService(
            $s->get(User::class), $s->get(Session::class), $s->get(Crypto::class), $appName, (string) ($config['security']['app_key'] ?? ''),
        ));
        $container->set(ImageUpload::class, static fn (): ImageUpload => new ImageUpload($root . '/public'));
        $container->set(\ExamCore\Services\ProctorService::class, static fn (Container $s): \ExamCore\Services\ProctorService => new \ExamCore\Services\ProctorService(
            $s->get(Database::class), $s->get(\ExamCore\Models\ExamSitting::class), $root . '/storage/proctoring',
        ));
        $container->set(SchoolService::class, static fn (Container $s): SchoolService => new SchoolService(
            $s->get(Database::class), $s->get(School::class), $s->get(Plan::class), $s->get(User::class), $s->get(AuditService::class), $root . '/public',
            $s->get(SchoolSetupService::class),
        ));
        $container->set(BackupService::class, static fn (Container $s): BackupService => new BackupService($s->get(Database::class), $root . '/storage/backups'));

        // Queue job handlers: job type => service id.
        $container->set(Worker::class, static fn (Container $s): Worker => new Worker($s->get(Queue::class), $s, $s->get(Logger::class), [
            AttemptService::JOB_RESULT_EMAIL => SendAttemptResultEmail::class,
            \ExamCore\Services\SittingService::JOB_STATS => \ExamCore\Jobs\RecordSittingStats::class,
        ]));
        $container->set(Scheduler::class, static fn (Container $s): Scheduler => self::schedule($s, $root));
        // Everything else (controllers, models, services with object-only dependencies) is autowired.

        $mode = (string) ($config['integration']['mode'] ?? 'standalone');
        if ($mode === 'standalone') {
            $container->set(IntegrationInterface::class, static fn (Container $s): IntegrationInterface => new StandaloneAdapter($s->get(Auth::class)));
        } elseif ($mode === 'website') {
            $provider = $config['integration']['identity_provider'] ?? null;
            $container->set(IntegrationInterface::class, static fn (): IntegrationInterface => new WebsiteAdapter(is_callable($provider) ? \Closure::fromCallable($provider) : static fn () => null));
        } else {
            throw new \RuntimeException('Integration mode configuration is invalid.');
        }
    }

    /** Maintenance tasks. Web-triggered runs use small budgets so no visitor ever waits on them. */
    private static function schedule(Container $services, string $root): Scheduler
    {
        $scheduler = new Scheduler($root . '/storage/cache/scheduler.json', $services->get(Logger::class));
        $scheduler->every(60, 'attempts:expire', static fn (bool $web): string
            => 'auto-submitted ' . $services->get(AttemptService::class)->expireOverdue(120, $web ? 25 : 200) . ' overdue attempt(s)');
        $scheduler->every(120, 'practice:expire', static fn (bool $web): string
            => 'auto-submitted ' . $services->get(\ExamCore\Services\PracticeService::class)->expireOverdue($web ? 15 : 200) . ' mock exam(s)');
        $scheduler->every(60, 'sittings:expire', static fn (bool $web): string
            => 'auto-submitted ' . $services->get(\ExamCore\Services\SittingService::class)->expireOverdue($web ? 25 : 500) . ' sitting(s)');
        $scheduler->every(60, 'push:drain', static fn (bool $web): string
            => 'woke ' . $services->get(\ExamCore\Services\PushService::class)->drain($web ? 60 : 300) . ' phone(s)');
        $scheduler->every(1800, 'push:engage', static fn (bool $web): string
            => 'nudged ' . $services->get(\ExamCore\Services\PushService::class)->engage($web ? 40 : 300) . ' device(s)');
        $scheduler->every(60, 'queue:work', static fn (bool $web): string
            => 'processed ' . $services->get(Worker::class)->work($web ? 3 : 50, $web ? 8.0 : 50.0) . ' job(s)');
        $scheduler->every(300, 'queue:release-stale', static fn (): string
            => 'released ' . $services->get(Queue::class)->releaseStale(600) . ' stale job(s)');
        $scheduler->every(86400, 'proctoring:purge', static fn (): string
            => 'deleted ' . $services->get(\ExamCore\Services\ProctorService::class)->purge(120) . ' old snapshot(s)');
        $scheduler->every(3600, 'imports:purge-credentials', static fn (): string
            => 'cleared ' . $services->get(ImportBatch::class)->purgeOld(24) . ' old import(s)');
        $scheduler->every(3600, 'schools:expire-trials', static fn (): string
            => 'suspended ' . $services->get(SchoolService::class)->suspendExpiredTrials() . ' expired trial(s)');
        $scheduler->every(21600, 'cleanup:rate-limits', static function () use ($root): string {
            $removed = 0;
            foreach (glob($root . '/storage/cache/rate-*.json') ?: [] as $file) {
                if (@filemtime($file) < time() - 3600 && @unlink($file)) {
                    $removed++;
                }
            }
            return 'removed ' . $removed . ' expired rate-limit file(s)';
        });
        return $scheduler;
    }

    /** @param array<string, array<string, mixed>> $config */
    private static function resolveBaseUrl(array $config): string
    {
        $baseUrl = (string) ($config['app']['base_url'] ?? '');
        if ($baseUrl !== '') {
            return rtrim($baseUrl, '/');
        }
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = rtrim(str_replace('\\', '/', dirname(dirname($scriptName))), '/.');
        return $basePath !== '' && $basePath !== '/' ? $basePath : '';
    }

    /** @param array<string, array<string, mixed>> $config */
    private static function routerBasePath(array $config, string $baseUrl): string
    {
        $configured = parse_url((string) ($config['app']['base_url'] ?? ''), PHP_URL_PATH);
        return is_string($configured) && $configured !== '' ? rtrim($configured, '/') : $baseUrl;
    }

    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }
}
