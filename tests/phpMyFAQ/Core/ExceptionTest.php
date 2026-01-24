<?php

/**
 * Test case for Core Exception
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Core;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class ExceptionTest
 */
#[AllowMockObjectsWithoutExpectations]
class ExceptionTest extends TestCase
{
    /**
     * Test exception creation with message
     */
    public function testExceptionCreation(): void
    {
        $message = 'Test error message';
        $exception = new Exception($message);

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Stringable::class, $exception);
    }

    /**
     * Test exception with message and code
     */
    public function testExceptionWithCode(): void
    {
        $message = 'Test error with code';
        $code = 500;
        $exception = new Exception($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    /**
     * Test exception with previous exception
     */
    public function testExceptionWithPrevious(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $exception = new Exception('Current error', 0, $previousException);

        $this->assertEquals('Current error', $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    /**
     * Test __toString method
     */
    public function testToString(): void
    {
        $message = 'Test exception message';
        $exception = new Exception($message);

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString('Exception phpMyFAQ\Core\Exception', $stringRepresentation);
        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString(__FILE__, $stringRepresentation);
        $this->assertStringContainsString('Stack trace:', $stringRepresentation);
    }

    /**
     * Test __toString with multiline message
     */
    public function testToStringWithMultilineMessage(): void
    {
        $message = "Line 1\nLine 2\nLine 3";
        $exception = new Exception($message);

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString('Exception phpMyFAQ\Core\Exception', $stringRepresentation);
    }

    /**
     * Test __toString with special characters
     */
    public function testToStringWithSpecialCharacters(): void
    {
        $message = 'Special chars: äöü ß & < > " \'';
        $exception = new Exception($message);

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString($message, $stringRepresentation);
    }

    /**
     * Test exception inheritance
     */
    public function testExceptionInheritance(): void
    {
        $exception = new Exception('Test');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(\Stringable::class, $exception);
    }

    /**
     * Test exception can be thrown and caught
     */
    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test thrown exception');

        throw new Exception('Test thrown exception');
    }

    /**
     * Test exception with empty message
     */
    public function testExceptionWithEmptyMessage(): void
    {
        $exception = new Exception('');

        $this->assertEquals('', $exception->getMessage());
        $this->assertStringContainsString('Exception phpMyFAQ\Core\Exception', (string) $exception);
    }
}
