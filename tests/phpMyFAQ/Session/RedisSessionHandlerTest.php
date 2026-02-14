<?php

namespace phpMyFAQ\Session;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(RedisSessionHandler::class)]
class RedisSessionHandlerTest extends TestCase
{
    public function testValidateConnectionThrowsForInvalidDsn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported Redis DSN scheme');

        RedisSessionHandler::validateConnection('http://redis:6379');
    }

    public function testValidateConnectionThrowsForUnreachableTarget(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis session handler is configured but unreachable');

        RedisSessionHandler::validateConnection('tcp://127.0.0.1:1', 0.1);
    }
}
