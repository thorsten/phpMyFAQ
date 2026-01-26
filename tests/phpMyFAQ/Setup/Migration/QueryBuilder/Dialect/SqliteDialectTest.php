<?php

namespace phpMyFAQ\Setup\Migration\QueryBuilder\Dialect;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SqliteDialectTest extends TestCase
{
    private SqliteDialect $dialect;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dialect = new SqliteDialect();
    }

    public function testGetType(): void
    {
        $this->assertEquals('sqlite3', $this->dialect->getType());
    }

    public function testInteger(): void
    {
        $this->assertEquals('INTEGER', $this->dialect->integer());
    }

    public function testBigInteger(): void
    {
        // SQLite stores all integers as INTEGER
        $this->assertEquals('INTEGER', $this->dialect->bigInteger());
    }

    public function testSmallInteger(): void
    {
        $this->assertEquals('INTEGER', $this->dialect->smallInteger());
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
        $this->assertEquals('INTEGER', $this->dialect->boolean());
    }

    public function testTimestamp(): void
    {
        $this->assertEquals('DATETIME', $this->dialect->timestamp());
    }

    public function testCurrentTimestamp(): void
    {
        $this->assertEquals('CURRENT_TIMESTAMP', $this->dialect->currentTimestamp());
    }

    public function testCurrentDate(): void
    {
        $this->assertEquals("(date('now'))", $this->dialect->currentDate());
    }

    public function testAutoIncrement(): void
    {
        $this->assertEquals('id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT', $this->dialect->autoIncrement('id'));
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
        $result = $this->dialect->addColumn('test_table', 'new_column', 'TEXT');
        $this->assertEquals('ALTER TABLE test_table ADD COLUMN new_column TEXT', $result);
    }

    public function testModifyColumnThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SQLite does not support modifying columns');

        $this->dialect->modifyColumn('test_table', 'column', 'TEXT');
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
        $this->assertEquals('DROP INDEX IF EXISTS idx_test', $result);
    }

    public function testSupportsColumnPositioning(): void
    {
        $this->assertFalse($this->dialect->supportsColumnPositioning());
    }

    public function testQuoteIdentifier(): void
    {
        $this->assertEquals('"column_name"', $this->dialect->quoteIdentifier('column_name'));
    }
}
