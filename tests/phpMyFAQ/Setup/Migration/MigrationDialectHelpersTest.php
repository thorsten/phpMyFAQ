<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

/**
 * Pins the SQL fragments the dialect helpers of AbstractMigration hand to the migrations.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractMigration::class)]
#[UsesNamespace('phpMyFAQ')]
final class MigrationDialectHelpersTest extends TestCase
{
    private mixed $previousDatabaseType = null;

    private string $previousTablePrefix = '';

    protected function setUp(): void
    {
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        $this->previousTablePrefix = Database::getTablePrefix();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
        Database::setTablePrefix($this->previousTablePrefix);
    }

    /**
     * A migration that only exposes the protected helpers under test.
     */
    private function migration(string $dbType, ?DatabaseDriver $driver = null, string $tablePrefix = 'pmf_'): object
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);
        Database::setTablePrefix($tablePrefix);

        $driver ??= $this->createStub(DatabaseDriver::class);
        $driver->method('escape')->willReturnArgument(0);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        return new readonly class ($configuration) extends AbstractMigration {
            public function getVersion(): string
            {
                return '0.0.0-test';
            }

            public function getDescription(): string
            {
                return 'Exposes the dialect helpers';
            }

            public function up(OperationRecorder $recorder): void
            {
            }

            public function indexQuery(string $table, string $index): string
            {
                return $this->indexExists($table, $index);
            }

            public function indexIsPresent(string $table, string $index): bool
            {
                return $this->hasIndex($table, $index);
            }

            public function columnIsPresent(string $table, string $column): bool
            {
                return $this->columnExists($table, $column);
            }

            public function timestamp(bool $withDefault): string
            {
                return $this->timestampType($withDefault);
            }

            public function boolean(): string
            {
                return $this->booleanType();
            }

            public function autoIncrement(string $column): string
            {
                return $this->autoIncrementColumn($column);
            }
        };
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function indexQueryProvider(): iterable
    {
        yield 'mysqli' => ['mysqli', 'information_schema.STATISTICS'];
        yield 'pdo_mysql' => ['pdo_mysql', 'information_schema.STATISTICS'];
        yield 'pgsql' => ['pgsql', 'pg_indexes'];
        yield 'pdo_pgsql' => ['pdo_pgsql', 'pg_indexes'];
        yield 'sqlite3' => ['sqlite3', 'sqlite_master'];
        yield 'pdo_sqlite' => ['pdo_sqlite', 'sqlite_master'];
        yield 'sqlsrv' => ['sqlsrv', 'sys.indexes'];
        yield 'pdo_sqlsrv' => ['pdo_sqlsrv', 'sys.indexes'];
    }

    #[DataProvider('indexQueryProvider')]
    public function testIndexExistenceQueryUsesTheDialectCatalog(string $dbType, string $catalog): void
    {
        $query = $this->migration($dbType)->indexQuery('faqdata', 'idx_faqdata_lang');

        $this->assertStringStartsWith('SELECT COUNT(*) as idx_count FROM ' . $catalog, $query);
        $this->assertStringContainsString('pmf_faqdata', $query);
        $this->assertStringContainsString('idx_faqdata_lang', $query);
    }

    /**
     * A PostgreSQL database can hold the tables of several installations in separate schemas;
     * the catalog checks must only see the schema the connection works in.
     */
    public function testPostgresCatalogChecksAreLimitedToTheCurrentSchema(): void
    {
        $queries = [];
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('query')->willReturnCallback(static function (string $query) use (&$queries): bool {
            $queries[] = $query;
            return true;
        });
        $migration = $this->migration('pdo_pgsql', $driver);

        $this->assertStringContainsString(
            "WHERE schemaname = current_schema() AND tablename = 'pmf_faqdata'",
            $migration->indexQuery('faqdata', 'idx_faqdata_lang'),
        );

        $migration->columnIsPresent('faquser', 'remember_me_expires');
        $this->assertSame(
            'SELECT column_name FROM information_schema.columns '
            . "WHERE table_schema = current_schema() AND table_name = 'pmf_faquser' "
            . "AND column_name = 'remember_me_expires'",
            $queries[0],
        );
    }

    /**
     * phpMyFAQ writes table and index names without quotes, so PostgreSQL stores them in lower
     * case. PostgreSQL was not available to run against: the test checks the catalog queries.
     */
    #[DataProvider('postgresDrivers')]
    public function testPostgresCatalogChecksLookUpTheLowerCaseNameOfAMixedCasePrefix(string $dbType): void
    {
        $queries = [];
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('query')->willReturnCallback(static function (string $query) use (&$queries): bool {
            $queries[] = $query;
            return true;
        });
        $migration = $this->migration($dbType, $driver, 'PMF_');

        $this->assertSame(
            'SELECT COUNT(*) as idx_count FROM pg_indexes '
            . "WHERE schemaname = current_schema() AND tablename = 'pmf_faqdata' "
            . "AND indexname = 'idx_faqdata_lang_pmf_'",
            $migration->indexQuery('faqdata', 'idx_faqdata_lang'),
        );

        $migration->columnIsPresent('faquser', 'remember_me_expires');
        $this->assertSame(
            'SELECT column_name FROM information_schema.columns '
            . "WHERE table_schema = current_schema() AND table_name = 'pmf_faquser' "
            . "AND column_name = 'remember_me_expires'",
            $queries[0],
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function postgresDrivers(): iterable
    {
        yield 'pgsql' => ['pgsql'];
        yield 'pdo_pgsql' => ['pdo_pgsql'];
    }

    /**
     * How the other databases compare names depends on the server, the collation or the
     * database itself: their catalog queries keep the prefix as it was configured.
     */
    #[DataProvider('databasesThatKeepTheConfiguredPrefix')]
    public function testOtherDatabasesLookUpAMixedCasePrefixAsConfigured(
        string $dbType,
        string $indexName,
        string $columnQuery,
    ): void {
        $queries = [];
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('query')->willReturnCallback(static function (string $query) use (&$queries): bool {
            $queries[] = $query;
            return true;
        });
        $migration = $this->migration($dbType, $driver, 'PMF_');

        $indexQuery = $migration->indexQuery('faqdata', 'idx_faqdata_lang');
        $this->assertStringContainsString("'PMF_faqdata'", $indexQuery);
        $this->assertStringContainsString("'" . $indexName . "'", $indexQuery);
        $this->assertStringNotContainsString('pmf_', $indexQuery);

        $migration->columnIsPresent('faquser', 'remember_me_expires');
        $this->assertSame($columnQuery, $queries[0]);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function databasesThatKeepTheConfiguredPrefix(): iterable
    {
        yield 'pdo_mysql' => [
            'pdo_mysql',
            'idx_faqdata_lang',
            "SHOW COLUMNS FROM PMF_faquser LIKE 'remember_me_expires'",
        ];
        yield 'pdo_sqlite' => [
            'pdo_sqlite',
            'idx_faqdata_lang_PMF_',
            "SELECT name FROM pragma_table_info('PMF_faquser') WHERE name = 'remember_me_expires'",
        ];
        yield 'pdo_sqlsrv' => [
            'pdo_sqlsrv',
            'idx_faqdata_lang',
            "SELECT name FROM sys.columns WHERE object_id = OBJECT_ID('PMF_faquser') AND name = 'remember_me_expires'",
        ];
    }

    public function testHasIndexReadsTheCountOfTheCatalogQuery(): void
    {
        $rows = [(object) ['idx_count' => '1'], (object) ['idx_count' => 0], false];
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('fetchObject')->willReturnCallback(static function () use (&$rows): mixed {
            return array_shift($rows);
        });
        $migration = $this->migration('pdo_mysql', $driver);

        $this->assertTrue($migration->indexIsPresent('faqadminlog', 'idx_hash'));
        $this->assertFalse($migration->indexIsPresent('faqadminlog', 'idx_hash'));
        $this->assertFalse($migration->indexIsPresent('faqadminlog', 'idx_hash'));
    }

    public function testIndexExistenceQueryRejectsAnUnknownDatabaseType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported database type for index existence check: oracle');

        $this->migration('oracle')->indexQuery('faqdata', 'idx');
    }

    /**
     * @return iterable<string, array{string, string, string, string, string}>
     */
    public static function columnTypeProvider(): iterable
    {
        yield 'mysql' => [
            'mysqli',
            'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'TIMESTAMP',
            'TINYINT(1)',
            'id INT NOT NULL AUTO_INCREMENT',
        ];
        yield 'postgresql' => [
            'pdo_pgsql',
            'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'TIMESTAMP',
            'INTEGER',
            'id SERIAL NOT NULL',
        ];
        yield 'sqlite' => [
            'sqlite3',
            'DATETIME DEFAULT CURRENT_TIMESTAMP',
            'TIMESTAMP',
            'INTEGER',
            'id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT',
        ];
        yield 'sql server' => [
            'sqlsrv',
            'DATETIME NOT NULL DEFAULT GETDATE()',
            'DATETIME',
            'TINYINT',
            'id INT IDENTITY(1,1) NOT NULL',
        ];
        yield 'unknown' => ['oracle', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', 'TIMESTAMP', 'INTEGER', 'id INTEGER NOT NULL'];
    }

    #[DataProvider('columnTypeProvider')]
    public function testColumnTypesFollowTheDialect(
        string $dbType,
        string $timestampWithDefault,
        string $timestampWithoutDefault,
        string $boolean,
        string $autoIncrement,
    ): void {
        $migration = $this->migration($dbType);

        $this->assertSame($timestampWithDefault, $migration->timestamp(true));
        $this->assertSame($timestampWithoutDefault, $migration->timestamp(false));
        $this->assertSame($boolean, $migration->boolean());
        $this->assertSame($autoIncrement, $migration->autoIncrement('id'));
    }
}
