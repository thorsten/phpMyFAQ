<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Installation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\PostgresDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\IndexName;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

/**
 * Drops and recreates the schema on a private copy of the SQLite test database.
 */
#[CoversClass(SchemaInstaller::class)]
#[UsesNamespace('phpMyFAQ')]
final class SchemaInstallerDatabaseTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private mixed $previousDatabaseType = null;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'sqlite3');
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
    }

    private function tableCount(): int
    {
        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query("SELECT COUNT(*) AS n FROM sqlite_master WHERE type = 'table'"));

        return (int) ($row['n'] ?? 0);
    }

    /**
     * SQLite, like PostgreSQL, keeps the index names of all tables in one namespace. The test
     * database already holds an installation without a table prefix, so a second and a third
     * installation in the same file only succeed when their index names carry the prefix.
     */
    public function testInstallationsWithDifferentTablePrefixesShareOneDatabase(): void
    {
        $installer = new SchemaInstaller($this->configuration, new SqliteDialect());

        $this->assertTrue($installer->createTables('one_'));
        $this->assertTrue($installer->createTables('two_'));
        $this->assertSame('', Database::getTablePrefix());

        $db = $this->configuration->getDb();
        $indexes = [];
        $result = $db->query("SELECT name, tbl_name FROM sqlite_master WHERE type = 'index' AND name LIKE 'idx_%'");
        foreach ($db->fetchAll($result) ?? [] as $row) {
            $indexes[(string) $row->name] = (string) $row->tbl_name;
        }

        $this->assertSame('faqadminlog', $indexes['idx_hash'] ?? null);
        $this->assertSame('one_faqadminlog', $indexes['idx_hash_one_'] ?? null);
        $this->assertSame('two_faqadminlog', $indexes['idx_hash_two_'] ?? null);
        $this->assertSame('two_faquserdata', $indexes['idx_faquserdata_oidc_identity_two_'] ?? null);

        $unique = $db->fetchObject($db->query(
            "SELECT sql FROM sqlite_master WHERE type = 'index' AND name = 'idx_push_endpoint_hash_two_'",
        ));
        $this->assertStringStartsWith('CREATE UNIQUE INDEX', (string) $unique->sql);
    }

    /**
     * PostgreSQL cuts identifiers off after 63 bytes. The installer accepts table prefixes of up
     * to 32 characters, and no index name of the schema may get longer than that with one.
     */
    public function testEveryIndexNameFitsThePostgreSqlIdentifierLimitWithTheLongestTablePrefix(): void
    {
        $longestPrefix = str_repeat('p', 32);

        foreach (new DatabaseSchema(new PostgresDialect())->getAllTables() as $tableBuilder) {
            foreach (array_keys($tableBuilder->getIndexes()) as $indexName) {
                $this->assertLessThanOrEqual(
                    63,
                    strlen(IndexName::forTablePrefix($indexName, 'pgsql', $longestPrefix)),
                    $indexName,
                );
            }
        }
    }

    public function testDropTablesRemovesEveryTableAndCreateMissingTablesBringsThemBack(): void
    {
        $installer = new SchemaInstaller($this->configuration, new SqliteDialect());
        $schemaTables = count($installer->getSchema()->getTableNames());
        $before = $this->tableCount();
        $this->assertGreaterThanOrEqual($schemaTables, $before);

        $this->assertTrue($installer->dropTables());
        $this->assertSame($before - $schemaTables, $this->tableCount());

        // Dropping again fails on the first missing table.
        $this->assertFalse($installer->dropTables());

        $installer->createMissingTables();
        $this->assertSame($before, $this->tableCount());

        // Existing tables are left alone: a second run creates nothing.
        $installer->createMissingTables();
        $this->assertSame($before, $this->tableCount());
    }

    public function testAddMissingColumnsRestoresAColumnTheSchemaDefines(): void
    {
        // Simulate an installation whose faquser table predates the remember_me_expires
        // column and whose recorded update state cannot see the migration that adds it.
        $db = $this->configuration->getDb();
        $db->query('ALTER TABLE faquser DROP COLUMN remember_me_expires');
        $this->assertSame(
            0,
            $db->numRows($db->query(
                "SELECT name FROM pragma_table_info('faquser') WHERE name = 'remember_me_expires'",
            )),
        );

        $installer = new SchemaInstaller($this->configuration, new SqliteDialect());
        $installer->addMissingColumns();

        $this->assertSame(
            1,
            $db->numRows($db->query(
                "SELECT name FROM pragma_table_info('faquser') WHERE name = 'remember_me_expires'",
            )),
        );

        // Existing columns are left alone: a second run issues nothing new.
        $this->assertContains(
            'ALTER TABLE faquser ADD COLUMN remember_me_expires INTEGER NULL',
            $installer->collectedSql,
        );
        $alreadyIssued = $installer->collectedSql;
        $installer->dryRun = true;
        $installer->addMissingColumns();
        $this->assertSame($alreadyIssued, $installer->collectedSql);
    }

    public function testAddMissingColumnsSkipsTablesTheDatabaseDoesNotHave(): void
    {
        $catalogQueries = [];
        $db = $this->createStub(DatabaseDriver::class);
        $db->method('query')->willReturnCallback(static function (string $query) use (&$catalogQueries): bool {
            $catalogQueries[] = $query;
            return true;
        });
        $db->method('numRows')->willReturn(0);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $installer = new SchemaInstaller($configuration, new SqliteDialect());
        $installer->addMissingColumns();

        $this->assertSame([], $installer->collectedSql, 'No column is appended to a table that is missing');
        $this->assertNotEmpty($catalogQueries);
        foreach ($catalogQueries as $catalogQuery) {
            $this->assertStringStartsWith('SELECT name FROM sqlite_master', $catalogQuery);
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function columnCatalogQueries(): iterable
    {
        yield 'mysqli' => ['mysqli', 'SHOW COLUMNS FROM faquser'];
        yield 'pdo_mysql' => ['pdo_mysql', 'SHOW COLUMNS FROM faquser'];
        yield 'pgsql' => [
            'pgsql',
            'SELECT column_name AS "Field" FROM information_schema.columns '
                . "WHERE table_schema = current_schema() AND table_name = 'faquser'",
        ];
        yield 'pdo_pgsql' => [
            'pdo_pgsql',
            'SELECT column_name AS "Field" FROM information_schema.columns '
                . "WHERE table_schema = current_schema() AND table_name = 'faquser'",
        ];
        yield 'sqlsrv' => ['sqlsrv', "SELECT name AS Field FROM sys.columns WHERE object_id = OBJECT_ID('faquser')"];
        yield 'pdo_sqlsrv' => [
            'pdo_sqlsrv',
            "SELECT name AS Field FROM sys.columns WHERE object_id = OBJECT_ID('faquser')",
        ];
    }

    #[DataProvider('columnCatalogQueries')]
    public function testAddMissingColumnsReadsTheColumnCatalogOfTheDatabaseType(
        string $databaseType,
        string $expectedCatalogQuery,
    ): void {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $databaseType);

        $catalogQueries = [];
        $db = $this->createStub(DatabaseDriver::class);
        $db->method('query')->willReturnCallback(static function (string $query) use (&$catalogQueries): bool {
            $catalogQueries[] = $query;
            return true;
        });
        // Every table exists, and the catalog reports it as having one column: the "Field" alias
        // the catalog queries share is what the installer reads.
        $db->method('numRows')->willReturn(1);
        $db->method('fetchAll')->willReturn([(object) ['Field' => 'user_id']]);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $installer = new SchemaInstaller($configuration, new SqliteDialect());
        $installer->dryRun = true;
        $installer->addMissingColumns();

        $this->assertContains($expectedCatalogQuery, $catalogQueries);
        $this->assertContains(
            'ALTER TABLE faquser ADD COLUMN remember_me_expires INTEGER NULL',
            $installer->collectedSql,
        );
        foreach ($installer->collectedSql as $statement) {
            $this->assertStringNotContainsString('ADD COLUMN user_id ', $statement, 'The reported column is kept');
        }
    }

    public function testTableExistenceCheckRejectsAnUnsupportedDatabaseType(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'oracle');
        $installer = new SchemaInstaller($this->configuration, new SqliteDialect());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported database type: oracle');

        $installer->createMissingTables();
    }
}
