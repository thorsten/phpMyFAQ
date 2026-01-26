<?php

namespace phpMyFAQ\Setup\Migration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Concrete implementation of AbstractMigration for testing.
 */
#[AllowMockObjectsWithoutExpectations]
readonly class TestMigration extends AbstractMigration
{
    public function getVersion(): string
    {
        return '1.0.0-test';
    }

    public function getDescription(): string
    {
        return 'Test migration';
    }

    public function up(OperationRecorder $recorder): void
    {
        // Empty for testing
    }

    // Expose protected methods for testing
    public function exposeTable(string $name): string
    {
        return $this->table($name);
    }

    public function exposeIsDbType(string|array $types): bool
    {
        return $this->isDbType($types);
    }

    public function exposeIsMySql(): bool
    {
        return $this->isMySql();
    }

    public function exposeIsPostgreSql(): bool
    {
        return $this->isPostgreSql();
    }

    public function exposeIsSqlite(): bool
    {
        return $this->isSqlite();
    }

    public function exposeIsSqlServer(): bool
    {
        return $this->isSqlServer();
    }

    public function exposeAddColumn(string $table, string $column, string $type, ?string $default = null): string
    {
        return $this->addColumn($table, $column, $type, $default);
    }

    public function exposeDropColumn(string $table, string $column): string
    {
        return $this->dropColumn($table, $column);
    }

    public function exposeDropColumns(string $table, array $columns): string
    {
        return $this->dropColumns($table, $columns);
    }

    public function exposeCreateIndex(string $table, string $indexName, string|array $columns): string
    {
        return $this->createIndex($table, $indexName, $columns);
    }

    public function exposeDropTable(string $table): string
    {
        return $this->dropTable($table);
    }

    public function exposeDropTableIfExists(string $table): string
    {
        return $this->dropTableIfExists($table);
    }

    public function exposeUpdateLanguageCode(string $table, string $column, string $oldCode, string $newCode): string
    {
        return $this->updateLanguageCode($table, $column, $oldCode, $newCode);
    }

    public function exposeIntegerType(): string
    {
        return $this->integerType();
    }

    public function exposeTextType(): string
    {
        return $this->textType();
    }

    public function exposeVarcharType(int $length): string
    {
        return $this->varcharType($length);
    }

    public function exposeTimestampType(bool $withDefault = true): string
    {
        return $this->timestampType($withDefault);
    }

    public function exposeBooleanType(): string
    {
        return $this->booleanType();
    }

    public function exposeAutoIncrementColumn(string $columnName = 'id'): string
    {
        return $this->autoIncrementColumn($columnName);
    }

    public function exposeIndexExists(string $table, string $indexName): string
    {
        return $this->indexExists($table, $indexName);
    }
}

