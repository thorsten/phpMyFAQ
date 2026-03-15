<?php

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GroupCategoryPermissionRepositoryTest extends TestCase
{
    private Sqlite3 $dbHandle;

    private Configuration $configuration;

    private GroupCategoryPermissionRepository $repository;

    private string $databasePath;

    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-group-cat-perm-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);
        $this->configuration = new Configuration($this->dbHandle);

        // Clean up test data
        $this->dbHandle->query('DELETE FROM faqgroup_right_category');
        $this->dbHandle->query('DELETE FROM faqgroup_right');
        $this->dbHandle->query('DELETE FROM faquser_group');
        $this->dbHandle->query('DELETE FROM faqgroup');

        $this->repository = new GroupCategoryPermissionRepository($this->configuration);
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

    public function testGetCategoryRestrictionsReturnsEmptyForInvalidInput(): void
    {
        $this->assertEmpty($this->repository->getCategoryRestrictions(0, 1));
        $this->assertEmpty($this->repository->getCategoryRestrictions(1, 0));
        $this->assertEmpty($this->repository->getCategoryRestrictions(-1, -1));
    }

    public function testGetCategoryRestrictionsReturnsEmptyWhenNoRestrictions(): void
    {
        $this->assertEmpty($this->repository->getCategoryRestrictions(1, 1));
    }

    public function testSetAndGetCategoryRestrictions(): void
    {
        $this->assertTrue($this->repository->setCategoryRestrictions(1, 1, [10, 20, 30]));

        $restrictions = $this->repository->getCategoryRestrictions(1, 1);
        $this->assertCount(3, $restrictions);
        $this->assertContains(10, $restrictions);
        $this->assertContains(20, $restrictions);
        $this->assertContains(30, $restrictions);
    }

    public function testSetCategoryRestrictionsReplacesExisting(): void
    {
        $this->assertTrue($this->repository->setCategoryRestrictions(1, 1, [10, 20]));
        $this->assertCount(2, $this->repository->getCategoryRestrictions(1, 1));

        $this->assertTrue($this->repository->setCategoryRestrictions(1, 1, [30, 40, 50]));
        $restrictions = $this->repository->getCategoryRestrictions(1, 1);
        $this->assertCount(3, $restrictions);
        $this->assertContains(30, $restrictions);
        $this->assertNotContains(10, $restrictions);
    }

    public function testSetCategoryRestrictionsWithEmptyArrayClearsRestrictions(): void
    {
        $this->assertTrue($this->repository->setCategoryRestrictions(1, 1, [10, 20]));
        $this->assertCount(2, $this->repository->getCategoryRestrictions(1, 1));

        $this->assertTrue($this->repository->setCategoryRestrictions(1, 1, []));
        $this->assertEmpty($this->repository->getCategoryRestrictions(1, 1));
    }

    public function testSetCategoryRestrictionsReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->setCategoryRestrictions(0, 1, [10]));
        $this->assertFalse($this->repository->setCategoryRestrictions(1, 0, [10]));
    }

    public function testDeleteCategoryRestrictions(): void
    {
        $this->repository->setCategoryRestrictions(1, 1, [10, 20]);
        $this->repository->setCategoryRestrictions(1, 2, [30]);

        $this->assertTrue($this->repository->deleteCategoryRestrictions(1, 1));
        $this->assertEmpty($this->repository->getCategoryRestrictions(1, 1));
        $this->assertCount(1, $this->repository->getCategoryRestrictions(1, 2));
    }

    public function testDeleteCategoryRestrictionsReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->deleteCategoryRestrictions(0, 1));
        $this->assertFalse($this->repository->deleteCategoryRestrictions(1, 0));
    }

    public function testDeleteAllForGroup(): void
    {
        $this->repository->setCategoryRestrictions(1, 1, [10, 20]);
        $this->repository->setCategoryRestrictions(1, 2, [30]);
        $this->repository->setCategoryRestrictions(2, 1, [40]);

        $this->assertTrue($this->repository->deleteAllForGroup(1));
        $this->assertEmpty($this->repository->getCategoryRestrictions(1, 1));
        $this->assertEmpty($this->repository->getCategoryRestrictions(1, 2));
        $this->assertCount(1, $this->repository->getCategoryRestrictions(2, 1));
    }

    public function testDeleteAllForGroupReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->deleteAllForGroup(0));
    }

    public function testGetAllCategoryRestrictions(): void
    {
        $this->assertEmpty($this->repository->getAllCategoryRestrictions(0));

        $this->repository->setCategoryRestrictions(1, 1, [10, 20]);
        $this->repository->setCategoryRestrictions(1, 3, [30]);

        $all = $this->repository->getAllCategoryRestrictions(1);
        $this->assertCount(2, $all);
        $this->assertArrayHasKey(1, $all);
        $this->assertArrayHasKey(3, $all);
        $this->assertContains(10, $all[1]);
        $this->assertContains(20, $all[1]);
        $this->assertContains(30, $all[3]);
    }

    public function testCheckUserGroupRightForCategoryWithNoRestrictions(): void
    {
        // Set up: create group, add user, grant right
        $this->dbHandle->query("INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (1, 'TestGroup', 'Test', 0)");
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 1)');
        $this->dbHandle->query('INSERT INTO faqgroup_right (group_id, right_id) VALUES (1, 1)');

        // No category restrictions -> should have access to any category
        $this->assertTrue($this->repository->checkUserGroupRightForCategory(1, 1, 99));
    }

    public function testCheckUserGroupRightForCategoryWithMatchingRestriction(): void
    {
        $this->dbHandle->query("INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (1, 'TestGroup', 'Test', 0)");
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 1)');
        $this->dbHandle->query('INSERT INTO faqgroup_right (group_id, right_id) VALUES (1, 1)');

        // Restrict right 1 to category 10
        $this->repository->setCategoryRestrictions(1, 1, [10, 20]);

        // Should have access to category 10
        $this->assertTrue($this->repository->checkUserGroupRightForCategory(1, 1, 10));
        // Should have access to category 20
        $this->assertTrue($this->repository->checkUserGroupRightForCategory(1, 1, 20));
        // Should NOT have access to category 30
        $this->assertFalse($this->repository->checkUserGroupRightForCategory(1, 1, 30));
    }

    public function testCheckUserGroupRightForCategoryReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->checkUserGroupRightForCategory(0, 1, 1));
        $this->assertFalse($this->repository->checkUserGroupRightForCategory(1, 0, 1));
        $this->assertFalse($this->repository->checkUserGroupRightForCategory(1, 1, 0));
    }

    public function testCheckUserGroupRightForCategoryWithMultipleGroups(): void
    {
        // Group 1: restricted to category 10
        $this->dbHandle->query("INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (1, 'Group1', 'Test', 0)");
        $this->dbHandle->query('INSERT INTO faqgroup_right (group_id, right_id) VALUES (1, 1)');
        $this->repository->setCategoryRestrictions(1, 1, [10]);

        // Group 2: unrestricted (no category restrictions)
        $this->dbHandle->query("INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (2, 'Group2', 'Test', 0)");
        $this->dbHandle->query('INSERT INTO faqgroup_right (group_id, right_id) VALUES (2, 1)');

        // User in both groups
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 1)');
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 2)');

        // User should have access to any category because Group 2 is unrestricted
        $this->assertTrue($this->repository->checkUserGroupRightForCategory(1, 1, 99));
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
