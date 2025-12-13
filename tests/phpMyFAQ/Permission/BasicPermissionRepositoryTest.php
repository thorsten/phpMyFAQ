<?php

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class BasicPermissionRepositoryTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private Configuration $configuration;
    private BasicPermissionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $this->repository = new BasicPermissionRepository($this->configuration);
    }

    public function testGrantUserRight(): void
    {
        $this->assertTrue($this->repository->grantUserRight(2, 1));

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
        $result = $this->repository->getRightData(1);

        $this->assertIsArray($result);
        $this->assertEquals($expected, $result);
        $this->assertEmpty($this->repository->getRightData(999));
    }

    public function testGetRightId(): void
    {
        $this->assertEquals(1, $this->repository->getRightId('add_user'));
        $this->assertEquals(0, $this->repository->getRightId('non_existent_right'));
    }

    public function testCheckUserRight(): void
    {
        $this->assertTrue($this->repository->checkUserRight(1, 1));
        $this->assertFalse($this->repository->checkUserRight(1, 0));
        $this->assertFalse($this->repository->checkUserRight(1, 999));
    }

    public function testGetUserRights(): void
    {
        $this->assertIsArray($this->repository->getUserRights(1));
        $this->assertNotEmpty($this->repository->getUserRights(1));
        $this->assertEmpty($this->repository->getUserRights(999));
    }

    public function testAddRight(): void
    {
        $rightData = [
            'name' => 'test_permission_repo',
            'description' => 'Test permission for repository',
            'for_users' => 1,
            'for_groups' => 1,
            'for_sections' => 1,
        ];

        $nextId = $this->repository->nextRightId();
        $this->assertTrue($this->repository->addRight($rightData, $nextId));

        // Verify it was added
        $addedRightId = $this->repository->getRightId('test_permission_repo');
        $this->assertEquals($nextId, $addedRightId);

        // Cleanup
        $this->dbHandle->query('DELETE FROM faqright WHERE name = "test_permission_repo"');
    }

    public function testRenameRight(): void
    {
        // First add a right to rename
        $rightData = [
            'name' => 'temp_right_to_rename',
            'description' => 'Temporary right',
            'for_users' => 1,
            'for_groups' => 1,
            'for_sections' => 1,
        ];

        $nextId = $this->repository->nextRightId();
        $this->repository->addRight($rightData, $nextId);

        // Rename it
        $this->assertTrue($this->repository->renameRight($nextId, 'renamed_right'));

        // Verify the rename
        $renamedId = $this->repository->getRightId('renamed_right');
        $this->assertEquals($nextId, $renamedId);

        // Cleanup
        $this->dbHandle->query('DELETE FROM faqright WHERE right_id = ' . $nextId);
    }

    public function testGetAllRightsData(): void
    {
        $result = $this->repository->getAllRightsData();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Test DESC order
        $resultDesc = $this->repository->getAllRightsData('DESC');
        $this->assertIsArray($resultDesc);
        $this->assertNotEmpty($resultDesc);
    }

    public function testRefuseAllUserRights(): void
    {
        // First grant a right
        $this->repository->grantUserRight(2, 1);

        // Then refuse all
        $this->assertTrue($this->repository->refuseAllUserRights(2));

        // Verify it was removed
        $userRights = $this->repository->getUserRights(2);
        $this->assertEmpty($userRights);
    }

    public function testNextRightId(): void
    {
        $nextId = $this->repository->nextRightId();
        $this->assertIsInt($nextId);
        $this->assertGreaterThan(0, $nextId);
    }
}