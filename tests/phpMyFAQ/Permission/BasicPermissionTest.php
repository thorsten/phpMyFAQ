<?php

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BasicPermissionTest extends TestCase
{
    private Sqlite3 $dbHandle;

    private Configuration $configuration;
    private BasicPermission $basicPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $this->basicPermission = new BasicPermission($this->configuration);
    }

    public function testGrantUserRight(): void
    {
        $this->assertTrue($this->basicPermission->grantUserRight(2, 1));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faquser_right WHERE right_id = 1 AND user_id = 2');
    }

    public function testGetRightData(): void
    {
        $expected = [
            'right_id' => 1,
            'name' => 'add_user',
            'description' => 'Right to add user accounts',
            'for_users' => true,
            'for_groups' => true,
            'for_sections' => true,
        ];
        $this->assertIsArray($this->basicPermission->getRightData(1));
        $this->assertEquals($expected, $this->basicPermission->getRightData(1));
        $this->assertEmpty($this->basicPermission->getRightData(999));
    }

    public function testHasPermission(): void
    {
        $this->assertTrue($this->basicPermission->hasPermission(1, 1));
        $this->assertFalse($this->basicPermission->hasPermission(-1, 1));
        $this->assertFalse($this->basicPermission->hasPermission(-1, PermissionType::USER_ADD));
        $this->assertFalse($this->basicPermission->hasPermission(-1, PermissionType::USER_ADD->value));
    }

    public function testGetRightId(): void
    {
        $this->assertEquals(1, $this->basicPermission->getRightId(PermissionType::USER_ADD->value));
        $this->assertEquals(0, $this->basicPermission->getRightId('non_existent_right'));
    }

    public function testCheckUserRight(): void
    {
        $this->assertTrue($this->basicPermission->checkUserRight(1, 1));
        $this->assertFalse($this->basicPermission->checkUserRight(1, 0));
        $this->assertFalse($this->basicPermission->checkUserRight(1, 999));
    }

    public function testGetAllUserRights(): void
    {
        $this->assertIsArray($this->basicPermission->getAllUserRights(1));
        $this->assertEmpty($this->basicPermission->getAllUserRights(999));
    }

    /**
     * @throws Exception
     */
    public function testGetUserRightsCount(): void
    {
        $user = new CurrentUser($this->configuration);
        $user->getUserById(1);

        $this->assertGreaterThan(0, $this->basicPermission->getUserRightsCount($user));
        $this->assertIsInt($this->basicPermission->getUserRightsCount($user));
    }

    public function testGetUserRights(): void
    {
        $this->assertIsArray($this->basicPermission->getUserRights(1));
        $this->assertEmpty($this->basicPermission->getUserRights(999));
    }

    public function testAddRight(): void
    {
        $correct = [
            'name' => 'add_new_permission',
            'description' => 'Right to add new permissions',
            'for_users' => true,
            'for_groups' => true,
            'for_sections' => true,
        ];

        $incorrect = [
            'name' => 'add_new_permission',
            'description' => 'Right to add new permissions',
            'for_users' => true,
            'for_groups' => true,
            'for_sections' => true,
            'non_existent' => true,
        ];

        $alreadyExists = [
            'name' => 'add_user',
            'description' => 'Right to add user accounts',
            'for_users' => true,
            'for_groups' => true,
            'for_sections' => true,
        ];

        $this->assertIsInt($this->basicPermission->addRight($correct));
        $this->assertEquals(0, $this->basicPermission->addRight($incorrect));
        $this->assertEquals(0, $this->basicPermission->addRight($alreadyExists));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faqright WHERE name = "add_new_permission"');
    }

    public function testCheckRightData(): void
    {
        $permissionToCheck = [
            'name' => 'add_new_permission',
            'description' => 'Right to add new permissions',
            'for_users' => true,
            'for_groups' => true,
            'for_sections' => true,
        ];

        $this->assertIsArray($this->basicPermission->checkRightData($permissionToCheck));
        $this->assertEquals($permissionToCheck, $this->basicPermission->checkRightData($permissionToCheck));
        $this->assertEquals(
            [
                'name' => 'DEFAULT_RIGHT',
                'description' => 'Short description.',
                'for_users' => 1,
                'for_groups' => 1,
                'for_sections' => 1,
            ],
            $this->basicPermission->checkRightData([]),
        );
    }

    public function testRenameRight(): void
    {
        $this->assertTrue($this->basicPermission->renameRight('add_user', 'add_user_test'));

        // Cleanup
        $this->dbHandle->query('UPDATE faqright SET name = "add_user" WHERE right_id = 1');
    }

    public function testGetAllRightsData(): void
    {
        $this->assertIsArray($this->basicPermission->getAllRightsData());
        $this->assertNotEmpty($this->basicPermission->getAllRightsData());
    }

    public function testRefuseAllUserRights(): void
    {
        $this->basicPermission->grantUserRight(2, 1);
        $this->assertTrue($this->basicPermission->refuseAllUserRights(2));
    }
}
