<?php

namespace phpMyFAQ\Setup\Migration\QueryBuilder;

use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\MysqlDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\PostgresDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TableBuilderTest extends TestCase
{
    private TableBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new TableBuilder(new MysqlDialect());
    }

    public function testTableSetsTableName(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->integer('id', false)
            ->primaryKey('id')
            ->build();

        $this->assertStringContainsString('CREATE TABLE test', $sql);
    }

    public function testIfNotExists(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->ifNotExists()
            ->integer('id', false)
            ->primaryKey('id')
            ->build();

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS test', $sql);
    }

    public function testIntegerColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->integer('id', false)
            ->primaryKey('id')
            ->build();

        $this->assertStringContainsString('id INT NOT NULL', $sql);
    }

    public function testIntegerColumnNullable(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->integer('nullable_col', true)
            ->build();

        $this->assertStringContainsString('nullable_col INT NULL', $sql);
    }

    public function testIntegerColumnWithDefault(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->integer('with_default', true, 0)
            ->build();

        $this->assertStringContainsString('DEFAULT 0', $sql);
    }

    public function testVarcharColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->varchar('name', 255, false)
            ->build();

        $this->assertStringContainsString('name VARCHAR(255) NOT NULL', $sql);
    }

    public function testVarcharColumnWithDefault(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->varchar('status', 50, true, 'active')
            ->build();

        $this->assertStringContainsString("DEFAULT 'active'", $sql);
    }

    public function testTextColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->text('content', true)
            ->build();

        $this->assertStringContainsString('content TEXT NULL', $sql);
    }

    public function testBooleanColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->boolean('is_active', false, true)
            ->build();

        $this->assertStringContainsString('is_active TINYINT(1) NOT NULL DEFAULT 1', $sql);
    }

    public function testTimestampColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->timestamp('created_at', false, true)
            ->build();

        $this->assertStringContainsString('created_at TIMESTAMP', $sql);
        $this->assertStringContainsString('DEFAULT CURRENT_TIMESTAMP', $sql);
    }

    public function testDateColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->date('birth_date', true)
            ->build();

        $this->assertStringContainsString('birth_date DATE NULL', $sql);
    }

    public function testCharColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->char('flag', 1, false, 'Y')
            ->build();

        $this->assertStringContainsString("flag CHAR(1) NOT NULL DEFAULT 'Y'", $sql);
    }

    public function testAutoIncrementColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->autoIncrement('id')
            ->build();

        $this->assertStringContainsString('id INT NOT NULL AUTO_INCREMENT', $sql);
    }

    public function testPrimaryKey(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->integer('id', false)
            ->primaryKey('id')
            ->build();

        $this->assertStringContainsString('PRIMARY KEY (id)', $sql);
    }

    public function testCompositePrimaryKey(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->integer('id', false)
            ->varchar('lang', 5, false)
            ->primaryKey(['id', 'lang'])
            ->build();

        $this->assertStringContainsString('PRIMARY KEY (id, lang)', $sql);
    }

    public function testIndex(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->varchar('email', 255)
            ->index('idx_email', 'email')
            ->build();

        $this->assertStringContainsString('INDEX idx_email (email)', $sql);
    }

    public function testCompositeIndex(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->integer('user_id')
            ->integer('faq_id')
            ->index('idx_user_faq', ['user_id', 'faq_id'])
            ->build();

        $this->assertStringContainsString('INDEX idx_user_faq (user_id, faq_id)', $sql);
    }

    public function testUniqueIndex(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->varchar('slug', 255)
            ->uniqueIndex('idx_slug', 'slug')
            ->build();

        $this->assertStringContainsString('UNIQUE INDEX idx_slug (slug)', $sql);
    }

    public function testBuildIndexStatements(): void
    {
        // Use PostgreSQL builder since MySQL inlines indexes in CREATE TABLE
        $postgresBuilder = new TableBuilder(new PostgresDialect());
        $postgresBuilder
            ->table('test', false)
            ->varchar('email', 255)
            ->index('idx_email', 'email');

        $statements = $postgresBuilder->buildIndexStatements();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('CREATE INDEX idx_email ON test', $statements[0]);
    }

    public function testBuildIndexStatementsForMySqlReturnsEmpty(): void
    {
        // MySQL should return empty array since indexes are inlined in CREATE TABLE
        $this->builder
            ->table('test', false)
            ->varchar('email', 255)
            ->index('idx_email', 'email');

        $statements = $this->builder->buildIndexStatements();

        $this->assertCount(0, $statements);
    }

    public function testBigIntegerColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->bigInteger('big_id', false)
            ->build();

        $this->assertStringContainsString('big_id BIGINT NOT NULL', $sql);
    }

    public function testSmallIntegerColumn(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->smallInteger('small_val', true)
            ->build();

        $this->assertStringContainsString('small_val SMALLINT NULL', $sql);
    }

    public function testCreateTableSuffix(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->integer('id', false)
            ->build();

        $this->assertStringContainsString('ENGINE = InnoDB', $sql);
        $this->assertStringContainsString('utf8mb4', $sql);
    }

    public function testMysqlDialectIncludesInlineIndexes(): void
    {
        $builder = new TableBuilder(new MysqlDialect());
        $sql = $builder->table('test', false)->varchar('email', 255)->index('idx_email', 'email')->build();

        // MySQL should have inline INDEX in CREATE TABLE
        $this->assertStringContainsString('INDEX idx_email (email)', $sql);
    }

    public function testPostgresDialectDoesNotIncludeInlineIndexes(): void
    {
        $builder = new TableBuilder(new PostgresDialect());
        $sql = $builder->table('test', false)->varchar('email', 255)->index('idx_email', 'email')->build();

        // PostgreSQL should NOT have inline INDEX in CREATE TABLE
        $this->assertStringNotContainsString('INDEX idx_email', $sql);
    }

    public function testSqliteDialectDoesNotIncludeInlineIndexes(): void
    {
        $builder = new TableBuilder(new SqliteDialect());
        $sql = $builder->table('test', false)->varchar('email', 255)->index('idx_email', 'email')->build();

        // SQLite should NOT have inline INDEX in CREATE TABLE
        $this->assertStringNotContainsString('INDEX idx_email', $sql);
    }

    public function testBuildIndexStatementsWorksForAllDialects(): void
    {
        // Test PostgreSQL
        $postgresBuilder = new TableBuilder(new PostgresDialect());
        $postgresBuilder->table('test', false)->varchar('email', 255)->index('idx_email', 'email');

        $statements = $postgresBuilder->buildIndexStatements();
        $this->assertCount(1, $statements);
        $this->assertStringContainsString('CREATE INDEX', $statements[0]);

        // Test SQLite
        $sqliteBuilder = new TableBuilder(new SqliteDialect());
        $sqliteBuilder->table('test', false)->varchar('email', 255)->index('idx_email', 'email');

        $statements = $sqliteBuilder->buildIndexStatements();
        $this->assertCount(1, $statements);
        $this->assertStringContainsString('CREATE INDEX', $statements[0]);
    }

    public function testVarcharWithQuotesInDefault(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->varchar('author', 100, true, "O'Reilly")
            ->build();

        // Single quotes should be escaped as ''
        $this->assertStringContainsString("DEFAULT 'O''Reilly'", $sql);
    }

    public function testCharWithQuotesInDefault(): void
    {
        $sql = $this->builder
            ->table('test', false)
            ->char('flag', 1, true, "Y'all")
            ->build();

        // Single quotes should be escaped as ''
        $this->assertStringContainsString("DEFAULT 'Y''all'", $sql);
    }

    public function testSqliteAutoIncrementDoesNotDuplicatePrimaryKey(): void
    {
        $sqliteBuilder = new TableBuilder(new SqliteDialect());
        $sql = $sqliteBuilder
            ->table('test', false)
            ->autoIncrement('id')
            ->varchar('name', 100)
            ->build();

        // SQLite autoIncrement already includes PRIMARY KEY
        $this->assertStringContainsString('INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT', $sql);

        // Should NOT have a duplicate "PRIMARY KEY (id)" clause
        // Count occurrences of "PRIMARY KEY" - should be exactly 1
        $count = substr_count($sql, 'PRIMARY KEY');
        $this->assertEquals(1, $count, 'Should have exactly one PRIMARY KEY clause, not duplicate');
    }

    public function testAutoIncrementSkipsExplicitPrimaryKey(): void
    {
        $sqliteBuilder = new TableBuilder(new SqliteDialect());
        $sql = $sqliteBuilder
            ->table('test', false)
            ->autoIncrement('id')
            ->varchar('name', 100)
            ->primaryKey('id') // This should be ignored when autoIncrement is used
            ->build();

        // Should still only have one PRIMARY KEY (from autoIncrement)
        $count = substr_count($sql, 'PRIMARY KEY');
        $this->assertEquals(1, $count, 'Should skip explicit PRIMARY KEY when AUTO_INCREMENT exists');
    }
}
