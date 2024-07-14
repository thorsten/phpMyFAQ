<?php

namespace phpMyFAQ;

use phpMyFAQ\Application;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;

class ApplicationTest extends TestCase
{
    public function testConstructor(): void
    {
        $config = $this->createMock(Configuration::class);
        $application = new Application($config);
        $this->assertInstanceOf(Application::class, $application);
    }


    public function testHandleRequest(): void
    {
        $routeCollection = $this->createMock(RouteCollection::class);
        $application = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $reflection = new \ReflectionClass(Application::class);
        $method = $reflection->getMethod('handleRequest');
        $method->setAccessible(true);

        $request = $this->createMock(Request::class);
        $request->method('getPathInfo')->willReturn('/test');

        $urlMatcher = $this->createMock(UrlMatcher::class);
        $urlMatcher->method('match')->willReturn(['_controller' => 'testController']);

        $controllerResolver = $this->createMock(ControllerResolver::class);
        $controllerResolver->method('getController')->willReturn(function() { return new Response('Test Response'); });

        $requestContext = $this->createMock(RequestContext::class);
        $requestContext->method('fromRequest')->willReturnSelf();

        $this->expectException(SuspiciousOperationException::class);
        $response = $method->invoke($application, $routeCollection);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Test Response', $response->getContent());
    }
}
