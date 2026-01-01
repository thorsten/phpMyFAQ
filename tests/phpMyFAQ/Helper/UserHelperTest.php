<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test case for UserHelper class
 */
#[AllowMockObjectsWithoutExpectations]
class UserHelperTest extends TestCase
{
    private MockObject $userMock;
    private UserHelper $userHelper;

    protected function setUp(): void
    {
        $this->userMock = $this->createMock(User::class);
        $this->userHelper = new UserHelper($this->userMock);
    }

    public function testGetAllUsersForTemplateWithDefaultParameters(): void
    {
        $userIds = [1, 2, 3];

        $this->userMock
            ->expects($this->once())
            ->method('getAllUsers')
            ->with(true, false)
            ->willReturn($userIds);

        $this->userMock
            ->expects($this->exactly(3))
            ->method('getUserById')
            ->with($this->callback(function ($userId) {
                static $callCount = 0;
                $expectedIds = [1, 2, 3];
                return $userId === $expectedIds[$callCount++];
            }));

        $this->userMock
            ->expects($this->exactly(3))
            ->method('getUserData')
            ->with('display_name')
            ->willReturnOnConsecutiveCalls('User One', 'User Two', 'User Three');

        $this->userMock
            ->expects($this->exactly(3))
            ->method('getLogin')
            ->willReturnOnConsecutiveCalls('user1', 'user2', 'user3');

        $result = $this->userHelper->getAllUsersForTemplate();

        $expected = [
            ['id' => 1, 'selected' => true, 'displayName' => 'User One', 'login' => 'user1'],
            ['id' => 2, 'selected' => false, 'displayName' => 'User Two', 'login' => 'user2'],
            ['id' => 3, 'selected' => false, 'displayName' => 'User Three', 'login' => 'user3'],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetAllUsersForTemplateWithSelectedUser(): void
    {
        $userIds = [1, 2, 3];
        $selectedId = 2;

        $this->userMock
            ->expects($this->once())
            ->method('getAllUsers')
            ->with(true, false)
            ->willReturn($userIds);

        $this->userMock->expects($this->exactly(3))->method('getUserById');

        $this->userMock
            ->expects($this->exactly(3))
            ->method('getUserData')
            ->willReturnOnConsecutiveCalls('User One', 'User Two', 'User Three');

        $this->userMock
            ->expects($this->exactly(3))
            ->method('getLogin')
            ->willReturnOnConsecutiveCalls('user1', 'user2', 'user3');

        $result = $this->userHelper->getAllUsersForTemplate($selectedId);

        $this->assertTrue($result[1]['selected']);
        $this->assertFalse($result[0]['selected']);
        $this->assertFalse($result[2]['selected']);
    }

    public function testGetAllUsersForTemplateWithAllowBlockedUsers(): void
    {
        $userIds = [1, 2];

        $this->userMock
            ->expects($this->once())
            ->method('getAllUsers')
            ->with(true, true)
            ->willReturn($userIds);

        $this->userMock->expects($this->exactly(2))->method('getUserById');

        $this->userMock
            ->expects($this->exactly(2))
            ->method('getUserData')
            ->willReturnOnConsecutiveCalls('Active User', 'Blocked User');

        $this->userMock
            ->expects($this->exactly(2))
            ->method('getLogin')
            ->willReturnOnConsecutiveCalls('active', 'blocked');

        $result = $this->userHelper->getAllUsersForTemplate(1, true);

        $this->assertCount(2, $result);
    }

    public function testGetAllUsersForTemplateSkipsInvalidUserId(): void
    {
        $userIds = [1, -1, 2];

        $this->userMock
            ->expects($this->once())
            ->method('getAllUsers')
            ->willReturn($userIds);

        $this->userMock
            ->expects($this->exactly(2))
            ->method('getUserById')
            ->with($this->callback(function ($userId) {
                static $callCount = 0;
                $expectedIds = [1, 2];
                return $userId === $expectedIds[$callCount++];
            }));

        $this->userMock
            ->expects($this->exactly(2))
            ->method('getUserData')
            ->willReturnOnConsecutiveCalls('User One', 'User Two');

        $this->userMock
            ->expects($this->exactly(2))
            ->method('getLogin')
            ->willReturnOnConsecutiveCalls('user1', 'user2');

        $result = $this->userHelper->getAllUsersForTemplate();

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(2, $result[1]['id']);
    }

    public function testGetAllUsersForTemplateWithEmptyUserList(): void
    {
        $this->userMock
            ->expects($this->once())
            ->method('getAllUsers')
            ->willReturn([]);

        $result = $this->userHelper->getAllUsersForTemplate();

        $this->assertEmpty($result);
    }
}
