<?php

namespace phpMyFAQ\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

/**
 * Class DatabaseHelperTest
 *
 * @testdox DatabaseHelper should
 * @package phpMyFAQ
 */
class DatabaseHelperTest extends TestCase
{
    /** @var DatabaseHelper */
    private DatabaseHelper $databaseHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $dbHandle->query(
            'CREATE TABLE faqtest (name VARCHAR(255) NOT NULL, testvalue VARCHAR(255) DEFAULT NULL, PRIMARY KEY (name))'
        );
        $dbHandle->query("INSERT INTO faqtest (name,testvalue) VALUES ('foo','bar')");
        $dbHandle->query("INSERT INTO faqtest (name,testvalue) VALUES ('bar','baz')");

        $configuration = new Configuration($dbHandle);
        $configuration->config['main.currentVersion'] = System::getVersion();

        $this->databaseHelper = new DatabaseHelper($configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $dbHandle->query('DROP TABLE faqtest');
    }

    /**
     * @testdox create the correct INSERT queries
     */
    public function testBuildInsertQueries(): void
    {
        $table = 'faqtest';
        $queries = $this->databaseHelper->buildInsertQueries('SELECT * FROM ' . $table, $table);

        $expected = [
            "\r\n-- Table: faqtest",
            "INSERT INTO faqtest (name,testvalue) VALUES ('foo','bar');",
            "INSERT INTO faqtest (name,testvalue) VALUES ('bar','baz');"
        ];

        $this->assertEquals($expected, $queries);
    }
}
