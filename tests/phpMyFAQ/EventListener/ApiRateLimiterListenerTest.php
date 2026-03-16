<?php

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Configuration;
use phpMyFAQ\Http\RateLimiter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ApiRateLimiterListener::class)]
#[UsesClass(RateLimiter::class)]
final class ApiRateLimiterListenerTest extends TestCase
{
    public function testOnKernelRequestSkipsSubRequests(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration->expects($this->never())->method('get');

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter);
        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestSkipsWhenRateLimitIsDisabled(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['api.rateLimit.requests', '0'],
                ['api.rateLimit.interval', '3600'],
            ]);

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter);
        $event = $this->createEvent();

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestAllowsRequestWithinLimit(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['api.rateLimit.requests', '100'],
                ['api.rateLimit.interval', '3600'],
            ]);

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter);
        $event = $this->createEvent();

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestReturnsTooManyRequestsResponseWhenLimitIsExceeded(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['api.rateLimit.requests', '1'],
                ['api.rateLimit.interval', '3600'],
            ]);
        $rateLimiter->check('127.0.0.1', 1, 3600);

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter);
        $event = $this->createEvent();

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame('1', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('Retry-After'));
        $this->assertStringContainsString('Too many requests.', (string) $response->getContent());
    }

    private function createEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $request = Request::create('/api/v3.2/search');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
    }
}
