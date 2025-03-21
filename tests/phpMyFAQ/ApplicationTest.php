<?php

namespace phpMyFAQ;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;

class ApplicationTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConstructor(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $application = new Application($container);
        $this->assertInstanceOf(Application::class, $application);
    }

    /**
     * @throws Exception
     * @throws \ReflectionException
     */
    public function testHandleRequest(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $application = new Application($container);

        $routeCollection = new RouteCollection();
        $routeCollection->add('test_route', new Route('/test', [
            '_controller' => function () {
                return new Response('Test Response');
            }
        ]));

        $request = Request::create('/test');
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $urlMatcher = new UrlMatcher($routeCollection, $requestContext);
        $controllerResolver = $this->createMock(ControllerResolver::class);
        $controllerResolver->method('getController')->willReturn(function () {
            return new Response('Test Response');
        });

        $application->setUrlMatcher($urlMatcher);
        $application->setControllerResolver($controllerResolver);

        $reflection = new \ReflectionClass(Application::class);
        $method = $reflection->getMethod('handleRequest');
        $method->setAccessible(true);

        $this->expectOutputString('Test Response');
        $method->invoke($application, $routeCollection, $request, $requestContext);
    }
}
