<?php

declare(strict_types=1);

namespace phpMyFAQ\Http;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RateLimiterTest extends TestCase
{
    private Configuration $configuration;
    private DatabaseDriver $db;

    protected function setUp(): void
    {
        parent::setUp();

        Database::setTablePrefix('');
        $this->configuration = $this->createMock(Configuration::class);
        $this->db = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($this->db);
    }

    public function testCheckAllowsRequestAndSetsHeaders(): void
    {
        $this->db->method('escape')->willReturnCallback(static fn (string $value): string => $value);
        $this->db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $this->db->method('query')->willReturnCallback(static function (string $query): mixed {
            if (str_starts_with($query, 'SELECT requests')) {
                return 'select-result';
            }

            return true;
        });
        $this->db->method('fetchObject')->with('select-result')->willReturn(false);

        $limiter = new RateLimiter($this->configuration);
        $allowed = $limiter->check('127.0.0.1', 5, 60);

        $this->assertTrue($allowed);
        $headers = $limiter->headers;
        $this->assertSame(5, $headers['X-RateLimit-Limit']);
        $this->assertSame(4, $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    public function testCheckDeniesRequestWhenLimitIsReached(): void
    {
        $this->db->method('escape')->willReturnCallback(static fn (string $value): string => $value);
        $this->db->method('query')->willReturn('select-result');
        $this->db->method('fetchObject')->with('select-result')->willReturn((object) ['requests' => 3]);

        $limiter = new RateLimiter($this->configuration);
        $allowed = $limiter->check('api-key-1', 3, 60);

        $this->assertFalse($allowed);
        $headers = $limiter->headers;
        $this->assertSame(0, $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('Retry-After', $headers);
    }
}
