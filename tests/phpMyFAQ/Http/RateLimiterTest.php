<?php

declare(strict_types=1);

namespace phpMyFAQ\Http;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

#[AllowMockObjectsWithoutExpectations]
class RateLimiterTest extends TestCase
{
    private Configuration $configuration;

    private InMemoryStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createMock(Configuration::class);
        $this->storage = new InMemoryStorage();
    }

    public function testCheckAllowsRequestAndSetsHeaders(): void
    {
        $limiter = new RateLimiter($this->configuration, $this->storage);
        $allowed = $limiter->check('127.0.0.1', 5, 60);

        $this->assertTrue($allowed);
        $headers = $limiter->headers;
        $this->assertSame(5, $headers['X-RateLimit-Limit']);
        $this->assertSame(4, $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertArrayNotHasKey('Retry-After', $headers);
    }

    public function testCheckDeniesRequestWhenLimitIsExceeded(): void
    {
        $limiter = new RateLimiter($this->configuration, $this->storage);

        // Exhaust the limit
        for ($i = 0; $i < 3; $i++) {
            $limiter->check('api-key-1', 3, 60);
        }

        // Next request should be denied
        $allowed = $limiter->check('api-key-1', 3, 60);

        $this->assertFalse($allowed);
        $headers = $limiter->headers;
        $this->assertSame(3, $headers['X-RateLimit-Limit']);
        $this->assertSame(0, $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('Retry-After', $headers);
    }

    public function testCheckDecrementsRemainingTokens(): void
    {
        $limiter = new RateLimiter($this->configuration, $this->storage);

        $limiter->check('api-key-2', 5, 60);
        $this->assertSame(4, $limiter->headers['X-RateLimit-Remaining']);

        $limiter->check('api-key-2', 5, 60);
        $this->assertSame(3, $limiter->headers['X-RateLimit-Remaining']);

        $limiter->check('api-key-2', 5, 60);
        $this->assertSame(2, $limiter->headers['X-RateLimit-Remaining']);
    }

    public function testCheckUsesIndependentKeysForDifferentClients(): void
    {
        $limiter = new RateLimiter($this->configuration, $this->storage);

        $limiter->check('client-a', 5, 60);
        $this->assertSame(4, $limiter->headers['X-RateLimit-Remaining']);

        $limiter->check('client-b', 5, 60);
        $this->assertSame(4, $limiter->headers['X-RateLimit-Remaining']);
    }

    public function testCheckEnforcesMinimumLimitAndInterval(): void
    {
        $limiter = new RateLimiter($this->configuration, $this->storage);

        // Negative values should be clamped to 1
        $allowed = $limiter->check('edge-case', -5, -10);

        $this->assertTrue($allowed);
        $headers = $limiter->headers;
        $this->assertSame(1, $headers['X-RateLimit-Limit']);
        $this->assertSame(0, $headers['X-RateLimit-Remaining']);
    }

    public function testGetHeadersReturnsStoredHeaders(): void
    {
        $limiter = new RateLimiter($this->configuration, $this->storage);
        $limiter->check('header-key', 3, 60);

        $this->assertSame($limiter->headers, $limiter->getHeaders());
        $this->assertSame(3, $limiter->getHeaders()['X-RateLimit-Limit']);
    }

    public function testResetHeaderIsInTheFuture(): void
    {
        $limiter = new RateLimiter($this->configuration, $this->storage);
        $limiter->check('time-key', 10, 3600);

        $resetTime = $limiter->headers['X-RateLimit-Reset'];
        $this->assertGreaterThanOrEqual(time(), $resetTime);
    }

    public function testDefaultStorageIsInMemory(): void
    {
        // Constructor without explicit storage should use InMemoryStorage
        $limiter = new RateLimiter($this->configuration);

        $allowed = $limiter->check('default-storage', 5, 60);
        $this->assertTrue($allowed);
        $this->assertSame(4, $limiter->headers['X-RateLimit-Remaining']);
    }
}
