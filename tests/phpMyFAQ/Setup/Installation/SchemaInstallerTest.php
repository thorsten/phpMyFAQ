<?php

namespace phpMyFAQ\Setup\Installation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\MysqlDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\PostgresDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqlServerDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\DialectInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SchemaInstallerTest extends TestCase
{
    /**
     * @return array<string, array{DialectInterface}>
     */
    public static function dialectProvider(): array
    {
        return [
            'mysql' => [new MysqlDialect()],
            'postgres' => [new PostgresDialect()],
            'sqlite' => [new SqliteDialect()],
            'sqlserver' => [new SqlServerDialect()],
        ];
    }

    #[DataProvider('dialectProvider')]
    public function testDryRunCollectsAllSql(DialectInterface $dialect): void
    {
        $db = $this->createStub(DatabaseDriver::class);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $installer = new SchemaInstaller($configuration, $dialect);
        $installer->dryRun = true;

        $result = $installer->createTables('');
        $this->assertTrue($result);

        $sql = $installer->collectedSql;
        $this->assertNotEmpty($sql);

        // Should have at least one CREATE TABLE per table definition
        $createTableCount = 0;
        foreach ($sql as $statement) {
            if (str_contains($statement, 'CREATE TABLE')) {
                $createTableCount++;
            }
        }
        $this->assertEquals(43, $createTableCount, 'Should generate CREATE TABLE for all 43 tables');
    }

    #[DataProvider('dialectProvider')]
    public function testDryRunDoesNotExecuteQueries(DialectInterface $dialect): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->never())->method('query');
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $installer = new SchemaInstaller($configuration, $dialect);
        $installer->dryRun = true;
        $installer->createTables('');
    }

    public function testGetSchemaReturnsDatabaseSchema(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $installer = new SchemaInstaller($configuration, new MysqlDialect());
        $this->assertInstanceOf(DatabaseSchema::class, $installer->getSchema());
    }

    public function testCreateTablesReturnsFalseOnDbError(): void
    {
        $db = $this->createStub(DatabaseDriver::class);
        $db->method('query')->willReturn(false);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $installer = new SchemaInstaller($configuration, new MysqlDialect());
        $result = $installer->createTables('');
        $this->assertFalse($result);
    }

    public function testMysqlDryRunContainsInnodb(): void
    {
        $db = $this->createStub(DatabaseDriver::class);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $installer = new SchemaInstaller($configuration, new MysqlDialect());
        $installer->dryRun = true;
        $installer->createTables('');

        $allSql = implode("\n", $installer->collectedSql);
        $this->assertStringContainsString('InnoDB', $allSql);
        $this->assertStringContainsString('utf8mb4', $allSql);
    }

    public function testPostgresNoIndexStatementsDuplicate(): void
    {
        $db = $this->createStub(DatabaseDriver::class);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $installer = new SchemaInstaller($configuration, new PostgresDialect());
        $installer->dryRun = true;
        $installer->createTables('');

        $sql = $installer->collectedSql;
        $indexes = [];
        foreach ($sql as $statement) {
            if (str_contains($statement, 'CREATE INDEX')) {
                $indexes[] = $statement;
            }
        }

        // PostgreSQL should have separate CREATE INDEX statements for indexes
        $this->assertGreaterThan(0, count($indexes), 'PostgreSQL should have separate CREATE INDEX statements');

        // No duplicate CREATE INDEX statements should be emitted
        $this->assertEquals(
            count($indexes),
            count(array_unique($indexes)),
            'PostgreSQL should not emit duplicate CREATE INDEX statements',
        );
    }
}
