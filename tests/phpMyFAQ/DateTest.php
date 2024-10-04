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
        $expected = 1648764000;

        $result = $date->getTrackingFileDate($file);
        $this->assertEquals($expected, $result);

        $expected = 1648850399;

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
}
