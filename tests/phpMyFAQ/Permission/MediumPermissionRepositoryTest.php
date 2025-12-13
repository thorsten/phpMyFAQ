<?php

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class MediumPermissionRepositoryTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private Configuration $configuration;
    private MediumPermissionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $this->repository = new MediumPermissionRepository($this->configuration);
    }

    public function testGetGroupRights(): void
    {
        $result = $this->repository->getGroupRights(1);
        $this->assertIsArray($result);

        // Test with invalid group ID
        $this->assertEmpty($this->repository->getGroupRights(0));
        $this->assertEmpty($this->repository->getGroupRights(-1));
    }

    public function testCheckUserGroupRight(): void
    {
        // Test with invalid inputs
        $this->assertFalse($this->repository->checkUserGroupRight(0, 1));
        $this->assertFalse($this->repository->checkUserGroupRight(1, 0));
        $this->assertFalse($this->repository->checkUserGroupRight(-1, -1));
    }

    public function testGrantGroupRight(): void
    {
        // Test with invalid inputs
        $this->assertFalse($this->repository->grantGroupRight(0, 1));
        $this->assertFalse($this->repository->grantGroupRight(1, 0));

        // Note: Real test would require a valid group and right to exist
    }

    public function testAddGroup(): void
    {
        $groupData = [
            'name' => 'test_group_repo',
            'description' => 'Test group for repository',
            'auto_join' => 0,
        ];

        $nextId = $this->repository->nextGroupId();
        $this->assertTrue($this->repository->addGroup($groupData, $nextId));

        // Verify it was added
        $addedGroupId = $this->repository->getGroupId('test_group_repo');
        $this->assertEquals($nextId, $addedGroupId);

        // Cleanup
        $this->dbHandle->query('DELETE FROM faqgroup WHERE name = "test_group_repo"');
    }

    public function testGetGroupId(): void
    {
        // Add a test group first
        $groupData = [
            'name' => 'temp_test_group',
            'description' => 'Temporary test group',
            'auto_join' => 0,
        ];

        $nextId = $this->repository->nextGroupId();
        $this->repository->addGroup($groupData, $nextId);

        // Test getting the group ID
        $groupId = $this->repository->getGroupId('temp_test_group');
        $this->assertEquals($nextId, $groupId);

        // Test non-existent group
        $this->assertEquals(0, $this->repository->getGroupId('non_existent_group'));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faqgroup WHERE group_id = ' . $nextId);
    }

    public function testChangeGroup(): void
    {
        // Add a test group first
        $groupData = [
            'name' => 'group_to_change',
            'description' => 'Original description',
            'auto_join' => 0,
        ];

        $nextId = $this->repository->nextGroupId();
        $this->repository->addGroup($groupData, $nextId);

        // Change the group
        $newData = [
            'description' => 'Updated description',
        ];

        $this->assertTrue($this->repository->changeGroup($nextId, $newData));

        // Verify the change
        $groupData = $this->repository->getGroupData($nextId);
        $this->assertEquals('Updated description', $groupData['description']);

        // Cleanup
        $this->dbHandle->query('DELETE FROM faqgroup WHERE group_id = ' . $nextId);
    }

    public function testDeleteGroup(): void
    {
        // Test with invalid group ID
        $this->assertFalse($this->repository->deleteGroup(0));
        $this->assertFalse($this->repository->deleteGroup(-1));

        // Add a test group to delete
        $groupData = [
            'name' => 'group_to_delete',
            'description' => 'Group to be deleted',
            'auto_join' => 0,
        ];

        $nextId = $this->repository->nextGroupId();
        $this->repository->addGroup($groupData, $nextId);

        // Delete the group
        $this->assertTrue($this->repository->deleteGroup($nextId));

        // Verify it was deleted
        $this->assertEquals(0, $this->repository->getGroupId('group_to_delete'));
    }

    public function testGetGroupMembers(): void
    {
        $result = $this->repository->getGroupMembers(1);
        $this->assertIsArray($result);

        // Test with invalid group ID
        $this->assertEmpty($this->repository->getGroupMembers(0));
        $this->assertEmpty($this->repository->getGroupMembers(-1));
    }

    public function testGetUserGroups(): void
    {
        $result = $this->repository->getUserGroups(1);
        $this->assertIsArray($result);
        $this->assertContains(-1, $result);

        // Test with invalid user ID
        $invalidResult = $this->repository->getUserGroups(0);
        $this->assertEquals([-1], $invalidResult);
    }

    public function testGetAllGroups(): void
    {
        $result = $this->repository->getAllGroups();
        $this->assertIsArray($result);

        // Test with specific user ID
        $userResult = $this->repository->getAllGroups(1);
        $this->assertIsArray($userResult);
    }

    public function testGetGroupName(): void
    {
        // Test with invalid group IDs
        $this->assertEquals('-', $this->repository->getGroupName(0));
        $this->assertEquals('-', $this->repository->getGroupName(-1));
    }

    public function testGetUserGroupRights(): void
    {
        $result = $this->repository->getUserGroupRights(1);
        $this->assertIsArray($result);

        // Test with invalid user ID
        $this->assertEmpty($this->repository->getUserGroupRights(0));
        $this->assertEmpty($this->repository->getUserGroupRights(-1));
    }

    public function testGetAutoJoinGroups(): void
    {
        $result = $this->repository->getAutoJoinGroups();
        $this->assertIsArray($result);
    }

    public function testAddToGroup(): void
    {
        // Test with invalid inputs
        $this->assertFalse($this->repository->addToGroup(0, 1));
        $this->assertFalse($this->repository->addToGroup(1, 0));
        $this->assertFalse($this->repository->addToGroup(-1, -1));
    }

    public function testGetGroupData(): void
    {
        // Test with invalid group ID
        $this->assertEmpty($this->repository->getGroupData(0));
        $this->assertEmpty($this->repository->getGroupData(-1));
    }

    public function testRemoveFromAllGroups(): void
    {
        // Test with invalid user ID
        $this->assertFalse($this->repository->removeFromAllGroups(0));
        $this->assertFalse($this->repository->removeFromAllGroups(-1));
    }

    public function testRefuseAllGroupRights(): void
    {
        // Test with invalid group ID
        $this->assertFalse($this->repository->refuseAllGroupRights(0));
        $this->assertFalse($this->repository->refuseAllGroupRights(-1));
    }

    public function testRemoveAllUsersFromGroup(): void
    {
        // Test with invalid group ID
        $this->assertFalse($this->repository->removeAllUsersFromGroup(0));
        $this->assertFalse($this->repository->removeAllUsersFromGroup(-1));
    }

    public function testNextGroupId(): void
    {
        $nextId = $this->repository->nextGroupId();
        $this->assertIsInt($nextId);
        $this->assertGreaterThan(0, $nextId);
    }

    public function testDeleteGroupMemberships(): void
    {
        // Add a test group
        $groupData = [
            'name' => 'group_for_membership_test',
            'description' => 'Test group',
            'auto_join' => 0,
        ];

        $nextId = $this->repository->nextGroupId();
        $this->repository->addGroup($groupData, $nextId);

        // Delete memberships (should work even if there are none)
        $this->assertTrue($this->repository->deleteGroupMemberships($nextId));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faqgroup WHERE group_id = ' . $nextId);
    }

    public function testDeleteGroupRights(): void
    {
        // Add a test group
        $groupData = [
            'name' => 'group_for_rights_test',
            'description' => 'Test group',
            'auto_join' => 0,
        ];

        $nextId = $this->repository->nextGroupId();
        $this->repository->addGroup($groupData, $nextId);

        // Delete rights (should work even if there are none)
        $this->assertTrue($this->repository->deleteGroupRights($nextId));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faqgroup WHERE group_id = ' . $nextId);
    }
}