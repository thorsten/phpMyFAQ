<?php

namespace phpMyFAQ\Core;

use ErrorException;
use phpMyFAQ\Environment;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testErrorHandler(): void
    {
        // Arrange
        $level = E_NOTICE;
        $message = "Undefined variable: x";
        $filename = "test.php";
        $line = 10;

        // Act
        try {
            Error::errorHandler($level, $message, $filename, $line);
        } catch (ErrorException $exception) {
            // Assert
            $this->assertSame($message, $exception->getMessage());
            $this->assertSame(0, $exception->getCode());
            $this->assertSame($level, $exception->getSeverity());
            $this->assertSame(Environment::isDebugMode() ? $filename : basename($filename), $exception->getFile());
            $this->assertSame($line, $exception->getLine());
        }
    }
}
