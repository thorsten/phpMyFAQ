<?php

declare(strict_types=1);

namespace phpMyFAQ\Session;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(RedisSessionHandler::class)]
class RedisSessionHandlerTest extends TestCase
{
    public function testConfigureThrowsWhenRedisExtensionNotLoaded(): void
    {
        if (extension_loaded('redis')) {
            static::markTestSkipped('Test requires the redis extension to NOT be loaded.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis session handler requires the PHP redis extension');

        RedisSessionHandler::configure();
    }

    public function testConfigureWithoutValidation(): void
    {
        if (!extension_loaded('redis')) {
            static::markTestSkipped('Test requires the redis extension.');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $originalPath = ini_get('session.save_path');

        try {
            RedisSessionHandler::configure(dsn: 'tcp://127.0.0.1:6379', validate: false);

            static::assertSame('redis', ini_get('session.save_handler'));
            static::assertSame('tcp://127.0.0.1:6379', ini_get('session.save_path'));
        } finally {
            // The original 'user' handler cannot be restored via ini_set;
            // reset to 'files' to avoid poisoning subsequent tests
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', $originalPath);
        }
    }

    public function testConfigureUsesDefaultDsnWhenEmpty(): void
    {
        if (!extension_loaded('redis')) {
            static::markTestSkipped('Test requires the redis extension.');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $originalPath = ini_get('session.save_path');

        try {
            RedisSessionHandler::configure(dsn: '', validate: false);

            static::assertSame('redis', ini_get('session.save_handler'));
            static::assertSame(RedisSessionHandler::DEFAULT_DSN, ini_get('session.save_path'));
        } finally {
            // The original 'user' handler cannot be restored via ini_set;
            // reset to 'files' to avoid poisoning subsequent tests
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', $originalPath);
        }
    }

    public function testValidateConnectionThrowsForUnsupportedScheme(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported Redis DSN scheme "http"');

        RedisSessionHandler::validateConnection('http://redis:6379');
    }

    public function testValidateConnectionThrowsForUnreachableTarget(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis session handler is configured but unreachable');

        RedisSessionHandler::validateConnection(dsn: 'tcp://127.0.0.1:1', timeoutSeconds: 0.1);
    }

    public function testValidateConnectionThrowsForInvalidDsn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Redis DSN for sessions.');

        RedisSessionHandler::validateConnection('not-a-valid-dsn');
    }

    public function testValidateConnectionThrowsForEmptyUnixPath(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Redis unix socket DSN for sessions.');

        // unix: with no path triggers the empty-path guard
        RedisSessionHandler::validateConnection('unix:');
    }

    public function testValidateConnectionUsesRedisScheme(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis session handler is configured but unreachable');

        RedisSessionHandler::validateConnection(dsn: 'redis://127.0.0.1:1', timeoutSeconds: 0.1);
    }

    public function testValidateConnectionUsesUnixSchemeWithUnreachableSocket(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis session handler is configured but unreachable');

        // unix:/path is parsed by parse_url as scheme=unix, path=/path
        RedisSessionHandler::validateConnection(dsn: 'unix:/tmp/nonexistent-redis.sock', timeoutSeconds: 0.1);
    }

    public function testValidateConnectionThrowsForMalformedDsn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Redis DSN for sessions.');

        RedisSessionHandler::validateConnection('://');
    }
}
