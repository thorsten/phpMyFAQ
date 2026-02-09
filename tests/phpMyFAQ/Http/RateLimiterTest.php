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
            // INSERT succeeds (new window), then SELECT returns the new row
            if (str_starts_with($query, 'INSERT')) {
                return true;
            }

            if (str_starts_with($query, 'SELECT')) {
                return 'select-result';
            }

            return true;
        });
        $this->db->method('fetchObject')->with('select-result')->willReturn((object) ['requests' => 1]);

        $limiter = new RateLimiter($this->configuration);
        $allowed = $limiter->check('127.0.0.1', 5, 60);

        $this->assertTrue($allowed);
        $headers = $limiter->headers;
        $this->assertSame(5, $headers['X-RateLimit-Limit']);
        $this->assertSame(4, $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    public function testCheckDeniesRequestWhenLimitIsExceeded(): void
    {
        $this->db->method('escape')->willReturnCallback(static fn (string $value): string => $value);
        $this->db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $this->db->method('query')->willReturnCallback(static function (string $query): mixed {
            // INSERT fails (row exists), UPDATE increments, SELECT returns over-limit count
            if (str_starts_with($query, 'INSERT')) {
                return false;
            }

            if (str_starts_with($query, 'SELECT')) {
                return 'select-result';
            }

            return true;
        });
        $this->db->method('fetchObject')->with('select-result')->willReturn((object) ['requests' => 4]);

        $limiter = new RateLimiter($this->configuration);
        $allowed = $limiter->check('api-key-1', 3, 60);

        $this->assertFalse($allowed);
        $headers = $limiter->headers;
        $this->assertSame(0, $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('Retry-After', $headers);
    }

    public function testCheckAllowsRequestAfterAtomicIncrement(): void
    {
        $this->db->method('escape')->willReturnCallback(static fn (string $value): string => $value);
        $this->db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $this->db->method('query')->willReturnCallback(static function (string $query): mixed {
            // INSERT fails (row exists), UPDATE increments, SELECT returns within-limit count
            if (str_starts_with($query, 'INSERT')) {
                return false;
            }

            if (str_starts_with($query, 'SELECT')) {
                return 'select-result';
            }

            return true;
        });
        $this->db->method('fetchObject')->with('select-result')->willReturn((object) ['requests' => 2]);

        $limiter = new RateLimiter($this->configuration);
        $allowed = $limiter->check('api-key-1', 5, 60);

        $this->assertTrue($allowed);
        $headers = $limiter->headers;
        $this->assertSame(5, $headers['X-RateLimit-Limit']);
        $this->assertSame(3, $headers['X-RateLimit-Remaining']);
    }

    public function testCheckDeniesRequestWhenUpdateFails(): void
    {
        $this->db->method('escape')->willReturnCallback(static fn (string $value): string => $value);
        $this->db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $this->db->method('query')->willReturnCallback(static function (string $query): mixed {
            // INSERT fails (row exists), UPDATE also fails (DB error)
            if (str_starts_with($query, 'INSERT')) {
                return false;
            }

            return false;
        });

        $limiter = new RateLimiter($this->configuration);
        $allowed = $limiter->check('api-key-1', 5, 60);

        $this->assertFalse($allowed);
        $headers = $limiter->headers;
        $this->assertSame(0, $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('Retry-After', $headers);
    }

    public function testCheckDeniesRequestWhenSelectFails(): void
    {
        $this->db->method('escape')->willReturnCallback(static fn (string $value): string => $value);
        $this->db->method('now')->willReturn('CURRENT_TIMESTAMP');
        $this->db->method('query')->willReturnCallback(static function (string $query): mixed {
            // INSERT succeeds, but SELECT fails
            if (str_starts_with($query, 'INSERT')) {
                return true;
            }

            return false;
        });

        $limiter = new RateLimiter($this->configuration);
        $allowed = $limiter->check('api-key-1', 5, 60);

        $this->assertFalse($allowed);
        $headers = $limiter->headers;
        $this->assertSame(0, $headers['X-RateLimit-Remaining']);
        $this->assertArrayHasKey('Retry-After', $headers);
    }
}
