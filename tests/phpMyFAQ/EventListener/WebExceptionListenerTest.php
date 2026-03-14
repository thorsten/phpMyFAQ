<?php

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Environment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

#[AllowMockObjectsWithoutExpectations]
class WebExceptionListenerTest extends TestCase
{
    private WebExceptionListener $listener;
    private bool $originalDebugMode;

    protected function setUp(): void
    {
        $this->listener = new WebExceptionListener();
        $this->originalDebugMode = Environment::isDebugMode();
    }

    protected function tearDown(): void
    {
        $this->setDebugMode($this->originalDebugMode);
    }

    private function createEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    private function invokePrivateMethod(string $method, mixed ...$arguments): mixed
    {
        $reflectionMethod = new ReflectionMethod($this->listener, $method);

        return $reflectionMethod->invoke($this->listener, ...$arguments);
    }

    private function setDebugMode(bool $enabled): void
    {
        $reflectionProperty = new ReflectionProperty(Environment::class, 'debugMode');
        $reflectionProperty->setValue(null, $enabled);
    }

    public function testIgnoresApiRequests(): void
    {
        $request = Request::create('/api/v3.2/version');
        $event = $this->createEvent($request, new \RuntimeException('error'));

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresApiContextAttribute(): void
    {
        $request = Request::create('/admin/api/something');
        $request->attributes->set('_api_context', true);
        $event = $this->createEvent($request, new \RuntimeException('error'));

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testHandlesResourceNotFoundException(): void
    {
        $request = Request::create('/nonexistent-page.html');
        $event = $this->createEvent($request, new ResourceNotFoundException('not found'));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        // Either PageNotFoundController handles it (404) or fallback (404)
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testHandlesUnauthorizedHttpException(): void
    {
        $request = Request::create('/secure-page.html');
        $event = $this->createEvent($request, new UnauthorizedHttpException('Bearer', 'Not logged in'));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('/login', $response->headers->get('Location'));
    }

    public function testHandlesForbiddenException(): void
    {
        $request = Request::create('/admin/settings.html');
        $event = $this->createEvent($request, new ForbiddenException('No permission'));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testHandlesBadRequestException(): void
    {
        $request = Request::create('/page.html');
        $event = $this->createEvent($request, new BadRequestException('Invalid'));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testHandlesGenericException(): void
    {
        $request = Request::create('/page.html');
        $event = $this->createEvent($request, new \RuntimeException('Server error'));

        // Suppress error_log output
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        $this->listener->onKernelException($event);

        ini_set('error_log', $originalErrorLog);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testHandleErrorResponseReturnsFallbackMessageOutsideDebugMode(): void
    {
        $this->setDebugMode(false);

        $response = $this->invokePrivateMethod(
            'handleErrorResponse',
            'Internal Server Error: :message at line :line at :file',
            'Internal Server Error',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            new \RuntimeException('Server error'),
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getContent());
    }

    public function testHandleErrorResponseReturnsFormattedMessageInDebugMode(): void
    {
        $this->setDebugMode(true);
        $exception = new \RuntimeException('Visible error');

        $response = $this->invokePrivateMethod(
            'handleErrorResponse',
            'Internal Server Error: :message at line :line at :file',
            'Internal Server Error',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $exception,
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertStringContainsString('Internal Server Error: Visible error', (string) $response->getContent());
        $this->assertStringContainsString((string) $exception->getLine(), (string) $response->getContent());
        $this->assertStringContainsString($exception->getFile(), (string) $response->getContent());
    }

    public function testFormatExceptionMessageReplacesPlaceholders(): void
    {
        $exception = new \RuntimeException('Formatted error');

        $message = $this->invokePrivateMethod(
            'formatExceptionMessage',
            'Oops: :message at :file line :line',
            $exception,
        );

        $this->assertSame(
            sprintf('Oops: %s at %s line %d', $exception->getMessage(), $exception->getFile(), $exception->getLine()),
            $message,
        );
    }
}
