<?php

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BasicPermissionTest extends TestCase
{
    use TestDatabaseTrait;

    private Sqlite3 $dbHandle;

    private Configuration $configuration;
    private BasicPermission $basicPermission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbHandle = $this->connectToTestDatabaseCopy(new Sqlite3());
        $this->configuration = new Configuration($this->dbHandle);

        $this->basicPermission = new BasicPermission($this->configuration);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
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

    /**
     * Revoking every right must also drop the language restrictions, so re-granting
     * the same right later does not silently resurrect the old scope.
     */
    public function testRefuseAllUserRightsClearsLanguageRestrictions(): void
    {
        $this->basicPermission->grantUserRight(2, 1);
        $this->basicPermission->setUserLanguageRestrictions(2, 1, ['de']);
        $this->assertSame(['de'], $this->basicPermission->getUserLanguageRestrictions(2, 1));

        $this->assertTrue($this->basicPermission->refuseAllUserRights(2));

        $this->assertSame([], $this->basicPermission->getUserLanguageRestrictions(2, 1));
    }

    public function testScopeAllowsCategoryDelegatesToGlobalCheck(): void
    {
        // Right 1 granted to user 1 in the fixture DB => category is irrelevant in basic mode
        $this->assertSame(
            $this->basicPermission->hasPermission(1, 1),
            $this->basicPermission->getScopeForRight(1, 1)->allowsCategoryInAnyLanguage(99),
        );
    }

    public function testScopeAllowsCategoryDeniesWithoutGlobalRight(): void
    {
        $this->assertFalse($this->basicPermission->getScopeForRight(0, 999)->allowsCategoryInAnyLanguage(1));
    }

    public function testScopeCategoryIdsIsAlwaysUnrestricted(): void
    {
        $this->assertNull($this->basicPermission->getScopeForRight(1, 1)->categoryIdsInAnyLanguage());
    }

    public function testScopeAllowsLanguageWithNoRestrictionIsUnrestricted(): void
    {
        // Right 1 granted to user 1 in the fixture DB, no language restriction set
        $this->assertTrue($this->basicPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('en'));
        $this->assertTrue($this->basicPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('de'));
    }

    public function testScopeAllowsLanguageDeniesWithoutGlobalRight(): void
    {
        $this->assertFalse($this->basicPermission->getScopeForRight(0, 999)->allowsLanguageInAnyCategory('en'));
    }

    public function testScopeAllowsLanguageDeniesOtherLanguageWhenRestricted(): void
    {
        // Fixture user 1 is a superadmin and would bypass every restriction, so
        // demote them for the duration of this restriction-behaviour test.
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->dbHandle->query("INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, 1, 'de')");

        $this->assertTrue($this->basicPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('de'));
        $this->assertFalse($this->basicPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('en'));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faquser_right_language WHERE user_id = 1 AND right_id = 1');
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
    }

    /**
     * A superadmin holds every right implicitly, without a faquser_right row.
     * Looking up direct-grant language restrictions must not deny them.
     */
    public function testScopeAllowsLanguageAllowsSuperAdminWithoutDirectGrant(): void
    {
        $this->dbHandle->query('DELETE FROM faquser_right WHERE user_id = 1 AND right_id = 1');

        $this->assertTrue($this->basicPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('en'));
        $this->assertNull($this->basicPermission->getScopeForRight(1, 1)->languagesInAnyCategory());

        // Cleanup
        $this->dbHandle->query('INSERT INTO faquser_right (user_id, right_id) VALUES (1, 1)');
    }

    /**
     * A superadmin must not be language-restricted even when restriction rows exist.
     */
    public function testScopeAllowsLanguageIgnoresRestrictionsForSuperAdmin(): void
    {
        $this->dbHandle->query("INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, 1, 'de')");

        $this->assertTrue($this->basicPermission->getScopeForRight(1, 1)->allowsLanguageInAnyCategory('en'));
        $this->assertNull($this->basicPermission->getScopeForRight(1, 1)->languagesInAnyCategory());

        // Cleanup
        $this->dbHandle->query('DELETE FROM faquser_right_language WHERE user_id = 1 AND right_id = 1');
    }

    public function testScopeLanguagesReturnsNullWhenUnrestricted(): void
    {
        $this->assertNull($this->basicPermission->getScopeForRight(1, 1)->languagesInAnyCategory());
    }

    public function testScopeLanguagesReturnsRestrictedSet(): void
    {
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->dbHandle->query("INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, 1, 'de')");

        $allowed = $this->basicPermission->getScopeForRight(1, 1)->languagesInAnyCategory();
        $this->assertSame(['de'], $allowed);

        // Cleanup
        $this->dbHandle->query('DELETE FROM faquser_right_language WHERE user_id = 1 AND right_id = 1');
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
    }

    public function testScopeLanguagesReturnsEmptyArrayWithoutRight(): void
    {
        $this->assertSame([], $this->basicPermission->getScopeForRight(0, 999)->languagesInAnyCategory());
    }

    public function testScopeIsEmptyWithoutRight(): void
    {
        $this->assertTrue($this->basicPermission->getScopeForRight(0, 999)->isEmpty());
    }

    public function testScopeOfSuperAdminIsUnrestricted(): void
    {
        $this->assertTrue($this->basicPermission->getScopeForRight(1, 1)->isUnrestricted());
    }

    public function testScopeOfUnrestrictedDirectGrantIsUnrestricted(): void
    {
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');

        $this->assertTrue($this->basicPermission->getScopeForRight(1, 1)->isUnrestricted());

        // Cleanup
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
    }

    public function testScopeOfLanguageRestrictedDirectGrantCoversEveryCategoryInThatLanguage(): void
    {
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->dbHandle->query("INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, 1, 'de')");

        $scope = $this->basicPermission->getScopeForRight(1, 1);

        $this->assertTrue($scope->allows([1, 2], 'de'));
        $this->assertTrue($scope->allows([], 'de'));
        $this->assertFalse($scope->allows([1], 'en'));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faquser_right_language WHERE user_id = 1 AND right_id = 1');
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
    }

    public function testGrantUserRightRejectsUnknownRight(): void
    {
        $this->assertFalse($this->basicPermission->grantUserRight(2, 999));
    }

    public function testRenameRightRejectsUnknownRight(): void
    {
        $this->assertFalse($this->basicPermission->renameRight('does_not_exist', 'new_name'));
    }

    public function testGetUserGroupsIsAlwaysEmpty(): void
    {
        $this->assertSame([], $this->basicPermission->getUserGroups(1));
    }

    public function testAddRightReturnsZeroWhenRepositoryInsertFails(): void
    {
        $repository = $this->createStub(BasicPermissionRepository::class);
        $repository->method('getRightId')->willReturn(0);
        $repository->method('nextRightId')->willReturn(500);
        $repository->method('addRight')->willReturn(false);
        $this->injectRepository($repository);

        $this->assertSame(0, $this->basicPermission->addRight(['name' => 'failing_right']));
    }

    public function testRefuseAllUserRightsReturnsFalseWhenRepositoryFails(): void
    {
        $repository = $this->createStub(BasicPermissionRepository::class);
        $repository->method('refuseAllUserRights')->willReturn(false);
        $this->injectRepository($repository);

        $this->assertFalse($this->basicPermission->refuseAllUserRights(1));
    }

    public function testGetAllUserLanguageRestrictionsReturnsRestrictionsKeyedByRight(): void
    {
        $this->dbHandle->query("INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, 1, 'de')");

        $this->assertSame([1 => ['de']], $this->basicPermission->getAllUserLanguageRestrictions(1));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faquser_right_language WHERE user_id = 1 AND right_id = 1');
    }

    public function testScopeAllowsLanguageResolvesPermissionTypeToRightId(): void
    {
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 0 WHERE user_id = 1');
        $this->dbHandle->query("INSERT INTO faquser_right_language (user_id, right_id, language) VALUES (1, 1, 'de')");

        $this->assertTrue($this->basicPermission->getScopeForRight(1, PermissionType::USER_ADD)->allowsLanguageInAnyCategory('de'));
        $this->assertFalse($this->basicPermission->getScopeForRight(1, PermissionType::USER_ADD)->allowsLanguageInAnyCategory('en'));

        // Cleanup
        $this->dbHandle->query('DELETE FROM faquser_right_language WHERE user_id = 1 AND right_id = 1');
        $this->dbHandle->query('UPDATE faquser SET is_superadmin = 1 WHERE user_id = 1');
    }

    private function injectRepository(BasicPermissionRepository $repository): void
    {
        $property = new \ReflectionProperty(BasicPermission::class, 'repository');
        $property->setValue($this->basicPermission, $repository);
    }
}
