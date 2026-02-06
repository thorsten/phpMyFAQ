<?php

namespace phpMyFAQ\Setup\Installation;

use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\MysqlDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\PostgresDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqlServerDialect;
use phpMyFAQ\Setup\Migration\QueryBuilder\DialectInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaTest extends TestCase
{
    private const EXPECTED_TABLE_COUNT = 44;

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
    public function testGetAllTablesReturnsExpectedCount(DialectInterface $dialect): void
    {
        $schema = new DatabaseSchema($dialect);
        $tables = $schema->getAllTables();
        $this->assertCount(self::EXPECTED_TABLE_COUNT, $tables);
    }

    #[DataProvider('dialectProvider')]
    public function testGetTableNamesReturnsCorrectNames(DialectInterface $dialect): void
    {
        $schema = new DatabaseSchema($dialect);
        $names = $schema->getTableNames();

        $this->assertContains('faqadminlog', $names);
        $this->assertContains('faqdata', $names);
        $this->assertContains('faquser', $names);
        $this->assertContains('faqconfig', $names);
        $this->assertContains('faqchat_messages', $names);
        $this->assertContains('faqcustompages', $names);
        $this->assertContains('faqseo', $names);
    }

    #[DataProvider('dialectProvider')]
    public function testAllTablesBuildValidSql(DialectInterface $dialect): void
    {
        $schema = new DatabaseSchema($dialect);
        foreach ($schema->getAllTables() as $name => $builder) {
            $sql = $builder->build();
            $this->assertStringContainsString('CREATE TABLE', $sql, "Table $name should produce CREATE TABLE SQL");
            $this->assertNotEmpty($sql, "Table $name should produce non-empty SQL");
        }
    }

    public function testFaqdataHasFulltextIndexForMysql(): void
    {
        $schema = new DatabaseSchema(new MysqlDialect());
        $sql = $schema->faqdata()->build();
        $this->assertStringContainsString('FULLTEXT (keywords,thema,content)', $sql);
    }

    public function testFaqdataHasNoFulltextIndexForPostgres(): void
    {
        $schema = new DatabaseSchema(new PostgresDialect());
        $sql = $schema->faqdata()->build();
        $this->assertStringNotContainsString('FULLTEXT', $sql);
    }

    public function testFaqattachmentFileUsesBlobForMysql(): void
    {
        $schema = new DatabaseSchema(new MysqlDialect());
        $sql = $schema->attachmentFile()->build();
        $this->assertStringContainsString('BLOB', $sql);
    }

    public function testFaqattachmentFileUsesByteaForPostgres(): void
    {
        $schema = new DatabaseSchema(new PostgresDialect());
        $sql = $schema->attachmentFile()->build();
        $this->assertStringContainsString('BYTEA', $sql);
    }

    public function testFaqdataUsesLongtextForMysql(): void
    {
        $schema = new DatabaseSchema(new MysqlDialect());
        $sql = $schema->faqdata()->build();
        $this->assertStringContainsString('LONGTEXT', $sql);
    }

    public function testFaqdataUsesTextForPostgres(): void
    {
        $schema = new DatabaseSchema(new PostgresDialect());
        $sql = $schema->faqdata()->build();
        // content should be TEXT (longText maps to TEXT in PostgreSQL)
        $this->assertStringContainsString('content TEXT', $sql);
    }

    public function testFaqchatMessagesHasAutoIncrement(): void
    {
        $schema = new DatabaseSchema(new MysqlDialect());
        $sql = $schema->faqchatMessages()->build();
        $this->assertStringContainsString('AUTO_INCREMENT', $sql);
    }

    public function testFaqchatMessagesHasIndexes(): void
    {
        $schema = new DatabaseSchema(new MysqlDialect());
        $sql = $schema->faqchatMessages()->build();
        $this->assertStringContainsString('idx_chat_sender', $sql);
        $this->assertStringContainsString('idx_chat_recipient', $sql);
        $this->assertStringContainsString('idx_chat_conversation', $sql);
        $this->assertStringContainsString('idx_chat_created', $sql);
    }

    public function testFaqsessionsHasTimeIndex(): void
    {
        $schema = new DatabaseSchema(new PostgresDialect());
        $builder = $schema->faqsessions();
        $indexStatements = $builder->buildIndexStatements();
        $this->assertCount(1, $indexStatements);
        $this->assertStringContainsString('idx_time', $indexStatements[0]);
    }

    public function testFaqsearchesHasMultipleIndexes(): void
    {
        $schema = new DatabaseSchema(new PostgresDialect());
        $builder = $schema->faqsearches();
        $indexStatements = $builder->buildIndexStatements();
        $this->assertCount(3, $indexStatements);
    }

    public function testFaqcategoryrelationsHasIndex(): void
    {
        $schema = new DatabaseSchema(new MysqlDialect());
        $sql = $schema->faqcategoryrelations()->build();
        $this->assertStringContainsString('idx_records', $sql);
    }

    #[DataProvider('dialectProvider')]
    public function testFaqconfigHasPrimaryKeyOnConfigName(DialectInterface $dialect): void
    {
        $schema = new DatabaseSchema($dialect);
        $sql = $schema->faqconfig()->build();
        $this->assertStringContainsString('PRIMARY KEY (config_name)', $sql);
    }

    #[DataProvider('dialectProvider')]
    public function testFaqcategoriesHasCompositePrimaryKey(DialectInterface $dialect): void
    {
        $schema = new DatabaseSchema($dialect);
        $sql = $schema->faqcategories()->build();
        $this->assertStringContainsString('PRIMARY KEY (id, lang)', $sql);
    }

    public function testFaqgroupHasNameIndex(): void
    {
        $schema = new DatabaseSchema(new MysqlDialect());
        $sql = $schema->faqgroup()->build();
        $this->assertStringContainsString('idx_name', $sql);
    }

    #[DataProvider('dialectProvider')]
    public function testFaqbookmarksHasNoPrimaryKey(DialectInterface $dialect): void
    {
        $schema = new DatabaseSchema($dialect);
        $sql = $schema->faqbookmarks()->build();
        $this->assertStringNotContainsString('PRIMARY KEY', $sql);
    }

    #[DataProvider('dialectProvider')]
    public function testFaqformsHasNoPrimaryKey(DialectInterface $dialect): void
    {
        $schema = new DatabaseSchema($dialect);
        $sql = $schema->faqforms()->build();
        $this->assertStringNotContainsString('PRIMARY KEY', $sql);
    }
}
