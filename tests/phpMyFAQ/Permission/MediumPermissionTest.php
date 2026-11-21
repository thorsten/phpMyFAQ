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
        $this->dbHandle->query('DELETE FROM faqgroup_right_language');
        $this->dbHandle->query('DELETE FROM faquser_right_language');
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

    public function testRemoveFromGroup(): void
    {
        $this->assertFalse($this->mediumPermission->removeFromGroup(0, 0));

        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => true,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->addToGroup(1, 1);

        $this->assertTrue($this->mediumPermission->removeFromGroup(1, 1));

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

    /**
     * Revoking every group right must also drop the group's language restrictions,
     * otherwise re-granting the same right later silently re-applies the old scope.
     *
     * @throws Exception
     */
    public function testRefuseAllGroupRightsClearsLanguageRestrictionsBeforeRegrant(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setLanguageRestrictions(1, 1, ['de']);

        $this->assertFalse($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('fr'));

        $this->assertTrue($this->mediumPermission->refuseAllGroupRights(1));
        $this->assertSame([], $this->mediumPermission->getLanguageRestrictions(1, 1));

        // Re-granting the right must not resurrect the stale 'de'-only scope.
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('fr'));

        $this->mediumPermission->deleteGroup(1);
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
        $this->configuration->getDb()->query('INSERT INTO faquser_right (user_id, right_id) VALUES (1, 1)');
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
    public function testScopeAllowsCategoryWithUnrestrictedGroupRight(): void
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
        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsCategoryInAnyLanguage(99));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testScopeAllowsCategoryWithRestrictedGroupRight(): void
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
        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsCategoryInAnyLanguage(10));
        // Should NOT have access to category 20
        $this->assertFalse($this->mediumPermission->getScopeForRight(1, 1)->allowsCategoryInAnyLanguage(20));

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

    /**
     * @throws Exception
     */
    public function testScopeCategoryIdsReturnsNullForUnrestrictedGroupRight(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        $this->assertNull($this->mediumPermission->getScopeForRight(1, 1)->categoryIdsInAnyLanguage());

        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testScopeCategoryIdsReturnsRestrictedCategories(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setCategoryRestrictions(1, 1, [10, 20]);

        $this->assertSame([10, 20], $this->mediumPermission->getScopeForRight(1, 1)->categoryIdsInAnyLanguage());

        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testScopeCategoryIdsReturnsNullForDirectUserRight(): void
    {
        // Fixture user 1 owns right 1 directly (faquser_right) => global, restrictions ignored
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');

        $this->assertNull($this->mediumPermission->getScopeForRight(1, 1)->categoryIdsInAnyLanguage());
    }

    /**
     * @throws Exception
     */
    public function testScopeCategoryIdsReturnsUnionAcrossGroups(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroupOne', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addGroup(['name' => 'TestGroupTwo', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->addToGroup(1, 2);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->grantGroupRight(2, 1);
        $this->mediumPermission->setCategoryRestrictions(1, 1, [10, 20]);
        $this->mediumPermission->setCategoryRestrictions(2, 1, [20, 30]);

        $this->assertSame([10, 20, 30], $this->mediumPermission->getScopeForRight(1, 1)->categoryIdsInAnyLanguage());

        $this->mediumPermission->deleteGroup(1);
        $this->mediumPermission->deleteGroup(2);
    }

    /**
     * @throws Exception
     */
    public function testScopeCategoryIdsReturnsEmptyArrayWithoutAnyGrant(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->assertSame([], $this->mediumPermission->getScopeForRight(1, 1)->categoryIdsInAnyLanguage());
    }

    /**
     * Pins that categoryIdsInAnyLanguage() and allowsCategoryInAnyLanguage()
     * agree for every category: a category is in the allowed set (or the set
     * is null) exactly when the per-category check grants it.
     *
     * @throws Exception
     */
    public function testScopeCategoryIdsMatchesPerCategoryChecks(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroupOne', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addGroup(['name' => 'TestGroupTwo', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->addToGroup(1, 2);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->grantGroupRight(2, 1);

        $categories = [10, 20, 30, 40];

        // Both groups restricted: allowed set is the union of the restrictions
        $this->mediumPermission->setCategoryRestrictions(1, 1, [10, 20]);
        $this->mediumPermission->setCategoryRestrictions(2, 1, [20, 30]);
        $this->assertConsistentWithPerCategoryChecks(1, 1, $categories);

        // One group unrestricted: the right applies globally
        $this->mediumPermission->setCategoryRestrictions(2, 1, []);
        $this->assertNull($this->mediumPermission->getScopeForRight(1, 1)->categoryIdsInAnyLanguage());
        $this->assertConsistentWithPerCategoryChecks(1, 1, $categories);

        $this->mediumPermission->deleteGroup(1);
        $this->mediumPermission->deleteGroup(2);
    }

    /**
     * @param array<int> $categories
     * @throws Exception
     */
    private function assertConsistentWithPerCategoryChecks(int $userId, int $rightId, array $categories): void
    {
        $allowed = $this->mediumPermission->getScopeForRight($userId, $rightId)->categoryIdsInAnyLanguage();
        foreach ($categories as $categoryId) {
            $this->assertSame(
                $allowed === null || in_array($categoryId, $allowed, strict: true),
                $this->mediumPermission->getScopeForRight($userId, $rightId)->allowsCategoryInAnyLanguage($categoryId),
                sprintf('Mismatch for category %d (allowed: %s)', $categoryId, json_encode($allowed)),
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testScopeAllowsLanguageWithUnrestrictedGroupRight(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        // No language restrictions -> should have access to any language
        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('en'));

        $this->mediumPermission->deleteGroup(1);
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
        $this->configuration->getDb()->query('INSERT INTO faquser_right (user_id, right_id) VALUES (1, 1)');
    }

    /**
     * @throws Exception
     */
    public function testScopeAllowsLanguageWithRestrictedGroupRight(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setLanguageRestrictions(1, 1, ['de']);

        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('de'));
        $this->assertFalse($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('en'));

        $this->mediumPermission->deleteGroup(1);
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
        $this->configuration->getDb()->query('INSERT INTO faquser_right (user_id, right_id) VALUES (1, 1)');
    }

    /**
     * @throws Exception
     */
    public function testScopeAllowsLanguageWithRestrictedDirectUserGrantOnly(): void
    {
        // Fixture user 1 owns right 1 directly; restrict it to 'de' only, no groups involved
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, ['de']);

        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('de'));
        $this->assertFalse($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('en'));

        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, []);
    }

    /**
     * @throws Exception
     */
    public function testScopeAllowsLanguageCombinesUserAndGroupGrantsAsUnion(): void
    {
        // Fixture user 1 owns right 1 directly, restricted to 'de'
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, ['de']);

        // Also a member of a group holding right 1, restricted to 'fr'
        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setLanguageRestrictions(1, 1, ['fr']);

        // Union of both grants: 'de' (direct) and 'fr' (group) both pass, 'es' fails
        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('de'));
        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('fr'));
        $this->assertFalse($this->mediumPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('es'));

        $this->mediumPermission->deleteGroup(1);
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, []);
    }

    public function testGetAndSetLanguageRestrictions(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->assertEmpty($this->mediumPermission->getLanguageRestrictions(1, 1));

        $this->assertTrue($this->mediumPermission->setLanguageRestrictions(1, 1, ['en', 'de']));
        $restrictions = $this->mediumPermission->getLanguageRestrictions(1, 1);
        $this->assertCount(2, $restrictions);
        $this->assertContains('en', $restrictions);
        $this->assertContains('de', $restrictions);

        $this->mediumPermission->deleteGroup(1);
    }

    public function testGetAndSetUserLanguageRestrictions(): void
    {
        $this->assertEmpty($this->mediumPermission->getUserLanguageRestrictions(1, 1));

        $this->assertTrue($this->mediumPermission->setUserLanguageRestrictions(1, 1, ['en', 'de']));
        $restrictions = $this->mediumPermission->getUserLanguageRestrictions(1, 1);
        $this->assertCount(2, $restrictions);
        $this->assertContains('en', $restrictions);
        $this->assertContains('de', $restrictions);

        $this->mediumPermission->setUserLanguageRestrictions(1, 1, []);
    }

    public function testGetAllLanguageRestrictions(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);

        $this->mediumPermission->setLanguageRestrictions(1, 1, ['en']);
        $this->mediumPermission->setLanguageRestrictions(1, 2, ['de', 'fr']);

        $all = $this->mediumPermission->getAllLanguageRestrictions(1);
        $this->assertCount(2, $all);
        $this->assertArrayHasKey(1, $all);
        $this->assertArrayHasKey(2, $all);

        $this->mediumPermission->deleteGroup(1);
    }

    public function testDeleteGroupCleansLanguageRestrictions(): void
    {
        $groupData = [
            'name' => 'TestGroup',
            'description' => 'TestDescription',
            'auto_join' => false,
        ];
        $this->mediumPermission->addGroup($groupData);
        $this->mediumPermission->setLanguageRestrictions(1, 1, ['en', 'de']);

        $this->assertTrue($this->mediumPermission->deleteGroup(1));
        $this->assertEmpty($this->mediumPermission->getAllLanguageRestrictions(1));
    }

    /**
     * @throws Exception
     */
    public function testScopeLanguagesReturnsNullForUnrestrictedGroupRight(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        $this->assertNull($this->mediumPermission->getScopeForRight(1, 1)->languagesInAnyCategory());

        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testScopeLanguagesReturnsRestrictedLanguages(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setLanguageRestrictions(1, 1, ['en', 'de']);

        $this->assertEqualsCanonicalizing(['en', 'de'], $this->mediumPermission->getScopeForRight(1, 1)->languagesInAnyCategory());

        $this->mediumPermission->deleteGroup(1);
    }

    /**
     * @throws Exception
     */
    public function testScopeLanguagesReturnsNullForUnrestrictedDirectUserRight(): void
    {
        // Fixture user 1 owns right 1 directly (faquser_right), unrestricted => global
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');

        $this->assertNull($this->mediumPermission->getScopeForRight(1, 1)->languagesInAnyCategory());
    }

    /**
     * @throws Exception
     */
    public function testScopeLanguagesReturnsUnionAcrossUserAndGroups(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, ['de']);

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setLanguageRestrictions(1, 1, ['fr']);

        $this->assertEqualsCanonicalizing(['de', 'fr'], $this->mediumPermission->getScopeForRight(1, 1)->languagesInAnyCategory());

        $this->mediumPermission->deleteGroup(1);
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, []);
    }

    /**
     * @throws Exception
     */
    public function testScopeLanguagesReturnsEmptyArrayWithoutAnyGrant(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->assertSame([], $this->mediumPermission->getScopeForRight(1, 1)->languagesInAnyCategory());
    }

    /**
     * Pins that languagesInAnyCategory() and allowsLanguageInAnyCategory()
     * agree for every language: a language is in the allowed set (or the set
     * is null) exactly when the per-language check grants it.
     *
     * @throws Exception
     */
    public function testScopeLanguagesMatchesPerLanguageChecks(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, ['en', 'de']);

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setLanguageRestrictions(1, 1, ['de', 'fr']);

        $languages = ['en', 'de', 'fr', 'es'];

        // Both grants restricted: allowed set is the union
        $this->assertConsistentWithPerLanguageChecks(1, 1, $languages);

        // Group grant becomes unrestricted: the right applies globally
        $this->mediumPermission->setLanguageRestrictions(1, 1, []);
        $this->assertNull($this->mediumPermission->getScopeForRight(1, 1)->languagesInAnyCategory());
        $this->assertConsistentWithPerLanguageChecks(1, 1, $languages);

        $this->mediumPermission->deleteGroup(1);
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, []);
    }

    /**
     * @param array<string> $languages
     * @throws Exception
     */
    private function assertConsistentWithPerLanguageChecks(int $userId, int $rightId, array $languages): void
    {
        $allowed = $this->mediumPermission->getScopeForRight($userId, $rightId)->languagesInAnyCategory();
        foreach ($languages as $language) {
            $this->assertSame(
                $allowed === null || in_array($language, $allowed, strict: true),
                $this->mediumPermission->getScopeForRight($userId, $rightId)->allowsLanguageInAnyCategory($language),
                sprintf('Mismatch for language %s (allowed: %s)', $language, json_encode($allowed)),
            );
        }
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

    public function testHasPermissionGrantsThroughGroupRightForNonSuperAdmin(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);

        $this->assertTrue($this->mediumPermission->hasPermission(1, 1));
        $this->assertTrue($this->mediumPermission->hasPermission(1, PermissionType::USER_ADD));
        $this->assertFalse($this->mediumPermission->hasPermission(1, 999));

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
        $this->configuration->getDb()->query('INSERT INTO faquser_right (user_id, right_id) VALUES (1, 1)');
    }

    public function testAddGroupReturnsZeroWhenRepositoryInsertFails(): void
    {
        $repository = $this->createStub(MediumPermissionRepository::class);
        $repository->method('getGroupId')->willReturn(0);
        $repository->method('nextGroupId')->willReturn(7);
        $repository->method('addGroup')->willReturn(false);
        $this->injectMediumRepository($repository);

        $this->assertSame(0, $this->mediumPermission->addGroup(['name' => 'FailingGroup']));
    }

    public function testDeleteGroupReturnsFalseWhenMembershipsCannotBeDeleted(): void
    {
        $repository = $this->createStub(MediumPermissionRepository::class);
        $repository->method('deleteGroup')->willReturn(true);
        $repository->method('deleteGroupMemberships')->willReturn(false);
        $this->injectMediumRepository($repository);

        $this->assertFalse($this->mediumPermission->deleteGroup(1));
    }

    public function testGetAllGroupsOptionsSkipsThePseudoGroupMinusOne(): void
    {
        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->dbHandle->query(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (-1, 'Pseudo', 'Test', 0)",
        );

        $user = new CurrentUser($this->configuration);
        $user->getUserById(1);

        $options = $this->mediumPermission->getAllGroupsOptions([], $user);

        $this->assertStringNotContainsString('value="-1"', $options);
        $this->assertStringContainsString('value="1"', $options);

        // Cleanup
        $this->mediumPermission->deleteGroup(1);
        $this->dbHandle->query('DELETE FROM faqgroup WHERE group_id = -1');
    }

    public function testGetAllGroupsReturnsOnlyOwnGroupsWithoutCategoryRestrictions(): void
    {
        $this->configuration->set('main.enableCategoryRestrictions', 'false');
        $this->mediumPermission->addGroup(['name' => 'OwnGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addGroup(['name' => 'OtherGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (2, 1)');

        $user = $this->createStub(CurrentUser::class);
        $user->method('getUserId')->willReturn(2);
        $user->method('isSuperAdmin')->willReturn(false);

        try {
            $this->assertSame([1], $this->mediumPermission->getAllGroups($user));
        } finally {
            $this->configuration->set('main.enableCategoryRestrictions', 'true');
            $this->mediumPermission->deleteGroup(1);
            $this->mediumPermission->deleteGroup(2);
        }
    }

    public function testGetUserRightsCountCountsAllUserRights(): void
    {
        $user = new CurrentUser($this->configuration);
        $user->getUserById(1);

        $this->assertSame(
            count($this->mediumPermission->getAllUserRights(1)),
            $this->mediumPermission->getUserRightsCount($user),
        );
        $this->assertGreaterThan(0, $this->mediumPermission->getUserRightsCount($user));
    }

    public function testAddToGroupRejectsUnknownGroup(): void
    {
        $this->assertFalse($this->mediumPermission->addToGroup(1, 999));
    }

    public function testScopeAllowsCategoryGrantsThroughDirectUserRight(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');

        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->allowsCategoryInAnyLanguage(10));
        $this->assertFalse($this->mediumPermission->getScopeForRight(1, 999)->allowsCategoryInAnyLanguage(10));

        // Cleanup
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
    }

    /**
     * @throws Exception
     */
    public function testSuperAdminBypassesCategoryAndLanguageRestrictions(): void
    {
        $this->assertNull($this->mediumPermission->getScopeForRight(1, 999)->categoryIdsInAnyLanguage());
        $this->assertNull($this->mediumPermission->getScopeForRight(1, 999)->languagesInAnyCategory());
        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 999)->allowsLanguageInAnyCategory('en'));
    }

    /**
     * @throws Exception
     */
    public function testScopeKeepsTheRestrictionsOfEachGrantApart(): void
    {
        $this->grantGermanDirectlyAndCategoryOneThroughGroup();

        $scope = $this->mediumPermission->getScopeForRight(1, 1);

        // Neither the direct "German" grant nor the "category 1" group grant covers
        // category 2 in English.
        $this->assertFalse($scope->allows([2], 'en'));
        $this->assertFalse($scope->allows([], 'en'));
        $this->assertFalse($scope->isUnrestricted());

        $this->assertTrue($scope->allows([2], 'de'));
        $this->assertTrue($scope->allows([], 'de'));
        $this->assertTrue($scope->allows([1], 'en'));
    }

    /**
     * @throws Exception
     */
    public function testScopeOfSeparateGrantsDoesNotCoverAnUnrestrictedGrant(): void
    {
        $this->grantGermanDirectlyAndCategoryOneThroughGroup();

        $scope = $this->mediumPermission->getScopeForRight(1, 1);

        $this->assertFalse($scope->covers(null, null));
        $this->assertFalse($scope->covers([2], ['en']));
        $this->assertTrue($scope->covers(null, ['de']));
        $this->assertTrue($scope->covers([1], null));
        $this->assertSame(['de'], $scope->languagesInEveryCategory());
    }

    /**
     * @throws Exception
     */
    public function testScopeCombinesCategoryAndLanguageRestrictionOfOneGroup(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setCategoryRestrictions(1, 1, [1]);
        $this->mediumPermission->setLanguageRestrictions(1, 1, ['de']);

        $scope = $this->mediumPermission->getScopeForRight(1, 1);

        $this->assertTrue($scope->allows([1], 'de'));
        $this->assertFalse($scope->allows([1], 'en'));
        $this->assertFalse($scope->allows([2], 'de'));
    }

    /**
     * @throws Exception
     */
    public function testScopeIsEmptyWithoutAnyGrant(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->isEmpty());
    }

    /**
     * @throws Exception
     */
    public function testScopeIgnoresGroupsThatDoNotHoldTheRight(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->configuration->getDb()->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->mediumPermission->addGroup(['name' => 'TestGroup', 'description' => 'Test', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 2);

        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 1)->isEmpty());
    }

    /**
     * @throws Exception
     */
    public function testScopeOfSuperAdminIsUnrestricted(): void
    {
        $this->assertTrue($this->mediumPermission->getScopeForRight(1, 999)->isUnrestricted());
    }

    /**
     * The setup from the advisory: user 1 holds right 1 directly for German only, and through
     * a group for category 1 only.
     */
    private function grantGermanDirectlyAndCategoryOneThroughGroup(): void
    {
        $this->configuration->getDb()->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->mediumPermission->setUserLanguageRestrictions(1, 1, ['de']);

        $this->mediumPermission->addGroup(['name' => 'cat1-editors', 'description' => '', 'auto_join' => false]);
        $this->mediumPermission->addToGroup(1, 1);
        $this->mediumPermission->grantGroupRight(1, 1);
        $this->mediumPermission->setCategoryRestrictions(1, 1, [1]);
    }

    private function injectMediumRepository(MediumPermissionRepository $repository): void
    {
        $property = new \ReflectionProperty(MediumPermission::class, 'mediumRepository');
        $property->setValue($this->mediumPermission, $repository);
    }
}
