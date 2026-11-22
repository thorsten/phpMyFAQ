<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Setup\Installation\DefaultDataSeeder;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\ConfigAddOperation;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[AllowMockObjectsWithoutExpectations]
class Migration420RC3Test extends TestCase
{
    private Migration420RC3 $migration;

    private Configuration $configuration;

    private string $databaseFile;

    protected function setUp(): void
    {
        parent::setUp();

        Database::factory('pdo_sqlite');
        Database::setTablePrefix('');

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'pmf-migration-420-rc3-');
        self::assertNotFalse($this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        // The schema as a 4.2 installer on SQLite left it: the indexes that have to be unique
        // are plain ones, the Keycloak, Entra ID and admin log indexes do not exist.
        $dbHandle->query('CREATE TABLE faqconfig (config_name VARCHAR(255) PRIMARY KEY, config_value TEXT)');
        $dbHandle->query('CREATE TABLE faqadminlog (id INTEGER PRIMARY KEY, hash VARCHAR(64))');
        $dbHandle->query('CREATE TABLE faqapi_keys (id INTEGER PRIMARY KEY, api_key VARCHAR(64) NOT NULL)');
        $dbHandle->query('CREATE INDEX idx_api_key_unique ON faqapi_keys (api_key)');
        $dbHandle->query(
            'CREATE TABLE faqpush_subscriptions (id INTEGER PRIMARY KEY, endpoint_hash VARCHAR(64) NOT NULL)',
        );
        $dbHandle->query('CREATE INDEX idx_push_endpoint_hash ON faqpush_subscriptions (endpoint_hash)');
        $dbHandle->query(
            'CREATE TABLE faquserdata (user_id INTEGER, keycloak_sub VARCHAR(255), entra_oid VARCHAR(255), '
            . 'oidc_sub VARCHAR(255), oidc_iss VARCHAR(255))',
        );
        $dbHandle->query('CREATE INDEX idx_faquserdata_oidc_identity ON faquserdata (oidc_iss, oidc_sub)');

        $this->configuration = new Configuration($dbHandle);
        $this->migration = new Migration420RC3($this->configuration);
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
        $this->assertSame('4.2.0-RC.3', $this->migration->getVersion());
    }

    public function testDependsOnTheRC2Migration(): void
    {
        $this->assertSame(['4.2.0-RC.2'], $this->migration->getDependencies());
    }

    public function testGetDescriptionNamesTheDecoySecret(): void
    {
        $this->assertStringContainsString('security.webAuthnDecoySecret', $this->migration->getDescription());
    }

