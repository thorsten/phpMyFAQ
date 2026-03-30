<?php

declare(strict_types=1);

namespace phpMyFAQ\Instance\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StopwordsTest extends TestCase
{
    private Configuration $configuration;
    private DatabaseDriver $dbMock;
    private Stopwords $stopwords;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dbMock = $this->createMock(DatabaseDriver::class);
        $this->configuration = $this->createStub(Configuration::class);
        $this->configuration->method('getDb')->willReturn($this->dbMock);
        $this->stopwords = new Stopwords($this->configuration);
    }

    public function testExecuteInsertQueriesWithoutPrefixSucceeds(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $result = $this->stopwords->executeInsertQueries();

        $this->assertTrue($result);
        $this->assertNotEmpty($executedQueries);
        $this->assertStringContainsString('INSERT INTO faqstopwords', $executedQueries[0]);
    }

    public function testExecuteInsertQueriesWithPrefixAppliesPrefix(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $result = $this->stopwords->executeInsertQueries('pmf_');

        $this->assertTrue($result);

        foreach ($executedQueries as $query) {
            $this->assertStringContainsString('pmf_faqstopwords', $query);
        }
    }

    public function testExecuteInsertQueriesReturnsFalseOnQueryFailure(): void
    {
        $this->dbMock->method('query')->willReturn(false);
        $this->dbMock->method('error')->willReturn('mock error');

        $this->expectOutputRegex('/.*/');
        $result = $this->stopwords->executeInsertQueries();

        $this->assertFalse($result);
    }

    public function testExecuteInsertQueriesStopsOnFirstFailure(): void
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
        $this->stopwords->executeInsertQueries();

        $this->assertSame(1, $queryCount);
    }

    public function testExecuteInsertQueriesContainsExpectedLanguages(): void
    {
        $executedQueries = [];
        $this->dbMock
            ->method('query')
            ->willReturnCallback(function (string $query) use (&$executedQueries) {
                $executedQueries[] = $query;
                return true;
            });

        $this->stopwords->executeInsertQueries();

        $allQueries = implode("\n", $executedQueries);
        $expectedLanguages = ['cs', 'de', 'en', 'fr', 'it', 'nl'];
        foreach ($expectedLanguages as $lang) {
            $this->assertStringContainsString("'$lang'", $allQueries, "Missing language: $lang");
        }
    }

    public function testExecuteInsertQueriesReturnsFalseOnMidwayFailure(): void
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
        $result = $this->stopwords->executeInsertQueries();

        $this->assertFalse($result);
        $this->assertSame(6, $queryCount);
    }
}
