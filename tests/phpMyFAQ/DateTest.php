<?php

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class DateTest extends TestCase
{
    private Configuration $mockConfiguration;
    private Date $dateInstance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfiguration = $this->createStub(Configuration::class);
        $this->mockConfiguration
            ->method('get')
            ->with('main.dateFormat')
            ->willReturn('Y-m-d H:i:s');

        $this->dateInstance = new Date($this->mockConfiguration);
    }

    public function testCreateIsoDateWithPhpMyFaqFormat(): void
    {
        $pmfDate = '20231225143000'; // 2023-12-25 14:30:00
        $result = Date::createIsoDate($pmfDate);

        $this->assertEquals('2023-12-25 14:30', $result);
    }

    public function testCreateIsoDateWithCustomFormat(): void
    {
        $pmfDate = '20231225143000';
        $customFormat = 'd.m.Y H:i';
        $result = Date::createIsoDate($pmfDate, $customFormat);

        $this->assertEquals('25.12.2023 14:30', $result);
    }

    public function testCreateIsoDateWithUnixTimestamp(): void
    {
        $timestamp = '1703511000'; // 2023-12-25 14:30:00
        $result = Date::createIsoDate($timestamp, 'Y-m-d H:i', false);

        $this->assertIsString($result);
        $this->assertStringContainsString('2023-12-25', $result);
    }

    public function testCreateIsoDateWithDifferentFormats(): void
    {
        $pmfDate = '20240101120000'; // 2024-01-01 12:00:00

        $isoFormat = Date::createIsoDate($pmfDate, 'c'); // ISO 8601
        $this->assertStringContainsString('2024-01-01T12:00:00', $isoFormat);

        $shortFormat = Date::createIsoDate($pmfDate, 'Y-m-d');
        $this->assertEquals('2024-01-01', $shortFormat);

        $longFormat = Date::createIsoDate($pmfDate, 'l, F j, Y');
        $this->assertStringContainsString('Monday', $longFormat);
        $this->assertStringContainsString('January', $longFormat);
    }

    public function testFormatWithValidDate(): void
    {
        $inputDate = '2023-12-25 14:30:00';
        $result = $this->dateInstance->format($inputDate);

        $this->assertEquals('2023-12-25 14:30:00', $result);
    }

    public function testFormatWithDifferentDateFormats(): void
    {
        // Test with different configuration formats
        $mockConfig = $this->createStub(Configuration::class);
        $mockConfig->method('get')->with('main.dateFormat')->willReturn('d.m.Y H:i');

        $dateInstance = new Date($mockConfig);
        $inputDate = '2023-12-25 14:30:00';
        $result = $dateInstance->format($inputDate);

        $this->assertEquals('25.12.2023 14:30', $result);
    }

    public function testFormatWithInvalidDate(): void
    {
        // Mock logger to verify error logging - use Monolog\Logger instead of generic LoggerInterface
        $mockLogger = $this->createMock(\Monolog\Logger::class);
        $mockLogger->expects($this->once())->method('error')->with($this->isString());

        $this->mockConfiguration->method('getLogger')->willReturn($mockLogger);

        $invalidDate = 'not-a-date';
        $result = $this->dateInstance->format($invalidDate);

        $this->assertEquals('', $result);
    }

    public function testFormatWithEmptyDate(): void
    {
        // Empty string in DateTime constructor gets parsed as current date, not an error
        // So we don't expect the logger to be called for an empty string
        $emptyDate = '';
        $result = $this->dateInstance->format($emptyDate);

        // Empty string gets parsed as current date, so we expect a valid date string
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testFormatWithActualInvalidDate(): void
    {
        // Mock logger for error case - use Monolog\Logger
        $mockLogger = $this->createMock(\Monolog\Logger::class);
        $mockLogger->expects($this->once())->method('error');
        $this->mockConfiguration->method('getLogger')->willReturn($mockLogger);

        // Use a truly invalid date format that will cause DateTime to throw an exception
        $invalidDate = 'totally-invalid-date-format-that-will-fail';
        $result = $this->dateInstance->format($invalidDate);

        $this->assertEquals('', $result);
    }

    public function testCreateIsoDateEdgeCases(): void
    {
        // Test with minimal valid phpMyFAQ date
        $minimalDate = '20230101000000';
        $result = Date::createIsoDate($minimalDate);
        $this->assertEquals('2023-01-01 00:00', $result);

        // Test with maximum values
        $maxDate = '20231231235959';
        $result = Date::createIsoDate($maxDate);
        $this->assertEquals('2023-12-31 23:59', $result);
    }

    public function testDateInstanceIsReadonly(): void
    {
        // Test that Date class is readonly by checking if we can create it properly
        $this->assertInstanceOf(Date::class, $this->dateInstance);

        // Verify the configuration is properly set through constructor
        $reflection = new \ReflectionClass($this->dateInstance);
        $this->assertTrue($reflection->isReadOnly());
    }
}
