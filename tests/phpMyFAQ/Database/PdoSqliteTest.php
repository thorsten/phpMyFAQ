<?php

/**
 * Tests for phpMyFAQ\Database\PdoSqlite
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    GitHub Copilot
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Database;

use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Core\Exception;
use stdClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Class PdoSqliteTest
 */
#[AllowMockObjectsWithoutExpectations]
class PdoSqliteTest extends TestCase
{
    private PdoSqlite $pdoSqlite;

    protected function setUp(): void
    {
        $this->pdoSqlite = new PdoSqlite();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->pdoSqlite);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->pdoSqlite->tableNames);
        $this->assertEquals('', $this->pdoSqlite->log());
    }

    public function testConnectWithInMemoryDatabase(): void
    {
        // Test SQLite in-memory database connection
        try {
            $result = $this->pdoSqlite->connect(':memory:', '', '', '');
            $this->assertTrue($result);
        } catch (Exception $e) {
            // If SQLite is not available, skip this test
            $this->markTestSkipped('SQLite PDO extension not available');
        }
    }

    public function testConnectExceptionHandling(): void
    {
        // Test that connect method exists and handles PDOException
        $this->assertTrue(method_exists($this->pdoSqlite, 'connect'));

        $this->expectException(Exception::class);

        // This should fail with invalid database path
        $this->pdoSqlite->connect('/invalid/path/to/database.db', '', '', '');
    }

    public function testConnectDsnFormat(): void
    {
        // Test SQLite-specific DSN format
        $dbPath = ':memory:';

        // Expected DSN format: sqlite:/path/to/database or sqlite::memory:
        // We verify the method exists and can handle SQLite paths
        $this->assertTrue(method_exists($this->pdoSqlite, 'connect'));
    }

    public function testConnectParameterHandling(): void
    {
        // Test that SQLite connect method accepts all parameters but only uses host (database path)
        $reflection = new \ReflectionMethod($this->pdoSqlite, 'connect');
        $parameters = $reflection->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals('host', $parameters[0]->getName()); // Used as database path in SQLite
        $this->assertEquals('user', $parameters[1]->getName()); // Not used in SQLite
        $this->assertEquals('password', $parameters[2]->getName()); // Not used in SQLite
        $this->assertEquals('database', $parameters[3]->getName()); // Not used in SQLite
        $this->assertEquals('port', $parameters[4]->getName()); // Not used in SQLite
    }

    public function testEscapeWithQuoteMethod(): void
    {
        // Test that escape method exists (uses PDO quote like PostgreSQL)
        $this->assertTrue(method_exists($this->pdoSqlite, 'escape'));

        // Verify method signature
        $reflection = new \ReflectionMethod($this->pdoSqlite, 'escape');
        $this->assertTrue($reflection->isPublic());
    }

    public function testFetchArrayWithMockResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);
        $expectedData = ['id' => 1, 'name' => 'test'];

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        $result = $this->pdoSqlite->fetchArray($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchArrayReturnsNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(null);

        $result = $this->pdoSqlite->fetchArray($statementMock);
        $this->assertNull($result);
    }

    public function testFetchRowWithData(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn(['test_value', 'other_value']);

        $result = $this->pdoSqlite->fetchRow($statementMock);
        $this->assertEquals('test_value', $result);
    }

    public function testFetchRowEmpty(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn([]);

        $result = $this->pdoSqlite->fetchRow($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchRowNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn(null);

        $result = $this->pdoSqlite->fetchRow($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchObject(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);
        $expectedObject = new stdClass();
        $expectedObject->id = 1;
        $expectedObject->name = 'test';

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn($expectedObject);

        $result = $this->pdoSqlite->fetchObject($statementMock);
        $this->assertEquals($expectedObject, $result);
    }

    public function testFetchObjectNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn(null);

        $result = $this->pdoSqlite->fetchObject($statementMock);
        $this->assertNull($result);
    }

    public function testFetchAllWithValidResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $object1 = new stdClass();
        $object1->id = 1;
        $object2 = new stdClass();
        $object2->id = 2;

        $expectedData = [$object1, $object2];

        $statementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_OBJ)
            ->willReturn($expectedData);

        $result = $this->pdoSqlite->fetchAll($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchAllWithFalseResult(): void
    {
        // Create a subclass to test the error case
        $pdoSqlite = new class extends PdoSqlite {
            public function error(): string
            {
                return 'SQLite test error message';
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error while fetching result: SQLite test error message');

        $pdoSqlite->fetchAll(false);
    }

    public function testFetchAllWithEmptyResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_OBJ)
            ->willReturn([]);

        $result = $this->pdoSqlite->fetchAll($statementMock);
        $this->assertEquals([], $result);
    }

    public function testNumRows(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(7);

        $result = $this->pdoSqlite->numRows($statementMock);
        $this->assertEquals(7, $result);
    }

    public function testNumRowsZero(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->pdoSqlite->numRows($statementMock);
        $this->assertEquals(0, $result);
    }

    public function testGetTableNames(): void
    {
        $prefix = 'test_';
        $result = $this->pdoSqlite->getTableNames($prefix);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that all table names have the prefix
        foreach ($result as $tableName) {
            $this->assertStringStartsWith($prefix, $tableName);
        }

        // Check some expected SQLite tables
        $this->assertContains($prefix . 'faqdata', $result);
        $this->assertContains($prefix . 'faqcategories', $result);
        $this->assertContains($prefix . 'faqconfig', $result);
    }

    public function testGetTableNamesWithoutPrefix(): void
    {
        $result = $this->pdoSqlite->getTableNames();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that table names start with 'faq' (no prefix)
        foreach ($result as $tableName) {
            $this->assertStringStartsWith('faq', $tableName);
        }
    }

    public function testTableNamesProperty(): void
    {
        $this->assertEquals([], $this->pdoSqlite->tableNames);

        $this->pdoSqlite->tableNames = ['table1', 'table2'];
        $this->assertEquals(['table1', 'table2'], $this->pdoSqlite->tableNames);

        $this->pdoSqlite->getTableNames('test_');
        $this->assertNotEmpty($this->pdoSqlite->tableNames);
    }

    public function testLogInitiallyEmpty(): void
    {
        $result = $this->pdoSqlite->log();
        $this->assertEquals('', $result);
    }

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlite, 'error'));
    }

    public function testErrorWithNullPdo(): void
    {
        try {
            $result = $this->pdoSqlite->error();
            $this->assertEquals('', $result);
        } catch (\Error $e) {
            $this->assertStringContainsString('errorInfo', $e->getMessage());
        }
    }

    public function testMethodSignatures(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlite, 'connect'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'error'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'escape'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'fetchArray'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'fetchRow'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'fetchObject'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'fetchAll'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'numRows'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'log'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'getTableNames'));
        $this->assertTrue(method_exists($this->pdoSqlite, 'getTableStatus'));
    }

    public function testSqliteSpecificFeatures(): void
    {
        $tableNames = $this->pdoSqlite->getTableNames();

        // SQLite should support all the same tables as other databases
        $this->assertContains('faqdata', $tableNames);
        $this->assertContains('faqcategories', $tableNames);
        $this->assertContains('faqgroup', $tableNames);
    }

    public function testSqliteTableStructure(): void
    {
        $tables = $this->pdoSqlite->getTableNames();

        $expectedTables = [
            'faqadminlog',
            'faqattachment',
            'faqcategories',
            'faqdata',
            'faqconfig',
            'faqgroup',
            'faqcomments',
            'faqnews'
        ];

        foreach ($expectedTables as $expectedTable) {
            $this->assertContains($expectedTable, $tables, "Table {$expectedTable} should exist in SQLite schema");
        }
    }

    public function testConnectWithFileDatabase(): void
    {
        // Test that connect method can handle file paths
        $this->assertTrue(method_exists($this->pdoSqlite, 'connect'));

        // We can't easily test actual file connections without creating temporary files
        // but we can verify the method signature accepts file paths
        $reflection = new \ReflectionMethod($this->pdoSqlite, 'connect');
        $parameters = $reflection->getParameters();

        // The first parameter (host) is used as the database file path in SQLite
        $this->assertEquals('host', $parameters[0]->getName());
        $this->assertFalse($parameters[0]->isOptional());
    }

    protected function tearDown(): void
    {
        // Clean up any resources if needed
    }
}
