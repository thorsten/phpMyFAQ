<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
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
}
