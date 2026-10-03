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
}
