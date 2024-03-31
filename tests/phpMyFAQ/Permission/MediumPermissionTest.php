<?php

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\TestCase;

class MediumPermissionTest extends TestCase
{
    private Sqlite3 $dbHandle;

    private Configuration $configuration;

    private MediumPermission $mediumPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $this->mediumPermission = new MediumPermission($this->configuration);
    }

    public function testGetGroupRights(): void
    {
        $this->assertEmpty($this->mediumPermission->getGroupRights(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        $this->assertEquals([1], $this->mediumPermission->getGroupRights(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testHasPermission(): void
    {
        $this->assertFalse($this->mediumPermission->hasPermission(0, 0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        $this->assertTrue($this->mediumPermission->hasPermission(1, 1));
        $this->assertTrue($this->mediumPermission->hasPermission(1, PermissionType::USER_ADD->value));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testCheckUserGroupRight(): void
    {
        $this->assertFalse($this->mediumPermission->checkUserGroupRight(0, 0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        $this->assertTrue($this->mediumPermission->checkUserGroupRight(1, 1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testGrantGroupRight(): void
    {
        $this->assertFalse($this->mediumPermission->grantGroupRight(0, 0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);

        $this->assertTrue($this->mediumPermission->grantGroupRight(1, 1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testAddGroup(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->assertEquals(1, $this->mediumPermission->addGroup($groupData));
        $this->assertEquals(0, $this->mediumPermission->addGroup($groupData));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testGetGroupId(): void
    {
        $this->assertEquals(0, $this->mediumPermission->getGroupId('TestGroup'));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->assertEquals(1, $this->mediumPermission->getGroupId('TestGroup'));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testCheckGroupData(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->assertEquals($groupData, $this->mediumPermission->checkGroupData($groupData));

        $expected = [
            'name' => 'DEFAULT_GROUP',
            'description' => 'Short group description.',
            'auto_join' => 0,
        ];
        $this->assertEquals($expected, $this->mediumPermission->checkGroupData([]));
    }

    public function testChangeGroup(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);

        $groupData = [
            'name' => 'TestGroup 2',
            'description' => 'TestDescription 2',
            'auto_join' => false,
        ];
        $this->assertTrue($this->mediumPermission->changeGroup(1, $groupData));
        $this->assertEquals(1, $this->mediumPermission->getGroupId('TestGroup 2'));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testDeleteGroup(): void
    {
        $this->assertFalse($this->mediumPermission->deleteGroup(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->assertTrue($this->mediumPermission->deleteGroup(1));
    }

    public function testGetGroupMembers(): void
    {
        $this->assertEmpty($this->mediumPermission->getGroupMembers(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);

        $this->assertEquals([1], $this->mediumPermission->getGroupMembers(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testGetUserGroups(): void
    {
        $this->assertEquals([-1], $this->mediumPermission->getUserGroups(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);

        $this->assertEquals([-1, 1], $this->mediumPermission->getUserGroups(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testGetAllGroupsOptions(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);

        $user = new CurrentUser($this->configuration);
        $user->getUserById(1);

        $this->assertEquals(
            '<option value="1" >TestGroup</option>',
            $this->mediumPermission->getAllGroupsOptions([], $user)
        );
        $this->assertEquals(
            '<option value="1" selected>TestGroup</option>',
            $this->mediumPermission->getAllGroupsOptions([1], $user)
        );

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }
}
