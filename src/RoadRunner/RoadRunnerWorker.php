<?php

declare(strict_types=1);

namespace VysokeSkoly\UtilsBundle\RoadRunner;

use Nyholm\Psr7\Factory\Psr17Factory;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker as RRWorker;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Contracts\Service\ResetInterface;
use VysokeSkoly\UtilsBundle\Environment\EnvironmentInterface;
use VysokeSkoly\UtilsBundle\Service\DebugLevel;
use VysokeSkoly\UtilsBundle\Service\DebugLevelResolverInterface;

/**
 * Reusable RoadRunner HTTP Worker for Symfony applications.
 *
 * Handles the full lifecycle: PSR-7 ↔ Symfony conversion, session management,
 * debug mode switching, service reset, graceful error handling, and shutdown safety.
 *
 * @see README.md for full documentation
 */
final class RoadRunnerWorker
{
    /** @var callable(string, bool): KernelInterface */
    private $kernelFactory;

    /** @var (callable(string, bool): KernelInterface)|null */
    private $devKernelFactory = null;

    private ?EnvironmentInterface $environment = null;
    private ?DebugLevelResolverInterface $debugResolver = null;
    private ?string $debugResolverServiceId = null;
    private ?string $debugCookieName = null;
    private ?string $errorController = null;

    /** @var (callable(Request, DebugLevel, EnvironmentInterface): void)|null */
    private $staticDebugResolver = null;

    /** @var (callable(): array)|null Returns array of objects with ->asCookie(string $path) method */
    private $pendingCookiesProvider = null;

    private bool $debugAllowed = false;

    private function __construct(callable $kernelFactory)
    {
        $this->kernelFactory = $kernelFactory;
    }

    /**
     * Create a new worker builder.
     *
     * @param callable(string $env, bool $debug): KernelInterface $kernelFactory
     *        Factory that creates a Symfony kernel for a given environment.
     */
    public static function build(callable $kernelFactory): self
    {
        return new self($kernelFactory);
    }

