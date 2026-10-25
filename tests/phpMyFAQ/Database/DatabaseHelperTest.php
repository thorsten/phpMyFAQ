<?php

namespace phpMyFAQ\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\System;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class DatabaseHelperTest
 *
 * @package phpMyFAQ
 */
#[AllowMockObjectsWithoutExpectations]
class DatabaseHelperTest extends TestCase
{
    use TestDatabaseTrait;

    /** @var DatabaseHelper */
    private DatabaseHelper $databaseHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = $this->connectToTestDatabaseCopy(new Sqlite3());
        $dbHandle->query(
            'CREATE TABLE faqtest (name VARCHAR(255) NOT NULL, testvalue VARCHAR(255) DEFAULT NULL, PRIMARY KEY (name))',
        );
        $dbHandle->query("INSERT INTO faqtest (name,testvalue) VALUES ('foo','bar')");
        $dbHandle->query("INSERT INTO faqtest (name,testvalue) VALUES ('bar','baz')");

        $configuration = new Configuration($dbHandle);
        $configuration->set('main.currentVersion', System::getVersion());

        $this->databaseHelper = new DatabaseHelper($configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $dbHandle = $this->connectToTestDatabaseCopy(new Sqlite3());
        $dbHandle->query('DROP TABLE faqtest');
    }

    public function testBuildInsertQueries(): void
    {
        $table = 'faqtest';
        $queries = $this->databaseHelper->buildInsertQueries('SELECT * FROM ' . $table, $table);

        $expected = [
            "\r\n-- Table: faqtest",
            "INSERT INTO faqtest (name,testvalue) VALUES ('foo','bar');",
            "INSERT INTO faqtest (name,testvalue) VALUES ('bar','baz');",
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testAlignTablePrefixRewritesDeleteStatements(): void
    {
        $query = 'DELETE FROM old_faqdata WHERE id = 1';

        $result = DatabaseHelper::alignTablePrefix($query, 'old_', 'new_');

        $this->assertSame('DELETE FROM new_faqdata WHERE id = 1', $result);
    }

    public function testAlignTablePrefixRewritesInsertStatements(): void
    {
        $query = "INSERT INTO old_faqconfig (name) VALUES ('main')";

        $result = DatabaseHelper::alignTablePrefix($query, 'old_', 'new_');

        $this->assertSame("INSERT INTO new_faqconfig (name) VALUES ('main')", $result);
    }

    public function testAlignTablePrefixLeavesOtherStatementsUntouched(): void
    {
        $query = 'SELECT * FROM old_faqdata';

        $result = DatabaseHelper::alignTablePrefix($query, 'old_', 'new_');

        $this->assertSame($query, $result);
    }

    public function testBuildInsertQueriesReturnsNothingWhenTheQueryFails(): void
    {
        $queries = $this->databaseHelper->buildInsertQueries('SELECT * FROM no_such_table', 'no_such_table');

        $this->assertSame([], $queries);
    }

    public function testBuildInsertQueriesRendersNumericAndNullValuesAndSkipsPositionalKeys(): void
    {
        $rows = [
            [0 => 'positional duplicate', 'name' => 'foo', 'amount' => '42', 'rights' => '7', 'note' => null],
            [],
        ];
        $driver = $this->createStub(DatabaseDriver::class);
        $driver->method('query')->willReturn(true);
        $driver->method('fetchArray')->willReturnCallback(static function () use (&$rows): array {
            return array_shift($rows) ?? [];
        });
        $driver->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($driver);

        $queries = new DatabaseHelper($configuration)->buildInsertQueries('SELECT * FROM faqtest', 'faqtest');

        $this->assertSame(
            [
                "\r\n-- Table: faqtest",
                "INSERT INTO faqtest (name,amount,rights,note) VALUES ('foo',42,'7',NULL);",
            ],
            $queries,
        );
    }
}
