<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
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
        $configuration->getAll();

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
            PMF_TEST_DIR . '/content/core/config/phpmyfaq-config-backup.' . date(format: 'Y-m-d') . '.zip'
        );

        unlink(PMF_TEST_DIR . '/content/core/config/phpmyfaq-config-backup.' . date(format: 'Y-m-d') . '.zip');
    }

    public function testIsConfigTableNotAvailable(): void
    {
        $this->update->setVersion('5.0.0');
        $this->assertFalse($this->update->isConfigTableNotAvailable($this->dbHandle));
    }

    /**
     * @throws Exception
     */
    public function testApplyUpdates(): void
    {
        $this->update->setVersion('5.0.0');
        $result = $this->update->applyUpdates();

        $this->assertTrue($result);
    }

    public function testApplyUpdatesWithDryRunForAlpha3(): void
    {
        $this->update->setVersion('5.0.0-alpha.2');
        $this->update->setDryRun(true);
        $this->update->applyUpdates();

        $result = $this->update->getDryRunQueries();

        $this->assertIsArray($result);
    }

    public function testSetDryRun()
    {
        $this->update->setDryRun(true);
        $reflection = new \ReflectionClass($this->update);
        $property = $reflection->getProperty('dryRun');
        $this->assertTrue($property->getValue($this->update));

        $this->update->setDryRun(false);
        $this->assertFalse($property->getValue($this->update));
    }
}