    /**
     * Enable debug mode switching based on the environment.
     * Without this, the worker always runs in prod mode.
     */
    public function withEnvironment(EnvironmentInterface $environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * Provide a separate factory for dev kernels.
     * If not set but environment IS set, the main kernel factory is reused with dev params.
     */
    public function withDevKernelFactory(callable $factory): self
    {
        $this->devKernelFactory = $factory;

        return $this;
    }

    /**
     * Container-based debug level resolver (e.g. checks CF Access tokens, user roles).
     * Only used when environment is set and debug is allowed.
     */
    public function withDebugResolver(DebugLevelResolverInterface $resolver): self
    {
        $this->debugResolver = $resolver;

        return $this;
    }

    /**
     * Service ID (FQCN) to look up in the container for debug level resolution.
     * Use when the resolver is a container service under a different interface than the UtilsBundle one.
     *
     * Example: DebugLevelResolverInterface::class
     */
    public function withDebugResolverServiceId(string $serviceId): self
    {
        $this->debugResolverServiceId = $serviceId;

        return $this;
    }

    /**
     * Static debug resolver that runs BEFORE the container-based one.
     * Useful for reading ?dbg= query param and debug cookies directly (no container needed).
     *
     * Example: fn(Request $r, DebugLevel $d, EnvironmentInterface $e) => resolveFromQueryParam($r, $d, $e)
     *
     * @param callable(Request, DebugLevel, EnvironmentInterface): void $resolver
     */
    public function withStaticDebugResolver(callable $resolver): self
    {
        $this->staticDebugResolver = $resolver;

        return $this;
    }

    /**
     * Cookie name for debug session affinity (Istio consistent hash).
     * When set, first ?dbg= request without cookie gets a 302 redirect to set the cookie,
     * ensuring Istio pins subsequent requests (profiler, toolbar) to the same pod.
     */
    public function withDebugCookieName(string $name): self
    {
        $this->debugCookieName = $name;

        return $this;
    }

    /**
     * Symfony error controller action for rendering nice error pages in prod.
     * Example: [ErrorController::class, 'showAction']
     *
     * @param array{0: class-string, 1: string} $controller
     */
    public function withErrorController(array $controller): self
    {
        $this->errorController = $controller[0] . '::' . $controller[1];

        return $this;
    }

    /**
     * Callable that returns pending cookies to set on the response.
     * Used because setcookie() doesn't work in RoadRunner — cookies must go through Response headers.
     *
     * @param callable(): array $provider Each element must have ->asCookie(string $path): Cookie method
     */
    public function withPendingCookiesProvider(callable $provider): self
    {
        $this->pendingCookiesProvider = $provider;

        return $this;
    }

    /**
     * Start the worker loop. This method blocks until the worker is stopped.
     */
    public function run(): void
    {
        // --- Session INI for RoadRunner ---
        // Disable PHP's native session cookie handling — Symfony's AbstractSessionListener
        // sets the cookie on the Response object, which IS compatible with RoadRunner.
        // Without this, AbstractSessionHandler::destroy() calls setcookie() into the void.
        ini_set('session.use_cookies', '0');

        // --- Environment variables ---
        $this->debugAllowed = filter_var(
            $_ENV['DEBUG_ALLOWED'] ?? $_SERVER['DEBUG_ALLOWED'] ?? 'false',
            FILTER_VALIDATE_BOOLEAN,
        );

        if (!isset($_SERVER['DEFAULT_URI'])) {
            $_SERVER['DEFAULT_URI'] = $_ENV['DEFAULT_URI'] ?? 'http://localhost';
        }

        // Trust all proxies — in k8s, traffic passes through Istio sidecar + ingress
        Request::setTrustedProxies(
            ['0.0.0.0/0', '::'],
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
        );

        if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
            Request::setTrustedHosts([$trustedHosts]);
        }

        // --- Boot prod kernel (kept warm for the lifetime of the worker) ---
        $prodKernel = ($this->kernelFactory)(EnvironmentInterface::SYMFONY_PROD_ENV, false);
        $prodKernel->boot();

        $kernel = $prodKernel;
        $currentEnv = EnvironmentInterface::SYMFONY_PROD_ENV;

        // --- Dev kernel factory ---
        $devFactory = $this->devKernelFactory ?? ($this->environment !== null ? $this->kernelFactory : null);

        $getKernelForEnv = function (string $env) use ($prodKernel, &$kernel, &$currentEnv, $devFactory): KernelInterface {
            if ($env === EnvironmentInterface::SYMFONY_PROD_ENV) {
                $kernel = $prodKernel;
                $currentEnv = EnvironmentInterface::SYMFONY_PROD_ENV;

                return $kernel;
            }

            if ($devFactory === null) {
                return $prodKernel;
            }

            $devKernel = ($devFactory)(EnvironmentInterface::SYMFONY_DEV_ENV, true);
            $devKernel->boot();
            $kernel = $devKernel;
            $currentEnv = EnvironmentInterface::SYMFONY_DEV_ENV;

            return $kernel;
        };

        // --- RoadRunner setup ---
        $rrWorker = RRWorker::create();
        $psr17Factory = new Psr17Factory();
        $httpFoundationFactory = new HttpFoundationFactory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $httpWorker = new PSR7Worker($rrWorker, $psr17Factory, $psr17Factory, $psr17Factory);

        // --- Shutdown handler: catches die()/exit()/OOM ---
        $handlingRequest = false;
        $responseStarted = false;

        register_shutdown_function(function () use (&$handlingRequest, &$responseStarted, &$httpWorker, &$kernel): void {
            /* @phpstan-ignore booleanNot.alwaysTrue, booleanOr.rightAlwaysFalse */
            if (!$handlingRequest || $responseStarted) {
                return;
            }

            $error = error_get_last();
            if ($error !== null && isset($error['message']) && str_contains($error['message'], 'Allowed memory size')) {
                @ini_set('memory_limit', '-1');
            }

            $message = $error !== null
                ? sprintf('Fatal: %s in %s:%d', $error['message'] ?? '?', $error['file'] ?? '?', $error['line'] ?? 0)
                : 'Worker terminated via die/exit during request';

            error_log('[RoadRunner Worker Shutdown] ' . $message);

            try {
                $sfResponse = $this->renderError(new \RuntimeException($message), $kernel, Request::create('/_error/500'));
                $psrFactory = new Psr17Factory();
                $factory = new PsrHttpFactory($psrFactory, $psrFactory, $psrFactory, $psrFactory);
                $httpWorker->respond($factory->createResponse($sfResponse));
            } catch (\Throwable) {
                try {
                    $httpWorker->getWorker()->error($message);
                } catch (\Throwable) {
                    // ignore
                }
            }
        });

        // --- Request loop ---
        try {
            while ($request = $httpWorker->waitRequest()) {
                $handlingRequest = true;
                $responseStarted = false;
                $sfRequest = null;
                $sfResponse = null;
                $hadException = false;

                try {
                    $sfRequest = $httpFoundationFactory->createRequest($request);

                    // --- Session & cookie reset ---
                    $this->resetSessionState($sfRequest);

                    // --- Debug level resolution ---
                    $debugLevel = new DebugLevel();
                    $debugLevel->clear();

                    if ($this->debugAllowed && $this->environment !== null) {
                        $debugLevel = $this->resolveDebugLevel($sfRequest, $kernel, $debugLevel);

                        $requiredEnv = $this->environment->getSymfonyEnvironment($debugLevel);
                        if ($requiredEnv !== $currentEnv) {
                            $kernel = $getKernelForEnv($requiredEnv);
                            $currentEnv = $requiredEnv;

                            if ($debugLevel->isDebug()) {
                                Debug::enable();
                            }
                        }
                    }

                    // --- First-request debug redirect (for Istio session affinity) ---
                    if ($this->shouldRedirectForDebugCookie($sfRequest, $debugLevel)) {
                        $sfResponse = $this->buildDebugRedirectResponse($sfRequest);
                    } else {
                        $sfResponse = $kernel->handle($sfRequest);
                    }

                    // --- Apply pending cookies ---
                    if ($this->pendingCookiesProvider !== null) {
                        foreach (($this->pendingCookiesProvider)() as $pendingCookie) {
                            $sfResponse->headers->setCookie($pendingCookie->asCookie('/'));
                        }
                    }
                } catch (\Throwable $e) {
                    $hadException = true;

                    error_log(sprintf(
                        "[RoadRunner Worker] Exception: %s in %s:%d\n%s",
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString(),
                    ));

                    $sfRequest ??= Request::create('/', 'GET');
                    $sfResponse = $this->renderError($e, $kernel, $sfRequest, $currentEnv);
                }

                try {
                    $responseStarted = true;
                    $httpWorker->respond($psrHttpFactory->createResponse($sfResponse));

                    if ($kernel instanceof TerminableInterface) {
                        $kernel->terminate($sfRequest, $sfResponse);
                    }

                    // Reset stateful services
                    $container = $kernel->getContainer();
                    if ($container->has('services_resetter')) {
                        /** @var ResetInterface $resetter */
                        $resetter = $container->get('services_resetter');
                        $resetter->reset();
                    }

                    // Reboot prod kernel after exception for clean state
                    if ($hadException && $kernel === $prodKernel) {
                        $prodKernel->shutdown();
                        $prodKernel->boot();
                        $kernel = $prodKernel;
                    }

                    // Switch back to prod after dev request
                    if ($currentEnv !== EnvironmentInterface::SYMFONY_PROD_ENV) {
                        $kernel->shutdown();
                        $kernel = $prodKernel;
                        $currentEnv = EnvironmentInterface::SYMFONY_PROD_ENV;
                    }
                } catch (\Throwable $e) {
                    error_log(sprintf(
                        "[RoadRunner Worker Fatal] %s in %s:%d\n%s",
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $e->getTraceAsString(),
                    ));

                    try {
                        $httpWorker->getWorker()->error((string) $e);
                    } catch (\Throwable) {
                        // ignore
                    }
                } finally {
                    $handlingRequest = false;
                    unset($request, $sfRequest, $sfResponse);
                }
            }
        } catch (\Throwable $e) {
            error_log(sprintf(
                "[RoadRunner Worker Fatal] %s in %s:%d\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString(),
            ));
            exit(1);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Reset PHP session state between requests.
     * In RoadRunner, $_COOKIE, $_SESSION, and session_id persist between requests.
     */
    private function resetSessionState(Request $sfRequest): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }
        $_SESSION = [];

        $_COOKIE = $sfRequest->cookies->all();

        $sessionName = session_name();
        if (isset($_COOKIE[$sessionName]) && $_COOKIE[$sessionName] !== '') {
            session_id($_COOKIE[$sessionName]);
        } else {
            session_id(session_create_id() ?: bin2hex(random_bytes(16)));
        }
    }

