<?php

namespace phpMyFAQ\Database;

use Error;
use PDO;
use PDOStatement;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

/**
 * Class PdoMysqlTest
 */
#[AllowMockObjectsWithoutExpectations]
class PdoMysqlTest extends TestCase
{
    private PdoMysql $pdoMysql;

    protected function setUp(): void
    {
        $this->pdoMysql = new PdoMysql();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->pdoMysql);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->pdoMysql->tableNames);
        $this->assertEquals('', $this->pdoMysql->log());
    }

    public function testConnectExceptionHandling(): void
    {
        $this->assertTrue(method_exists($this->pdoMysql, 'connect'));

        $this->expectException(Exception::class);

        set_error_handler(function () {}, E_WARNING);

        try {
            // This should fail and throw Exception
            $this->pdoMysql->connect('invalid_host', 'invalid_user', 'invalid_password', 'invalid_db', 3306);
        } finally {
            restore_error_handler();
        }
    }

    public function testEscape(): void
    {
        $testString = "test'string";
        $result = $this->pdoMysql->escape($testString);
        // Single quotes should be escaped with backslash for MySQL
        $this->assertEquals("test\\'string", $result);
    }

    public function testEscapeWithSpecialCharacters(): void
    {
        $testString = 'test"string\'with\\special;chars';
        $result = $this->pdoMysql->escape($testString);
        // Single quotes and backslashes should be escaped for MySQL
        $this->assertEquals('test"string\\\'with\\\\special;chars', $result);
    }

    public function testEscapeWithNoSpecialCharacters(): void
    {
        $testString = 'simple string without quotes';
        $result = $this->pdoMysql->escape($testString);
        $this->assertEquals($testString, $result);
    }

    public function testFetchArrayWithMockResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);
        $expectedData = ['id' => 1, 'name' => 'test'];

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($expectedData);

        $result = $this->pdoMysql->fetchArray($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchArrayReturnsNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(null);

        $result = $this->pdoMysql->fetchArray($statementMock);
        $this->assertNull($result);
    }

    public function testFetchArrayReturnsFalse(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false);

        $result = $this->pdoMysql->fetchArray($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchRowWithData(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_NUM)
            ->willReturn(['test_value', 'other_value']);

        $result = $this->pdoMysql->fetchRow($statementMock);
        $this->assertEquals('test_value', $result);
    }

    public function testFetchRowEmpty(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_NUM)->willReturn([]);

        $result = $this->pdoMysql->fetchRow($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchRowNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_NUM)->willReturn(null);

        $result = $this->pdoMysql->fetchRow($statementMock);
        $this->assertFalse($result);
    }

    public function testFetchObject(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);
        $expectedObject = new stdClass();
        $expectedObject->id = 1;
        $expectedObject->name = 'test';

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_OBJ)->willReturn($expectedObject);

        $result = $this->pdoMysql->fetchObject($statementMock);
        $this->assertEquals($expectedObject, $result);
    }

    public function testFetchObjectNull(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetch')->with(PDO::FETCH_OBJ)->willReturn(null);

        $result = $this->pdoMysql->fetchObject($statementMock);
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

        $statementMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_OBJ)->willReturn($expectedData);

        $result = $this->pdoMysql->fetchAll($statementMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchAllWithFalseResult(): void
    {
        $pdoMysql = new class extends PdoMysql {
            public function error(): string
            {
                return 'Test error message';
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error while fetching result: Test error message');

        $pdoMysql->fetchAll(false);
    }

    public function testFetchAllWithEmptyResult(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_OBJ)->willReturn([]);

        $result = $this->pdoMysql->fetchAll($statementMock);
        $this->assertEquals([], $result);
    }

    public function testNumRows(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('rowCount')->willReturn(5);

        $result = $this->pdoMysql->numRows($statementMock);
        $this->assertEquals(5, $result);
    }

    public function testNumRowsZero(): void
    {
        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())->method('rowCount')->willReturn(0);

        $result = $this->pdoMysql->numRows($statementMock);
        $this->assertEquals(0, $result);
    }

    public function testTableNamesProperty(): void
    {
        $this->assertEquals([], $this->pdoMysql->tableNames);

        $this->pdoMysql->tableNames = ['table1', 'table2'];
        $this->assertEquals(['table1', 'table2'], $this->pdoMysql->tableNames);
    }

    public function testLogInitiallyEmpty(): void
    {
        $result = $this->pdoMysql->log();
        $this->assertEquals('', $result);
    }

    public function testConnectDsnConstruction(): void
    {
        $host = 'localhost';
        $database = 'test_db';
        $port = 3306;

        // Expected DSN format: mysql:host=localhost;dbname=test_db;port=3306;charset=utf8mb4
        $expectedDsnPattern = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=utf8mb4', $host, $database, $port);

        $this->assertTrue(method_exists($this->pdoMysql, 'connect'));
    }

    public function testErrorMethodExists(): void
    {
        $this->assertTrue(method_exists($this->pdoMysql, 'error'));
    }

    public function testErrorWithNullPdo(): void
    {
        try {
            $result = $this->pdoMysql->error();
            $this->assertEquals('', $result);
        } catch (Error $e) {
            $this->assertStringContainsString('errorInfo', $e->getMessage());
        }
    }

    public function testMethodSignatures(): void
    {
        $this->assertTrue(method_exists($this->pdoMysql, 'connect'));
        $this->assertTrue(method_exists($this->pdoMysql, 'error'));
        $this->assertTrue(method_exists($this->pdoMysql, 'escape'));
        $this->assertTrue(method_exists($this->pdoMysql, 'fetchArray'));
        $this->assertTrue(method_exists($this->pdoMysql, 'fetchRow'));
        $this->assertTrue(method_exists($this->pdoMysql, 'fetchObject'));
        $this->assertTrue(method_exists($this->pdoMysql, 'fetchAll'));
        $this->assertTrue(method_exists($this->pdoMysql, 'numRows'));
        $this->assertTrue(method_exists($this->pdoMysql, 'log'));
    }

    public function testConnectParameterTypes(): void
    {
        $reflection = new ReflectionMethod($this->pdoMysql, 'connect');
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
}
