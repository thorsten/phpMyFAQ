<?php

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class LanguagePermissionRepositoryTest extends TestCase
{
    private Sqlite3 $dbHandle;

    private Configuration $configuration;

    private LanguagePermissionRepository $repository;

    private string $databasePath;

    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-lang-perm-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);
        $this->configuration = new Configuration($this->dbHandle);

        // Clean up test data
        $this->dbHandle->query('DELETE FROM faquser_right_language');
        $this->dbHandle->query('DELETE FROM faqgroup_right_language');
        $this->dbHandle->query('DELETE FROM faqgroup_right');
        $this->dbHandle->query('DELETE FROM faquser_group');
        $this->dbHandle->query('DELETE FROM faqgroup');

        $this->repository = new LanguagePermissionRepository($this->configuration);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
        }

        // setUp() points the Database statics at this test's handle; leaving them
        // set would hand a later test in the same process a closed connection.
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseReflection->getProperty('databaseDriver')->setValue(null, null);
        $databaseReflection->getProperty('dbType')->setValue(null, '');

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testGetUserLanguageRestrictionsReturnsEmptyForInvalidInput(): void
    {
        $this->assertEmpty($this->repository->getUserLanguageRestrictions(0, 1));
        $this->assertEmpty($this->repository->getUserLanguageRestrictions(1, 0));
        $this->assertEmpty($this->repository->getUserLanguageRestrictions(-1, -1));
    }

    public function testGetUserLanguageRestrictionsReturnsEmptyWhenNoRestrictions(): void
    {
        $this->assertEmpty($this->repository->getUserLanguageRestrictions(1, 1));
    }

    public function testSetAndGetUserLanguageRestrictions(): void
    {
        $this->assertTrue($this->repository->setUserLanguageRestrictions(1, 1, ['en', 'de']));

        $restrictions = $this->repository->getUserLanguageRestrictions(1, 1);
        $this->assertCount(2, $restrictions);
        $this->assertContains('en', $restrictions);
        $this->assertContains('de', $restrictions);
    }

    public function testSetUserLanguageRestrictionsReplacesExisting(): void
    {
        $this->assertTrue($this->repository->setUserLanguageRestrictions(1, 1, ['en', 'de']));
        $this->assertCount(2, $this->repository->getUserLanguageRestrictions(1, 1));

        $this->assertTrue($this->repository->setUserLanguageRestrictions(1, 1, ['fr']));
        $restrictions = $this->repository->getUserLanguageRestrictions(1, 1);
        $this->assertCount(1, $restrictions);
        $this->assertContains('fr', $restrictions);
        $this->assertNotContains('en', $restrictions);
    }

    public function testSetUserLanguageRestrictionsWithEmptyArrayClearsRestrictions(): void
    {
        $this->assertTrue($this->repository->setUserLanguageRestrictions(1, 1, ['en', 'de']));
        $this->assertCount(2, $this->repository->getUserLanguageRestrictions(1, 1));

        $this->assertTrue($this->repository->setUserLanguageRestrictions(1, 1, []));
        $this->assertEmpty($this->repository->getUserLanguageRestrictions(1, 1));
    }

    public function testSetUserLanguageRestrictionsSkipsUnsupportedLanguageCodes(): void
    {
        $this->assertTrue($this->repository->setUserLanguageRestrictions(1, 1, ['en', 'not-a-language']));

        $restrictions = $this->repository->getUserLanguageRestrictions(1, 1);
        $this->assertCount(1, $restrictions);
        $this->assertContains('en', $restrictions);
    }

    /**
     * An empty restriction set means "unrestricted", so silently dropping every
     * unsupported code would widen the right instead of narrowing it. The write
     * must fail closed and leave the existing restrictions untouched.
     */
    public function testSetUserLanguageRestrictionsRejectsAllUnsupportedLanguageCodes(): void
    {
        $this->repository->setUserLanguageRestrictions(1, 1, ['de']);

        $this->assertFalse($this->repository->setUserLanguageRestrictions(1, 1, ['not-a-language', 'xx-YY']));

        // The previous restriction must survive the refused write.
        $this->assertSame(['de'], $this->repository->getUserLanguageRestrictions(1, 1));
    }

    public function testSetLanguageRestrictionsRejectsAllUnsupportedLanguageCodes(): void
    {
        $this->repository->setLanguageRestrictions(1, 1, ['de']);

        $this->assertFalse($this->repository->setLanguageRestrictions(1, 1, ['not-a-language']));

        $this->assertSame(['de'], $this->repository->getLanguageRestrictions(1, 1));
    }

    public function testSetUserLanguageRestrictionsReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->setUserLanguageRestrictions(0, 1, ['en']));
        $this->assertFalse($this->repository->setUserLanguageRestrictions(1, 0, ['en']));
    }

    public function testDeleteUserLanguageRestrictions(): void
    {
        $this->repository->setUserLanguageRestrictions(1, 1, ['en', 'de']);
        $this->repository->setUserLanguageRestrictions(1, 2, ['fr']);

        $this->assertTrue($this->repository->deleteUserLanguageRestrictions(1, 1));
        $this->assertEmpty($this->repository->getUserLanguageRestrictions(1, 1));
        $this->assertCount(1, $this->repository->getUserLanguageRestrictions(1, 2));
    }

    public function testDeleteUserLanguageRestrictionsReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->deleteUserLanguageRestrictions(0, 1));
        $this->assertFalse($this->repository->deleteUserLanguageRestrictions(1, 0));
    }

    public function testDeleteAllForUser(): void
    {
        $this->repository->setUserLanguageRestrictions(1, 1, ['en']);
        $this->repository->setUserLanguageRestrictions(1, 2, ['de']);
        $this->repository->setUserLanguageRestrictions(2, 1, ['fr']);

        $this->assertTrue($this->repository->deleteAllForUser(1));
        $this->assertEmpty($this->repository->getUserLanguageRestrictions(1, 1));
        $this->assertEmpty($this->repository->getUserLanguageRestrictions(1, 2));
        $this->assertCount(1, $this->repository->getUserLanguageRestrictions(2, 1));
    }

    public function testDeleteAllForUserReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->deleteAllForUser(0));
    }

    public function testCheckUserRightForLanguageWithNoRestrictions(): void
    {
        $this->dbHandle->query('INSERT INTO faquser_right (user_id, right_id) VALUES (1, 1)');

        $this->assertTrue($this->repository->checkUserRightForLanguage(1, 1, 'en'));
        $this->assertTrue($this->repository->checkUserRightForLanguage(1, 1, 'de'));
    }

    public function testCheckUserRightForLanguageWithMatchingRestriction(): void
    {
        $this->repository->setUserLanguageRestrictions(1, 1, ['de', 'fr']);

        $this->assertTrue($this->repository->checkUserRightForLanguage(1, 1, 'de'));
        $this->assertTrue($this->repository->checkUserRightForLanguage(1, 1, 'fr'));
        $this->assertFalse($this->repository->checkUserRightForLanguage(1, 1, 'en'));
    }

    public function testCheckUserRightForLanguageReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->checkUserRightForLanguage(0, 1, 'en'));
        $this->assertFalse($this->repository->checkUserRightForLanguage(1, 0, 'en'));
        $this->assertFalse($this->repository->checkUserRightForLanguage(1, 1, 'not-a-language'));
    }

    public function testGetLanguageRestrictionsReturnsEmptyForInvalidInput(): void
    {
        $this->assertEmpty($this->repository->getLanguageRestrictions(0, 1));
        $this->assertEmpty($this->repository->getLanguageRestrictions(1, 0));
    }

    public function testSetAndGetLanguageRestrictions(): void
    {
        $this->assertTrue($this->repository->setLanguageRestrictions(1, 1, ['en', 'de', 'fr']));

        $restrictions = $this->repository->getLanguageRestrictions(1, 1);
        $this->assertCount(3, $restrictions);
        $this->assertContains('en', $restrictions);
        $this->assertContains('de', $restrictions);
        $this->assertContains('fr', $restrictions);
    }

    public function testSetLanguageRestrictionsReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->setLanguageRestrictions(0, 1, ['en']));
        $this->assertFalse($this->repository->setLanguageRestrictions(1, 0, ['en']));
    }

    public function testDeleteLanguageRestrictions(): void
    {
        $this->repository->setLanguageRestrictions(1, 1, ['en', 'de']);
        $this->repository->setLanguageRestrictions(1, 2, ['fr']);

        $this->assertTrue($this->repository->deleteLanguageRestrictions(1, 1));
        $this->assertEmpty($this->repository->getLanguageRestrictions(1, 1));
        $this->assertCount(1, $this->repository->getLanguageRestrictions(1, 2));
    }

    public function testDeleteLanguageRestrictionsReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->deleteLanguageRestrictions(0, 1));
        $this->assertFalse($this->repository->deleteLanguageRestrictions(1, 0));
    }

    public function testDeleteAllForGroup(): void
    {
        $this->repository->setLanguageRestrictions(1, 1, ['en', 'de']);
        $this->repository->setLanguageRestrictions(1, 2, ['fr']);
        $this->repository->setLanguageRestrictions(2, 1, ['es']);

        $this->assertTrue($this->repository->deleteAllForGroup(1));
        $this->assertEmpty($this->repository->getLanguageRestrictions(1, 1));
        $this->assertEmpty($this->repository->getLanguageRestrictions(1, 2));
        $this->assertCount(1, $this->repository->getLanguageRestrictions(2, 1));
    }

    public function testDeleteAllForGroupReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->deleteAllForGroup(0));
    }

    public function testGetAllLanguageRestrictions(): void
    {
        $this->assertEmpty($this->repository->getAllLanguageRestrictions(0));

        $this->repository->setLanguageRestrictions(1, 1, ['en', 'de']);
        $this->repository->setLanguageRestrictions(1, 3, ['fr']);

        $all = $this->repository->getAllLanguageRestrictions(1);
        $this->assertCount(2, $all);
        $this->assertArrayHasKey(1, $all);
        $this->assertArrayHasKey(3, $all);
        $this->assertContains('en', $all[1]);
        $this->assertContains('de', $all[1]);
        $this->assertContains('fr', $all[3]);
    }

    public function testCheckUserGroupRightForLanguageWithNoRestrictions(): void
    {
        $this->dbHandle->query(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (1, 'TestGroup', 'Test', 0)",
        );
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 1)');
        $this->dbHandle->query('INSERT INTO faqgroup_right (group_id, right_id) VALUES (1, 1)');

        // No language restrictions -> should have access to any language
        $this->assertTrue($this->repository->checkUserGroupRightForLanguage(1, 1, 'en'));
    }

    public function testCheckUserGroupRightForLanguageWithMatchingRestriction(): void
    {
        $this->dbHandle->query(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (1, 'TestGroup', 'Test', 0)",
        );
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 1)');
        $this->dbHandle->query('INSERT INTO faqgroup_right (group_id, right_id) VALUES (1, 1)');

        $this->repository->setLanguageRestrictions(1, 1, ['de', 'fr']);

        $this->assertTrue($this->repository->checkUserGroupRightForLanguage(1, 1, 'de'));
        $this->assertTrue($this->repository->checkUserGroupRightForLanguage(1, 1, 'fr'));
        $this->assertFalse($this->repository->checkUserGroupRightForLanguage(1, 1, 'en'));
    }

    public function testCheckUserGroupRightForLanguageReturnsFalseForInvalidInput(): void
    {
        $this->assertFalse($this->repository->checkUserGroupRightForLanguage(0, 1, 'en'));
        $this->assertFalse($this->repository->checkUserGroupRightForLanguage(1, 0, 'en'));
        $this->assertFalse($this->repository->checkUserGroupRightForLanguage(1, 1, 'not-a-language'));
    }

    public function testCheckUserGroupRightForLanguageWithMultipleGroups(): void
    {
        // Group 1: restricted to 'de'
        $this->dbHandle->query(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (1, 'Group1', 'Test', 0)",
        );
        $this->dbHandle->query('INSERT INTO faqgroup_right (group_id, right_id) VALUES (1, 1)');
        $this->repository->setLanguageRestrictions(1, 1, ['de']);

        // Group 2: unrestricted (no language restrictions)
        $this->dbHandle->query(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (2, 'Group2', 'Test', 0)",
        );
        $this->dbHandle->query('INSERT INTO faqgroup_right (group_id, right_id) VALUES (2, 1)');

        // User in both groups
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 1)');
        $this->dbHandle->query('INSERT INTO faquser_group (user_id, group_id) VALUES (1, 2)');

        // User should have access to any language because Group 2 is unrestricted
        $this->assertTrue($this->repository->checkUserGroupRightForLanguage(1, 1, 'en'));
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