    /**
     * Resolve debug level from static resolver and/or container-based resolver.
     */
    private function resolveDebugLevel(Request $request, KernelInterface $kernel, DebugLevel $debugLevel): DebugLevel
    {
        assert($this->environment !== null);

        // 1. Static resolution (reads ?dbg= and cookies directly, no container needed)
        if ($this->staticDebugResolver !== null) {
            ($this->staticDebugResolver)($request, $debugLevel, $this->environment);
        }

        // 2. Container-based resolution
        if ($this->debugResolver !== null) {
            $resolvedLevel = $this->debugResolver->resolve($request, $this->environment)?->getDebugLevel();
            if ($resolvedLevel !== null) {
                $debugLevel->setLevel($resolvedLevel);
            }
        } else {
            // Look up resolver from container (by explicit service ID or default interface)
            $serviceId = $this->debugResolverServiceId ?? DebugLevelResolverInterface::class;
            $container = $kernel->getContainer();
            if ($container->has($serviceId)) {
                /** @var DebugLevelResolverInterface $resolver */
                $resolver = $container->get($serviceId);
                $resolvedLevel = $resolver->resolve($request, $this->environment)?->getDebugLevel();
                if ($resolvedLevel !== null) {
                    $debugLevel->setLevel($resolvedLevel);
                }
            }
        }

        return $debugLevel;
    }