#[AllowMockObjectsWithoutExpectations]
class AbstractMigrationTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetDependenciesReturnsEmptyArrayByDefault(): void
    {
        $migration = new TestMigration($this->configuration);

        $this->assertEquals([], $migration->getDependencies());
    }

    public function testIsReversibleReturnsFalseByDefault(): void
    {
        $migration = new TestMigration($this->configuration);

        $this->assertFalse($migration->isReversible());
    }

    public function testDownDoesNothingByDefault(): void
    {
        $migration = new TestMigration($this->configuration);
        $recorder = $this->createMock(OperationRecorder::class);

        // Should not throw
        $migration->down($recorder);
        $this->assertTrue(true);
    }

    public function testGetChecksum(): void
    {
        $migration = new TestMigration($this->configuration);

        $checksum = $migration->getChecksum();

        $this->assertNotEmpty($checksum);
        $this->assertEquals(64, strlen($checksum)); // SHA256 produces 64 char hex
    }

    public function testGetChecksumIsConsistent(): void
    {
        $migration1 = new TestMigration($this->configuration);
        $migration2 = new TestMigration($this->configuration);

        $this->assertEquals($migration1->getChecksum(), $migration2->getChecksum());
    }

    public function testGetVersion(): void
    {
        $migration = new TestMigration($this->configuration);

        $this->assertEquals('1.0.0-test', $migration->getVersion());
    }

    public function testGetDescription(): void
    {
        $migration = new TestMigration($this->configuration);

        $this->assertEquals('Test migration', $migration->getDescription());
    }

    public function testDropColumn(): void
    {
        $migration = new TestMigration($this->configuration);

        $sql = $migration->exposeDropColumn('users', 'old_column');

        $this->assertStringContainsString('ALTER TABLE', $sql);
        $this->assertStringContainsString('DROP COLUMN old_column', $sql);
    }

    public function testDropColumns(): void
    {
        $migration = new TestMigration($this->configuration);

        $sql = $migration->exposeDropColumns('users', ['col1', 'col2']);

        $this->assertStringContainsString('DROP COLUMN col1', $sql);
        $this->assertStringContainsString('DROP COLUMN col2', $sql);
    }

    public function testDropTable(): void
    {
        $migration = new TestMigration($this->configuration);

        $sql = $migration->exposeDropTable('old_table');

        $this->assertStringContainsString('DROP TABLE', $sql);
    }

    public function testDropTableIfExists(): void
    {
        $migration = new TestMigration($this->configuration);

        $sql = $migration->exposeDropTableIfExists('old_table');

        $this->assertStringContainsString('DROP TABLE IF EXISTS', $sql);
    }

    public function testUpdateLanguageCode(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(fn($value) => addslashes($value));

        $this->configuration->method('getDb')->willReturn($db);

        $migration = new TestMigration($this->configuration);

        $sql = $migration->exposeUpdateLanguageCode('faq', 'lang', 'en-US', 'en');

        $this->assertStringContainsString('UPDATE', $sql);
        $this->assertStringContainsString("lang='en'", $sql);
        $this->assertStringContainsString("lang='en-US'", $sql);
    }

    public function testUpdateLanguageCodeEscapesValues(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->exactly(2))->method('escape')->willReturnCallback(fn($value) => addslashes($value));

        $this->configuration->method('getDb')->willReturn($db);

        $migration = new TestMigration($this->configuration);

        // Test with values that need escaping
        $sql = $migration->exposeUpdateLanguageCode('faq', 'lang', "en'; DROP TABLE faq;--", 'en');

        // The malicious code should be escaped with backslashes
        $this->assertStringContainsString("en\\'", $sql);
    }

    public function testIndexExistsGeneratesQuery(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(fn($value) => addslashes($value));

        $this->configuration->method('getDb')->willReturn($db);

        $migration = new TestMigration($this->configuration);
        $sql = $migration->exposeIndexExists('users', 'idx_username');

        // Should generate a query to check for index existence
        $this->assertStringContainsString('idx_username', $sql);
        $this->assertStringContainsString('users', $sql);
        $this->assertStringContainsString('COUNT', $sql);
    }

    public function testIndexExistsEscapesValues(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->exactly(2))->method('escape')->willReturnCallback(fn($value) => addslashes($value));

        $this->configuration->method('getDb')->willReturn($db);

        $migration = new TestMigration($this->configuration);

        // Test with values that need escaping
        $sql = $migration->exposeIndexExists('users', "idx'; DROP TABLE users;--");

        // The malicious code should be escaped
        $this->assertStringContainsString("\\'", $sql);
    }

    public function testCreateIndexGeneratesValidSql(): void
    {
        $migration = new TestMigration($this->configuration);

        $sql = $migration->exposeCreateIndex('users', 'idx_username', 'username');

        // Should generate a CREATE INDEX statement
        $this->assertStringContainsString('CREATE INDEX', $sql);
        $this->assertStringContainsString('idx_username', $sql);
        $this->assertStringContainsString('ON', $sql);
        $this->assertStringContainsString('username', $sql);
    }
}
