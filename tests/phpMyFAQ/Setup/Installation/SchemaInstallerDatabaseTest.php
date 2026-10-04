<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Installation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\QueryBuilder\Dialect\SqliteDialect;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

/**
 * Drops and recreates the schema on a private copy of the SQLite test database.
 */
#[CoversClass(SchemaInstaller::class)]
#[UsesNamespace('phpMyFAQ')]
final class SchemaInstallerDatabaseTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private mixed $previousDatabaseType = null;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'sqlite3');
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
    }

    private function tableCount(): int
    {
        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query("SELECT COUNT(*) AS n FROM sqlite_master WHERE type = 'table'"));

        return (int) ($row['n'] ?? 0);
    }

    public function testDropTablesRemovesEveryTableAndCreateMissingTablesBringsThemBack(): void
    {
        $installer = new SchemaInstaller($this->configuration, new SqliteDialect());
        $schemaTables = count($installer->getSchema()->getTableNames());
        $before = $this->tableCount();
        $this->assertGreaterThanOrEqual($schemaTables, $before);

        $this->assertTrue($installer->dropTables());
        $this->assertSame($before - $schemaTables, $this->tableCount());

        // Dropping again fails on the first missing table.
        $this->assertFalse($installer->dropTables());

        $installer->createMissingTables();
        $this->assertSame($before, $this->tableCount());

        // Existing tables are left alone: a second run creates nothing.
        $installer->createMissingTables();
        $this->assertSame($before, $this->tableCount());
    }

    public function testTableExistenceCheckRejectsAnUnsupportedDatabaseType(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'oracle');
        $installer = new SchemaInstaller($this->configuration, new SqliteDialect());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported database type: oracle');

        $installer->createMissingTables();
    }
}
