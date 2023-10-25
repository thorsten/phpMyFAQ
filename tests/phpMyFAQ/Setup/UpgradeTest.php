<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\DownloadHostType;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

class UpgradeTest extends TestCase
{
    private Upgrade $upgrade;
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $this->upgrade = new Upgrade(new System(), $configuration);
        $this->upgrade->setUpgradeDirectory(PMF_CONTENT_DIR . '/upgrades');
    }

    /**
     * @throws Exception
     */
    public function testDownloadPackage(): void
    {
        $actual = $this->upgrade->downloadPackage('3.1.15');
        $this->assertIsString($actual);

        $this->expectException('phpMyFAQ\Core\Exception');
        $this->upgrade->downloadPackage('1.2.3');
    }

    /**
     * @throws Exception
     */
    public function testCheckFilesystemValid(): void
    {
        touch(PMF_CONTENT_DIR . '/core/config/constants.php');

        $this->assertTrue($this->upgrade->checkFilesystem());

        unlink(PMF_CONTENT_DIR . '/core/config/constants.php');
    }

    /**
     * @throws Exception
     */
    public function testCheckFilesystemMissingConfigFiles(): void
    {
        $this->expectException('phpMyFAQ\Core\Exception');
        $this->expectExceptionMessage(
            'The files /content/core/config/constant.php and /content/core/config/database.php are missing.'
        );
        $this->upgrade->checkFilesystem();
    }

    public function testGetDownloadHostForNightly(): void
    {
        $this->upgrade->setIsNightly(true);

        $this->assertEquals(DownloadHostType::GITHUB->value, $this->upgrade->getDownloadHost());
    }

    public function testGetDownloadHostForNonNightly(): void
    {
        $this->upgrade->setIsNightly(false);

        $this->assertEquals(DownloadHostType::PHPMYFAQ->value, $this->upgrade->getDownloadHost());
    }

    public function testGetPathForNightly(): void
    {
        $this->upgrade->setIsNightly(true);

        $expectedPath = sprintf(Upgrade::GITHUB_PATH, date('Y-m-d', strtotime('-1 days')));
        $this->assertEquals($expectedPath, $this->upgrade->getPath());
    }

    public function testGetPathForNonNightly(): void
    {
        $this->upgrade->setIsNightly(false);

        $this->assertEquals('', $this->upgrade->getPath());
    }
}
