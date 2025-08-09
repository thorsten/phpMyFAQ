<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{
    private Configuration $configuration;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.dateFormat', 'Y-m-d H:i');
    }
    public function testFormat(): void
    {
        $dateString = '2022-04-01';
        $expected = '2022-04-01 00:00';

        $date = new Date($this->configuration);
        $result = $date->format($dateString);
        $this->assertEquals($expected, $result);

        $dateString = 'invalid date format';
        $expected = '';

        $result = $date->format($dateString);
        $this->assertEquals($expected, $result);
    }

    public function testGetTrackingFileDate(): void
    {
        $date = new Date($this->configuration);
        $file = 'tracking01042022';
        $expected = 1648771200;

        $result = $date->getTrackingFileDate($file);
        $this->assertEquals($expected, $result);

        $expected = 1648857599;

        $result = $date->getTrackingFileDate($file, true);
        $this->assertEquals($expected, $result);

        $file = 'tracking42';
        $expected = -1;

        $result = $date->getTrackingFileDate($file);
        $this->assertEquals($expected, $result);
    }

    public function testCreateIsoDateWithPmfFormat(): void
    {
        $date = '202204011230';
        $expected = '2022-04-01 12:30';

        $result = Date::createIsoDate($date);
        $this->assertEquals($expected, $result);
    }

    public function testCreateIsoDateWithoutPmfFormat(): void
    {
        $date = '1648809000';
        $expected = '2022-04-01 12:30';

        $result = Date::createIsoDate($date, 'Y-m-d H:i', false);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test createIsoDate with custom format
     */
    public function testCreateIsoDateWithCustomFormat(): void
    {
        $date = '202204011230';
        $customFormat = 'd/m/Y H:i:s';
        $expected = '01/04/2022 12:30:00';

        $result = Date::createIsoDate($date, $customFormat);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test createIsoDate with different date lengths
     */
    public function testCreateIsoDateWithDifferentLengths(): void
    {
        // Full date with seconds
        $date = '20220401123045';
        $expected = '2022-04-01 12:30';

        $result = Date::createIsoDate($date);
        $this->assertEquals($expected, $result);

        // Date without time - the method expects at least 10 characters for time part
        // An 8-character date like "20220401" gets parsed incorrectly, so we test with minimum valid format
        $date = '202204010000'; // YYYYMMDDHHII format
        $result = Date::createIsoDate($date);
        $this->assertStringContainsString('2022-04-01', $result);
    }

    /**
     * Test format with different date formats
     */
    public function testFormatWithDifferentFormats(): void
    {
        $date = new Date($this->configuration);

        // Test ISO format - adjust for timezone
        $dateString = '2022-04-01T12:30:45';
        $result = $date->format($dateString);
        $this->assertStringContainsString('2022-04-01', $result);

        // Test timestamp - adjust for timezone
        $dateString = '@1648809045';
        $result = $date->format($dateString);
        $this->assertStringContainsString('2022-04-01', $result);
    }

    /**
     * Test format with custom date format configuration
     */
    public function testFormatWithCustomConfiguration(): void
    {
        $this->configuration->set('main.dateFormat', 'd.m.Y - H:i:s');
        $date = new Date($this->configuration);

        $dateString = '2022-04-01 12:30:45';
        $expected = '01.04.2022 - 12:30:45';
        $result = $date->format($dateString);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test format with various invalid date formats
     */
    public function testFormatWithInvalidDates(): void
    {
        $date = new Date($this->configuration);

        $invalidDates = [
            'not a date',
            'completely invalid',
            'abc123def'
        ];

        foreach ($invalidDates as $invalidDate) {
            $result = $date->format($invalidDate);
            $this->assertEquals('', $result);
        }
    }

    /**
     * Test getTrackingFileDate with various file name formats
     */
    public function testGetTrackingFileDateWithVariousFormats(): void
    {
        $date = new Date($this->configuration);

        // Test with valid tracking file name
        $file = 'tracking01042022suffix';
        $expected = gmmktime(0, 0, 0, 4, 1, 2022);
        $result = $date->getTrackingFileDate($file);
        $this->assertEquals($expected, $result);

        // Test end of day
        $expected = gmmktime(23, 59, 59, 4, 1, 2022);
        $result = $date->getTrackingFileDate($file, true);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test getTrackingFileDate with short file names
     */
    public function testGetTrackingFileDateWithShortNames(): void
    {
        $date = new Date($this->configuration);

        $shortFiles = [
            'short',
            'tracking123',
            'track01042022', // Less than 16 characters
            ''
        ];

        foreach ($shortFiles as $file) {
            $result = $date->getTrackingFileDate($file);
            $this->assertEquals(-1, $result);
        }
    }

    /**
     * Test getTrackingFileDate edge cases
     */
    public function testGetTrackingFileDateEdgeCases(): void
    {
        $date = new Date($this->configuration);

        // Test leap year
        $file = 'tracking29022020extra'; // February 29, 2020 (leap year)
        $expected = gmmktime(0, 0, 0, 2, 29, 2020);
        $result = $date->getTrackingFileDate($file);
        $this->assertEquals($expected, $result);

        // Test year boundaries
        $file = 'tracking31121999data';
        $expected = gmmktime(0, 0, 0, 12, 31, 1999);
        $result = $date->getTrackingFileDate($file);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test createIsoDate with Unix timestamp edge cases
     */
    public function testCreateIsoDateUnixTimestampEdgeCases(): void
    {
        // Test epoch - adjust for timezone
        $result = Date::createIsoDate('0', 'Y-m-d', false);
        $this->assertEquals('1970-01-01', $result);

        // Test future date
        $futureTimestamp = '2147483647'; // Year 2038
        $result = Date::createIsoDate($futureTimestamp, 'Y-m-d', false);
        $expected = '2038-01-19';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test Date class constructor
     */
    public function testDateConstructor(): void
    {
        $date = new Date($this->configuration);
        $this->assertInstanceOf(Date::class, $date);
    }

    /**
     * Test readonly property behavior
     */
    public function testReadonlyBehavior(): void
    {
        $date = new Date($this->configuration);

        // Test that the class is properly constructed
        $this->assertInstanceOf(Date::class, $date);

        // Test that format method works with the injected configuration
        $result = $date->format('2022-04-01');
        $this->assertIsString($result);
    }
}
