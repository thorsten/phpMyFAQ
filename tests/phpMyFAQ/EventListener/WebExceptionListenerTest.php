<?php

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Controller\Exception\ForbiddenException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
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

    protected function setUp(): void
    {
        $this->listener = new WebExceptionListener();
    }

    private function createEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
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
}
