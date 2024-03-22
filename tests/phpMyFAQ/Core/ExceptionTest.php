<?php

namespace phpMyFAQ\Core;

use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testToString(): void
    {
        $message = 'Test exception message';
        $file = __FILE__;
        $line = 14;
        $exception = new Exception($message, 0, null);

        // Generate the expected string representation
        $expected = sprintf(
            "Exception %s with message %s in %s: %d\nStack trace:\n%s",
            Exception::class,
            $message,
            $file,
            $line,
            $exception->getTraceAsString()
        );

        // Ensure the __toString method returns the expected string
        $this->assertSame($expected, (string)$exception);
    }
}
