<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

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
     * @throws RandomException
     */
    public function testCreateConfigBackup(): void
    {
        $this->update->setVersion('4.0.0');
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
            'Backup filename should contain 8-character hexadecimal hash'
        );

        // Cleanup
        unlink($files[0]);
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
        $this->update->setVersion('4.0.0');
        $result = $this->update->applyUpdates();

        $this->assertTrue($result);
    }

    public function testApplyUpdatesWithDryRunForAlpha3(): void
    {
        $this->update->setVersion('4.0.0-alpha.2');
        $this->update->setDryRun(true);
        $this->update->applyUpdates();

        $result = $this->update->getDryRunQueries();

        $this->assertIsArray($result);
        $this->assertStringContainsString(
            "CREATE TABLE faqseo (
                            id INT NOT NULL,
                            type VARCHAR(32) NOT NULL,
                            reference_id INT NOT NULL,
                            reference_language VARCHAR(5) NOT NULL,
                            title TEXT NULL,
                            description TEXT NULL,
                            slug TEXT NULL,
                            created DATE NOT NULL DEFAULT (date('now')),
                            PRIMARY KEY (id))",
            $result[0]
        );
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
