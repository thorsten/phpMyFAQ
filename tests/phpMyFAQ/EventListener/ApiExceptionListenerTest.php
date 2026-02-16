<?php

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ApiExceptionListenerTest extends TestCase
{
    private ApiExceptionListener $listener;
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getDefaultUrl')->willReturn('https://localhost');
        $this->listener = new ApiExceptionListener($this->configuration);
    }

    private function createEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testIgnoresNonApiRequests(): void
    {
        $request = Request::create('/some-page.html');
        $event = $this->createEvent($request, new \RuntimeException('error'));

        $this->listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testHandlesApiRequestsByPath(): void
    {
        $request = Request::create('/api/v3.2/version');
        $event = $this->createEvent($request, new ResourceNotFoundException('Route not found'));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('https://localhost/problems/not-found', $content['type']);
        $this->assertEquals('Resource not found', $content['title']);
        $this->assertEquals(404, $content['status']);
    }

    public function testHandlesApiContextAttribute(): void
    {
        $request = Request::create('/admin/api/something');
        $request->attributes->set('_api_context', true);
        $event = $this->createEvent($request, new ResourceNotFoundException('not found'));

        $this->listener->onKernelException($event);

        $this->assertNotNull($event->getResponse());
        $this->assertEquals(Response::HTTP_NOT_FOUND, $event->getResponse()->getStatusCode());
    }

    public function testHandlesUnauthorizedException(): void
    {
        $request = Request::create('/api/v3.2/secure');
        $event = $this->createEvent($request, new UnauthorizedHttpException('Bearer', 'Missing token'));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(401, $content['status']);
        $this->assertEquals('Unauthorized', $content['title']);
    }

    public function testHandlesForbiddenException(): void
    {
        $request = Request::create('/api/v3.2/admin');
        $event = $this->createEvent($request, new ForbiddenException('Access denied'));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(403, $content['status']);
        $this->assertEquals('Forbidden', $content['title']);
    }

    public function testHandlesBadRequestException(): void
    {
        $request = Request::create('/api/v3.2/test');
        $event = $this->createEvent($request, new BadRequestException('Invalid input'));

        $this->listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(400, $content['status']);
        $this->assertEquals('Bad Request', $content['title']);
    }

    public function testHandlesGenericException(): void
    {
        $request = Request::create('/api/v3.2/error');
        $event = $this->createEvent($request, new \RuntimeException('Something went wrong'));

        // Suppress error_log output
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        $this->listener->onKernelException($event);

        ini_set('error_log', $originalErrorLog);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(500, $content['status']);
        $this->assertEquals('Internal Server Error', $content['title']);
    }

    public function testWithoutConfiguration(): void
    {
        $listener = new ApiExceptionListener(null);
        $request = Request::create('/api/v3.2/test');
        $event = $this->createEvent($request, new ResourceNotFoundException('not found'));

        $listener->onKernelException($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('/problems/not-found', $content['type']);
    }
}
