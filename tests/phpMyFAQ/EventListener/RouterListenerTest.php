<?php

namespace phpMyFAQ\EventListener;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

#[AllowMockObjectsWithoutExpectations]
class RouterListenerTest extends TestCase
{
    private function createEvent(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, $type);
    }

    public function testMatchesRoute(): void
    {
        $routes = new RouteCollection();
        $routes->add('test_route', new Route('/test', [
            '_controller' => function () {
                return new Response('OK');
            },
        ]));

        $listener = new RouterListener($routes);
        $request = Request::create('/test');
        $event = $this->createEvent($request);

        $listener->onKernelRequest($event);

        $this->assertTrue($request->attributes->has('_controller'));
        $this->assertEquals('test_route', $request->attributes->get('_route'));
    }

    public function testSkipsSubRequests(): void
    {
        $routes = new RouteCollection();
        $listener = new RouterListener($routes);

        $request = Request::create('/test');
        $event = $this->createEvent($request, HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_controller'));
    }

    public function testSkipsAlreadyMatchedRequests(): void
    {
        $routes = new RouteCollection();
        $listener = new RouterListener($routes);

        $request = Request::create('/test');
        $request->attributes->set('_controller', 'SomeController::action');
        $event = $this->createEvent($request);

        $listener->onKernelRequest($event);

        $this->assertEquals('SomeController::action', $request->attributes->get('_controller'));
    }

    public function testThrowsNotFoundHttpExceptionOnNoMatch(): void
    {
        $routes = new RouteCollection();
        $listener = new RouterListener($routes);

        $request = Request::create('/nonexistent');
        $event = $this->createEvent($request);

        try {
            $listener->onKernelRequest($event);
            $this->fail('Expected NotFoundHttpException was not thrown.');
        } catch (NotFoundHttpException $exception) {
            $this->assertInstanceOf(ResourceNotFoundException::class, $exception->getPrevious());
        }
    }

    public function testMatchesRouteWithTrailingSlash(): void
    {
        $routes = new RouteCollection();
        $routes->add('test_route', new Route('/update', [
            '_controller' => function () {
                return new Response('OK');
            },
        ]));

        $listener = new RouterListener($routes);
        $request = Request::create('/update/');
        $event = $this->createEvent($request);

        $listener->onKernelRequest($event);

        $this->assertEquals('test_route', $request->attributes->get('_route'));
    }

    public function testMatchesRouteWithIndexPhpSuffix(): void
    {
        $routes = new RouteCollection();
        $routes->add('test_route', new Route('/update', [
            '_controller' => function () {
                return new Response('OK');
            },
        ]));

        $listener = new RouterListener($routes);
        $request = Request::create('/update/index.php');
        $event = $this->createEvent($request);

        $listener->onKernelRequest($event);

        $this->assertEquals('test_route', $request->attributes->get('_route'));
    }

    public function testRootPathIsNotStripped(): void
    {
        $routes = new RouteCollection();
        $routes->add('root_route', new Route('/', [
            '_controller' => function () {
                return new Response('OK');
            },
        ]));

        $listener = new RouterListener($routes);
        $request = Request::create('/');
        $event = $this->createEvent($request);

        $listener->onKernelRequest($event);

        $this->assertEquals('root_route', $request->attributes->get('_route'));
    }

    public function testThrowsMethodNotAllowedHttpExceptionWhenMethodIsNotAllowed(): void
    {
        $routes = new RouteCollection();
        $routes->add(
            'test_route',
            new Route(
                '/test',
                [
                    '_controller' => static function () {
                        return new Response('OK');
                    },
                ],
                [],
                [],
                '',
                [],
                ['GET'],
            ),
        );

        $listener = new RouterListener($routes);
        $request = Request::create('/test', 'POST');
        $event = $this->createEvent($request);

        try {
            $listener->onKernelRequest($event);
            $this->fail('Expected MethodNotAllowedHttpException was not thrown.');
        } catch (MethodNotAllowedHttpException $exception) {
            $this->assertStringContainsString('GET', $exception->getHeaders()['Allow'] ?? '');
            $this->assertInstanceOf(MethodNotAllowedException::class, $exception->getPrevious());
        }
    }
}