    public function testUpAddsARandomDecoySecret(): void
    {
        $first = $this->recordedDecoySecrets();
        $second = $this->recordedDecoySecrets();

        $this->assertCount(1, $first);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $first[0]);
        $this->assertNotSame($first[0], $second[0], 'Every installation needs a secret of its own.');
    }

    /**
     * @return list<string>
     */
    private function recordedDecoySecrets(): array
    {
        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $secrets = [];
        foreach ($recorder->getOperations() as $operation) {
            if ($operation instanceof ConfigAddOperation && $operation->getKey() === 'security.webAuthnDecoySecret') {
                $secrets[] = (string) $operation->getValue();
            }
        }

        return $secrets;
    }

    public function testUpReplacesThePlainIndexesWithUniqueOnesAndAddsTheMissingIndexes(): void
    {
        $this->applyMigration();

        foreach ([
            'idx_api_key_unique',
            'idx_push_endpoint_hash',
            'idx_faquserdata_keycloak_sub',
            'idx_faquserdata_entra_oid',
            'idx_faquserdata_oidc_identity',
        ] as $indexName) {
            $this->assertStringStartsWith('CREATE UNIQUE INDEX', $this->indexDefinition($indexName), $indexName);
        }

        $this->assertStringStartsWith('CREATE INDEX', $this->indexDefinition('idx_hash'));

        $db = $this->configuration->getDb();
        $this->assertNotFalse($db->query("INSERT INTO faqpush_subscriptions (id, endpoint_hash) VALUES (1, 'hash')"));
        $this->assertFalse(
            $db->query("INSERT INTO faqpush_subscriptions (id, endpoint_hash) VALUES (2, 'hash')"),
            'PushSubscriptionRepository::save() relies on the duplicate endpoint being rejected',
        );
        $this->assertNotFalse($db->query('INSERT INTO faquserdata (user_id) VALUES (1)'));
        $this->assertNotFalse(
            $db->query('INSERT INTO faquserdata (user_id) VALUES (2)'),
            'Accounts without an identity link must not collide on the unique indexes',
        );
    }

    public function testUpRecordsNoSchemaChangeOnceTheIndexesAreInPlace(): void
    {
        $this->applyMigration();

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame([], $recorder->getSqlQueries());
    }

    public function testUpKeepsDuplicateRowsAndLeavesThatIndexPlain(): void
    {
        $db = $this->configuration->getDb();
        $db->query("INSERT INTO faqapi_keys (id, api_key) VALUES (1, 'same-key')");
        $db->query("INSERT INTO faqapi_keys (id, api_key) VALUES (2, 'same-key')");
        $db->query("INSERT INTO faquserdata (user_id, entra_oid) VALUES (1, 'same-oid')");
        $db->query("INSERT INTO faquserdata (user_id, entra_oid) VALUES (2, 'same-oid')");

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);
        $joined = implode("\n", $recorder->getSqlQueries());

        $this->assertStringNotContainsString('idx_api_key_unique', $joined);
        $this->assertStringNotContainsString('idx_faquserdata_entra_oid', $joined);
        $this->assertStringContainsString('CREATE UNIQUE INDEX idx_push_endpoint_hash', $joined);

        foreach ($recorder->getOperations() as $operation) {
            $this->assertTrue($operation->execute(), $operation->getDescription());
        }

        $this->assertStringStartsWith('CREATE INDEX', $this->indexDefinition('idx_api_key_unique'));
        $this->assertSame('', $this->indexDefinition('idx_faquserdata_entra_oid'));
        $row = $db->fetchArray($db->query('SELECT COUNT(*) AS c FROM faqapi_keys'));
        $this->assertSame(2, (int) $row['c'], 'The migration never deletes rows');
    }

    #[DataProvider('uniqueIndexDialects')]
    public function testUniqueIndexesAreCreatedInTheSyntaxOfTheDatabase(
        string $dbType,
        string $apiKeyIndex,
        string $identityIndex,
    ): void {
        $previousType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);

        try {
            // A driver that reports neither an index nor a duplicate
            $driver = $this->createStub(DatabaseDriver::class);
            $driver->method('escape')->willReturnArgument(0);
            $configuration = $this->createStub(Configuration::class);
            $configuration->method('getDb')->willReturn($driver);

            $recorder = new OperationRecorder($configuration);
            new Migration420RC3($configuration)->up($recorder);
            $queries = $recorder->getSqlQueries();
        } finally {
            new ReflectionProperty(Database::class, 'dbType')->setValue(null, $previousType);
        }

        $this->assertContains($apiKeyIndex, $queries);
        $this->assertContains($identityIndex, $queries);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function uniqueIndexDialects(): iterable
    {
        yield 'pdo_mysql' => [
            'pdo_mysql',
            'CREATE UNIQUE INDEX idx_api_key_unique ON faqapi_keys (api_key)',
            'CREATE UNIQUE INDEX idx_faquserdata_oidc_identity ON faquserdata (oidc_iss, oidc_sub)',
        ];
        yield 'pdo_pgsql' => [
            'pdo_pgsql',
            'CREATE UNIQUE INDEX idx_api_key_unique ON faqapi_keys (api_key)',
            'CREATE UNIQUE INDEX idx_faquserdata_oidc_identity ON faquserdata (oidc_iss, oidc_sub)',
        ];
        yield 'pdo_sqlsrv' => [
            'pdo_sqlsrv',
            'CREATE UNIQUE INDEX idx_api_key_unique ON faqapi_keys (api_key)',
            'CREATE UNIQUE INDEX idx_faquserdata_oidc_identity ON faquserdata (oidc_iss, oidc_sub) '
                . 'WHERE oidc_iss IS NOT NULL AND oidc_sub IS NOT NULL',
        ];
    }

    /**
     * SQLite, like PostgreSQL, has one namespace for the index names of all tables. Next to the
     * installation without a prefix from setUp() lives a second one with the prefix "b_": it
     * could not create the indexes whose names the first one holds, and its own admin log index
     * still has the name both would ask for.
     */
    public function testUpGivesAPrefixedInstallationItsOwnIndexNamesAndLeavesTheNeighbourAlone(): void
    {
        $db = $this->configuration->getDb();
        $db->query('CREATE TABLE b_faqconfig (config_name VARCHAR(255) PRIMARY KEY, config_value TEXT)');
        $db->query('CREATE TABLE b_faqadminlog (id INTEGER PRIMARY KEY, hash VARCHAR(64))');
        $db->query('CREATE INDEX idx_hash ON b_faqadminlog (hash)');
        $db->query('CREATE TABLE b_faqsessions (sid INTEGER PRIMARY KEY, time INTEGER)');
        $db->query('CREATE TABLE b_faqapi_keys (id INTEGER PRIMARY KEY, api_key VARCHAR(64) NOT NULL)');
        $db->query('CREATE TABLE b_faqpush_subscriptions (id INTEGER PRIMARY KEY, endpoint_hash VARCHAR(64) NOT NULL)');
        $db->query(
            'CREATE TABLE b_faquserdata (user_id INTEGER, keycloak_sub VARCHAR(255), entra_oid VARCHAR(255), '
            . 'oidc_sub VARCHAR(255), oidc_iss VARCHAR(255))',
        );
        $db->query('CREATE UNIQUE INDEX idx_faquserdata_entra_oid ON b_faquserdata (entra_oid)');

        Database::setTablePrefix('b_');

        try {
            $migration = new Migration420RC3($this->configuration);
            $recorder = new OperationRecorder($this->configuration);
            $migration->up($recorder);

            foreach ($recorder->getOperations() as $operation) {
                $this->assertTrue($operation->execute(), $operation->getDescription());
            }

            $secondRun = new OperationRecorder($this->configuration);
            $migration->up($secondRun);
        } finally {
            Database::setTablePrefix('');
        }

        $this->assertSame([], $secondRun->getSqlQueries(), 'A second run finds nothing left to do');

        // The index that had the shared name moved to the prefixed one, the missing ones exist
        $this->assertSame('', $this->indexDefinition('idx_hash'));
        $this->assertSame('CREATE INDEX idx_hash_b_ ON b_faqadminlog (hash)', $this->indexDefinition('idx_hash_b_'));
        $this->assertSame('CREATE INDEX idx_time_b_ ON b_faqsessions (time)', $this->indexDefinition('idx_time_b_'));
        $this->assertSame('', $this->indexDefinition('idx_faquserdata_entra_oid'));

        foreach ([
            'idx_api_key_unique_b_' => 'b_faqapi_keys',
            'idx_push_endpoint_hash_b_' => 'b_faqpush_subscriptions',
            'idx_faquserdata_keycloak_sub_b_' => 'b_faquserdata',
            'idx_faquserdata_entra_oid_b_' => 'b_faquserdata',
            'idx_faquserdata_oidc_identity_b_' => 'b_faquserdata',
        ] as $indexName => $tableName) {
            $this->assertStringStartsWith(
                sprintf('CREATE UNIQUE INDEX %s ON %s ', $indexName, $tableName),
                $this->indexDefinition($indexName),
            );
        }

        // Indexes on tables this installation does not have are left to the schema convergence
        $this->assertSame('', $this->indexDefinition('idx_records_b_'));

        // The installation without a prefix keeps what it had
        $this->assertSame(
            'CREATE INDEX idx_api_key_unique ON faqapi_keys (api_key)',
            $this->indexDefinition('idx_api_key_unique'),
        );
        $this->assertSame(
            'CREATE INDEX idx_push_endpoint_hash ON faqpush_subscriptions (endpoint_hash)',
            $this->indexDefinition('idx_push_endpoint_hash'),
        );
        $this->assertSame(
            'CREATE INDEX idx_faquserdata_oidc_identity ON faquserdata (oidc_iss, oidc_sub)',
            $this->indexDefinition('idx_faquserdata_oidc_identity'),
        );
    }

    /**
     * PostgreSQL was not available to run against: the test checks the statements the migration
     * records for a driver that reports the admin log index under the shared name, bound to the
     * table of this installation, and no other index.
     */
    public function testUpRenamesTheIndexesOfAPrefixedPostgreSqlInstallation(): void
    {
        $catalogQueries = [];
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('escape')->willReturnArgument(0);
        $driver->method('numRows')->willReturn(1);
        $driver
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$catalogQueries): string {
                $catalogQueries[] = $query;
                return $query;
            });
        $driver
            ->method('fetchObject')
            ->willReturnCallback(static fn(mixed $result): object => (object) [
                'idx_count' => str_contains(
                    (string) $result,
                    "tablename = 'pmf_faqadminlog' AND indexname = 'idx_hash'",
                )
                    ? 1
                    : 0,
                'duplicate_count' => 0,
            ]);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        $queries = $this->recordedQueriesFor('pdo_pgsql', 'pmf_', $configuration);

        $this->assertContains('DROP INDEX idx_hash', $queries);
        $this->assertContains('CREATE INDEX IF NOT EXISTS idx_hash_pmf_ ON pmf_faqadminlog (hash)', $queries);
        $this->assertContains(
            'CREATE INDEX IF NOT EXISTS idx_records_pmf_ ON pmf_faqcategoryrelations (record_id, record_lang)',
            $queries,
        );
        $this->assertContains('CREATE UNIQUE INDEX idx_api_key_unique_pmf_ ON pmf_faqapi_keys (api_key)', $queries);
        $this->assertNotContains('DROP INDEX idx_api_key_unique', $queries);
        $this->assertContains(
            'SELECT COUNT(*) AS idx_count FROM pg_indexes '
            . "WHERE schemaname = current_schema() AND tablename = 'pmf_faqapi_keys' AND indexname = 'idx_api_key_unique'",
            $catalogQueries,
        );
    }

    /**
     * phpMyFAQ writes table and index names without quotes, so PostgreSQL stores the names of an
     * installation with the prefix PMF_ in lower case. The statements keep the prefix as it was
     * configured, the catalog lookups have to ask for the lower-case names. PostgreSQL was not
     * available to run against: the driver answers the way its catalog would.
     */
    public function testUpFindsTheIndexesOfAPostgreSqlInstallationWithAMixedCasePrefix(): void
    {
        $catalogQueries = [];
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('escape')->willReturnArgument(0);
        $driver
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$catalogQueries): string {
                $catalogQueries[] = $query;
                return $query;
            });
        // information_schema.columns lists the lower-case table name only
        $driver
            ->method('numRows')
            ->willReturnCallback(static fn(mixed $result): int => str_contains((string) $result, "table_name = 'pmf_")
                ? 1
                : 0);
        // pg_indexes holds the shared admin log index and the prefixed API key index
        $driver
            ->method('fetchObject')
            ->willReturnCallback(static fn(mixed $result): object => (object) [
                'idx_count' => (
                    str_contains((string) $result, "tablename = 'pmf_faqadminlog' AND indexname = 'idx_hash'")
                    || str_contains(
                        (string) $result,
                        "tablename = 'pmf_faqapi_keys' AND indexname = 'idx_api_key_unique_pmf_'",
                    )
                )
                    ? 1
                    : 0,
                'duplicate_count' => 0,
            ]);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        $queries = $this->recordedQueriesFor('pdo_pgsql', 'PMF_', $configuration);

        $this->assertContains('DROP INDEX idx_hash', $queries);
        $this->assertContains('CREATE INDEX IF NOT EXISTS idx_hash_PMF_ ON PMF_faqadminlog (hash)', $queries);
        $this->assertNotContains(
            'CREATE UNIQUE INDEX idx_api_key_unique_PMF_ ON PMF_faqapi_keys (api_key)',
            $queries,
        );
        $this->assertSame([], array_values(array_filter(
            $catalogQueries,
            static fn(string $query): bool => (
                (str_contains($query, 'pg_indexes') || str_contains($query, 'information_schema.columns'))
                && str_contains($query, 'PMF_')
            ),
        )));
    }

    /**
     * MySQL, MariaDB and SQL Server scope an index name to its table: a table prefix changes
     * nothing there, and no index is dropped or created beyond what the migration did before.
     */
    #[DataProvider('databasesThatScopeIndexNamesToTheTable')]
    public function testUpKeepsTheIndexNamesOfAPrefixedInstallationWhereTheyBelongToTheTable(string $dbType): void
    {
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('escape')->willReturnArgument(0);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        $queries = $this->recordedQueriesFor($dbType, 'pmf_', $configuration);
        $joined = implode("\n", $queries);

        $this->assertStringContainsString(
            'CREATE UNIQUE INDEX idx_api_key_unique ON pmf_faqapi_keys (api_key)',
            $joined,
        );
        $this->assertStringContainsString('CREATE INDEX idx_hash ON pmf_faqadminlog (hash)', $joined);
        $this->assertStringNotContainsString('_pmf_', $joined);
        $this->assertStringNotContainsString('DROP INDEX', $joined);
        $this->assertCount(6, $queries);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function databasesThatScopeIndexNamesToTheTable(): iterable
    {
        yield 'pdo_mysql' => ['pdo_mysql'];
        yield 'mysqli' => ['mysqli'];
        yield 'pdo_sqlsrv' => ['pdo_sqlsrv'];
        yield 'sqlsrv' => ['sqlsrv'];
    }

    /**
     * @return string[]
     */
    private function recordedQueriesFor(string $dbType, string $tablePrefix, Configuration $configuration): array
    {
        $previousType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);
        Database::setTablePrefix($tablePrefix);

        try {
            $recorder = new OperationRecorder($configuration);
            new Migration420RC3($configuration)->up($recorder);

            return $recorder->getSqlQueries();
        } finally {
            new ReflectionProperty(Database::class, 'dbType')->setValue(null, $previousType);
            Database::setTablePrefix('');
        }
    }

    public function testUpRepairsTheSingleLineRobotsTextOfTheInstaller(): void
    {
        $this->configuration->add('seo.contentRobotsText', 'User-agent: *\nDisallow: /admin/\nSitemap: /sitemap.xml');

        $this->applyMigration();

        $stored = (string) new Configuration($this->configuration->getDb())->get('seo.contentRobotsText');
        $this->assertSame(DefaultDataSeeder::ROBOTS_TEXT, $stored);
        $this->assertStringContainsString("User-agent: *\nDisallow: /admin/\n", $stored);
    }

    public function testUpLeavesAnEditedRobotsTextAlone(): void
    {
        $this->configuration->add('seo.contentRobotsText', "User-agent: *\nDisallow: /private/");

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame([], $recorder->getOperationsByType('config_update'));
    }

    public function testUpAddsTheMissingLoginWithEmailAddressConfiguration(): void
    {
        $this->applyMigration();

        $this->assertFalse(new Configuration($this->configuration->getDb())->get('security.loginWithEmailAddress'));
    }

    public function testUpCarriesTheValueOfTheOldLoginWithEmailAddressKeyOver(): void
    {
        $this->configuration->add('main.loginWithEmailAddress', 'true');

        $this->applyMigration();

        $reloaded = new Configuration($this->configuration->getDb());
        $this->assertTrue($reloaded->get('security.loginWithEmailAddress'));
        $this->assertNull($reloaded->get('main.loginWithEmailAddress'));
    }

    public function testUpLeavesAnExistingLoginWithEmailAddressConfigurationAlone(): void
    {
        $this->configuration->add('security.loginWithEmailAddress', 'true');

        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        $this->assertSame([], $recorder->getOperationsByType('config_rename'));
        foreach ($recorder->getOperationsByType('config_add') as $operation) {
            $this->assertNotSame('security.loginWithEmailAddress', $operation->getKey());
        }
    }

    private function applyMigration(): void
    {
        $recorder = new OperationRecorder($this->configuration);
        $this->migration->up($recorder);

        foreach ($recorder->getOperations() as $operation) {
            $this->assertTrue($operation->execute(), $operation->getDescription());
        }
    }

    private function indexDefinition(string $indexName): string
    {
        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query(sprintf(
            "SELECT sql FROM sqlite_master WHERE type = 'index' AND name = '%s'",
            $indexName,
        )));

        return (string) ($row['sql'] ?? '');
    }
}
