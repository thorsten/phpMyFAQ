<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

#[CoversClass(Migration420Beta3::class)]
#[UsesNamespace('phpMyFAQ')]
class Migration420Beta3Test extends TestCase
{
    private Migration420Beta3 $migration;

    private Configuration $configuration;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        Database::factory('pdo_sqlite');
        Database::setTablePrefix('');

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-migration-420-beta-3-');
        self::assertNotFalse($this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->migration = new Migration420Beta3($this->configuration);
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
        $this->assertSame('4.2.0-beta.3', $this->migration->getVersion());
    }

    public function testDependsOnTheBetaMigration(): void
    {
        $this->assertSame(['4.2.0-beta'], $this->migration->getDependencies());
    }

    public function testUpRecordsColumnIndexAndConfigurationWhenMissing(): void
    {
        $this->configuration->getDb()->query('CREATE TABLE faquserdata (user_id INTEGER, keycloak_sub VARCHAR(255))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame(
            [
                'ALTER TABLE faquserdata ADD COLUMN oidc_sub VARCHAR(255) NULL',
                'CREATE UNIQUE INDEX IF NOT EXISTS idx_faquserdata_oidc_sub ON faquserdata (oidc_sub)',
                'ALTER TABLE faquserdata ADD COLUMN oidc_iss VARCHAR(255) NULL',
            ],
            $recorder->getSqlQueries(),
        );

        $configOperations = $recorder->getOperationsByType('config_add');
        $recorded = [];
        foreach ($configOperations as $operation) {
            $recorded[$operation->getKey()] = $operation->getValue();
        }

        $this->assertSame(
            [
                'oidc.enable' => 'false',
                'oidc.providerName' => 'OpenID Connect',
                'oidc.discoveryUrl' => '',
                'oidc.clientId' => '',
                'oidc.clientSecret' => '',
                'oidc.redirectUri' => '',
                'oidc.scopes' => 'openid profile email',
                'oidc.autoProvision' => 'true',
                'oidc.groupsClaim' => 'groups',
                'oidc.groupAutoAssign' => 'false',
                'oidc.groupSyncOnLogin' => 'false',
                'oidc.groupMapping' => '',
                'oidc.logoutRedirectUrl' => '',
            ],
            $recorded,
        );
    }

    public function testUpSkipsTheColumnsWhenTheyAlreadyExist(): void
    {
        $this->configuration
            ->getDb()
            ->query('CREATE TABLE faquserdata (user_id INTEGER, oidc_sub VARCHAR(255), oidc_iss VARCHAR(255))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame([], $recorder->getSqlQueries());
        $this->assertCount(13, $recorder->getOperationsByType('config_add'));
    }

    public function testUpOnlyAddsTheIssuerColumnWhenTheSubjectColumnExists(): void
    {
        $this->configuration->getDb()->query('CREATE TABLE faquserdata (user_id INTEGER, oidc_sub VARCHAR(255))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame(
            ['ALTER TABLE faquserdata ADD COLUMN oidc_iss VARCHAR(255) NULL'],
            $recorder->getSqlQueries(),
        );
    }

    public function testRecordedQueriesApplyToSqlite(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE faquserdata (user_id INTEGER, keycloak_sub VARCHAR(255))');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        foreach ($recorder->getSqlQueries() as $query) {
            $this->assertNotFalse($db->query($query), $query);
        }

        $this->assertNotFalse($db->query('SELECT oidc_sub, oidc_iss FROM faquserdata'));
    }
}
