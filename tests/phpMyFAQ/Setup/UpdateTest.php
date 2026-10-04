<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Migration\MigrationTracker;
use phpMyFAQ\Setup\Migration\Versions\Migration420Beta;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;

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
        // The constructor only registers the very first instance of the process as the
        // singleton; install this one explicitly so the migrations run against this copy.
        $configurationProperty->setValue(null, $configuration);
        $configuration->set('main.currentVersion', '4.0.0');
        $configuration->getAll();

        $this->update = new Update(new System(), $configuration);
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

    public function testApplyUpdatesReRunsAmendedMigration(): void
    {
        // Simulate an installation that executed 4.2.0-alpha.2 before the migration
        // was amended: the recorded checksum no longer matches the registered one.
        // Uses the temporary database copy explicitly — the Configuration singleton
        // still points at the shared bootstrap database.
        $configuration = new Configuration($this->dbHandle);
        $tracker = new MigrationTracker($configuration);
        $tracker->ensureTableExists();
        $tracker->recordMigration('4.2.0-alpha.2', 1, 'outdated', 'pre-amendment state');

        $update = new Update(new System(), $configuration);
        $update->version = '4.2.0-alpha.2';
        $update->dryRun = true;
        $update->applyUpdates();

        $queries = array_filter($update->dryRunQueries, static fn(string $query): bool => str_contains(
            $query,
            'faqquestion_history',
        ));

        $this->assertNotEmpty($queries, 'Amended migration must be re-run for installations that already applied it');
    }

    public function testApplyUpdatesReRunsMigrationRecordedWithoutChecksum(): void
    {
        // Simulate an installation that executed 4.2.0-alpha.2 before checksums were
        // recorded at all: the tracker row exists with a NULL checksum. Such an
        // installation can never match the registered checksum, so it must be treated
        // as amended — skipping it silently misses every later amendment.
        $configuration = new Configuration($this->dbHandle);
        $tracker = new MigrationTracker($configuration);
        $tracker->ensureTableExists();
        $tracker->recordMigration('4.2.0-alpha.2', 1, null, 'pre-checksum state');

        $update = new Update(new System(), $configuration);
        $update->version = '4.2.0-alpha.2';
        $update->dryRun = true;
        $update->applyUpdates();

        $queries = array_filter($update->dryRunQueries, static fn(string $query): bool => str_contains(
            $query,
            'faqquestion_history',
        ));

        $this->assertNotEmpty(
            $queries,
            'A migration recorded without a checksum must be re-run so pre-checksum installations pick up amendments',
        );
    }

    public function testApplyUpdatesCreatesMissingTables(): void
    {
        // Simulate a fresh installation made at 4.2.0-alpha.2 before the migration was
        // amended: current version matches the code, no migration tracker rows exist,
        // and the faqquestion_history table is missing. Neither version-based nor
        // checksum-based selection can see this state — the schema convergence must.
        $this->dbHandle->query('DROP TABLE IF EXISTS faqquestion_history');
        $this->dbHandle->query('DROP TABLE IF EXISTS faqmigrations');

        $configuration = new Configuration($this->dbHandle);
        $configuration->set('main.currentVersion', '4.2.0-alpha.2');

        $update = new Update(new System(), $configuration);
        $update->version = '4.2.0-alpha.2';
        $this->assertTrue($update->applyUpdates());

        $result = $this->dbHandle->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='faqquestion_history'",
        );
        $this->assertSame(1, $this->dbHandle->numRows($result), 'Missing table must be created by the update');
    }

    public function testApplyUpdatesSkipsAppliedMigrationWithUnchangedChecksum(): void
    {
        $configuration = new Configuration($this->dbHandle);
        $migration = new Migration420Beta($configuration);
        $tracker = new MigrationTracker($configuration);
        $tracker->ensureTableExists();
        $tracker->recordMigration('4.2.0-beta', 1, $migration->getChecksum(), $migration->getDescription());

        $update = new Update(new System(), $configuration);
        $update->version = '4.2.0-beta';
        $update->dryRun = true;
        $update->applyUpdates();

        $this->assertSame([], $update->dryRunQueries);
    }

    public function testApplyUpdatesWithDryRunForAlpha3(): void
    {
        $this->update->version = '5.0.0-alpha.2';
        $this->update->dryRun = true;
        $this->update->applyUpdates();

        $result = $this->update->dryRunQueries;

        $this->assertIsArray($result);
    }

    public function testRewriteBaseUpdateIsAllowedDuringInstallation(): void
    {
        $databaseConfig = PMF_CONFIG_DIR . '/database.php';
        $backup = $databaseConfig . '.rewrite-test.bak';
        $hidden = false;
        if (is_file($databaseConfig)) {
            rename($databaseConfig, $backup);
            $hidden = true;
        }

        try {
            $this->assertTrue($this->update->isRewriteBaseUpdateAllowed());
        } finally {
            if ($hidden) {
                rename($backup, $databaseConfig);
            }
        }
    }

    public function testRewriteBaseUpdateIsRefusedForAnonymousRequestsAfterInstallation(): void
    {
        $databaseConfig = PMF_CONFIG_DIR . '/database.php';
        $created = false;
        if (!is_file($databaseConfig)) {
            file_put_contents($databaseConfig, "<?php\n");
            $created = true;
        }

        try {
            $this->assertFalse($this->update->isRewriteBaseUpdateAllowed());
        } finally {
            if ($created) {
                unlink($databaseConfig);
            }
        }
    }

    public function testCheckInitialRewriteBasePathDoesNotTouchHtaccessForAnonymousRequests(): void
    {
        $databaseConfig = PMF_CONFIG_DIR . '/database.php';
        $created = false;
        if (!is_file($databaseConfig)) {
            file_put_contents($databaseConfig, "<?php\n");
            $created = true;
        }

        $htaccess = PMF_ROOT_DIR . '/.htaccess';
        $before = file_get_contents($htaccess);
        $backupsBefore = glob($htaccess . '.backup-*') ?: [];

        try {
            $request = Request::create('/some/other/base/update');
            $this->assertTrue($this->update->checkInitialRewriteBasePath($request));
            $this->assertStringEqualsFile($htaccess, (string) $before);
            $this->assertSame($backupsBefore, glob($htaccess . '.backup-*') ?: []);
        } finally {
            if ($created) {
                unlink($databaseConfig);
            }
        }
    }

    public function testDryRunResultsListThePendingMigrationsWithoutApplyingThem(): void
    {
        $this->update->version = '4.0.0';
        $this->update->dryRun = true;

        $report = $this->update->getDryRunResults();

        $this->assertArrayHasKey('migrations', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertNotEmpty($report['migrations']);
        $this->assertGreaterThan(0, $report['summary']['migrationCount']);

        $formatted = $this->update->getFormattedDryRunReport();
        $this->assertStringContainsString((string) array_key_first($report['migrations']), $formatted);

        $reloaded = new Configuration($this->dbHandle);
        $this->assertSame('4.0.0', $reloaded->get('main.currentVersion'));
    }

    public function testExecuteQueriesReportsTheFailingStatement(): void
    {
        new \ReflectionProperty(Update::class, 'queries')->setValue($this->update, [
            'UPDATE faqconfig SET config_value = config_value WHERE config_name = \'main.language\'',
            'UPDATE no_such_table SET x = 1',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('(Query: UPDATE no_such_table SET x = 1)');

        new \ReflectionMethod(Update::class, 'executeQueries')->invoke($this->update);
    }

    public function testExecuteQueriesOnlyCollectsTheStatementsInDryRunMode(): void
    {
        $this->update->dryRun = true;
        new \ReflectionProperty(Update::class, 'queries')->setValue($this->update, ['UPDATE no_such_table SET x = 1']);

        new \ReflectionMethod(Update::class, 'executeQueries')->invoke($this->update);

        $this->assertSame(['UPDATE no_such_table SET x = 1'], $this->update->dryRunQueries);
    }

    /**
     * @return array<int, string>
     */
    private function queuedQueries(): array
    {
        /** @var array<int, string> $queries */
        $queries = new \ReflectionProperty(Update::class, 'queries')->getValue($this->update);

        return $queries;
    }

    public function testOptimizeTablesQueuesTheDialectSpecificStatements(): void
    {
        $dbTypeProperty = new \ReflectionProperty(Database::class, 'dbType');

        try {
            $dbTypeProperty->setValue(null, 'pgsql');
            $this->update->optimizeTables();
            $this->assertSame(['VACUUM ANALYZE;'], $this->queuedQueries());

            $dbTypeProperty->setValue(null, 'mysqli');
            $this->update->optimizeTables();
            $queries = $this->queuedQueries();
            $this->assertContains('OPTIMIZE TABLE faqconfig', $queries);
            $this->assertContains('OPTIMIZE TABLE faqdata', $queries);
            $this->assertGreaterThan(20, count($queries));

            $before = count($queries);
            $dbTypeProperty->setValue(null, 'sqlite3');
            $this->update->optimizeTables();
            $this->assertCount($before, $this->queuedQueries(), 'SQLite needs no table optimisation.');
        } finally {
            $dbTypeProperty->setValue(null, 'sqlite3');
        }
    }

    public function testInsertFormInputsReplacesTheDefaultFormDefinitions(): void
    {
        new \ReflectionMethod(Update::class, 'insertFormInputs')->invoke($this->update);

        $queries = $this->queuedQueries();
        $this->assertSame('DELETE FROM faqforms', $queries[0]);
        $inserts = array_filter($queries, static fn(string $query): bool => str_starts_with($query, 'INSERT INTO faqforms'));
        $this->assertCount(count(new \phpMyFAQ\Setup\Installation\DefaultDataSeeder()->getFormInputs()), $inserts);
    }

    public function testMigrateAdminLogHashesChainsEntriesThatHaveNoHashYet(): void
    {
        $this->dbHandle->query('DELETE FROM faqadminlog');
        $this->dbHandle->query("INSERT INTO faqadminlog (id, time, usr, ip, text) VALUES (1, 1700000000, 1, '127.0.0.1', 'first')");
        $this->dbHandle->query("INSERT INTO faqadminlog (id, time, usr, ip, text) VALUES (2, 1700000001, 1, '127.0.0.1', 'second')");
        $this->dbHandle->query("INSERT INTO faqadminlog (id, time, usr, ip, text) VALUES (3, 1700000002, 1, '127.0.0.1', 'third')");

        $this->update->version = '4.1.9';
        new \ReflectionMethod(Update::class, 'migrateAdminLogHashes')->invoke($this->update);

        $entries = array_values(new \phpMyFAQ\Administration\AdminLogRepository(new Configuration($this->dbHandle))->getAll());
        $this->assertCount(3, $entries);
        $this->assertNull($entries[0]->getPreviousHash());
        $this->assertNotNull($entries[0]->getHash());
        $this->assertSame($entries[0]->getHash(), $entries[1]->getPreviousHash());
        $this->assertSame($entries[1]->getHash(), $entries[2]->getPreviousHash());
        foreach ($entries as $entry) {
            $this->assertTrue($entry->verifyIntegrity());
        }
    }

    public function testMigrateAdminLogHashesLeavesInstallationsFrom420Alone(): void
    {
        $this->dbHandle->query('DELETE FROM faqadminlog');
        $this->dbHandle->query("INSERT INTO faqadminlog (id, time, usr, ip, text) VALUES (1, 1700000000, 1, '127.0.0.1', 'first')");

        $this->update->version = '4.2.0-alpha';
        new \ReflectionMethod(Update::class, 'migrateAdminLogHashes')->invoke($this->update);

        $entries = array_values(new \phpMyFAQ\Administration\AdminLogRepository(new Configuration($this->dbHandle))->getAll());
        $this->assertNull($entries[0]->getHash());
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
