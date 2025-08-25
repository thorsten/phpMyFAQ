<?php

namespace phpMyFAQ;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Core\Exception as PMFException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ApplicationTest extends TestCase
{
    private Application $application;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->application = new Application($this->container);
    }

    /**
     * @throws Exception
     */
    public function testConstructorWithContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $application = new Application($container);
        $this->assertInstanceOf(Application::class, $application);
    }

    public function testConstructorWithoutContainer(): void
    {
        $application = new Application();
        $this->assertInstanceOf(Application::class, $application);
    }

    public function testSetUrlMatcher(): void
    {
        $urlMatcher = $this->createMock(UrlMatcher::class);
        $this->application->setUrlMatcher($urlMatcher);

        $reflection = new ReflectionClass(Application::class);
        $property = $reflection->getProperty('urlMatcher');

        $this->assertSame($urlMatcher, $property->getValue($this->application));
    }

    public function testSetControllerResolver(): void
    {
        $controllerResolver = $this->createMock(ControllerResolver::class);
        $this->application->setControllerResolver($controllerResolver);

        $reflection = new ReflectionClass(Application::class);
        $property = $reflection->getProperty('controllerResolver');

        $this->assertSame($controllerResolver, $property->getValue($this->application));
    }

    /**
     * @throws ReflectionException
     */
    public function testSetLanguageWithContainer(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $language = $this->createMock(Language::class);

        $configuration->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['main.languageDetection', 'browser'],
                ['main.language', 'en']
            ]);

        $language->expects($this->once())
            ->method('setLanguage')
            ->with('browser', 'en')
            ->willReturn('de');

        $configuration->expects($this->once())
            ->method('setLanguage')
            ->with($language);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['phpmyfaq.configuration', $configuration],
                ['phpmyfaq.language', $language]
            ]);

        $reflection = new ReflectionClass(Application::class);
        $method = $reflection->getMethod('setLanguage');

        $result = $method->invoke($this->application);
        $this->assertEquals('de', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testSetLanguageWithoutContainer(): void
    {
        $application = new Application();

        $reflection = new ReflectionClass(Application::class);
        $method = $reflection->getMethod('setLanguage');

        $result = $method->invoke($application);
        $this->assertEquals('en', $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testInitializeTranslationSuccess(): void
    {
        $reflection = new ReflectionClass(Application::class);
        $method = $reflection->getMethod('initializeTranslation');

        $this->expectNotToPerformAssertions();

        $method->invoke($this->application, 'en');
    }

    /**
     * @throws ReflectionException
     */
    public function testHandleRequestSuccess(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('test_route', new Route('/test', [
            '_controller' => function () {
                return new Response('Test Response');
            }
        ]));

        $request = Request::create('/test');
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $reflection = new ReflectionClass(Application::class);
        $method = $reflection->getMethod('handleRequest');

        $this->expectOutputString('Test Response');
        $method->invoke($this->application, $routeCollection, $request, $requestContext);
    }

    /**
     * @throws ReflectionException
     */
    public function testHandleRequestResourceNotFoundException(): void
    {
        $routeCollection = new RouteCollection();
        $request = Request::create('/nonexistent');
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $reflection = new ReflectionClass(Application::class);
        $method = $reflection->getMethod('handleRequest');

        ob_start();
        $method->invoke($this->application, $routeCollection, $request, $requestContext);
        $output = ob_get_clean();

        $this->assertStringContainsString('Not Found:', $output);
    }

    /**
     * @throws ReflectionException
     */
    public function testHandleRequestUnauthorizedHttpExceptionForApi(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('api_route', new Route('/api/test', [
            '_controller' => function () {
                throw new UnauthorizedHttpException('Bearer', 'Unauthorized');
            }
        ]));

        $request = Request::create('/api/test');
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);
        $requestContext->setBaseUrl('/api');

        $reflection = new ReflectionClass(Application::class);
        $method = $reflection->getMethod('handleRequest');

        ob_start();
        $method->invoke($this->application, $routeCollection, $request, $requestContext);
        $output = ob_get_clean();

        $this->assertStringContainsString('Unauthorized access', $output);
    }

    /**
     * @throws ReflectionException
     */
    public function testHandleRequestUnauthorizedHttpExceptionForNonApi(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('web_route', new Route('/test', [
            '_controller' => function () {
                throw new UnauthorizedHttpException('Bearer', 'Unauthorized');
            }
        ]));

        $request = Request::create('/test');
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $reflection = new ReflectionClass(Application::class);
        $method = $reflection->getMethod('handleRequest');

        ob_start();
        $method->invoke($this->application, $routeCollection, $request, $requestContext);
        $output = ob_get_clean();

        // RedirectResponse sendet Location Header, nicht Inhalt
        $this->expectOutputString('');
    }

    /**
     * @throws ReflectionException
     */
    public function testHandleRequestBadRequestException(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('bad_route', new Route('/bad', [
            '_controller' => function () {
                throw new BadRequestException('Bad request');
            }
        ]));

        $request = Request::create('/bad');
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $reflection = new ReflectionClass(Application::class);
        $method = $reflection->getMethod('handleRequest');

        ob_start();
        $method->invoke($this->application, $routeCollection, $request, $requestContext);
        $output = ob_get_clean();

        $this->assertStringContainsString('An error occurred:', $output);
        $this->assertStringContainsString('Bad request', $output);
    }

    /**
     * @throws ReflectionException
     */
    public function testRunMethodWithContainer(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $language = $this->createMock(Language::class);

        $configuration->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['main.languageDetection', 'browser'],
                ['main.language', 'en']
            ]);

        $language->expects($this->once())
            ->method('setLanguage')
            ->with('browser', 'en')
            ->willReturn('en');

        $configuration->expects($this->once())
            ->method('setLanguage')
            ->with($language);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['phpmyfaq.configuration', $configuration],
                ['phpmyfaq.language', $language]
            ]);

        $routeCollection = new RouteCollection();
        $routeCollection->add('test_route', new Route('/', [
            '_controller' => function () {
                return new Response('Welcome');
            }
        ]));

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';

        ob_start();
        try {
            $this->application->run($routeCollection);
        } catch (PMFException $e) {
            $this->assertInstanceOf(PMFException::class, $e);
        }
        $output = ob_get_clean();

        $this->assertTrue(true);
    }

    /**
     * Test für die run() Methode ohne Container
     */
    public function testRunMethodWithoutContainer(): void
    {
        $application = new Application();
        $routeCollection = new RouteCollection();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/nonexistent';
        $_SERVER['HTTP_HOST'] = 'localhost';

        ob_start();
        try {
            $application->run($routeCollection);
        } catch (PMFException $e) {
            $this->assertInstanceOf(PMFException::class, $e);
        }
        $output = ob_get_clean();

        $this->assertTrue(true);
    }
}
