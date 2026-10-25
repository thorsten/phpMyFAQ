<?php

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Http\RateLimiter;
use phpMyFAQ\Http\SecurityEventLogger;
use phpMyFAQ\Tenant\TenantContext;
use phpMyFAQ\Tenant\TenantQuotas;
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
#[UsesClass(SecurityEventLogger::class)]
#[UsesClass(TenantContext::class)]
#[UsesClass(TenantQuotas::class)]
final class ApiRateLimiterListenerTest extends TestCase
{
    public function testOnKernelRequestSkipsSubRequests(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration->expects($this->never())->method('get');

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext());
        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestSkipsPrivateApiRoutes(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration->expects($this->never())->method('get');

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext());
        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_route', 'api.private.webauthn.prepare');

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestSkipsSafeRequests(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration->expects($this->never())->method('get');

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext());
        $request = Request::create('/api/v4.0/version', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestSkipsWhenIntervalIsNotPositive(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['api.rateLimit.requests', '1'],
                ['api.rateLimit.interval', '0'],
            ]);

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext(1));
        $event = $this->createEvent();

        $listener->onKernelRequest($event);

        $this->assertNull($event->getResponse());
        $this->assertTrue($rateLimiter->peek('tenant-7', 1, 3600));
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

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext());
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

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext());
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

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())->method('warning')->with($this->stringContains('security-rate-limit-exceeded'));
        $configuration->method('getLogger')->willReturn($logger);

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext());
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

    public function testOnKernelRequestReturnsTooManyRequestsResponseWhenTenantQuotaIsExceeded(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration->method('get')->willReturnMap([
            ['api.rateLimit.requests', '100'],
            ['api.rateLimit.interval', '3600'],
        ]);
        $rateLimiter->check('tenant-7', 1, 3600);

        $logger = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Tenant quota of 1 API requests per 3600 seconds exceeded'));
        $configuration->method('getLogger')->willReturn($logger);

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext(1));
        $event = $this->createEvent();

        $listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame('1', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
    }

    public function testOnKernelRequestEnforcesTenantQuotaWhenClientRateLimitIsDisabled(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration->method('get')->willReturnMap([
            ['api.rateLimit.requests', '0'],
            ['api.rateLimit.interval', '3600'],
        ]);
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext(1));

        $firstEvent = $this->createEvent();
        $listener->onKernelRequest($firstEvent);
        $this->assertNull($firstEvent->getResponse());

        $secondEvent = $this->createEvent();
        $listener->onKernelRequest($secondEvent);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $secondEvent->getResponse()?->getStatusCode());
    }

    public function testOnKernelRequestDoesNotConsumeTenantQuotaWhenClientIsRejected(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $rateLimiter = new RateLimiter(storage: new InMemoryStorage());

        $configuration->method('get')->willReturnMap([
            ['api.rateLimit.requests', '1'],
            ['api.rateLimit.interval', '3600'],
        ]);
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));
        $rateLimiter->check('127.0.0.1', 1, 3600);

        $listener = new ApiRateLimiterListener($configuration, $rateLimiter, $this->createTenantContext(1));
        $event = $this->createEvent();

        $listener->onKernelRequest($event);

        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $event->getResponse()?->getStatusCode());
        $this->assertTrue($rateLimiter->peek('tenant-7', 1, 3600));
    }

    private function createTenantContext(?int $maxApiRequests = null): TenantContext
    {
        return new TenantContext(7, 'localhost', 'faq_', '', 'free', new TenantQuotas(maxApiRequests: $maxApiRequests));
    }

    private function createEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $request = Request::create('/api/v4.0/search', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
    }
}
