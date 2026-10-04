<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use LogicException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Records the 3.2.0-beta and 4.0.0-alpha migrations for every supported dialect and pins
 * the statements that differ between databases.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Migration320Beta::class)]
#[CoversClass(Migration400Alpha::class)]
#[UsesNamespace('phpMyFAQ')]
final class OlderMigrationsDialectsTest extends TestCase
{
    private mixed $previousDatabaseType = null;

    private string $previousTablePrefix = '';

    protected function setUp(): void
    {
        $this->previousDatabaseType = new ReflectionProperty(Database::class, 'dbType')->getValue();
        $this->previousTablePrefix = Database::getTablePrefix();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $this->previousDatabaseType);
        Database::setTablePrefix($this->previousTablePrefix);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dialectProvider(): iterable
    {
        foreach (['mysqli', 'pdo_mysql', 'pgsql', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite', 'sqlsrv', 'pdo_sqlsrv'] as $type) {
            yield $type => [$type];
        }
    }

    /**
     * @param class-string<Migration320Beta|Migration400Alpha> $migrationClass
     */
    private function queries(string $migrationClass, string $dbType): string
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);
        Database::setTablePrefix('pmf_');

        $configuration = $this->createStub(Configuration::class);
        $recorder = new OperationRecorder($configuration);
        new $migrationClass($configuration)->up($recorder);

        return implode("\n", $recorder->getSqlQueries());
    }

    #[DataProvider('dialectProvider')]
    public function testBookmarksAndStickyOrderFollowTheDialect(string $dbType): void
    {
        $joined = $this->queries(Migration400Alpha::class, $dbType);

        $this->assertSame(1, substr_count($joined, 'pmf_faqbookmarks'), 'one bookmarks statement');
        $this->assertSame(2, substr_count($joined, 'sticky_order'), 'faqdata and its revisions');

        if (in_array($dbType, ['mysqli', 'pdo_mysql'], true)) {
            $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS pmf_faqbookmarks (userid int(11)', $joined);
            $this->assertStringContainsString('ADD COLUMN sticky_order int(10) DEFAULT NULL', $joined);
        } elseif (in_array($dbType, ['pgsql', 'pdo_pgsql'], true)) {
            $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS pmf_faqbookmarks (userid INTEGER', $joined);
            $this->assertStringContainsString('ADD COLUMN IF NOT EXISTS sticky_order integer DEFAULT NULL', $joined);
        } elseif (in_array($dbType, ['sqlsrv', 'pdo_sqlsrv'], true)) {
            $this->assertStringContainsString('CREATE TABLE pmf_faqbookmarks (userid INTEGER', $joined);
            $this->assertStringNotContainsString('IF NOT EXISTS pmf_faqbookmarks', $joined);
            $this->assertStringContainsString('ADD COLUMN sticky_order integer DEFAULT NULL', $joined);
        } else {
            $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS pmf_faqbookmarks (userid INTEGER', $joined);
            $this->assertStringContainsString('ADD COLUMN sticky_order integer DEFAULT NULL', $joined);
        }
    }

    #[DataProvider('dialectProvider')]
    public function testLinkVerificationColumnsAndConfigValueTypeFollowTheDialect(string $dbType): void
    {
        $joined = $this->queries(Migration320Beta::class, $dbType);

        if (in_array($dbType, ['sqlite3', 'pdo_sqlite'], true)) {
            $this->assertStringContainsString('CREATE TABLE pmf_faqdata_new (', $joined);
            $this->assertStringContainsString('CREATE TABLE pmf_faqdata_revisions_new (', $joined);
            $this->assertStringContainsString('CREATE TABLE pmf_faqconfig_new (', $joined);
            $this->assertStringContainsString('ALTER TABLE pmf_faqconfig_new RENAME TO pmf_faqconfig', $joined);
            $this->assertStringNotContainsString('DROP COLUMN', $joined);
        } elseif (in_array($dbType, ['pgsql', 'pdo_pgsql'], true)) {
            $this->assertStringContainsString(
                'ALTER TABLE pmf_faqdata DROP COLUMN IF EXISTS links_state, DROP COLUMN IF EXISTS links_check_date',
                $joined,
            );
            $this->assertStringContainsString('ALTER TABLE pmf_faqconfig ALTER COLUMN config_value TYPE TEXT', $joined);
        } elseif (in_array($dbType, ['mysqli', 'pdo_mysql'], true)) {
            $this->assertStringContainsString('ALTER TABLE pmf_faqdata DROP COLUMN links_state', $joined);
            $this->assertStringContainsString('ALTER TABLE pmf_faqdata_revisions DROP COLUMN links_check_date', $joined);
            $this->assertStringContainsString('ALTER TABLE pmf_faqconfig MODIFY config_value TEXT DEFAULT NULL', $joined);
        } else {
            $this->assertStringContainsString('ALTER TABLE pmf_faqdata DROP COLUMN links_state', $joined);
            $this->assertStringContainsString('ALTER TABLE pmf_faqconfig ALTER COLUMN config_value NVARCHAR(MAX)', $joined);
        }
    }

    public function testSqliteTableRebuildRefusesUnknownTables(): void
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, 'sqlite3');
        $configuration = $this->createStub(Configuration::class);
        $migration = new Migration320Beta($configuration);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('rebuildTableWithoutColumns() only supports [faqdata, faqdata_revisions], got "faqconfig"');

        new ReflectionMethod($migration, 'rebuildTableWithoutColumns')->invoke(
            $migration,
            new OperationRecorder($configuration),
            'faqconfig',
        );
    }
}
