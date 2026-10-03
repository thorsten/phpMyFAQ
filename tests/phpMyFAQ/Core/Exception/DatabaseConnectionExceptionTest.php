<?php

declare(strict_types=1);

namespace phpMyFAQ\Core\Exception;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(DatabaseConnectionException::class)]
final class DatabaseConnectionExceptionTest extends TestCase
{
    public function testIsACoreException(): void
    {
        $exception = new DatabaseConnectionException('Unable to connect to the database');

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('Unable to connect to the database', $exception->getMessage());
    }

    public function testKeepsCodeAndPrevious(): void
    {
        $previous = new RuntimeException('SQLSTATE[HY000] [2002] Connection refused');

        $exception = new DatabaseConnectionException('Unable to connect', 2002, $previous);

        $this->assertSame(2002, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
