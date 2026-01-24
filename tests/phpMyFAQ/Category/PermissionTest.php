<?php

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class PermissionTest
 */
#[AllowMockObjectsWithoutExpectations]
class PermissionTest extends TestCase
{
    private Permission $permission;
    private Configuration $configurationMock;
    private DatabaseDriver $databaseMock;

    protected function setUp(): void
    {
        $this->databaseMock = $this->createMock(DatabaseDriver::class);
        $this->configurationMock = $this->createMock(Configuration::class);

        $this->configurationMock
            ->expects($this->any())
            ->method('getDb')
            ->willReturn($this->databaseMock);

        $this->permission = new Permission($this->configurationMock);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Permission::class, $this->permission);
    }

    public function testConstants(): void
    {
        $this->assertEquals('user', Permission::USER);
        $this->assertEquals('group', Permission::GROUP);
    }

    public function testAddWithUserMode(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1, 2];
        $userIds = [10, 20];

        // Mock existance check (returns 0 rows = not exists)
        $this->databaseMock
            ->expects($this->exactly(4))
            ->method('numRows')
            ->willReturn(0);

        // Mock queries for existence checks and inserts
        $this->databaseMock
            ->expects($this->exactly(8))
            ->method('query')
            ->willReturn(true);

        $result = $this->permission->add(Permission::USER, $categories, $userIds);
        $this->assertTrue($result);
    }

    public function testAddWithGroupMode(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1];
        $groupIds = [5];

        $this->databaseMock
            ->expects($this->once())
            ->method('numRows')
            ->willReturn(0);

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturn(true);

        $result = $this->permission->add(Permission::GROUP, $categories, $groupIds);
        $this->assertTrue($result);
    }

    public function testAddWithInvalidMode(): void
    {
        $result = $this->permission->add('invalid', [1], [10]);
        $this->assertFalse($result);
    }

    public function testAddWithExistingPermission(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1];
        $userIds = [10];

        // Mock existence check (returns 1 row = already exists)
        $this->databaseMock
            ->expects($this->once())
            ->method('numRows')
            ->willReturn(1);

        // Only expect one query for the existence check, no insert
        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $result = $this->permission->add(Permission::USER, $categories, $userIds);
        $this->assertTrue($result);
    }

    public function testDeleteWithUserMode(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1, 2];

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('query')
            ->with($this->stringContains('faqcategory_user'))
            ->willReturn(true);

        $result = $this->permission->delete(Permission::USER, $categories);
        $this->assertTrue($result);
    }

    public function testDeleteWithGroupMode(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1];

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $result = $this->permission->delete(Permission::GROUP, $categories);
        $this->assertTrue($result);
    }

    public function testDeleteWithInvalidMode(): void
    {
        $result = $this->permission->delete('invalid', [1]);
        $this->assertFalse($result);
    }

    public function testIsRestrictedWithUserPermissions(): void
    {
        Database::setTablePrefix('test_');

        $categoryId = 1;

        // Mock for user permissions query
        $userResult = 'user_result';
        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls($userResult, 'group_result');

        // Mock user permissions exist
        $userRow = new stdClass();
        $userRow->permission = 10;

        $this->databaseMock
            ->expects($this->any())
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls($userRow, false, false);

        $result = $this->permission->isRestricted($categoryId);
        $this->assertTrue($result);
    }

    public function testIsRestrictedWithGroupPermissions(): void
    {
        Database::setTablePrefix('test_');

        $categoryId = 1;

        // Mock for both queries
        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls('user_result', 'group_result');

        // Mock no user permissions but group permissions exist
        $groupRow = new stdClass();
        $groupRow->permission = 5;

        $this->databaseMock
            ->expects($this->any())
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls(false, $groupRow, false);

        $result = $this->permission->isRestricted($categoryId);
        $this->assertTrue($result);
    }

    public function testIsRestrictedWithNoPermissions(): void
    {
        Database::setTablePrefix('test_');

        $categoryId = 1;

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls('user_result', 'group_result');

        // Mock no permissions for both user and group
        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchObject')
            ->willReturn(false);

        $result = $this->permission->isRestricted($categoryId);
        $this->assertFalse($result);
    }

    public function testGetWithUserMode(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1, 2];

        $result_mock = 'query_result';
        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT user_id AS permission FROM test_faqcategory_user'))
            ->willReturn($result_mock);

        $row1 = new stdClass();
        $row1->permission = 10;
        $row2 = new stdClass();
        $row2->permission = 20;

        $this->databaseMock
            ->expects($this->exactly(3))
            ->method('fetchObject')
            ->with($result_mock)
            ->willReturnOnConsecutiveCalls($row1, $row2, false);

        $result = $this->permission->get(Permission::USER, $categories);
        $this->assertEquals([10, 20], $result);
    }

    public function testGetWithGroupMode(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1];

        $result_mock = 'query_result';
        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT group_id AS permission FROM test_faqcategory_group'))
            ->willReturn($result_mock);

        $row = new stdClass();
        $row->permission = 5;

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls($row, false);

        $result = $this->permission->get(Permission::GROUP, $categories);
        $this->assertEquals([5], $result);
    }

    public function testGetWithInvalidMode(): void
    {
        $result = $this->permission->get('invalid', [1]);
        $this->assertEquals([], $result);
    }

    public function testGetWithEmptyResult(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1];

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn('query_result');

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchObject')
            ->willReturn(false);

        $result = $this->permission->get(Permission::USER, $categories);
        $this->assertEquals([], $result);
    }

    public function testGetAll(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1, 2];

        // Mock two queries (user and group)
        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls('user_result', 'group_result');

        // Mock user permissions
        $userRow1 = new stdClass();
        $userRow1->category_id = 1;
        $userRow1->permission = 10;

        $userRow2 = new stdClass();
        $userRow2->category_id = 2;
        $userRow2->permission = 20;

        // Mock group permissions
        $groupRow = new stdClass();
        $groupRow->category_id = 1;
        $groupRow->permission = 5;

        $this->databaseMock
            ->expects($this->exactly(5))
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls(
                $userRow1,
                $userRow2,
                false, // User query results
                $groupRow,
                false, // Group query results
            );

        $result = $this->permission->getAll($categories);

        $expected = [
            1 => [
                Permission::USER => [10],
                Permission::GROUP => [5],
            ],
            2 => [
                Permission::USER => [20],
                Permission::GROUP => [],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetAllWithEmptyCategories(): void
    {
        $result = $this->permission->getAll([]);
        $this->assertEquals([], $result);
    }

    public function testGetAllWithNoPermissions(): void
    {
        Database::setTablePrefix('test_');

        $categories = [1];

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls('user_result', 'group_result');

        // Mock no results for both queries
        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchObject')
            ->willReturn(false);

        $result = $this->permission->getAll($categories);

        $expected = [
            1 => [
                Permission::USER => [],
                Permission::GROUP => [],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    protected function tearDown(): void
    {
        // Reset table prefix if needed
        Database::setTablePrefix('');
    }
}
