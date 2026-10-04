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
    private function migration(string $dbType): object
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);
        Database::setTablePrefix('pmf_');

        $driver = $this->createStub(DatabaseDriver::class);
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