    /**
     * Check if we should redirect to set the debug cookie (for Istio affinity).
     */
    private function shouldRedirectForDebugCookie(Request $request, DebugLevel $debugLevel): bool
    {
        if ($this->debugCookieName === null || !$this->debugAllowed) {
            return false;
        }

        return $request->isMethodSafe()
            && $request->query->has('dbg')
            && !$request->cookies->has($this->debugCookieName)
            && $debugLevel->isDebug();
    }

    /**
     * Build a 302 redirect response that strips ?dbg from the URL.
     * The debug cookie will be set by the pending cookies provider.
     */
    private function buildDebugRedirectResponse(Request $request): Response
    {
        $params = $request->query->all();
        unset($params['dbg']);
        $qs = http_build_query($params, '', '&');

        $target = $request->getSchemeAndHttpHost()
            . $request->getPathInfo()
            . ($qs !== '' ? '?' . $qs : '');

        return new Response('', Response::HTTP_FOUND, ['Location' => $target]);
    }

    /**
     * Render an error response — nice error page if possible, raw HTML fallback.
     */
    private function renderError(
        \Throwable $e,
        KernelInterface $kernel,
        Request $request,
        string $env = EnvironmentInterface::SYMFONY_PROD_ENV,
    ): Response {
        try {
            if ($env === EnvironmentInterface::SYMFONY_DEV_ENV) {
                $renderer = new HtmlErrorRenderer(true);
                $flat = $renderer->render($e);

                return new Response($flat->getAsString(), $flat->getStatusCode(), $flat->getHeaders());
            }

            if ($this->errorController !== null) {
                $flat = FlattenException::createFromThrowable($e);
                $subRequest = $request->duplicate(null, null, [
                    '_controller' => $this->errorController,
                    'exception' => $flat,
                ]);

                return $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
            }
        } catch (\Throwable $fallback) {
            error_log(sprintf('[RoadRunner Worker] Error handler failed: %s', $fallback->getMessage()));
        }

        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        return new Response(
            sprintf('<html><body><h1>Error %d</h1></body></html>', $status),
            $status,
            ['Content-Type' => 'text/html'],
        );
    }
}
