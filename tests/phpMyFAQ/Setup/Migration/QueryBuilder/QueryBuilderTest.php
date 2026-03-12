<?php

namespace phpMyFAQ\Setup\Migration\QueryBuilder;

use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\MysqlDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(QueryBuilder::class)]
#[UsesClass(Database::class)]
#[UsesClass(TableBuilder::class)]
#[UsesClass(AlterTableBuilder::class)]
#[UsesClass(DialectFactory::class)]
#[UsesClass(MysqlDialect::class)]
#[UsesClass(SqliteDialect::class)]
final class QueryBuilderTest extends TestCase
{
    private ?string $originalTablePrefix;
    private string $originalDatabaseType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTablePrefix = Database::getTablePrefix();
        $this->originalDatabaseType = $this->getDatabaseType();

        Database::setTablePrefix('faq_');
    }

    protected function tearDown(): void
    {
        Database::setTablePrefix($this->originalTablePrefix ?? '');
        $this->setDatabaseType($this->originalDatabaseType);

        parent::tearDown();
    }

    public function testCreateTableReturnsBuilderWithPrefixedTable(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $sql = $builder->createTable('content')->integer('id', false)->primaryKey('id')->build();

        $this->assertInstanceOf(TableBuilder::class, $builder->createTable('content'));
        $this->assertStringContainsString('CREATE TABLE faq_content', $sql);
    }

    public function testCreateTableCanSkipPrefix(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $sql = $builder->createTable('content', false)->integer('id', false)->primaryKey('id')->build();

        $this->assertStringContainsString('CREATE TABLE content', $sql);
        $this->assertStringNotContainsString('CREATE TABLE faq_content', $sql);
    }

    public function testCreateTableIfNotExistsReturnsConfiguredBuilder(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $sql = $builder->createTableIfNotExists('logs')->integer('id', false)->primaryKey('id')->build();

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS faq_logs', $sql);
    }

    public function testAlterTableReturnsBuilderWithPrefixedTable(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $statements = $builder->alterTable('content')->addInteger('views', false, 0)->build();

        $this->assertInstanceOf(AlterTableBuilder::class, $builder->alterTable('content'));
        $this->assertSame(['ALTER TABLE faq_content ADD COLUMN views INT NOT NULL DEFAULT 0'], $statements);
    }

    public function testAlterTableCanSkipPrefix(): void
    {
        $builder = new QueryBuilder(new SqliteDialect());

        $statements = $builder->alterTable('content', false)->addText('notes')->build();

        $this->assertSame(['ALTER TABLE content ADD COLUMN notes TEXT NULL'], $statements);
    }

    public function testDropTableReturnsPrefixedStatement(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $this->assertSame('DROP TABLE faq_archive', $builder->dropTable('archive'));
    }

    public function testDropTableCanSkipPrefix(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $this->assertSame('DROP TABLE archive', $builder->dropTable('archive', false));
    }

    public function testDropTableIfExistsReturnsPrefixedStatement(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $this->assertSame('DROP TABLE IF EXISTS faq_archive', $builder->dropTableIfExists('archive'));
    }

    public function testDropTableIfExistsCanSkipPrefix(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $this->assertSame('DROP TABLE IF EXISTS archive', $builder->dropTableIfExists('archive', false));
    }

    public function testCreateIndexDelegatesToDialectWithPrefixedTable(): void
    {
        $dialect = $this->createMock(DialectInterface::class);
        $dialect
            ->expects($this->once())
            ->method('createIndex')
            ->with('idx_title', 'faq_articles', ['title', 'slug'], false)
            ->willReturn('CREATE INDEX idx_title ON faq_articles (title, slug)');

        $builder = new QueryBuilder($dialect);

        $this->assertSame('CREATE INDEX idx_title ON faq_articles (title, slug)', $builder->createIndex(
            'idx_title',
            'articles',
            ['title', 'slug'],
        ));
    }

    public function testCreateIndexIfNotExistsDelegatesToDialectWithoutPrefixWhenRequested(): void
    {
        $dialect = $this->createMock(DialectInterface::class);
        $dialect
            ->expects($this->once())
            ->method('createIndex')
            ->with('idx_title', 'articles', ['title'], true)
            ->willReturn('CREATE INDEX IF NOT EXISTS idx_title ON articles (title)');

        $builder = new QueryBuilder($dialect);

        $this->assertSame('CREATE INDEX IF NOT EXISTS idx_title ON articles (title)', $builder->createIndexIfNotExists(
            'idx_title',
            'articles',
            'title',
            false,
        ));
    }

    public function testDropIndexDelegatesToDialectWithPrefixedTable(): void
    {
        $dialect = $this->createMock(DialectInterface::class);
        $dialect
            ->expects($this->once())
            ->method('dropIndex')
            ->with('idx_title', 'faq_articles')
            ->willReturn('DROP INDEX idx_title');

        $builder = new QueryBuilder($dialect);

        $this->assertSame('DROP INDEX idx_title', $builder->dropIndex('idx_title', 'articles'));
    }

    public function testGetDialectReturnsInjectedDialect(): void
    {
        $dialect = new MysqlDialect();
        $builder = new QueryBuilder($dialect);

        $this->assertSame($dialect, $builder->getDialect());
    }

    public function testGetTablePrefixReturnsCurrentPrefix(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $this->assertSame('faq_', $builder->getTablePrefix());
    }

    public function testTableReturnsPrefixedName(): void
    {
        $builder = new QueryBuilder(new MysqlDialect());

        $this->assertSame('faq_faqdata', $builder->table('faqdata'));
    }

    public function testConstructorUsesDialectFactoryWhenNoDialectIsInjected(): void
    {
        $this->setDatabaseType('pdo_sqlite');

        $builder = new QueryBuilder();

        $this->assertInstanceOf(SqliteDialect::class, $builder->getDialect());
    }

    private function getDatabaseType(): string
    {
        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty('dbType');
        $property->setValue(null, $property->getValue());

        return (string) $property->getValue();
    }

    private function setDatabaseType(string $databaseType): void
    {
        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty('dbType');
        $property->setValue(null, $databaseType);
    }
}
