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
            'Backup filename should contain 8-character hexadecimal hash',
        );

        // Cleanup
        unlink($files[0]);
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

    /**
     * PostgreSQL rejects "ALTER COLUMN ... SET TYPE"; the correct syntax is
     * "ALTER COLUMN ... TYPE". The invalid statement aborted the whole database
     * update for every PostgreSQL installation older than 4.0.5.
     */
    public function testApplyUpdates405GeneratesValidPostgresAlterColumnSyntax(): void
    {
        $queries = $this->collectQueriesForPostgres('applyUpdates405', '4.0.4');

        $alterStatements = array_values(
            array_filter($queries, static fn(string $query): bool => str_contains($query, 'input_label')),
        );

        $this->assertNotEmpty($alterStatements, 'The faqforms migration should emit statements.');
        $this->assertStringContainsString(
            'ALTER TABLE faqforms ALTER COLUMN input_label TYPE VARCHAR(500)',
            $alterStatements[0],
        );

        foreach ($queries as $query) {
            $this->assertStringNotContainsString(
                'SET TYPE',
                $query,
                'PostgreSQL does not support "ALTER COLUMN ... SET TYPE".',
            );
        }
    }

    /**
     * The sequence created for faqseo.id is prefixed, so every statement referring
     * back to it must use the same prefix.
     */
    public function testApplyUpdates409UsesTablePrefixForSeoSequence(): void
    {
        $queries = $this->collectQueriesForPostgres('applyUpdates409', '4.0.8', 'faq_');

        $this->assertContains('CREATE SEQUENCE faq_faqseo_id_seq', $queries);
        $this->assertContains(
            "ALTER TABLE faq_faqseo ALTER COLUMN id SET DEFAULT nextval('faq_faqseo_id_seq')",
            $queries,
        );
        $this->assertContains(
            "SELECT setval('faq_faqseo_id_seq', (SELECT MAX(id) FROM faq_faqseo));",
            $queries,
        );
    }

    /**
     * Invokes a version-specific update step against PostgreSQL and returns the
     * SQL it queued, without touching a real database.
     *
     * @return string[]
     */
    private function collectQueriesForPostgres(string $method, string $fromVersion, string $prefix = ''): array
    {
        $databaseType = new \ReflectionProperty(Database::class, 'dbType');
        $tablePrefix = new \ReflectionProperty(Database::class, 'tablePrefix');

        $hadType = $databaseType->isInitialized();
        $previousType = $hadType ? $databaseType->getValue() : null;
        $previousPrefix = $tablePrefix->getValue();

        $databaseType->setValue(null, 'pgsql');
        $tablePrefix->setValue(null, $prefix);

        try {
            $this->update->setVersion($fromVersion);

            $reflection = new \ReflectionClass($this->update);
            $reflection->getMethod($method)->invoke($this->update);

            return $reflection->getProperty('queries')->getValue($this->update);
        } finally {
            // Database keeps this state statically, so leave it exactly as found.
            if ($hadType) {
                $databaseType->setValue(null, $previousType);
            }

            $tablePrefix->setValue(null, $previousPrefix);
        }
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
