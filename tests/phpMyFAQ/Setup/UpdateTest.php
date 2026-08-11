<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use phpMyFAQ\User;
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

        $pathToBackup = $this->update->createConfigBackup($configPath);

        // The archive contains the database credentials, so the caller must not get a
        // URL that could be handed out as a download link
        $this->assertSame($configPath, dirname($pathToBackup), 'Backup path should be a file system path');
        $this->assertStringStartsNotWith('http', $pathToBackup);

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

        $this->assertContains('CREATE SEQUENCE IF NOT EXISTS faq_faqseo_id_seq', $queries);
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
     * A failed update attempt leaves the database half-migrated, because every
     * statement is auto-committed but the version number is only written at the
     * very end. A re-run then starts over from the old version, so all PostgreSQL
     * DDL must be guarded with IF (NOT) EXISTS to pass statements that were
     * already applied by the previous attempt.
     */
    public function testPostgresUpdateQueriesAreIdempotentForReRuns(): void
    {
        // The update steps also write configuration items; a mocked configuration
        // keeps them away from the shared test database, so only the queued SQL
        // is inspected.
        $update = new Update(new System(), $this->createMock(Configuration::class));

        $methods = [
            'applyUpdates310Alpha',
            'applyUpdates310Beta',
            'applyUpdates320Alpha',
            'applyUpdates320Beta',
            'applyUpdates400Beta2',
            'applyUpdates409',
        ];

        $queries = [];
        foreach ($methods as $method) {
            $queries = $this->collectQueriesForPostgres($method, '3.0.11', '', $update);
        }

        $this->assertNotEmpty($queries);

        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*CREATE TABLE (?!IF NOT EXISTS)/i',
                $query,
                'CREATE TABLE must be guarded with IF NOT EXISTS: ' . $query,
            );
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*CREATE SEQUENCE (?!IF NOT EXISTS)/i',
                $query,
                'CREATE SEQUENCE must be guarded with IF NOT EXISTS: ' . $query,
            );
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*DROP TABLE (?!IF EXISTS)/i',
                $query,
                'DROP TABLE must be guarded with IF EXISTS: ' . $query,
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\bADD (?!COLUMN IF NOT EXISTS)/i',
                $query,
                'ADD COLUMN must be guarded with IF NOT EXISTS: ' . $query,
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\bDROP COLUMN (?!IF EXISTS)/i',
                $query,
                'DROP COLUMN must be guarded with IF EXISTS: ' . $query,
            );
        }
    }

    /**
     * Invokes a version-specific update step against PostgreSQL and returns the
     * SQL it queued, without touching a real database.
     *
     * @return string[]
     */
    private function collectQueriesForPostgres(
        string $method,
        string $fromVersion,
        string $prefix = '',
        ?Update $update = null,
    ): array {
        $update ??= $this->update;

        $databaseType = new \ReflectionProperty(Database::class, 'dbType');
        $tablePrefix = new \ReflectionProperty(Database::class, 'tablePrefix');

        $hadType = $databaseType->isInitialized();
        $previousType = $hadType ? $databaseType->getValue() : null;
        $previousPrefix = $tablePrefix->getValue();

        $databaseType->setValue(null, 'pgsql');
        $tablePrefix->setValue(null, $prefix);

        try {
            $update->setVersion($fromVersion);

            $reflection = new \ReflectionClass($update);
            $reflection->getMethod($method)->invoke($update);

            return $reflection->getProperty('queries')->getValue($update);
        } finally {
            // Database keeps this state statically, so leave it exactly as found.
            if ($hadType) {
                $databaseType->setValue(null, $previousType);
            }

            $tablePrefix->setValue(null, $previousPrefix);
        }
    }

    /**
     * PermissionType::FAQ_ADD was renamed from 'addfaq' to 'add_faq' in v4.0.15
     * without a database migration, so upgraded installations kept the legacy
     * row name and silently lost the "Add new FAQ" entry in the admin menu.
     */
    public function testApplyUpdates418RenamesLegacyAddFaqRight(): void
    {
        $permission = (new User(Configuration::getConfigurationInstance()))->perm;

        // Simulate a database that was installed before v4.0.15
        $this->assertTrue($permission->renameRight('add_faq', 'addfaq'));

        $this->update->setVersion('4.1.7');
        (new \ReflectionClass($this->update))->getMethod('applyUpdates418')->invoke($this->update);

        $this->assertSame(0, $permission->getRightId('addfaq'));
        $this->assertGreaterThan(0, $permission->getRightId('add_faq'));
    }

    public function testApplyUpdates418KeepsAlreadyMigratedRightUntouched(): void
    {
        $permission = (new User(Configuration::getConfigurationInstance()))->perm;
        $rightId = $permission->getRightId('add_faq');
        $this->assertGreaterThan(0, $rightId);

        $this->update->setVersion('4.1.7');
        (new \ReflectionClass($this->update))->getMethod('applyUpdates418')->invoke($this->update);

        $this->assertSame($rightId, $permission->getRightId('add_faq'));
    }

    /**
     * Later update steps write configuration values longer than 255 characters
     * (e.g. seo.contentRobotsText) directly to the database, so the widening of
     * faqconfig.config_value to TEXT must be executed immediately instead of
     * being queued for the final batch — otherwise updating from 3.1.x fails
     * with "Data too long for column 'config_value'".
     */
    public function testApplyUpdates320BetaWidensConfigValueColumnImmediately(): void
    {
        $update = new Update(new System(), $this->createMock(Configuration::class));
        $update->setDryRun(true);

        $queuedQueries = $this->collectQueriesForPostgres('applyUpdates320Beta', '3.1.0', '', $update);

        $this->assertContains(
            'ALTER TABLE faqconfig ALTER COLUMN config_value TYPE TEXT',
            $update->getDryRunQueries(),
            'The config_value widening must be executed immediately, not queued.',
        );

        foreach ($queuedQueries as $queuedQuery) {
            $this->assertStringNotContainsString(
                'config_value',
                $queuedQuery,
                'The config_value widening must not wait for the final query batch.',
            );
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
