<?php

namespace phpMyFAQ\Setup\Migration\QueryBuilder;

use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\MysqlDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\PostgresDialect;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
class AlterTableBuilderTest extends TestCase
{
    private AlterTableBuilder $mysqlBuilder;
    private AlterTableBuilder $pgsqlBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mysqlBuilder = new AlterTableBuilder(new MysqlDialect());
        $this->pgsqlBuilder = new AlterTableBuilder(new PostgresDialect());
    }

    public function testAddInteger(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addInteger('new_col', true)
            ->build();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('ALTER TABLE test', $statements[0]);
        $this->assertStringContainsString('ADD COLUMN new_col INT NULL', $statements[0]);
    }

    public function testAddIntegerWithAfter(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addInteger('new_col', true, null, 'existing_col')
            ->build();

        $this->assertStringContainsString('AFTER existing_col', $statements[0]);
    }

    public function testAddIntegerWithAfterIgnoredOnPostgres(): void
    {
        $statements = $this->pgsqlBuilder
            ->table('test', false)
            ->addInteger('new_col', true, null, 'existing_col')
            ->build();

        $this->assertStringNotContainsString('AFTER', $statements[0]);
    }

    public function testAddVarchar(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addVarchar('name', 255, false)
            ->build();

        $this->assertStringContainsString('ADD COLUMN name VARCHAR(255) NOT NULL', $statements[0]);
    }

    public function testAddVarcharWithDefault(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addVarchar('status', 50, true, 'pending')
            ->build();

        $this->assertStringContainsString("DEFAULT 'pending'", $statements[0]);
    }

    public function testAddText(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addText('description', true)
            ->build();

        $this->assertStringContainsString('ADD COLUMN description TEXT NULL', $statements[0]);
    }

    public function testAddBoolean(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addBoolean('is_active', false, true)
            ->build();

        $this->assertStringContainsString('ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1', $statements[0]);
    }

    public function testAddTimestamp(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addTimestamp('created_at', false, true)
            ->build();

        $this->assertStringContainsString('ADD COLUMN created_at TIMESTAMP', $statements[0]);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', $statements[0]);
    }

    public function testModifyColumn(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->modifyColumn('description', 'TEXT')
            ->build();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('ALTER TABLE test MODIFY description TEXT', $statements[0]);
    }

    public function testModifyColumnPostgres(): void
    {
        $statements = $this->pgsqlBuilder
            ->table('test', false)
            ->modifyColumn('description', 'TEXT')
            ->build();

        $this->assertStringContainsString('ALTER COLUMN description TYPE TEXT', $statements[0]);
    }

    public function testDropColumn(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->dropColumn('old_col')
            ->build();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('ALTER TABLE test DROP COLUMN old_col', $statements[0]);
    }

    public function testMultipleAlterations(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addInteger('new_int', true)
            ->addVarchar('new_varchar', 100)
            ->dropColumn('old_col')
            ->build();

        $this->assertCount(3, $statements);
    }

    public function testBuildCombined(): void
    {
        $sql = $this->mysqlBuilder
            ->table('test', false)
            ->addInteger('col1', true)
            ->addVarchar('col2', 100, false)
            ->dropColumn('col3')
            ->buildCombined();

        $this->assertStringContainsString('ALTER TABLE test', $sql);
        $this->assertStringContainsString('ADD COLUMN col1 INT NULL', $sql);
        $this->assertStringContainsString('ADD COLUMN col2 VARCHAR(100) NOT NULL', $sql);
        $this->assertStringContainsString('DROP COLUMN col3', $sql);
    }

    public function testBuildCombinedWithAfter(): void
    {
        $sql = $this->mysqlBuilder
            ->table('test', false)
            ->addInteger('new_col', true, null, 'existing_col')
            ->buildCombined();

        $this->assertStringContainsString('AFTER existing_col', $sql);
    }

    public function testAddIntegerWithDefault(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addInteger('count', true, 0)
            ->build();

        $this->assertStringContainsString('DEFAULT 0', $statements[0]);
    }

    public function testAddVarcharWithQuotesInDefault(): void
    {
        $statements = $this->mysqlBuilder
            ->table('test', false)
            ->addVarchar('status', 50, true, "it's working")
            ->build();

        // Single quotes should be escaped as ''
        $this->assertStringContainsString("DEFAULT 'it''s working'", $statements[0]);
    }

    public function testBuildWithoutCallingTableThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table name not set. Call table() before building');

        $builder = new AlterTableBuilder(new MysqlDialect());
        $builder->addInteger('col1')->build();
    }

    public function testBuildCombinedWithoutCallingTableThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table name not set. Call table() before building');

        $builder = new AlterTableBuilder(new MysqlDialect());
        $builder->addInteger('col1')->buildCombined();
    }
}
