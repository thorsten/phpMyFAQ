<?php

/**
 * The phpMyFAQ Kernel
 *
 * The phpMyFAQ Kernel provides event-based request handling via Symfony HttpKernel,
 * with exception listeners and a single shared DI container.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-15
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Controller\ContainerControllerResolver;
use phpMyFAQ\EventListener\ApiExceptionListener;
use phpMyFAQ\EventListener\ControllerContainerListener;
use phpMyFAQ\EventListener\LanguageListener;
use phpMyFAQ\EventListener\RouterListener;
use phpMyFAQ\EventListener\WebExceptionListener;
use phpMyFAQ\Form\FormsServiceProvider;
use phpMyFAQ\Routing\RouteCacheManager;
use phpMyFAQ\Routing\RouteCollectionBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

class Kernel implements HttpKernelInterface
{
    private ?ContainerBuilder $container = null;

    private ?HttpKernel $httpKernel = null;

    private bool $booted = false;

    private ?RouteCollection $routes = null;

    public function __construct(
        private readonly string $routingContext = 'public',
        private readonly bool $debug = false,
    ) {
    }

    /**
     * Boots the Kernel: builds the DI container, loads routes, registers listeners, and creates the HttpKernel.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->container = $this->buildContainer();
        $this->routes = $this->loadRoutes();
        $this->httpKernel = $this->createHttpKernel();
        $this->booted = true;
    }

    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        if (!$this->booted) {
            $this->boot();
        }

        // Mark API context on the request for exception listeners
        if ($this->routingContext === 'api' || $this->routingContext === 'admin-api') {
            $request->attributes->set('_api_context', true);
        }

        return $this->httpKernel->handle($request, $type, $catch);
    }

    public function getContainer(): ContainerInterface
    {
        if (!$this->booted) {
            $this->boot();
        }

        return $this->container;
    }

    public function getRoutingContext(): string
    {
        return $this->routingContext;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    private function buildContainer(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();
        $phpFileLoader = new PhpFileLoader($containerBuilder, new FileLocator(PMF_SRC_DIR));

        try {
            $phpFileLoader->load(resource: 'services.php');
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                'Kernel boot failed while loading "services.php"; cannot resolve "phpmyfaq.event_dispatcher".',
                0,
                $exception,
            );
        }

        // Register Forms services
        FormsServiceProvider::register($containerBuilder);

        // Register kernel-level services
        $containerBuilder->set('kernel', $this);

        return $containerBuilder;
    }

    private function loadRoutes(): RouteCollection
    {
        $configuration = $this->container?->get(id: 'phpmyfaq.configuration');

        $cacheEnabled = filter_var(Environment::get('ROUTING_CACHE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN);
        $cacheDir = Environment::get('ROUTING_CACHE_DIR', PMF_ROOT_DIR . '/cache/routes');

        if ($cacheEnabled && !$this->debug && !Environment::isDebugMode()) {
            $cacheManager = new RouteCacheManager($cacheDir, Environment::isDebugMode());
            return $cacheManager->getRoutes($this->routingContext, function () use ($configuration) {
                $builder = new RouteCollectionBuilder($configuration);
                return $builder->build($this->routingContext);
            });
        }

        $builder = new RouteCollectionBuilder($configuration);
        return $builder->build($this->routingContext);
    }

    private function createHttpKernel(): HttpKernel
    {
        $dispatcher = $this->container->get('phpmyfaq.event_dispatcher');

        if (!$dispatcher instanceof EventDispatcher) {
            $dispatcher = new EventDispatcher();
        }

        $this->registerEventListeners($dispatcher);

        $controllerResolver = new ContainerControllerResolver($this->container);
        $requestStack = new RequestStack();
        $argumentResolver = new ArgumentResolver();

        return new HttpKernel($dispatcher, $controllerResolver, $requestStack, $argumentResolver);
    }

    private function registerEventListeners(EventDispatcher $dispatcher): void
    {
        // Router listener — matches request to route (priority 256, runs early)
        $routerListener = new RouterListener($this->routes);
        $dispatcher->addListener(KernelEvents::REQUEST, [$routerListener, 'onKernelRequest'], 256);

        // Language listener — detects language and initializes translations (priority 200, after router)
        $languageListener = new LanguageListener($this->container);
        $dispatcher->addListener(KernelEvents::REQUEST, [$languageListener, 'onKernelRequest'], 200);

        // API exception listener — converts exceptions to RFC 7807 JSON (priority 0)
        $configuration = $this->container->has('phpmyfaq.configuration')
            ? $this->container->get('phpmyfaq.configuration')
            : null;
        $apiExceptionListener = new ApiExceptionListener($configuration);
        $dispatcher->addListener(KernelEvents::EXCEPTION, [$apiExceptionListener, 'onKernelException'], 0);

        // Web exception listener — handles web (non-API) exceptions (priority -10, after API listener)
        $webExceptionListener = new WebExceptionListener($this->container);
        $dispatcher->addListener(KernelEvents::EXCEPTION, [$webExceptionListener, 'onKernelException'], -10);

        // Controller container listener — injects shared container into controllers
        $controllerContainerListener = new ControllerContainerListener($this->container);
        $dispatcher->addListener(KernelEvents::CONTROLLER, [$controllerContainerListener, 'onKernelController'], 0);
    }
}
