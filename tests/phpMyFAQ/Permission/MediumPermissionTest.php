<?php

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class MediumPermissionTest extends TestCase
{
    private Sqlite3 $dbHandle;

    private Configuration $configuration;

    private MediumPermission $mediumPermission;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-medium-permission-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);
        $this->configuration = new Configuration($this->dbHandle);
        $this->dbHandle->query('DELETE FROM faqgroup_right_category');
        $this->dbHandle->query('DELETE FROM faqgroup_right');
        $this->dbHandle->query('DELETE FROM faquser_group');
        $this->dbHandle->query('DELETE FROM faqgroup');

        $this->mediumPermission = new MediumPermission($this->configuration);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
        }

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
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

        $this->assertEquals('<option value="1" >TestGroup</option>', $this->mediumPermission->getAllGroupsOptions(
            [],
            $user,
        ));
        $this->assertEquals('<option value="1" selected>TestGroup</option>', $this->mediumPermission->getAllGroupsOptions(
            [1],
            $user,
        ));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testGetAllGroups(): void
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

        $this->assertEquals([1], $this->mediumPermission->getAllGroups($user));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testGetGroupName(): void
    {
        $this->assertEquals('-', $this->mediumPermission->getGroupName(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->assertEquals('TestGroup', $this->mediumPermission->getGroupName(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testGetAllUserRights(): void
    {
        $this->assertEquals([], $this->mediumPermission->getAllUserRights(0));
        $this->assertIsArray($this->mediumPermission->getAllUserRights(1));
    }

    public function testGetUserGroupRights(): void
    {
        $this->assertEmpty($this->mediumPermission->getUserGroupRights(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        $this->assertEquals([1], $this->mediumPermission->getUserGroupRights(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testAutoJoin(): void
    {
        $this->assertFalse($this->mediumPermission->autoJoin(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => true,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->assertTrue($this->mediumPermission->autoJoin(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testAddToGroup(): void
    {
        $this->assertFalse($this->mediumPermission->addToGroup(0, 0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => true,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->assertTrue($this->mediumPermission->addToGroup(1, 1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testGetGroupData(): void
    {
        $this->assertEquals([], $this->mediumPermission->getGroupData(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => true,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->assertEquals(
            [
                'name' => 'TestGroup',
                'description' => 'TestDescription',
                'auto_join' => 1,
                'group_id' => 1,
            ],
            $this->mediumPermission->getGroupData(1),
        );

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testRemoveFromAllGroups(): void
    {
        $this->assertFalse($this->mediumPermission->removeFromAllGroups(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => true,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);

        $this->assertTrue($this->mediumPermission->removeFromAllGroups(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testRefuseAllGroupRights(): void
    {
        $this->assertFalse($this->mediumPermission->refuseAllGroupRights(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => true,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        $this->assertTrue($this->mediumPermission->refuseAllGroupRights(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testRemoveAllUsersFromGroup(): void
    {
        $this->assertFalse($this->mediumPermission->removeAllUsersFromGroup(0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => true,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);

        $this->assertTrue($this->mediumPermission->removeAllUsersFromGroup(1));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testFindOrCreateGroupByName(): void
    {
        $groupName = 'TestADGroup';
        $description = 'Test AD Group Description';

        // Test creating a new group
        $groupId = $this->mediumPermission->findOrCreateGroupByName($groupName, $description);
        $this->assertGreaterThan(0, $groupId);

        // Test finding an existing group
        $existingGroupId = $this->mediumPermission->findOrCreateGroupByName($groupName, $description);
        $this->assertEquals($groupId, $existingGroupId);

        // Test creating without description
        $groupName2 = 'TestADGroup2';
        $groupId2 = $this->mediumPermission->findOrCreateGroupByName($groupName2);
        $this->assertGreaterThan(0, $groupId2);
        $this->assertNotEquals($groupId, $groupId2);

        // Cleanup
        $this->mediumPermission->deleteGroup($groupId);
        $this->mediumPermission->deleteGroup($groupId2);
    }

    /**
     * @throws Exception
     */
    public function testHasPermissionForCategoryWithUnrestrictedGroupRight(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        // No category restrictions -> should have access to any category
        $this->assertTrue($this->mediumPermission->hasPermissionForCategory(1, 1, 99));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testHasPermissionForCategoryWithRestrictedGroupRight(): void
    {
        // Make user 1 non-superadmin and remove direct user right so only group right applies
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        // Restrict right 1 to category 10
        $this->mediumPermission->setCategoryRestrictions(1, 1, [10]);

        // Should have access to category 10
        $this->assertTrue($this->mediumPermission->hasPermissionForCategory(1, 1, 10));
        // Should NOT have access to category 20
        $this->assertFalse($this->mediumPermission->hasPermissionForCategory(1, 1, 20));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
        $this->configuration->getDb()->query('INSERT INTO faquser_right (user_id, right_id) VALUES (1, 1)');
    }

    public function testGetAndSetCategoryRestrictions(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->assertEmpty($this->mediumPermission->getCategoryRestrictions(1, 1));

        $this->assertTrue($this->mediumPermission->setCategoryRestrictions(1, 1, [10, 20]));
        $restrictions = $this->mediumPermission->getCategoryRestrictions(1, 1);
        $this->assertCount(2, $restrictions);
        $this->assertContains(10, $restrictions);
        $this->assertContains(20, $restrictions);

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testGetAllCategoryRestrictions(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->mediumPermission->setCategoryRestrictions(1, 1, [10]);
        $this->mediumPermission->setCategoryRestrictions(1, 2, [20, 30]);

        $all = $this->mediumPermission->getAllCategoryRestrictions(1);
        $this->assertCount(2, $all);
        $this->assertArrayHasKey(1, $all);
        $this->assertArrayHasKey(2, $all);

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    public function testDeleteGroupCleansCategoryRestrictions(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->setCategoryRestrictions(1, 1, [10, 20]);

        $this->assertTrue($this->mediumPermission->deleteGroup(1));
        $this->assertEmpty($this->mediumPermission->getAllCategoryRestrictions(1));
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }
}
