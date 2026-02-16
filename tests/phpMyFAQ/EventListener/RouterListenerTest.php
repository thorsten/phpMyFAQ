<?php

namespace phpMyFAQ\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

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

    public function testThrowsOnNoMatch(): void
    {
        $routes = new RouteCollection();
        $listener = new RouterListener($routes);

        $request = Request::create('/nonexistent');
        $event = $this->createEvent($request);

        $this->expectException(ResourceNotFoundException::class);
        $listener->onKernelRequest($event);
    }
}
