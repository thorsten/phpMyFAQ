<?php

namespace phpMyFAQ\Database;

use mysqli_result;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class MysqliTest
 */
#[AllowMockObjectsWithoutExpectations]
class MysqliTest extends TestCase
{
    private Mysqli $mysqli;

    protected function setUp(): void
    {
        $this->mysqli = new Mysqli();
    }

    public function testImplementsDatabaseDriver(): void
    {
        $this->assertInstanceOf(DatabaseDriver::class, $this->mysqli);
    }

    public function testInitialState(): void
    {
        $this->assertEquals([], $this->mysqli->tableNames);
        $this->assertEquals('', $this->mysqli->log());
    }

    public function testFetchArrayWithMockResult(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);
        $expectedData = ['id' => 1, 'name' => 'test'];

        $resultMock->expects($this->once())->method('fetch_assoc')->willReturn($expectedData);

        $result = $this->mysqli->fetchArray($resultMock);
        $this->assertEquals($expectedData, $result);
    }

    public function testFetchArrayNull(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);

        $resultMock->expects($this->once())->method('fetch_assoc')->willReturn(null);

        $result = $this->mysqli->fetchArray($resultMock);
        $this->assertNull($result);
    }

    public function testFetchRowWithData(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);

        $resultMock->expects($this->once())->method('fetch_row')->willReturn(['test_value', 'other_value']);

        $result = $this->mysqli->fetchRow($resultMock);
        $this->assertEquals('test_value', $result);
    }

    public function testFetchRowEmpty(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);

        $resultMock->expects($this->once())->method('fetch_row')->willReturn([]);

        $result = $this->mysqli->fetchRow($resultMock);
        $this->assertFalse($result);
    }

    public function testFetchRowNull(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);

        $resultMock->expects($this->once())->method('fetch_row')->willReturn(null);

        $result = $this->mysqli->fetchRow($resultMock);
        $this->assertFalse($result);
    }

    public function testFetchObject(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);
        $expectedObject = new stdClass();
        $expectedObject->id = 1;
        $expectedObject->name = 'test';

        $resultMock->expects($this->once())->method('fetch_object')->willReturn($expectedObject);

        $result = $this->mysqli->fetchObject($resultMock);
        $this->assertEquals($expectedObject, $result);
    }

    public function testFetchObjectNull(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);

        $resultMock->expects($this->once())->method('fetch_object')->willReturn(null);

        $result = $this->mysqli->fetchObject($resultMock);
        $this->assertNull($result);
    }

    public function testNumRowsWithMysqliResult(): void
    {
        $nonMysqliObject = new stdClass();
        $result = $this->mysqli->numRows($nonMysqliObject);
        $this->assertEquals(0, $result);

        $result = $this->mysqli->numRows('not_a_result');
        $this->assertEquals(0, $result);

        $result = $this->mysqli->numRows(['data']);
        $this->assertEquals(0, $result);
    }

    public function testNumRowsWithNonMysqliResult(): void
    {
        $result = $this->mysqli->numRows('invalid_result');
        $this->assertEquals(0, $result);
    }

    public function testNumRowsWithNull(): void
    {
        $result = $this->mysqli->numRows(null);
        $this->assertEquals(0, $result);
    }

    public function testNumRowsWithFalse(): void
    {
        $result = $this->mysqli->numRows(false);
        $this->assertEquals(0, $result);
    }

    public function testTableNamesProperty(): void
    {
        $this->assertEquals([], $this->mysqli->tableNames);

        $this->mysqli->tableNames = ['table1', 'table2'];
        $this->assertEquals(['table1', 'table2'], $this->mysqli->tableNames);
    }

    public function testFetchAllWithValidResult(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);

        $object1 = new stdClass();
        $object1->id = 1;
        $object2 = new stdClass();
        $object2->id = 2;

        $resultMock
            ->expects($this->exactly(3))
            ->method('fetch_object')
            ->willReturnOnConsecutiveCalls($object1, $object2, false);

        $result = $this->mysqli->fetchAll($resultMock);

        $this->assertCount(2, $result);
        $this->assertEquals($object1, $result[0]);
        $this->assertEquals($object2, $result[1]);
    }

    public function testFetchAllWithFalseResult(): void
    {
        $mysqli = new class extends Mysqli {
            public function error(): string
            {
                return 'Test error message';
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error while fetching result: Test error message');

        $mysqli->fetchAll(false);
    }

    public function testFetchAllWithEmptyResult(): void
    {
        $resultMock = $this->createMock(mysqli_result::class);

        $resultMock->expects($this->once())->method('fetch_object')->willReturn(false);

        $result = $this->mysqli->fetchAll($resultMock);
        $this->assertEquals([], $result);
    }

    public function testConnectExceptionHandling(): void
    {
        $mysqli = new Mysqli();

        $this->assertTrue(method_exists($mysqli, 'connect'));

        $this->expectException(Exception::class);

        set_error_handler(function () {}, E_WARNING);

        try {
            $mysqli->connect('', '', '', '');
        } finally {
            restore_error_handler();
        }
    }

    public function testLogInitiallyEmpty(): void
    {
        $result = $this->mysqli->log();
        $this->assertEquals('', $result);
    }
}
