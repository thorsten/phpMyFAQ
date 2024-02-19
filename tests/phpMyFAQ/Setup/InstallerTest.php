<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

class InstallerTest extends TestCase
{
    private Installer $installer;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->installer = new Installer(new System());
    }

    public function testCheckMinimumPhpVersion(): void
    {
        $this->assertTrue($this->installer->checkMinimumPhpVersion());
    }

    /**
     * @throws Exception
     */
    public function testAdjustRewriteBaseHtaccess(): void
    {
        $this->installer->adjustRewriteBaseHtaccess(PMF_TEST_DIR);
        $this->assertFileExists(PMF_TEST_DIR . '/.htaccess');
        $this->assertStringContainsString('RewriteBase /', file_get_contents(PMF_TEST_DIR . '/.htaccess'));
    }
}
