<?php

/**
 * Functional tests for Kernel routing and exception handling
 *
 * Tests the HttpKernel integration: request routing, exception listeners,
 * and response handling through the full Kernel stack.
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

namespace phpMyFAQ\Functional;

use phpMyFAQ\EventListener\ApiExceptionListener;
use phpMyFAQ\EventListener\RouterListener;
use phpMyFAQ\EventListener\WebExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class KernelRoutingTest extends TestCase
{
    private function createKernelStack(RouteCollection $routes, bool $isApi = false): HttpKernel
    {
        $dispatcher = new EventDispatcher();

        // Register router listener
        $routerListener = new RouterListener($routes);
        $dispatcher->addListener(KernelEvents::REQUEST, [$routerListener, 'onKernelRequest'], 256);

        // Register exception listeners
        $apiListener = new ApiExceptionListener(null);
        $dispatcher->addListener(KernelEvents::EXCEPTION, [$apiListener, 'onKernelException'], 0);

        $webListener = new WebExceptionListener();
        $dispatcher->addListener(KernelEvents::EXCEPTION, [$webListener, 'onKernelException'], -10);

        return new HttpKernel(
            $dispatcher,
            new ControllerResolver(),
            new RequestStack(),
            new ArgumentResolver(),
        );
    }

    public function testSuccessfulRouteReturnsOk(): void
    {
        $routes = new RouteCollection();
        $routes->add('test_route', new Route('/test', [
            '_controller' => function () {
                return new Response('Hello World');
            },
        ]));

        $kernel = $this->createKernelStack($routes);
        $request = Request::create('/test');
        $response = $kernel->handle($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Hello World', $response->getContent());
    }

    public function testNotFoundReturns404ForWebRequest(): void
    {
        $routes = new RouteCollection();
        $kernel = $this->createKernelStack($routes);
        $request = Request::create('/nonexistent');
        $response = $kernel->handle($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testNotFoundReturns404JsonForApiRequest(): void
    {
        $routes = new RouteCollection();
        $kernel = $this->createKernelStack($routes, isApi: true);
        $request = Request::create('/api/v3.2/nonexistent');
        $response = $kernel->handle($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertEquals(404, $content['status']);
        $this->assertEquals('Resource not found', $content['title']);
    }

    public function testControllerExceptionHandledByApiListener(): void
    {
        $routes = new RouteCollection();
        $routes->add('api_error', new Route('/api/v3.2/error', [
            '_controller' => function () {
                throw new \RuntimeException('Test error');
            },
        ]));

        // Suppress error_log output
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        $kernel = $this->createKernelStack($routes, isApi: true);
        $request = Request::create('/api/v3.2/error');
        $response = $kernel->handle($request);

        ini_set('error_log', $originalErrorLog);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertEquals(500, $content['status']);
        $this->assertEquals('Internal Server Error', $content['title']);
    }

    public function testControllerExceptionHandledByWebListener(): void
    {
        $routes = new RouteCollection();
        $routes->add('web_error', new Route('/error-page', [
            '_controller' => function () {
                throw new \RuntimeException('Test web error');
            },
        ]));

        // Suppress error_log output
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        $kernel = $this->createKernelStack($routes);
        $request = Request::create('/error-page');
        $response = $kernel->handle($request);

        ini_set('error_log', $originalErrorLog);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testMultipleRoutesResolveCorrectly(): void
    {
        $routes = new RouteCollection();
        $routes->add('route_a', new Route('/page-a', [
            '_controller' => function () {
                return new Response('Page A');
            },
        ]));
        $routes->add('route_b', new Route('/page-b', [
            '_controller' => function () {
                return new Response('Page B');
            },
        ]));

        $kernel = $this->createKernelStack($routes);

        $responseA = $kernel->handle(Request::create('/page-a'));
        $this->assertEquals('Page A', $responseA->getContent());

        $responseB = $kernel->handle(Request::create('/page-b'));
        $this->assertEquals('Page B', $responseB->getContent());
    }

    public function testRouteWithParameters(): void
    {
        $routes = new RouteCollection();
        $routes->add('param_route', new Route('/items/{id}', [
            '_controller' => function (Request $request) {
                $id = $request->attributes->get('id');
                return new Response(sprintf('Item %s', $id));
            },
        ], requirements: ['id' => '\d+']));

        $kernel = $this->createKernelStack($routes);

        $response = $kernel->handle(Request::create('/items/42'));
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Item 42', $response->getContent());
    }
}
