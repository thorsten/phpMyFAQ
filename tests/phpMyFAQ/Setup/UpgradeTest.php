<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
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
    }

    public function testDownloadPackage(): void
    {
        $actual = $this->upgrade->downloadPackage('3.1.15');
        $this->assertIsString($actual);

        $actual = $this->upgrade->downloadPackage('1.2.3');
        $this->assertFalse($actual);
    }

    public function testVerifyPackage(): void
    {
        $this->markTestSkipped();
    }

    public function testCreateTemporaryBackup(): void
    {
        $this->markTestSkipped();
    }

    public function testUnpackPackage(): void
    {
        $this->markTestSkipped();
    }

    public function testRestoreTemporaryBackup(): void
    {
        $this->markTestSkipped();
    }

    public function testCheckFilesystem(): void
    {
        $this->markTestSkipped();
    }

    public function testInstallPackage(): void
    {
        $this->markTestSkipped();
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
