<?php

namespace phpMyFAQ\Setup\Migration\QueryBuilder\Dialect;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SqlServerDialectTest extends TestCase
{
    private SqlServerDialect $dialect;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dialect = new SqlServerDialect();
    }

    public function testGetType(): void
    {
        $this->assertEquals('sqlsrv', $this->dialect->getType());
    }

    public function testInteger(): void
    {
        $this->assertEquals('INT', $this->dialect->integer());
    }

    public function testBigInteger(): void
    {
        $this->assertEquals('BIGINT', $this->dialect->bigInteger());
    }

    public function testSmallInteger(): void
    {
        $this->assertEquals('SMALLINT', $this->dialect->smallInteger());
    }

    public function testVarchar(): void
    {
        // SQL Server uses NVARCHAR for Unicode support
        $this->assertEquals('NVARCHAR(255)', $this->dialect->varchar(255));
    }

    public function testText(): void
    {
        $this->assertEquals('NVARCHAR(MAX)', $this->dialect->text());
    }

    public function testBoolean(): void
    {
        $this->assertEquals('TINYINT', $this->dialect->boolean());
    }

    public function testTimestamp(): void
    {
        $this->assertEquals('DATETIME', $this->dialect->timestamp());
    }

    public function testCurrentTimestamp(): void
    {
        $this->assertEquals('GETDATE()', $this->dialect->currentTimestamp());
    }

    public function testCurrentDate(): void
    {
        $this->assertEquals('GETDATE()', $this->dialect->currentDate());
    }

    public function testAutoIncrement(): void
    {
        $this->assertEquals('id INT IDENTITY(1,1) NOT NULL', $this->dialect->autoIncrement('id'));
    }

    public function testCreateTablePrefix(): void
    {
        $this->assertEquals('CREATE TABLE test_table', $this->dialect->createTablePrefix('test_table'));
    }

    public function testCreateTablePrefixWithIfNotExists(): void
    {
        $result = $this->dialect->createTablePrefix('test_table', true);
        $this->assertStringContainsString('IF NOT EXISTS', $result);
        $this->assertStringContainsString('CREATE TABLE test_table', $result);
    }

    public function testCreateTableSuffix(): void
    {
        $this->assertEquals('', $this->dialect->createTableSuffix());
    }

    public function testAddColumn(): void
    {
        $result = $this->dialect->addColumn('test_table', 'new_column', 'NVARCHAR(255)');
        $this->assertEquals('ALTER TABLE test_table ADD new_column NVARCHAR(255)', $result);
    }

    public function testModifyColumn(): void
    {
        $result = $this->dialect->modifyColumn('test_table', 'column', 'NVARCHAR(MAX)');
        $this->assertEquals('ALTER TABLE test_table ALTER COLUMN column NVARCHAR(MAX)', $result);
    }

    public function testCreateIndex(): void
    {
        $result = $this->dialect->createIndex('idx_test', 'test_table', ['column1']);
        $this->assertEquals('CREATE INDEX idx_test ON test_table (column1)', $result);
    }

    public function testCreateIndexWithIfNotExists(): void
    {
        $result = $this->dialect->createIndex('idx_test', 'test_table', ['column1'], true);
        $this->assertStringContainsString('IF NOT EXISTS', $result);
        $this->assertStringContainsString('CREATE INDEX idx_test', $result);
    }

    public function testDropIndex(): void
    {
        $result = $this->dialect->dropIndex('idx_test', 'test_table');
        $this->assertEquals('DROP INDEX idx_test ON test_table', $result);
    }

    public function testSupportsColumnPositioning(): void
    {
        $this->assertFalse($this->dialect->supportsColumnPositioning());
    }

    public function testQuoteIdentifier(): void
    {
        $this->assertEquals('[column_name]', $this->dialect->quoteIdentifier('column_name'));
        $this->assertEquals('[table]]name]', $this->dialect->quoteIdentifier('table]name'));
    }

    public function testChar(): void
    {
        $this->assertEquals('NCHAR(1)', $this->dialect->char(1));
    }
}
