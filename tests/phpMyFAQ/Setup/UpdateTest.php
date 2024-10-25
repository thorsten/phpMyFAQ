<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private Update $update;
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($this->dbHandle);
        $configuration->set('main.currentVersion', '4.0.0');

        $this->update = new Update(new System(), Configuration::getConfigurationInstance());
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    public function testApplyUpdates(): void
    {
        $progressCallback = function ($query) {
            echo $query;
        };

        $this->update->setVersion('4.0.0');
        $result = $this->update->applyUpdates($progressCallback);

        $this->assertTrue($result);
    }

    public function testSetDryRun()
    {
        $this->update->setDryRun(true);
        $reflection = new \ReflectionClass($this->update);
        $property = $reflection->getProperty('dryRun');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue($this->update));

        $this->update->setDryRun(false);
        $this->assertFalse($property->getValue($this->update));
    }
}
