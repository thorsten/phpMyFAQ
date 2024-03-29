<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private Update $update;
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->configuration->set('main.currentVersion', '4.0.0');

        $this->update = new Update(new System(), Configuration::getConfigurationInstance());
    }

    public function testCreateConfigBackup(): void
    {
        $this->update->setVersion('4.0.0');
        $configPath = PMF_TEST_DIR . '/content/core/config';

        $this->update->createConfigBackup($configPath);

        $this->assertFileExists(
            PMF_TEST_DIR . '/content/core/config/phpmyfaq-config-backup.' . date('Y-m-d') . '.zip'
        );

        unlink(PMF_TEST_DIR . '/content/core/config/phpmyfaq-config-backup.' . date('Y-m-d') . '.zip');
    }

    public function testIsConfigTableNotAvailable(): void
    {
        $this->update->setVersion('4.0.0');
        $this->assertFalse($this->update->isConfigTableNotAvailable($this->dbHandle));
    }
}
