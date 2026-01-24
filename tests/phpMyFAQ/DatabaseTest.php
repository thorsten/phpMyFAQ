<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DatabaseTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testFactoryReturnsInstanceOfDatabaseDriver(): void
    {
        $type = 'sqlite3';
        $driver = Database::factory($type);
        $this->assertInstanceOf(Sqlite3::class, $driver);
    }

    public function testGetInstance(): void
    {
        $instance = Database::getInstance();

        $this->assertInstanceOf(Sqlite3::class, $instance);
        $this->assertSame($instance, Database::getInstance());
    }

    public function testGetType(): void
    {
        $expectedType = 'sqlite3';

        Database::getInstance();

        $actualType = Database::getType();
        $this->assertEquals($expectedType, $actualType);
    }

    /**
     * @throws Exception
     */
    public function testCheckOnEmptyTable(): void
    {
        $dbConfig = new DatabaseConfiguration(PMF_TEST_DIR . '/content/core/config/database.php');
        Database::setTablePrefix($dbConfig->getPrefix());
        $db = Database::factory($dbConfig->getType());
        $db->connect(
            $dbConfig->getServer(),
            $dbConfig->getUser(),
            $dbConfig->getPassword(),
            $dbConfig->getDatabase(),
            $dbConfig->getPort(),
        );

        $actual = Database::checkOnEmptyTable('faqconfig');
        $this->assertEquals(0, $actual);
    }

    public function testErrorPage(): void
    {
        ob_start();
        Database::errorPage('Error message');
        $output = ob_get_clean();

        $this->assertStringContainsString('<title>Fatal phpMyFAQ Error</title>', $output);
        $this->assertStringContainsString(
            '<p class="alert alert-danger mt-5">The connection to the database server could not be established.</p>',
            $output,
        );
        $this->assertStringContainsString(
            '<p class="alert alert-info p-2">The error message of the database server: Error message</p>',
            $output,
        );
    }

    /**
     * Test database connection with valid parameters
     */
    public function testDatabaseConnectionSuccess(): void
    {
        $db = new Sqlite3();
        $result = $db->connect(PMF_TEST_DIR . '/test.db', '', '');

        $this->assertTrue($result);
    }

    /**
     * Test SQL query execution
     */
    public function testQueryExecution(): void
    {
        $db = new Sqlite3();
        $db->connect(PMF_TEST_DIR . '/test.db', '', '');

        // Create test table
        $createTable = 'CREATE TABLE IF NOT EXISTS test_table (id INTEGER PRIMARY KEY, name TEXT)';
        $result = $db->query($createTable);

        $this->assertNotFalse($result);
    }

    /**
     * Test data insertion and retrieval
     */
    public function testDataInsertionAndRetrieval(): void
    {
        $db = new Sqlite3();
        $db->connect(PMF_TEST_DIR . '/test.db', '', '');

        $db->query('CREATE TABLE IF NOT EXISTS test_data (id INTEGER PRIMARY KEY, name TEXT, value INTEGER)');

        // Insert test data
        $insertSql = "INSERT INTO test_data (name, value) VALUES ('test_name', 42)";
        $result = $db->query($insertSql);
        $this->assertNotFalse($result);

        // Retrieve and verify data
        $selectSql = "SELECT * FROM test_data WHERE name = 'test_name'";
        $result = $db->query($selectSql);
        $this->assertNotFalse($result);

        $row = $db->fetchObject($result);
        $this->assertEquals('test_name', $row->name);
        $this->assertEquals(42, $row->value);
    }

    /**
     * Test multiple database types factory method
     *
     * @throws Exception
     */
    public function testFactoryWithDifferentTypes(): void
    {
        $types = ['sqlite3'];

        foreach ($types as $type) {
            $driver = Database::factory($type);
            $this->assertInstanceOf('phpMyFAQ\Database\\' . ucfirst($type), $driver);
        }
    }

    /**
     * Test database schema operations
     */
    public function testSchemaOperations(): void
    {
        $db = new Sqlite3();
        $db->connect(PMF_TEST_DIR . '/test.db', '', '');

        // Test table creation
        $createSql = 'CREATE TABLE IF NOT EXISTS test_schema (
            id INTEGER PRIMARY KEY,
            name VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )';

        $result = $db->query($createSql);
        $this->assertNotFalse($result);

        // Test table exists
        $checkSql = "SELECT name FROM sqlite_master WHERE type='table' AND name='test_schema'";
        $result = $db->query($checkSql);
        $this->assertNotFalse($result);

        $row = $db->fetchObject($result);
        $this->assertEquals('test_schema', $row->name);
    }

    /**
     * Test connection pooling/reuse
     */
    public function testConnectionReuse(): void
    {
        $db1 = Database::getInstance();
        $db2 = Database::getInstance();

        $this->assertSame($db1, $db2);
    }

    /**
     * Test database version information
     */
    public function testDatabaseVersion(): void
    {
        $db = new Sqlite3();
        $db->connect(PMF_TEST_DIR . '/test.db', '', '');

        $version = $db->clientVersion();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);

        $serverVersion = $db->serverVersion();
        $this->assertIsString($serverVersion);
        $this->assertNotEmpty($serverVersion);
    }

    /**
     * Test concurrent access handling
     */
    public function testConcurrentAccess(): void
    {
        $db1 = new Sqlite3();
        $db1->connect(PMF_TEST_DIR . '/test.db', '', '');

        $db2 = new Sqlite3();
        $db2->connect(PMF_TEST_DIR . '/test.db', '', '');

        $db1->query('CREATE TABLE IF NOT EXISTS test_concurrent (id INTEGER PRIMARY KEY, thread TEXT)');

        // Both connections should work
        $result1 = $db1->query("INSERT INTO test_concurrent (thread) VALUES ('thread1')");
        $result2 = $db2->query("INSERT INTO test_concurrent (thread) VALUES ('thread2')");

        $this->assertNotFalse($result1);
        $this->assertNotFalse($result2);
    }

    /**
     * Test database cleanup
     */
    public function testDatabaseCleanup(): void
    {
        $db = new Sqlite3();
        $db->connect(PMF_TEST_DIR . '/test.db', '', '');

        // Create and populate test table
        $db->query('CREATE TABLE IF NOT EXISTS test_cleanup (id INTEGER PRIMARY KEY, temp_data TEXT)');
        $db->query("INSERT INTO test_cleanup (temp_data) VALUES ('temp')");

        // Test cleanup
        $result = $db->query("DELETE FROM test_cleanup WHERE temp_data = 'temp'");
        $this->assertNotFalse($result);

        // Verify cleanup
        $checkResult = $db->query("SELECT COUNT(*) as count FROM test_cleanup WHERE temp_data = 'temp'");
        $row = $db->fetchObject($checkResult);
        $this->assertEquals(0, $row->count);
    }
}
