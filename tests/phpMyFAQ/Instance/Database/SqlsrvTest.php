<?php

declare(strict_types=1);

namespace phpMyFAQ\Instance\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SqlsrvTest extends TestCase
{
    private Configuration $configuration;
    private DatabaseDriver $dbMock;
    private Sqlsrv $sqlsrv;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration = $this->createStub(Configuration::class);
        $this->configuration->method('getDb')->willReturn($this->dbMock);
        $this->sqlsrv = new Sqlsrv($this->configuration);
    }

    public function testImplementsDriverInterface(): void
    {
        $this->assertInstanceOf(DriverInterface::class, $this->sqlsrv);
    }

    public function testCreateTablesWithoutPrefixSucceeds(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $result = $this->sqlsrv->createTables();

        $this->assertTrue($result);
        $this->assertNotEmpty($executedQueries);
        $this->assertStringContainsString('CREATE TABLE faqadminlog', $executedQueries[0]);
    }

    public function testUserContentColumnsUseUnicodeCapableTypes(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->sqlsrv->createTables();

        foreach (['faqconfig', 'faqcustompages', 'faqseo', 'faquser'] as $tableName) {
            $tableQueries = array_filter(
                $executedQueries,
                static fn(string $query): bool => str_contains($query, 'CREATE TABLE ' . $tableName),
            );

            $this->assertNotEmpty($tableQueries, "Missing CREATE TABLE for: $tableName");

            foreach ($tableQueries as $query) {
                $this->assertDoesNotMatchRegularExpression(
                    '/(?<!N)VARCHAR/',
                    $query,
                    "Lossy VARCHAR column in $tableName",
                );
                $this->assertDoesNotMatchRegularExpression('/\bTEXT\b/', $query, "Lossy TEXT column in $tableName");
            }
        }
    }

    /**
     * INT(11) is invalid T-SQL and "timestamp" is an alias for rowversion on SQL Server,
     * not a date type — both made the fresh-install CREATE TABLE statements fail.
     */
    public function testBackupAndBookmarkTablesUseValidSqlServerTypes(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->sqlsrv->createTables();

        foreach (['faqbackup', 'faqbookmarks'] as $tableName) {
            $tableQueries = array_filter(
                $executedQueries,
                static fn(string $query): bool => str_contains($query, 'CREATE TABLE ' . $tableName),
            );

            $this->assertNotEmpty($tableQueries, "Missing CREATE TABLE for: $tableName");

            foreach ($tableQueries as $query) {
                $this->assertStringNotContainsString('INT(11)', $query, "Invalid INT(11) in $tableName");
                $this->assertDoesNotMatchRegularExpression(
                    '/\btimestamp\b/i',
                    $query,
                    "Rowversion timestamp column in $tableName",
                );
            }
        }
    }

    public function testCreateTablesWithPrefixAppliesPrefix(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $result = $this->sqlsrv->createTables('pmf_');

        $this->assertTrue($result);

        foreach ($executedQueries as $query) {
            $this->assertMatchesRegularExpression('/pmf_faq/', $query);
        }
    }

    public function testCreateTablesReturnsFalseOnQueryFailure(): void
    {
        $this->dbMock->method('query')->willReturn(false);

        $result = $this->sqlsrv->createTables();

        $this->assertFalse($result);
    }

    public function testCreateTablesStopsOnFirstFailure(): void
    {
        $queryCount = 0;
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function () use (&$queryCount) {
                $queryCount++;
                return false;
            });

        $this->sqlsrv->createTables();

        $this->assertSame(1, $queryCount);
    }

    public function testCreateTablesReturnsFalseOnMidwayFailure(): void
    {
        $queryCount = 0;
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function () use (&$queryCount) {
                $queryCount++;
                return $queryCount <= 5 ? true : false;
            });

        $result = $this->sqlsrv->createTables();

        $this->assertFalse($result);
        $this->assertSame(6, $queryCount);
    }

    public function testCreateTablesCreatesAllExpectedTables(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->sqlsrv->createTables();

        $expectedTableNames = [
            'faqadminlog',
            'faqattachment',
            'faqattachment_file',
            'faqbackup',
            'faqbookmarks',
            'faqcaptcha',
            'faqcategories',
            'faqcategoryrelations',
            'faqconfig',
            'faqdata',
            'faqdata_revisions',
            'faqglossary',
            'faqnews',
            'faqquestions',
            'faqright',
            'faqsearches',
            'faqseo',
            'faqsessions',
            'faqstopwords',
            'faqtags',
            'faquser',
            'faquserdata',
            'faquserlogin',
            'faqvisits',
            'faqvoting',
            'faqchat_messages',
        ];

        $allQueries = implode("\n", $executedQueries);
        foreach ($expectedTableNames as $tableName) {
            $this->assertStringContainsString($tableName, $allQueries, "Missing table: $tableName");
        }
    }

    /**
     * Regression guard for the editorial workflow migration (Task 7): a freshly created
     * multi-site instance must get the same faqdata/faqdata_revisions shape as the main
     * schema (DatabaseSchema.php) -- a "status" column, no legacy "active" column -- or
     * FaqRepository::insert()/update() fail against it with an unknown-column error.
     */
    public function testCreateTablesFaqdataAndRevisionsUseStatusNotActive(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->sqlsrv->createTables();

        $faqdataQuery = null;
        $faqdataRevisionsQuery = null;
        foreach ($executedQueries as $query) {
            if (str_contains($query, 'CREATE TABLE faqdata (')) {
                $faqdataQuery = $query;
            }
            if (str_contains($query, 'CREATE TABLE faqdata_revisions (')) {
                $faqdataRevisionsQuery = $query;
            }
        }

        $this->assertNotNull($faqdataQuery, 'Expected a CREATE TABLE faqdata statement.');
        $this->assertNotNull($faqdataRevisionsQuery, 'Expected a CREATE TABLE faqdata_revisions statement.');

        foreach ([$faqdataQuery, $faqdataRevisionsQuery] as $query) {
            $this->assertStringContainsString('status', $query);
            $this->assertDoesNotMatchRegularExpression('/\bactive\b/i', $query);
        }
    }

    public function testCreateTablesIncludesIndexStatements(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->sqlsrv->createTables();

        $allQueries = implode("\n", $executedQueries);
        $this->assertStringContainsString('CREATE INDEX', $allQueries);
        $this->assertStringContainsString('idx_records', $allQueries);
        $this->assertStringContainsString('idx_time', $allQueries);
    }

    public function testCreateTablesWithSchemaCreatesSchema(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $result = $this->sqlsrv->createTables('', 'tenant1');

        $this->assertTrue($result);
        $this->assertStringContainsString("sys.schemas WHERE name = 'tenant1'", $executedQueries[0]);
        $this->assertStringContainsString('CREATE SCHEMA [tenant1]', $executedQueries[0]);
    }

    public function testCreateTablesWithSchemaRejectsSqlInjection(): void
    {
        $result = $this->sqlsrv->createTables('', 'tenant;DROP');

        $this->assertFalse($result);
    }

    public function testCreateTablesWithEmptySchemaSkipsSchemaCreation(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->sqlsrv->createTables('', '');

        foreach ($executedQueries as $query) {
            $this->assertStringNotContainsString('CREATE SCHEMA', $query);
        }
    }

    public function testCreateTablesWithNullSchemaSkipsSchemaCreation(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->sqlsrv->createTables('', null);

        foreach ($executedQueries as $query) {
            $this->assertStringNotContainsString('CREATE SCHEMA', $query);
        }
    }

    public function testCreateTablesWithSchemaReturnsFalseOnCreateSchemaFailure(): void
    {
        $this->dbMock->method('query')->willReturn(false);

        $result = $this->sqlsrv->createTables('', 'tenant1');

        $this->assertFalse($result);
    }
}
