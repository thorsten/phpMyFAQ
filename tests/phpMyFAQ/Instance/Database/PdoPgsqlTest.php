<?php

declare(strict_types=1);

namespace phpMyFAQ\Instance\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PdoPgsqlTest extends TestCase
{
    private Configuration $configuration;
    private DatabaseDriver $dbMock;
    private PdoPgsql $pdoPgsql;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration = $this->createStub(Configuration::class);
        $this->configuration->method('getDb')->willReturn($this->dbMock);
        $this->pdoPgsql = new PdoPgsql($this->configuration);
    }

    public function testImplementsDriverInterface(): void
    {
        $this->assertInstanceOf(DriverInterface::class, $this->pdoPgsql);
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

        $result = $this->pdoPgsql->createTables();

        $this->assertTrue($result);
        $this->assertNotEmpty($executedQueries);
        $this->assertStringContainsString('CREATE SEQUENCE', $executedQueries[0]);
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

        $result = $this->pdoPgsql->createTables('pmf_');

        $this->assertTrue($result);

        foreach ($executedQueries as $query) {
            $this->assertMatchesRegularExpression('/pmf_faq/', $query);
        }
    }

    public function testCreateTablesReturnsFalseOnQueryFailure(): void
    {
        $this->dbMock->method('query')->willReturn(false);

        $result = $this->pdoPgsql->createTables();

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

        $this->pdoPgsql->createTables();

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

        $result = $this->pdoPgsql->createTables();

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

        $this->pdoPgsql->createTables();

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

    public function testCreateTablesIncludesSequences(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->pdoPgsql->createTables();

        $allQueries = implode("\n", $executedQueries);
        $this->assertStringContainsString('CREATE SEQUENCE', $allQueries);
        $this->assertStringContainsString('faquser_user_id_seq', $allQueries);
        $this->assertStringContainsString('faqcategory_order_position_seq', $allQueries);
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

        $this->pdoPgsql->createTables();

        $allQueries = implode("\n", $executedQueries);
        $this->assertStringContainsString('CREATE INDEX', $allQueries);
        $this->assertStringContainsString('idx_records', $allQueries);
        $this->assertStringContainsString('idx_time', $allQueries);
    }

    public function testCreateTablesWithPrefixOnDualArgIndexStatements(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->pdoPgsql->createTables('test_');

        $allQueries = implode("\n", $executedQueries);

        // idx_records and faqsessions_idx have two %s placeholders
        $this->assertStringContainsString('idx_records_test_', $allQueries);
        $this->assertStringContainsString('idx_time_test_', $allQueries);
        $this->assertStringContainsString('ON test_faqcategoryrelations', $allQueries);
        $this->assertStringContainsString('ON test_faqsessions', $allQueries);
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

        $result = $this->pdoPgsql->createTables('', 'tenant1');

        $this->assertTrue($result);
        $this->assertStringContainsString('CREATE SCHEMA IF NOT EXISTS', $executedQueries[0]);
        $this->assertStringContainsString('"tenant1"', $executedQueries[0]);
        $this->assertStringContainsString('SET search_path TO', $executedQueries[1]);
        $this->assertStringContainsString('"tenant1"', $executedQueries[1]);
    }

    public function testCreateTablesWithSchemaRejectsSqlInjection(): void
    {
        $result = $this->pdoPgsql->createTables('', 'tenant;DROP');

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

        $this->pdoPgsql->createTables('', '');

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

        $this->pdoPgsql->createTables('', null);

        foreach ($executedQueries as $query) {
            $this->assertStringNotContainsString('CREATE SCHEMA', $query);
        }
    }

    public function testCreateTablesWithSchemaReturnsFalseOnCreateSchemaFailure(): void
    {
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) {
                if (str_contains($query, 'CREATE SCHEMA')) {
                    return false;
                }
                return true;
            });

        $result = $this->pdoPgsql->createTables('', 'tenant1');

        $this->assertFalse($result);
    }

    public function testCreateTablesWithSchemaReturnsFalseOnSetSearchPathFailure(): void
    {
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) {
                if (str_contains($query, 'SET search_path')) {
                    return false;
                }
                return true;
            });

        $result = $this->pdoPgsql->createTables('', 'tenant1');

        $this->assertFalse($result);
    }
}
