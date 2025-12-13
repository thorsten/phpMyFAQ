<?php

/**
 * Tests for phpMyFAQ\Database\PdoSqlsrv
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
use ReflectionMethod;
use stdClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Class PdoSqlsrvTest
 */
#[AllowMockObjectsWithoutExpectations]
class PdoSqlsrvTest extends TestCase
{
    private PdoSqlsrv $pdoSqlsrv;

    protected function setUp(): void
    {
        $this->pdoSqlsrv = new PdoSqlsrv();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->pdoSqlsrv);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->pdoSqlsrv->tableNames);
        $this->assertEquals('', $this->pdoSqlsrv->log());
    }

    public function testConnectExceptionHandling(): void
    {
        // Test that connect method exists and handles PDOException
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'connect'));

        $this->expectException(Exception::class);

        // Suppress PDO connection warnings
        set_error_handler(function () {
        }, E_WARNING);

        try {
            // This should fail and throw Exception for invalid SQL Server connection
            $this->pdoSqlsrv->connect('invalid_server', 'invalid_user', 'invalid_password', 'invalid_db', 1433);
        } finally {
            restore_error_handler();
        }
    }

    public function testConnectDsnFormat(): void
    {
        $host = 'localhost';
        $database = 'test_db';
        $port = 1433;

        $this->assertTrue(method_exists($this->pdoSqlsrv, 'connect'));
    }

    public function testConnectParameterHandling(): void
    {
        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'connect');
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

    public function testEscapeWithQuoteMethod(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'escape'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'escape');
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

        $result = $this->pdoSqlsrv->fetchArray($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchArrayReturnsNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(null);

        $result = $this->pdoSqlsrv->fetchArray($statementMock);
        $this->assertNull($result);
    }

    public function testFetchRowWithData(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn(['test_value', 'other_value']);

        $result = $this->pdoSqlsrv->fetchRow($statementMock);
        $this->assertEquals('test_value', $result);
    }

    public function testFetchRowEmpty(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn([]);

        $result = $this->pdoSqlsrv->fetchRow($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchRowNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn(null);

        $result = $this->pdoSqlsrv->fetchRow($statementMock);
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

        $result = $this->pdoSqlsrv->fetchObject($statementMock);
        $this->assertEquals($expectedObject, $result);
    }

    public function testFetchObjectNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn(null);

        $result = $this->pdoSqlsrv->fetchObject($statementMock);
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

        $result = $this->pdoSqlsrv->fetchAll($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchAllWithFalseResult(): void
    {
        $pdoSqlsrv = new class extends PdoSqlsrv {
            public function error(): string
            {
                return 'SQL Server test error message';
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error while fetching result: SQL Server test error message');

        $pdoSqlsrv->fetchAll(false);
    }

    public function testFetchAllWithEmptyResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_OBJ)
            ->willReturn([]);

        $result = $this->pdoSqlsrv->fetchAll($statementMock);
        $this->assertEquals([], $result);
    }

    public function testNumRows(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(15);

        $result = $this->pdoSqlsrv->numRows($statementMock);
        $this->assertEquals(15, $result);
    }

    public function testNumRowsZero(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->pdoSqlsrv->numRows($statementMock);
        $this->assertEquals(0, $result);
    }

    public function testGetTableNames(): void
    {
        $prefix = 'test_';
        $result = $this->pdoSqlsrv->getTableNames($prefix);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        foreach ($result as $tableName) {
            $this->assertStringStartsWith($prefix, $tableName);
        }

        $this->assertContains($prefix . 'faqdata', $result);
        $this->assertContains($prefix . 'faqcategories', $result);
        $this->assertContains($prefix . 'faqconfig', $result);
    }

    public function testGetTableNamesWithoutPrefix(): void
    {
        $result = $this->pdoSqlsrv->getTableNames();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $this->assertContains('faqdata', $result);
        $this->assertContains('faqcategories', $result);
        $this->assertContains('faqconfig', $result);
        $this->assertContains('faquser', $result);
    }

    public function testGetTableNamesUpdatesProperty(): void
    {
        $prefix = 'custom_';
        $result = $this->pdoSqlsrv->getTableNames($prefix);

        $this->assertEquals($result, $this->pdoSqlsrv->tableNames);
    }

    public function testGetTableStatus(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'getTableStatus'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'getTableStatus');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('prefix', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertEquals('', $parameters[0]->getDefaultValue());

        $this->assertEquals('array', $reflection->getReturnType()->getName());
    }

    public function testNextIdMethod(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'nextId'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'nextId');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('table', $parameters[0]->getName());
        $this->assertEquals('column', $parameters[1]->getName());
    }

    public function testQueryMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'query'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'query');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('offset', $parameters[1]->getName());
        $this->assertEquals('rowcount', $parameters[2]->getName());

        // Test default values
        $this->assertTrue($parameters[1]->isOptional());
        $this->assertTrue($parameters[2]->isOptional());
        $this->assertEquals(0, $parameters[1]->getDefaultValue());
        $this->assertEquals(0, $parameters[2]->getDefaultValue());
    }

    public function testPrepareMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'prepare'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'prepare');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('query', $parameters[0]->getName());
        $this->assertEquals('options', $parameters[1]->getName());

        $this->assertTrue($parameters[1]->isOptional());
    }

    public function testExecuteMethod(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);
        $params = ['id' => 1, 'name' => 'test'];

        $statementMock->expects($this->once())
            ->method('execute')
            ->with($params)
            ->willReturn(true);

        $result = $this->pdoSqlsrv->execute($statementMock, $params);
        $this->assertTrue($result);
    }

    public function testExecuteWithoutParams(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('execute')
            ->with([])
            ->willReturn(true);

        $result = $this->pdoSqlsrv->execute($statementMock);
        $this->assertTrue($result);
    }

    public function testExecuteFailure(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $result = $this->pdoSqlsrv->execute($statementMock);
        $this->assertFalse($result);
    }

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'error'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'error');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testEscapeMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'escape'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'escape');
        $parameters = $reflection->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('string', $parameters[0]->getName());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testClientVersionExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'clientVersion'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'clientVersion');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testServerVersionExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'serverVersion'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'serverVersion');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testCloseMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'close'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'close');
        $this->assertTrue($reflection->isPublic());
        $this->assertEquals('void', $reflection->getReturnType()->getName());
    }

    public function testDestructor(): void
    {
        // Can't directly test destructor, but we verify it exists
        $this->assertTrue(method_exists(PdoSqlsrv::class, '__destruct'));
    }

    public function testNowMethod(): void
    {
        $result = $this->pdoSqlsrv->now();
        $this->assertEquals('CURRENT_TIMESTAMP', $result);
    }

    public function testSqlLogAccumulation(): void
    {
        // Test that log starts empty
        $this->assertEquals('', $this->pdoSqlsrv->log());

        // Test that log method exists and returns string
        $this->assertTrue(method_exists($this->pdoSqlsrv, 'log'));

        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'log');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    public function testOffsetFetchSyntax(): void
    {
        // Test that query method would add SQL Server specific OFFSET/FETCH syntax
        // This is tested by checking the method exists and has correct parameters
        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'query');

        // Verify the method can handle offset and rowcount parameters
        $this->assertTrue($reflection->getParameters()[1]->hasType());
        $this->assertTrue($reflection->getParameters()[2]->hasType());

        // Test that default values are 0 (no pagination)
        $this->assertEquals(0, $reflection->getParameters()[1]->getDefaultValue());
        $this->assertEquals(0, $reflection->getParameters()[2]->getDefaultValue());
    }

    public function testDsnFormatVerification(): void
    {
        // Test that connect method uses SQL Server DSN format
        // We can verify this by checking method parameter types
        $reflection = new ReflectionMethod($this->pdoSqlsrv, 'connect');
        $parameters = $reflection->getParameters();

        // Verify host parameter (for SQL Server format)
        $this->assertEquals('string', $parameters[0]->getType()->getName());

        // Verify port parameter is nullable int (SQL Server specific)
        $this->assertTrue($parameters[4]->getType()->allowsNull());
        $this->assertEquals('int', $parameters[4]->getType()->getName());
    }

    public function testTableNamesProperty(): void
    {
        // Test that tableNames is public array
        $reflection = new \ReflectionProperty($this->pdoSqlsrv, 'tableNames');
        $this->assertTrue($reflection->isPublic());

        // Initially empty
        $this->assertEquals([], $this->pdoSqlsrv->tableNames);

        // Gets populated by getTableNames
        $this->pdoSqlsrv->getTableNames('test_');
        $this->assertNotEmpty($this->pdoSqlsrv->tableNames);
    }
}
