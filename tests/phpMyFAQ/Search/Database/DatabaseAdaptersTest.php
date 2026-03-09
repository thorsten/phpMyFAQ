<?php

namespace phpMyFAQ\Search\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Search\SearchDatabase;
use phpMyFAQ\Strings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DatabaseAdaptersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Strings::init('en');
    }

    /**
     * @return array<string, array{0: class-string<SearchDatabase>}>
     */
    public static function adapterClassesProvider(): array
    {
        return [
            'mysqli' => [Mysqli::class],
            'pdo-mysql' => [PdoMysql::class],
            'pdo-pgsql' => [PdoPgsql::class],
            'pdo-sqlite' => [PdoSqlite::class],
            'pdo-sqlsrv' => [PdoSqlsrv::class],
            'pgsql' => [Pgsql::class],
            'sqlite3' => [Sqlite3::class],
            'sqlsrv' => [Sqlsrv::class],
        ];
    }

    /**
     * @return array<string, array{0: class-string<SearchDatabase>}>
     */
    public static function likeAdaptersProvider(): array
    {
        return [
            'pdo-sqlite' => [PdoSqlite::class],
            'pdo-sqlsrv' => [PdoSqlsrv::class],
            'sqlite3' => [Sqlite3::class],
            'sqlsrv' => [Sqlsrv::class],
        ];
    }

    /**
     * @return array<string, array{0: class-string<SearchDatabase>}>
     */
    public static function mysqlAdaptersProvider(): array
    {
        return [
            'mysqli' => [Mysqli::class],
            'pdo-mysql' => [PdoMysql::class],
        ];
    }

    /**
     * @return array<string, array{0: class-string<SearchDatabase>}>
     */
    public static function pgsqlAdaptersProvider(): array
    {
        return [
            'pdo-pgsql' => [PdoPgsql::class],
            'pgsql' => [Pgsql::class],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('adapterClassesProvider')]
    public function testAdaptersUseParentSearchForNumericSolutionIds(string $adapterClass): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(static fn(string $query): bool => str_contains($query, '= 42')))
            ->willReturn('numeric-result');

        $configuration = $this->createConfiguration($db, [
            'search.searchForSolutionId' => true,
            'search.enableRelevance' => true,
            'search.relevance' => 'thema,content,keywords',
        ]);

        $adapter = new $adapterClass($configuration);
        $this->configureAdapter($adapter);

        $this->assertSame('numeric-result', $adapter->search('42'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('likeAdaptersProvider')]
    public function testLikeAdaptersBuildMatchClauseQueries(string $adapterClass): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(static function (string $query): bool {
                return (
                    str_contains($query, "fd.thema LIKE '%thorsten%'")
                    && str_contains($query, "fd.thema LIKE '%rinne%'")
                    && str_contains($query, "fd.active = 'yes'")
                );
            }))
            ->willReturn('like-result');

        $configuration = $this->createConfiguration($db, [
            'search.searchForSolutionId' => false,
            'search.enableRelevance' => false,
            'search.relevance' => 'thema,content,keywords',
        ]);

        $adapter = new $adapterClass($configuration);
        $this->configureAdapter($adapter);

        $this->assertSame('like-result', $adapter->search('thorsten rinne'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mysqlAdaptersProvider')]
    public function testMysqlAdaptersBuildRelevanceQueryAndFallbackWhenNoRows(string $adapterClass): void
    {
        $queries = [];
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db->method('numRows')->willReturn(0);
        $db->method('query')->willReturnCallback(static function (string $query) use (&$queries): string {
            $queries[] = $query;

            return count($queries) === 1 ? 'first-result' : 'fallback-result';
        });

        $configuration = $this->createConfiguration($db, [
            'search.searchForSolutionId' => false,
            'search.enableRelevance' => true,
            'search.relevance' => 'thema,content,keywords',
        ]);

        $adapter = new $adapterClass($configuration);
        $this->configureAdapter($adapter);

        $this->assertSame('fallback-result', $adapter->search('test…'));
        $this->assertCount(2, $queries);
        $this->assertStringContainsString('MATCH (fd.thema, fd.content, fd.keywords) AGAINST', $queries[0]);
        $this->assertStringContainsString('ORDER BY score DESC', $queries[0]);
        $this->assertStringContainsString("fd.thema LIKE '%test...%'", $queries[1]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mysqlAdaptersProvider')]
    public function testMysqlAdaptersDoNotOrderByScoreWhenRelevanceIsDisabled(string $adapterClass): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db->method('numRows')->willReturn(1);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(static function (string $query): bool {
                return (
                    str_contains($query, 'MATCH (fd.thema, fd.content, fd.keywords) AGAINST')
                    && !str_contains($query, 'ORDER BY score DESC')
                );
            }))
            ->willReturn('no-relevance-result');

        $configuration = $this->createConfiguration($db, [
            'search.searchForSolutionId' => false,
            'search.enableRelevance' => false,
            'search.relevance' => 'thema,content,keywords',
        ]);

        $adapter = new $adapterClass($configuration);
        $this->configureAdapter($adapter);

        $this->assertSame('no-relevance-result', $adapter->search('thorsten'));
        $this->assertSame(
            "MATCH (fd.thema, fd.content, fd.keywords) AGAINST ('thorsten' IN BOOLEAN MODE) as score",
            $adapter->setRelevanceRanking('thorsten'),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pgsqlAdaptersProvider')]
    public function testPgsqlAdaptersBuildRelevanceColumnsAndOrder(string $adapterClass): void
    {
        $configuration = $this->createConfiguration($this->createStub(DatabaseDriver::class), [
            'search.enableRelevance' => true,
            'search.relevance' => 'thema,keywords',
        ]);

        $adapter = new $adapterClass($configuration);
        $this->configureAdapter($adapter);

        $resultColumns = $adapter->getMatchingColumnsAsResult();

        $this->assertStringContainsString('AS relevance_thema', $resultColumns);
        $this->assertStringContainsString('AS relevance_keywords', $resultColumns);
        $this->assertStringNotContainsString('relevance_content', $resultColumns);
        $this->assertSame('relevance_thema DESC, relevance_keywords DESC', $adapter->getMatchingOrder());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pgsqlAdaptersProvider')]
    public function testPgsqlAdaptersBuildMatchingColumnsForRelevanceAndFallback(string $adapterClass): void
    {
        $configuration = $this->createConfiguration($this->createStub(DatabaseDriver::class), [
            'search.enableRelevance' => true,
            'search.relevance' => 'thema,content,keywords',
        ]);

        $adapter = new $adapterClass($configuration);
        $this->configureAdapter($adapter);

        $this->assertStringContainsString("to_tsvector(coalesce(fd.thema,''))", $adapter->getMatchingColumns());
        $this->assertStringContainsString(
            "fd.thema || ' ' || fd.content || ' ' || fd.keywords",
            $adapter->getMatchingColumns(),
        );

        $configurationNoRelevance = $this->createConfiguration($this->createStub(DatabaseDriver::class), [
            'search.enableRelevance' => false,
            'search.relevance' => 'thema,content,keywords',
        ]);
        $adapterNoRelevance = new $adapterClass($configurationNoRelevance);
        $this->configureAdapter($adapterNoRelevance);

        $this->assertSame(
            "fd.thema || ' ' || fd.content || ' ' || fd.keywords",
            $adapterNoRelevance->getMatchingColumns(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pgsqlAdaptersProvider')]
    public function testPgsqlAdaptersBuildTsQuerySearches(string $adapterClass): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnArgument(0);
        $db
            ->expects($this->once())
            ->method('query')
            ->with($this->callback(static function (string $query): bool {
                return (
                    str_contains($query, "plainto_tsquery('thorsten') query")
                    && str_contains($query, 'ORDER BY relevance_thema DESC')
                    && str_contains($query, "ILIKE ('%thorsten%')")
                );
            }))
            ->willReturn('pgsql-result');

        $configuration = $this->createConfiguration($db, [
            'search.searchForSolutionId' => false,
            'search.enableRelevance' => true,
            'search.relevance' => 'thema',
        ]);

        $adapter = new $adapterClass($configuration);
        $this->configureAdapter($adapter);

        $this->assertSame('pgsql-result', $adapter->search('thorsten'));
    }

    private function configureAdapter(SearchDatabase $adapter): void
    {
        $adapter
            ->setTable('faqdata fd')
            ->setJoinedTable('faqcategoryrelations fcr')
            ->setJoinedColumns(['fd.id = fcr.record_id', 'fd.lang = fcr.record_lang'])
            ->setResultColumns([
                'fd.id AS id',
                'fd.lang AS lang',
                'fd.thema AS question',
            ])
            ->setMatchingColumns(['fd.thema', 'fd.content', 'fd.keywords'])
            ->setConditions([
                'fd.active' => "'yes'",
                'fcr.category_id' => [1, 2],
            ]);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createConfiguration(DatabaseDriver $db, array $values): Configuration
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($db);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $values[$item] ?? null);

        return $configuration;
    }
}
