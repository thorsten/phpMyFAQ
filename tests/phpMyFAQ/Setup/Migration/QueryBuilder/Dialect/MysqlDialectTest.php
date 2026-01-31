<?php

namespace phpMyFAQ\Setup\Migration\QueryBuilder\Dialect;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
class MysqlDialectTest extends TestCase
{
    private MysqlDialect $dialect;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dialect = new MysqlDialect();
    }

    public function testGetType(): void
    {
        $this->assertEquals('mysqli', $this->dialect->getType());
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
        $this->assertEquals('VARCHAR(255)', $this->dialect->varchar(255));
        $this->assertEquals('VARCHAR(50)', $this->dialect->varchar(50));
    }

    public function testText(): void
    {
        $this->assertEquals('TEXT', $this->dialect->text());
    }

    public function testBoolean(): void
    {
        $this->assertEquals('TINYINT(1)', $this->dialect->boolean());
    }

    public function testTimestamp(): void
    {
        $this->assertEquals('TIMESTAMP', $this->dialect->timestamp());
    }

    public function testDate(): void
    {
        $this->assertEquals('DATE', $this->dialect->date());
    }

    public function testChar(): void
    {
        $this->assertEquals('CHAR(1)', $this->dialect->char(1));
        $this->assertEquals('CHAR(10)', $this->dialect->char(10));
    }

    public function testCurrentTimestamp(): void
    {
        $this->assertEquals('CURRENT_TIMESTAMP', $this->dialect->currentTimestamp());
    }

    public function testCurrentDate(): void
    {
        $this->assertEquals('CURDATE()', $this->dialect->currentDate());
    }

    public function testAutoIncrement(): void
    {
        $this->assertEquals('id INT NOT NULL PRIMARY KEY AUTO_INCREMENT', $this->dialect->autoIncrement('id'));
        $this->assertEquals('user_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT', $this->dialect->autoIncrement('user_id'));
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
        $this->assertEquals(
            'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
            $this->dialect->createTableSuffix(),
        );
    }

    public function testAddColumn(): void
    {
        $result = $this->dialect->addColumn('test_table', 'new_column', 'VARCHAR(255)');
        $this->assertEquals('ALTER TABLE test_table ADD COLUMN new_column VARCHAR(255)', $result);
    }

    public function testAddColumnWithAfter(): void
    {
        $result = $this->dialect->addColumn('test_table', 'new_column', 'INT', 'existing_column');
        $this->assertEquals('ALTER TABLE test_table ADD COLUMN new_column INT AFTER existing_column', $result);
    }

    public function testModifyColumn(): void
    {
        $result = $this->dialect->modifyColumn('test_table', 'column', 'TEXT');
        $this->assertEquals('ALTER TABLE test_table MODIFY column TEXT', $result);
    }

    public function testCreateIndex(): void
    {
        $result = $this->dialect->createIndex('idx_test', 'test_table', ['column1', 'column2']);
        $this->assertEquals('CREATE INDEX idx_test ON test_table (column1, column2)', $result);
    }

    public function testCreateIndexThrowsExceptionWhenIfNotExistsIsTrue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MySQL/MariaDB does not support IF NOT EXISTS for CREATE INDEX');

        $this->dialect->createIndex('idx_test', 'test_table', ['column1'], true);
    }

    public function testDropIndex(): void
    {
        $result = $this->dialect->dropIndex('idx_test', 'test_table');
        $this->assertEquals('DROP INDEX idx_test ON test_table', $result);
    }

    public function testSupportsColumnPositioning(): void
    {
        $this->assertTrue($this->dialect->supportsColumnPositioning());
    }

    public function testQuoteIdentifier(): void
    {
        $this->assertEquals('`column_name`', $this->dialect->quoteIdentifier('column_name'));
        $this->assertEquals('`table``name`', $this->dialect->quoteIdentifier('table`name'));
    }
}
