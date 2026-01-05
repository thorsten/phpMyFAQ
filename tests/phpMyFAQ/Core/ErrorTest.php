<?php

namespace phpMyFAQ\Core;

use ErrorException;
use Exception;
use phpMyFAQ\Environment;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ErrorTest extends TestCase
{
    private int $originalErrorReporting;
    private string $originalLogErrors;
    private string $originalErrorLog;

    protected function setUp(): void
    {
        $this->originalErrorReporting = error_reporting();
        $this->originalLogErrors = ini_get('log_errors');
        $this->originalErrorLog = ini_get('error_log');

        error_reporting(E_ALL);
    }

    protected function tearDown(): void
    {
        error_reporting($this->originalErrorReporting);
        ini_set('log_errors', $this->originalLogErrors);
        ini_set('error_log', $this->originalErrorLog);
    }

    public function testErrorHandlerThrowsExceptionWhenErrorReportingIsEnabled(): void
    {
        $level = E_NOTICE;
        $message = 'Undefined variable: x';
        $filename = 'test.php';
        $line = 10;

        error_reporting(E_ALL);

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage($message);

        Error::errorHandler($level, $message, $filename, $line);
    }

    /**
     * @throws ErrorException
     */
    public function testErrorHandlerDoesNotThrowExceptionWhenErrorReportingIsDisabled(): void
    {
        $level = E_NOTICE;
        $message = 'Undefined variable: x';
        $filename = 'test.php';
        $line = 10;

        error_reporting(0);

        Error::errorHandler($level, $message, $filename, $line);

        $this->assertTrue(true);
    }

    public function testErrorHandlerUsesCorrectFilenameInDebugMode(): void
    {
        if (!method_exists(Environment::class, 'isDebugMode')) {
            $this->markTestSkipped('Environment::isDebugMode method not available');
        }

        $level = E_WARNING;
        $message = 'Test warning';
        $filename = '/path/to/test.php';
        $line = 15;

        try {
            Error::errorHandler($level, $message, $filename, $line);
            $this->fail('Expected ErrorException was not thrown');
        } catch (ErrorException $exception) {
            $expectedFilename = Environment::isDebugMode() ? $filename : basename($filename);
            $this->assertSame($expectedFilename, $exception->getFile());
            $this->assertSame($level, $exception->getSeverity());
            $this->assertSame($line, $exception->getLine());
            $this->assertSame(0, $exception->getCode());
        }
    }

    public function testErrorHandlerWithDifferentErrorLevels(): void
    {
        $errorLevelsThatThrow = [
            E_ERROR,
            E_WARNING,
            E_NOTICE,
            E_USER_ERROR,
            E_USER_WARNING,
            E_USER_NOTICE,
            E_RECOVERABLE_ERROR,
        ];

        foreach ($errorLevelsThatThrow as $level) {
            try {
                Error::errorHandler($level, 'Test message', 'test.php', 1);
                $this->fail("Expected ErrorException was not thrown for level: $level");
            } catch (ErrorException $exception) {
                $this->assertSame($level, $exception->getSeverity());
            }
        }

        // Deprecation warnings should not throw exceptions
        Error::errorHandler(E_DEPRECATED, 'Test deprecated', 'test.php', 1);
        Error::errorHandler(E_USER_DEPRECATED, 'Test user deprecated', 'test.php', 1);
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExceptionHandlerSets404ResponseCode(): void
    {
        $exception = new Exception('Not found', 404);

        $this->expectOutputRegex('/<h1>phpMyFAQ Fatal error<\/h1>/');

        Error::exceptionHandler($exception);

        $this->assertSame(404, http_response_code());
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExceptionHandlerSets500ResponseCodeForNon404Errors(): void
    {
        $exception = new Exception('Test error', 200);

        $this->expectOutputRegex('/phpMyFAQ Fatal error/');

        Error::exceptionHandler($exception);

        $this->assertSame(500, http_response_code());
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExceptionHandlerOutputContainsExpectedElements(): void
    {
        $exception = new Exception("Test message with <script>alert('xss')</script>", 500);

        ini_set('log_errors', '0');

        ob_start();
        Error::exceptionHandler($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('<h1>phpMyFAQ Fatal error</h1>', $output);
        $this->assertStringContainsString("Uncaught exception: 'Exception'", $output);
        $this->assertStringContainsString("Message: '", $output);
        $this->assertStringContainsString('Stack trace:', $output);
        $this->assertStringContainsString("Thrown in '", $output);
        $this->assertStringContainsString('on line ', $output);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExceptionHandlerWithCustomException(): void
    {
        $customException = new class('Custom error message', 123) extends Exception {
            // Custom exception class for testing
        };

        $this->expectOutputRegex('/Custom error message/');

        Error::exceptionHandler($customException);

        $this->assertSame(500, http_response_code());
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExceptionHandlerLogsErrorWhenLogErrorsIsEnabled(): void
    {
        ini_set('log_errors', '1');

        $logFile = tempnam(sys_get_temp_dir(), 'phpunit_error_log');
        ini_set('error_log', $logFile);

        $exception = new Exception('Test log message', 500);

        try {
            ob_start();
            Error::exceptionHandler($exception);
            ob_get_contents();
            ob_end_clean();

            $this->assertFileExists($logFile);
            $logContent = file_get_contents($logFile);
            $this->assertStringContainsString('phpMyFAQ Exception', $logContent);
            $this->assertStringContainsString('Test log message', $logContent);
            $this->assertStringContainsString('Stack trace:', $logContent);
        } finally {
            if (file_exists($logFile)) {
                unlink($logFile);
            }
        }
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExceptionHandlerDoesNotLogWhenLogErrorsIsDisabled(): void
    {
        ini_set('log_errors', '0');

        $logFile = tempnam(sys_get_temp_dir(), 'phpunit_error_log');
        ini_set('error_log', $logFile);

        file_put_contents($logFile, '');

        $exception = new Exception('Test message', 500);

        try {
            ob_start();
            Error::exceptionHandler($exception);
            ob_get_contents();
            ob_end_clean();

            $logContent = file_get_contents($logFile);
            $this->assertEmpty($logContent);
        } finally {
            if (file_exists($logFile)) {
                unlink($logFile);
            }
        }
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testExceptionHandlerWithErrorException(): void
    {
        $errorException = new ErrorException('Test error exception', 0, E_ERROR, 'test.php', 42);

        $this->expectOutputRegex('/Test error exception/');

        Error::exceptionHandler($errorException);

        $this->assertSame(500, http_response_code());
    }
}
