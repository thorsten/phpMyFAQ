<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class UpdateTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private Update $update;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-setup-update-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);
        $configuration = new Configuration($this->dbHandle);
        $configuration->set('main.currentVersion', '4.0.0');
        $configuration->getAll();

        $this->update = new Update(new System(), Configuration::getConfigurationInstance());
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
        }

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testCreateConfigBackup(): void
    {
        $this->update->version = '4.0.0';
        $configPath = PMF_TEST_DIR . '/content/core/config';

        // Clean up any existing backup files before test
        $existingFiles = glob($configPath . '/phpmyfaq-config-backup.*.zip');
        foreach ($existingFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->update->createConfigBackup($configPath);

        // Find a backup file with a pattern: phpmyfaq-config-backup.YYYY-MM-DD.XXXXXXXX.zip
        $pattern = PMF_TEST_DIR . '/content/core/config/phpmyfaq-config-backup.' . date(format: 'Y-m-d') . '.*.zip';
        $files = glob($pattern);

        $this->assertNotEmpty($files, 'Backup file should exist with random hash');
        $this->assertCount(1, $files, 'Exactly one backup file should exist');

        // Verify filename format: date.hash.zip where hash is 8 hex characters
        $filename = basename($files[0]);
        $this->assertMatchesRegularExpression(
            '/^phpmyfaq-config-backup\.\d{4}-\d{2}-\d{2}\.[0-9a-f]{8}\.zip$/',
            $filename,
            'Backup filename should contain 8-character hexadecimal hash',
        );

        // Cleanup
        unlink($files[0]);
    }

    public function testIsConfigTableNotAvailable(): void
    {
        $this->update->version = '5.0.0';
        $this->assertFalse($this->update->isConfigTableNotAvailable($this->dbHandle));
    }

    /**
     * @throws Exception
     */
    public function testApplyUpdates(): void
    {
        $this->update->version = '5.0.0';
        $this->update->dryRun = true; // Use dry-run to avoid writing to read-only test database
        $result = $this->update->applyUpdates();

        $this->assertTrue($result);
    }

    public function testApplyUpdatesWithDryRunForAlpha3(): void
    {
        $this->update->version = '5.0.0-alpha.2';
        $this->update->dryRun = true;
        $this->update->applyUpdates();

        $result = $this->update->dryRunQueries;

        $this->assertIsArray($result);
    }

    public function testSetDryRun(): void
    {
        $this->update->dryRun = true;
        $reflection = new \ReflectionClass($this->update);
        $property = $reflection->getProperty('dryRun');
        $this->assertTrue($property->getValue($this->update));

        $this->update->dryRun = false;
        $this->assertFalse($property->getValue($this->update));
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }
}
