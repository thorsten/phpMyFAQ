<?php

namespace phpMyFAQ\Setup\Migration\QueryBuilder\Dialect;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PostgresDialectTest extends TestCase
{
    private PostgresDialect $dialect;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dialect = new PostgresDialect();
    }

    public function testGetType(): void
    {
        $this->assertEquals('pgsql', $this->dialect->getType());
    }

    public function testInteger(): void
    {
        $this->assertEquals('INTEGER', $this->dialect->integer());
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
        $this->assertEquals('VARCHAR(255)', $this->dialect->varchar(255));
    }

    public function testText(): void
    {
        $this->assertEquals('TEXT', $this->dialect->text());
    }

    public function testBoolean(): void
    {
        $this->assertEquals('SMALLINT', $this->dialect->boolean());
    }

    public function testTimestamp(): void
    {
        $this->assertEquals('TIMESTAMP', $this->dialect->timestamp());
    }

    public function testCurrentTimestamp(): void
    {
        $this->assertEquals('CURRENT_TIMESTAMP', $this->dialect->currentTimestamp());
    }

    public function testCurrentDate(): void
    {
        $this->assertEquals('CURRENT_DATE', $this->dialect->currentDate());
    }

    public function testAutoIncrement(): void
    {
        $this->assertEquals('id SERIAL NOT NULL', $this->dialect->autoIncrement('id'));
    }

    public function testCreateTablePrefix(): void
    {
        $this->assertEquals('CREATE TABLE test_table', $this->dialect->createTablePrefix('test_table'));
    }

    public function testCreateTablePrefixWithIfNotExists(): void
    {
        $this->assertEquals('CREATE TABLE IF NOT EXISTS test_table', $this->dialect->createTablePrefix(
            'test_table',
            true,
        ));
    }

    public function testCreateTableSuffix(): void
    {
        $this->assertEquals('', $this->dialect->createTableSuffix());
    }

    public function testAddColumn(): void
    {
        $result = $this->dialect->addColumn('test_table', 'new_column', 'VARCHAR(255)');
        $this->assertEquals('ALTER TABLE test_table ADD COLUMN new_column VARCHAR(255)', $result);
    }

    public function testAddColumnIgnoresAfter(): void
    {
        // PostgreSQL doesn't support AFTER clause
        $result = $this->dialect->addColumn('test_table', 'new_column', 'INT', 'existing_column');
        $this->assertEquals('ALTER TABLE test_table ADD COLUMN new_column INT', $result);
    }

    public function testModifyColumn(): void
    {
        $result = $this->dialect->modifyColumn('test_table', 'column', 'TEXT');
        $this->assertEquals('ALTER TABLE test_table ALTER COLUMN column TYPE TEXT', $result);
    }

    public function testCreateIndex(): void
    {
        $result = $this->dialect->createIndex('idx_test', 'test_table', ['column1']);
        $this->assertEquals('CREATE INDEX idx_test ON test_table (column1)', $result);
    }

    public function testCreateIndexWithIfNotExists(): void
    {
        $result = $this->dialect->createIndex('idx_test', 'test_table', ['column1'], true);
        $this->assertEquals('CREATE INDEX IF NOT EXISTS idx_test ON test_table (column1)', $result);
    }

    public function testDropIndex(): void
    {
        $result = $this->dialect->dropIndex('idx_test', 'test_table');
        $this->assertEquals('DROP INDEX idx_test', $result);
    }

    public function testSupportsColumnPositioning(): void
    {
        $this->assertFalse($this->dialect->supportsColumnPositioning());
    }

    public function testQuoteIdentifier(): void
    {
        $this->assertEquals('"column_name"', $this->dialect->quoteIdentifier('column_name'));
        $this->assertEquals('"table""name"', $this->dialect->quoteIdentifier('table"name'));
    }
}
