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
        $this->assertEquals(56, $createTableCount, 'Should generate CREATE TABLE for all 56 tables');
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

    /**
     * @return array<string, array{DialectInterface, list<string>}>
     */
    public static function schemaStatementProvider(): array
    {
        return [
            'mysql' => [new MysqlDialect(), ['CREATE DATABASE IF NOT EXISTS `tenant_a`', 'USE `tenant_a`']],
            'postgres' => [new PostgresDialect(), ['CREATE SCHEMA IF NOT EXISTS "tenant_a"', 'SET search_path TO "tenant_a"']],
            'sqlserver' => [
                new SqlServerDialect(),
                ["IF NOT EXISTS (SELECT * FROM sys.schemas WHERE name = 'tenant_a') EXEC('CREATE SCHEMA [tenant_a]')"],
            ],
            'sqlite' => [new SqliteDialect(), []],
        ];
    }

    /**
     * @param list<string> $expectedStatements
     */
    #[DataProvider('schemaStatementProvider')]
    public function testASchemaNameSwitchesToThatSchemaBeforeCreatingTables(
        DialectInterface $dialect,
        array $expectedStatements,
    ): void {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($this->createStub(DatabaseDriver::class));

        $installer = new SchemaInstaller($configuration, $dialect);
        $installer->dryRun = true;

        $this->assertTrue($installer->createTables('', 'tenant_a'));
        $this->assertSame($expectedStatements, array_slice($installer->collectedSql, 0, count($expectedStatements)));
        $this->assertStringContainsString('CREATE TABLE', $installer->collectedSql[count($expectedStatements)]);
    }

    public function testASchemaThatCannotBeCreatedAbortsBeforeAnyTable(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->once())->method('query')->willReturn(false);
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $installer = new SchemaInstaller($configuration, new MysqlDialect());

        $this->assertFalse($installer->createTables('', 'tenant_a'));
        $this->assertSame(['CREATE DATABASE IF NOT EXISTS `tenant_a`'], $installer->collectedSql);
    }
}
