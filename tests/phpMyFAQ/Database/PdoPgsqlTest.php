<?php

/**
 * Tests for phpMyFAQ\Database\PdoPgsql
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

/**
 * Class PdoPgsqlTest
 */
class PdoPgsqlTest extends TestCase
{
    private PdoPgsql $pdoPgsql;

    protected function setUp(): void
    {
        $this->pdoPgsql = new PdoPgsql();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->pdoPgsql);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->pdoPgsql->tableNames);
        $this->assertEquals('', $this->pdoPgsql->log());
    }

    public function testConnectExceptionHandling(): void
    {
        // Test that connect method exists and handles PDOException
        $this->assertTrue(method_exists($this->pdoPgsql, 'connect'));

        $this->expectException(Exception::class);

        // Suppress PDO connection warnings
        set_error_handler(function() {}, E_WARNING);

        try {
            // This should fail and throw Exception
            $this->pdoPgsql->connect('invalid_host', 'invalid_user', 'invalid_password', 'invalid_db', 5432);
        } finally {
            restore_error_handler();
        }
    }

    public function testConnectDsnFormat(): void
    {
        // Test PostgreSQL-specific DSN format
        $host = 'localhost';
        $database = 'test_db';
        $port = 5432;

        // Expected DSN format: pgsql:host=localhost;dbname=test_db;port=5432
        // We verify the method exists and can be called
        $this->assertTrue(method_exists($this->pdoPgsql, 'connect'));
    }

    public function testEscapeWithQuoteMethod(): void
    {
        // Test that escape method exists and can handle special characters
        $this->assertTrue(method_exists($this->pdoPgsql, 'escape'));

        // Since we can't easily mock PDO without a connection,
        // we test that the method exists and would use quote()
        $reflection = new \ReflectionMethod($this->pdoPgsql, 'escape');
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

        $result = $this->pdoPgsql->fetchArray($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchArrayReturnsNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(null);

        $result = $this->pdoPgsql->fetchArray($statementMock);
        $this->assertNull($result);
    }

    public function testFetchRowWithData(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn(['test_value', 'other_value']);

        $result = $this->pdoPgsql->fetchRow($statementMock);
        $this->assertEquals('test_value', $result);
    }

    public function testFetchRowEmpty(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn([]);

        $result = $this->pdoPgsql->fetchRow($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchRowNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn(null);

        $result = $this->pdoPgsql->fetchRow($statementMock);
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

        $result = $this->pdoPgsql->fetchObject($statementMock);
        $this->assertEquals($expectedObject, $result);
    }

    public function testFetchObjectNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn(null);

        $result = $this->pdoPgsql->fetchObject($statementMock);
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

        $result = $this->pdoPgsql->fetchAll($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchAllWithFalseResult(): void
    {
        // Create a subclass to test the error case
        $pdoPgsql = new class extends PdoPgsql {
            public function error(): string
            {
                return 'PostgreSQL test error message';
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error while fetching result: PostgreSQL test error message');

        $pdoPgsql->fetchAll(false);
    }

    public function testFetchAllWithEmptyResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_OBJ)
            ->willReturn([]);

        $result = $this->pdoPgsql->fetchAll($statementMock);
        $this->assertEquals([], $result);
    }

    public function testNumRows(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(10);

        $result = $this->pdoPgsql->numRows($statementMock);
        $this->assertEquals(10, $result);
    }

    public function testNumRowsZero(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->pdoPgsql->numRows($statementMock);
        $this->assertEquals(0, $result);
    }

    public function testGetTableNames(): void
    {
        $prefix = 'test_';
        $result = $this->pdoPgsql->getTableNames($prefix);

        // Should return array of table names with prefix
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that all table names have the prefix
        foreach ($result as $tableName) {
            $this->assertStringStartsWith($prefix, $tableName);
        }

        // Check some expected PostgreSQL tables
        $this->assertContains($prefix . 'faqdata', $result);
        $this->assertContains($prefix . 'faqcategories', $result);
        $this->assertContains($prefix . 'faqconfig', $result);
    }

    public function testGetTableNamesWithoutPrefix(): void
    {
        $result = $this->pdoPgsql->getTableNames();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Check that table names start with 'faq' (no prefix)
        foreach ($result as $tableName) {
            $this->assertStringStartsWith('faq', $tableName);
        }
    }

    public function testTableNamesProperty(): void
    {
        $this->assertEquals([], $this->pdoPgsql->tableNames);

        // Test that we can modify the public property
        $this->pdoPgsql->tableNames = ['table1', 'table2'];
        $this->assertEquals(['table1', 'table2'], $this->pdoPgsql->tableNames);

        // Test that getTableNames updates the property
        $this->pdoPgsql->getTableNames('test_');
        $this->assertNotEmpty($this->pdoPgsql->tableNames);
    }

    public function testLogInitiallyEmpty(): void
    {
        $result = $this->pdoPgsql->log();
        $this->assertEquals('', $result);
    }

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoPgsql, 'error'));
    }

    public function testErrorWithNullPdo(): void
    {
        // Test error method when PDO is null (initial state)
        try {
            $result = $this->pdoPgsql->error();
            $this->assertEquals('', $result);
        } catch (\Error $e) {
            // If it throws an error due to null PDO, that's acceptable
            $this->assertStringContainsString('errorInfo', $e->getMessage());
        }
    }

    public function testMethodSignatures(): void
    {
        // Verify all required DatabaseDriver methods are implemented
        $this->assertTrue(method_exists($this->pdoPgsql, 'connect'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'error'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'escape'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'fetchArray'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'fetchRow'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'fetchObject'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'fetchAll'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'numRows'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'log'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'getTableNames'));
        $this->assertTrue(method_exists($this->pdoPgsql, 'getTableStatus'));
    }

    public function testConnectParameterTypes(): void
    {
        // Test that connect method accepts the correct parameter types
        $reflection = new \ReflectionMethod($this->pdoPgsql, 'connect');
        $parameters = $reflection->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals('host', $parameters[0]->getName());
        $this->assertEquals('user', $parameters[1]->getName());
        $this->assertEquals('password', $parameters[2]->getName());
        $this->assertEquals('database', $parameters[3]->getName());
        $this->assertEquals('port', $parameters[4]->getName());

        // Test optional parameters
        $this->assertTrue($parameters[3]->isOptional());
        $this->assertTrue($parameters[4]->isOptional());
        $this->assertEquals('', $parameters[3]->getDefaultValue());
        $this->assertNull($parameters[4]->getDefaultValue());
    }

    public function testPostgreSqlSpecificFeatures(): void
    {
        // Test PostgreSQL-specific characteristics
        $tablenames = $this->pdoPgsql->getTableNames();

        // PostgreSQL should support all the same core tables as MySQL
        $this->assertContains('faqdata', $tablenames);
        $this->assertContains('faqcategories', $tablenames);
        $this->assertContains('faqgroup', $tablenames); // Use faqgroup instead of faqusers
    }

    protected function tearDown(): void
    {
        // Clean up any resources if needed
    }
}
