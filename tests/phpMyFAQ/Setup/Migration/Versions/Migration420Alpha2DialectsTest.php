<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Records the 4.2.0-alpha.2 migration for every supported database dialect. The schema
 * lookups of the migration are answered by a stubbed driver, so the dialect specific
 * CREATE TABLE statements and the SQL Server constraint handling can be pinned without
 * a server of that type.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Migration420Alpha2::class)]
#[UsesNamespace('phpMyFAQ')]
final class Migration420Alpha2DialectsTest extends TestCase
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
     * @param bool $columnsExist what every column lookup of the migration reports
     */
    private function record(string $dbType, bool $columnsExist): OperationRecorder
    {
        new ReflectionProperty(Database::class, 'dbType')->setValue(null, $dbType);
        Database::setTablePrefix('pmf_');

        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('query')->willReturn(true);
        $driver->method('numRows')->willReturn($columnsExist ? 1 : 0);
        $driver->method('escape')->willReturnArgument(0);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        $recorder = new OperationRecorder($configuration);
        new Migration420Alpha2($configuration)->up($recorder);

        return $recorder;
    }

    /**
     * @return list<string>
     */
    private function queriesMentioning(OperationRecorder $recorder, string $needle): array
    {
        return array_values(array_filter(
            $recorder->getSqlQueries(),
            static fn(string $query): bool => str_contains($query, $needle),
        ));
    }

    /**
     * @return list<string>
     */
    private function statusColumnAdditions(OperationRecorder $recorder): array
    {
        return array_values(array_filter(
            $recorder->getSqlQueries(),
            static fn(string $query): bool => preg_match(
                '/ALTER TABLE pmf_faqdata(_revisions)? ADD (COLUMN )?status /',
                $query,
            ) === 1,
        ));
    }

    public function testDependsOnTheAlphaMigration(): void
    {
        $this->assertSame(['4.2.0-alpha'], new Migration420Alpha2($this->createStub(Configuration::class))
            ->getDependencies());
    }

    #[DataProvider('dialectProvider')]
    public function testEveryDialectCreatesThePrefixedLanguageRightAndHistoryTables(string $dbType): void
    {
        $recorder = $this->record($dbType, columnsExist: false);

        foreach (['pmf_faquser_right_language', 'pmf_faqgroup_right_language', 'pmf_faqquestion_history'] as $table) {
            $creates = array_filter(
                $this->queriesMentioning($recorder, $table),
                static fn(string $query): bool => str_contains($query, 'CREATE TABLE'),
            );
            $this->assertCount(1, $creates, $dbType . ' must create ' . $table . ' exactly once');
        }

        // The status column is reported missing, so it is added to both tables and no backfill runs.
        $this->assertCount(2, $this->statusColumnAdditions($recorder));
        $this->assertSame([], $this->queriesMentioning($recorder, "CASE WHEN active = 'yes' THEN 'published'"));
    }

    #[DataProvider('dialectProvider')]
    public function testTheTableStatementsUseTheDialectSyntax(string $dbType): void
    {
        $recorder = $this->record($dbType, columnsExist: false);
        $history = $this->queriesMentioning($recorder, 'pmf_faqquestion_history');
        $joined = implode("\n", $history);

        match (true) {
            in_array($dbType, ['mysqli', 'pdo_mysql'], true) => (function () use ($joined, $history): void {
                $this->assertStringContainsString('ENGINE=InnoDB', $joined);
                $this->assertStringContainsString('id INT NOT NULL', $joined);
                $this->assertStringContainsString('INDEX idx_faqquestion_history', $joined);
                $this->assertCount(1, $history, 'MySQL creates the index inline');
            })(),
            in_array($dbType, ['pgsql', 'pdo_pgsql', 'sqlite3', 'pdo_sqlite'], true) => (function () use (
                $joined,
                $history,
            ): void {
                $this->assertStringContainsString('id INTEGER NOT NULL', $joined);
                $this->assertStringContainsString('CREATE INDEX IF NOT EXISTS idx_faqquestion_history', $joined);
                $this->assertCount(2, $history, 'PostgreSQL and SQLite create the index separately');
            })(),
            default => (function () use ($joined): void {
                $this->assertStringContainsString("IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'pmf_faqquestion_history')", $joined);
                $this->assertStringContainsString('id INTEGER NOT NULL', $joined);
            })(),
        };
    }

    #[DataProvider('dialectProvider')]
    public function testAnExistingActiveColumnIsBackfilledAndDropped(string $dbType): void
    {
        $recorder = $this->record($dbType, columnsExist: true);

        $this->assertSame([], $this->statusColumnAdditions($recorder));
        $this->assertCount(2, $this->queriesMentioning($recorder, "CASE WHEN active = 'yes' THEN 'published'"));

        $constraintDrops = $this->queriesMentioning($recorder, "AND c.name = 'active'");
        if (in_array($dbType, ['sqlsrv', 'pdo_sqlsrv'], true)) {
            // SQL Server has to drop the default constraint bound to the column first.
            $this->assertCount(2, $constraintDrops);
            $this->assertStringContainsString("OBJECT_ID('pmf_faqdata')", $constraintDrops[0]);
            $this->assertStringContainsString("OBJECT_ID('pmf_faqdata_revisions')", $constraintDrops[1]);
        } else {
            $this->assertSame([], $constraintDrops);
        }
    }
}
