<?php

declare(strict_types=1);

namespace phpMyFAQ\Instance\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PdoMysqlTest extends TestCase
{
    private Configuration $configuration;
    private DatabaseDriver $dbMock;
    private PdoMysql $pdoMysql;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration = $this->createStub(Configuration::class);
        $this->configuration->method('getDb')->willReturn($this->dbMock);
        $this->pdoMysql = new PdoMysql($this->configuration);
    }

    public function testImplementsDriverInterface(): void
    {
        $this->assertInstanceOf(DriverInterface::class, $this->pdoMysql);
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

        $result = $this->pdoMysql->createTables();

        $this->assertTrue($result);
        $this->assertNotEmpty($executedQueries);
        $this->assertStringContainsString('CREATE TABLE faqadminlog', $executedQueries[0]);
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

        $result = $this->pdoMysql->createTables('pmf_');

        $this->assertTrue($result);

        foreach ($executedQueries as $query) {
            $this->assertMatchesRegularExpression('/pmf_faq/', $query);
        }
    }

    public function testCreateTablesReturnsFalseOnQueryFailure(): void
    {
        $this->dbMock->method('query')->willReturn(false);
        $this->dbMock->method('error')->willReturn('mock error');

        $this->expectOutputRegex('/.*/');
        $result = $this->pdoMysql->createTables();

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
        $this->dbMock->method('error')->willReturn('');

        $this->expectOutputRegex('/.*/');
        $this->pdoMysql->createTables();

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
        $this->dbMock->method('error')->willReturn('');

        $this->expectOutputRegex('/.*/');
        $result = $this->pdoMysql->createTables();

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

        $this->pdoMysql->createTables();

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

    public function testCreateTablesIncludesIndexStatements(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->pdoMysql->createTables();

        $allQueries = implode("\n", $executedQueries);
        $this->assertStringContainsString('CREATE INDEX', $allQueries);
        $this->assertStringContainsString('idx_time', $allQueries);
    }

    public function testCreateTablesWithPrefixOnIndexStatements(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->pdoMysql->createTables('test_');

        $indexQueries = array_filter($executedQueries, fn($q) => str_contains($q, 'CREATE INDEX'));
        $this->assertNotEmpty($indexQueries);

        foreach ($indexQueries as $query) {
            $this->assertStringContainsString('test_faq', $query);
        }
    }

    public function testCreateTablesWithSchemaCreatesDatabase(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $result = $this->pdoMysql->createTables('', 'tenant1');

        $this->assertTrue($result);
        $this->assertStringContainsString('CREATE DATABASE IF NOT EXISTS', $executedQueries[0]);
        $this->assertStringContainsString('`tenant1`', $executedQueries[0]);
        $this->assertStringContainsString('USE', $executedQueries[1]);
        $this->assertStringContainsString('`tenant1`', $executedQueries[1]);
    }

    public function testCreateTablesWithSchemaRejectsSqlInjection(): void
    {
        $result = $this->pdoMysql->createTables('', 'tenant;DROP');

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

        $this->pdoMysql->createTables('', '');

        foreach ($executedQueries as $query) {
            $this->assertStringNotContainsString('CREATE DATABASE', $query);
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

        $this->pdoMysql->createTables('', null);

        foreach ($executedQueries as $query) {
            $this->assertStringNotContainsString('CREATE DATABASE', $query);
        }
    }

    public function testCreateTablesWithSchemaReturnsFalseOnCreateDatabaseFailure(): void
    {
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) {
                if (str_contains($query, 'CREATE DATABASE')) {
                    return false;
                }
                return true;
            });

        $result = $this->pdoMysql->createTables('', 'tenant1');

        $this->assertFalse($result);
    }

    public function testCreateTablesWithSchemaReturnsFalseOnUseFailure(): void
    {
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) {
                if (str_contains($query, 'USE')) {
                    return false;
                }
                return true;
            });

        $result = $this->pdoMysql->createTables('', 'tenant1');

        $this->assertFalse($result);
    }
}
