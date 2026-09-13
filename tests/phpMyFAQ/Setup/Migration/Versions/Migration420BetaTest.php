<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\TestCase;

class Migration420BetaTest extends TestCase
{
    private Migration420Beta $migration;

    private Configuration $configuration;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        Database::factory('pdo_sqlite');
        Database::setTablePrefix('');

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-migration-420-beta-');
        self::assertNotFalse($this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->migration = new Migration420Beta($this->configuration);
    }

    protected function tearDown(): void
    {
        $this->configuration->getDb()->close();

        parent::tearDown();

        @unlink($this->databaseFile);
    }

    public function testImplementsMigrationInterface(): void
    {
        $this->assertInstanceOf(MigrationInterface::class, $this->migration);
    }

    public function testGetVersion(): void
    {
        $this->assertSame('4.2.0-beta', $this->migration->getVersion());
    }

    public function testDependsOnTheAlpha2Migration(): void
    {
        $this->assertSame(['4.2.0-alpha.2'], $this->migration->getDependencies());
    }

    public function testGetDescriptionMentionsEverySchemaChange(): void
    {
        $description = $this->migration->getDescription();

        $this->assertStringContainsString('remember-me', $description);
        $this->assertStringContainsString('Entra ID', $description);
        $this->assertStringContainsString('TOTP', $description);
        $this->assertStringContainsString('upgrade.allowUnverifiedNightly', $description);
    }

    public function testUpRecordsTheNightlyVerificationConfiguration(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faquser (user_id INTEGER, remember_me_expires INTEGER)');
        $db->query('CREATE TABLE faquserdata (user_id INTEGER, entra_oid VARCHAR(255), twofactor_last_slice INTEGER)');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $operations = $recorder->getOperationsByType('config_add');

        $this->assertCount(1, $operations);
        $this->assertSame('upgrade.allowUnverifiedNightly', $operations[0]->getKey());
        $this->assertSame('false', $operations[0]->getValue());
    }

    public function testUpRecordsAllColumnsAndTheUniqueIndexWhenMissing(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faquser (user_id INTEGER, remember_me VARCHAR(150))');
        $db->query('CREATE TABLE faquserdata (user_id INTEGER, keycloak_sub VARCHAR(255), secret VARCHAR(128))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $queries = $recorder->getSqlQueries();

        $this->assertSame(
            [
                'ALTER TABLE faquser ADD COLUMN remember_me_expires INTEGER NULL',
                'ALTER TABLE faquserdata ADD COLUMN entra_oid VARCHAR(255) NULL',
                'CREATE UNIQUE INDEX IF NOT EXISTS idx_faquserdata_entra_oid ON faquserdata (entra_oid)',
                'ALTER TABLE faquserdata ADD COLUMN twofactor_last_slice INTEGER NULL',
            ],
            $queries,
        );
    }

    public function testUpIsANoOpWhenEveryColumnAlreadyExists(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faquser (user_id INTEGER, remember_me VARCHAR(150), remember_me_expires INTEGER)');
        $db->query('CREATE TABLE faquserdata (user_id INTEGER, entra_oid VARCHAR(255), twofactor_last_slice INTEGER)');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }

    public function testUpOnlyRecordsTheMissingColumns(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faquser (user_id INTEGER, remember_me_expires INTEGER)');
        $db->query('CREATE TABLE faquserdata (user_id INTEGER, entra_oid VARCHAR(255))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame(
            ['ALTER TABLE faquserdata ADD COLUMN twofactor_last_slice INTEGER NULL'],
            $recorder->getSqlQueries(),
        );
    }

    public function testRecordedQueriesApplyToSqlite(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faquser (user_id INTEGER, remember_me VARCHAR(150))');
        $db->query('CREATE TABLE faquserdata (user_id INTEGER, keycloak_sub VARCHAR(255), secret VARCHAR(128))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        foreach ($recorder->getSqlQueries() as $query) {
            $this->assertNotFalse($db->query($query), $query);
        }

        $db->query("INSERT INTO faquserdata (user_id, entra_oid) VALUES (1, 'oid-1')");
        $this->assertFalse(
            $db->query("INSERT INTO faquserdata (user_id, entra_oid) VALUES (2, 'oid-1')"),
            'The Entra ID object identifier must be unique per account',
        );
        $this->assertNotFalse(
            $db->query('INSERT INTO faquserdata (user_id, entra_oid) VALUES (3, NULL)'),
            'Accounts without an Entra ID link must not collide on the unique index',
        );
    }
}
