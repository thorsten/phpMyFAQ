<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\GroupCategoryPermissionRepository;
use phpMyFAQ\Permission\LanguagePermissionRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PermissionBackfillOperation::class)]
#[UsesClass(\phpMyFAQ\Auth::class)]
#[UsesClass(\phpMyFAQ\Auth\AuthDatabase::class)]
#[UsesClass(\phpMyFAQ\Auth\PasswordHasher::class)]
#[UsesClass(\phpMyFAQ\Configuration::class)]
#[UsesClass(\phpMyFAQ\Configuration\ConfigurationRepository::class)]
#[UsesClass(\phpMyFAQ\Configuration\LayoutSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\LdapSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\MailSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\SearchSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\SecuritySettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\FilesystemConfigurationCache::class)]
#[UsesClass(\phpMyFAQ\Configuration\Storage\HybridConfigurationStore::class)]
#[UsesClass(\phpMyFAQ\Configuration\UrlSettings::class)]
#[UsesClass(\phpMyFAQ\Database::class)]
#[UsesClass(\phpMyFAQ\Database\Sqlite3::class)]
#[UsesClass(\phpMyFAQ\Encryption::class)]
#[UsesClass(\phpMyFAQ\Environment::class)]
#[UsesClass(\phpMyFAQ\Language::class)]
#[UsesClass(\phpMyFAQ\Language\LanguageCodes::class)]
#[UsesClass(\phpMyFAQ\Permission::class)]
#[UsesClass(\phpMyFAQ\Permission\BasicPermission::class)]
#[UsesClass(\phpMyFAQ\Permission\BasicPermissionRepository::class)]
#[UsesClass(GroupCategoryPermissionRepository::class)]
#[UsesClass(LanguagePermissionRepository::class)]
#[UsesClass(\phpMyFAQ\Permission\MediumPermission::class)]
#[UsesClass(\phpMyFAQ\Permission\MediumPermissionRepository::class)]
#[UsesClass(\phpMyFAQ\Plugin\PluginDiscovery::class)]
#[UsesClass(\phpMyFAQ\Plugin\PluginManager::class)]
#[UsesClass(\phpMyFAQ\System::class)]
#[UsesClass(\phpMyFAQ\Translation::class)]
#[UsesClass(\phpMyFAQ\User::class)]
#[UsesClass(\phpMyFAQ\User\UserData::class)]
#[UsesTrait(\phpMyFAQ\ConfigurationMethodsTrait::class)]
class PermissionBackfillOperationTest extends TestCase
{
    private Sqlite3 $dbHandle;

    private Configuration $configuration;

    private string $databasePath;

    private ?Configuration $previousConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-perm-backfill-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->initializeDatabaseStatics($this->dbHandle);
        $this->configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $this->configuration);
        $this->configuration->set('security.permLevel', 'medium');

        $this->dbHandle->query('DELETE FROM faqgroup_right_language');
        $this->dbHandle->query('DELETE FROM faqgroup_right_category');
        $this->dbHandle->query('DELETE FROM faquser_right_language');
        $this->dbHandle->query('DELETE FROM faqgroup_right');
        $this->dbHandle->query('DELETE FROM faqgroup');
    }

    protected function tearDown(): void
    {
        $configurationReflection = new ReflectionClass(Configuration::class);
        $configurationReflection->getProperty('configuration')->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
        }

        $databaseReflection = new ReflectionClass(Database::class);
        $databaseReflection->getProperty('databaseDriver')->setValue(null, null);
        $databaseReflection->getProperty('dbType')->setValue(null, '');

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testGetTypeAndDescriptionDescribeTheBackfill(): void
    {
        $operation = new PermissionBackfillOperation(
            $this->configuration,
            'faq_publish',
            'Right to publish FAQs',
            mirrorFrom: 'approverec',
        );

        self::assertSame('permission_backfill', $operation->getType());
        self::assertSame('faq_publish', $operation->getPermissionName());
        self::assertSame('Right to publish FAQs', $operation->getPermissionDescription());
        self::assertSame('approverec', $operation->getMirrorFrom());
        self::assertStringContainsString('faq_publish', $operation->getDescription());
        self::assertStringContainsString('approverec', $operation->getDescription());
    }

    public function testToArrayCarriesTheConfiguration(): void
    {
        $operation = new PermissionBackfillOperation(
            $this->configuration,
            'view_faqs',
            'Right to view FAQs',
            grantToAllUsers: true,
            grantToAllGroups: true,
        );

        $array = $operation->toArray();

        self::assertSame('permission_backfill', $array['type']);
        self::assertSame('view_faqs', $array['permissionName']);
        self::assertTrue($array['grantToAllUsers']);
        self::assertTrue($array['grantToAllGroups']);
        self::assertNull($array['mirrorFrom']);
    }

    /**
     * PermissionGrantOperation reports failure when the right already exists, because
     * addRight() returns 0 for a duplicate name. A backfill exists precisely to enforce a right
     * that every installation already carries, so it has to succeed instead.
     */
    public function testSucceedsForARightThatAlreadyExists(): void
    {
        $this->dbHandle->query('DELETE FROM faquser_right');

        $operation = new PermissionBackfillOperation(
            $this->configuration,
            PermissionType::FAQS_VIEW->value,
            'Right to view FAQs',
            grantToAllUsers: true,
        );

        self::assertTrue($operation->execute());
        self::assertSame(1, $this->countRights(PermissionType::FAQS_VIEW->value));
        self::assertTrue($this->userHasRight(1, PermissionType::FAQS_VIEW->value));
    }

    public function testCreatesTheRightWhenItIsMissing(): void
    {
        $this->dbHandle->query("DELETE FROM faqright WHERE name = 'faq_publish'");
        self::assertSame(0, $this->countRights('faq_publish'));

        $operation = new PermissionBackfillOperation(
            $this->configuration,
            'faq_publish',
            'Right to publish FAQs',
            grantToAllUsers: true,
        );

        self::assertTrue($operation->execute());
        self::assertSame(1, $this->countRights('faq_publish'));
        self::assertTrue($this->userHasRight(1, 'faq_publish'));
    }

    /**
     * grantUserRight() is a plain INSERT, so a second run after a partially failed update must
     * not blow up on the primary key.
     */
    public function testIsIdempotent(): void
    {
        $operation = new PermissionBackfillOperation(
            $this->configuration,
            PermissionType::FAQS_VIEW->value,
            'Right to view FAQs',
            grantToAllUsers: true,
            grantToAllGroups: true,
        );

        self::assertTrue($operation->execute());
        self::assertTrue($operation->execute());

        self::assertSame(1, $this->countRights(PermissionType::FAQS_VIEW->value));
        self::assertSame(1, $this->countUserGrants(1, PermissionType::FAQS_VIEW->value));
    }

    public function testMirrorsGrantsFromTheSourceRight(): void
    {
        $this->dbHandle->query("DELETE FROM faqright WHERE name = 'faq_publish'");
        $this->dbHandle->query('DELETE FROM faquser_right');
        $approveRightId = $this->seedApproveRight();
        $this->dbHandle->query(sprintf(
            'INSERT INTO faquser_right (user_id, right_id) VALUES (1, %d)',
            $approveRightId,
        ));
        $this->createGroup(7);
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (7, %d)',
            $approveRightId,
        ));

        $operation = new PermissionBackfillOperation(
            $this->configuration,
            'faq_publish',
            'Right to publish FAQs',
            mirrorFrom: 'approverec',
        );

        self::assertTrue($operation->execute());

        self::assertTrue($this->userHasRight(1, 'faq_publish'));
        self::assertTrue($this->groupHasRight(7, 'faq_publish'));
    }

    /**
     * Without carrying the restrictions across, a group allowed to approve German FAQs in one
     * category would come out of the upgrade able to publish everything, everywhere.
     */
    public function testMirrorsCategoryAndLanguageRestrictions(): void
    {
        $this->dbHandle->query("DELETE FROM faqright WHERE name = 'faq_publish'");
        $approveRightId = $this->seedApproveRight();
        $this->createGroup(7);
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (7, %d)',
            $approveRightId,
        ));

        new GroupCategoryPermissionRepository($this->configuration)
            ->setCategoryRestrictions(7, $approveRightId, [3, 4]);
        new LanguagePermissionRepository($this->configuration)
            ->setLanguageRestrictions(7, $approveRightId, ['de']);

        $operation = new PermissionBackfillOperation(
            $this->configuration,
            'faq_publish',
            'Right to publish FAQs',
            mirrorFrom: 'approverec',
            mirrorRestrictions: true,
        );

        self::assertTrue($operation->execute());

        $publishRightId = $this->rightId('faq_publish');
        $categories = new GroupCategoryPermissionRepository($this->configuration)
            ->getCategoryRestrictions(7, $publishRightId);
        $languages = new LanguagePermissionRepository($this->configuration)
            ->getLanguageRestrictions(7, $publishRightId);

        sort($categories);
        self::assertSame([3, 4], $categories);
        self::assertSame(['de'], $languages);
    }

    /**
     * An unrestricted source grant must stay unrestricted: an empty restriction set means
     * "everywhere", so there is nothing to copy and nothing may be invented.
     */
    public function testDoesNotInventRestrictionsForAnUnrestrictedGrant(): void
    {
        $this->dbHandle->query("DELETE FROM faqright WHERE name = 'faq_publish'");
        $approveRightId = $this->seedApproveRight();
        $this->createGroup(7);
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (7, %d)',
            $approveRightId,
        ));

        $operation = new PermissionBackfillOperation(
            $this->configuration,
            'faq_publish',
            'Right to publish FAQs',
            mirrorFrom: 'approverec',
            mirrorRestrictions: true,
        );

        self::assertTrue($operation->execute());

        $publishRightId = $this->rightId('faq_publish');
        self::assertSame(
            [],
            new GroupCategoryPermissionRepository($this->configuration)
                ->getCategoryRestrictions(7, $publishRightId),
        );
    }

    /**
     * approverec is no longer seeded by DefaultDataSeeder (Task 7 retired it), but these tests
     * use it as a stand-in source right to exercise the generic mirrorFrom mechanism, so they
     * create it directly instead of relying on installation defaults.
     */
    private function seedApproveRight(): int
    {
        // A bootstrap database predating the approverec retirement may still carry the
        // right — remove it so the seed below cannot produce a duplicate source right.
        $this->dbHandle->query("DELETE FROM faqright WHERE name = 'approverec'");

        $result = $this->dbHandle->query('SELECT MAX(right_id) AS max_id FROM faqright');
        $row = $this->dbHandle->fetchArray($result);
        $rightId = (is_array($row) ? (int) $row['max_id'] : 0) + 1;

        $this->dbHandle->query(sprintf(
            "INSERT INTO faqright (right_id, name, description, for_users, for_groups, for_sections) "
            . "VALUES (%d, 'approverec', 'Right to approve FAQs', 1, 1, 1)",
            $rightId,
        ));

        return $rightId;
    }

    private function createGroup(int $groupId): void
    {
        $this->dbHandle->query(sprintf(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (%d, 'group%d', '', 0)",
            $groupId,
            $groupId,
        ));
    }

    private function rightId(string $name): int
    {
        $result = $this->dbHandle->query(sprintf("SELECT right_id FROM faqright WHERE name = '%s'", $name));
        $row = $this->dbHandle->fetchArray($result);

        return is_array($row) ? (int) $row['right_id'] : 0;
    }

    private function countRights(string $name): int
    {
        $result = $this->dbHandle->query(sprintf("SELECT COUNT(*) AS c FROM faqright WHERE name = '%s'", $name));
        $row = $this->dbHandle->fetchArray($result);

        return is_array($row) ? (int) $row['c'] : 0;
    }

    private function countUserGrants(int $userId, string $rightName): int
    {
        $result = $this->dbHandle->query(sprintf(
            'SELECT COUNT(*) AS c FROM faquser_right WHERE user_id = %d AND right_id = %d',
            $userId,
            $this->rightId($rightName),
        ));
        $row = $this->dbHandle->fetchArray($result);

        return is_array($row) ? (int) $row['c'] : 0;
    }

    private function userHasRight(int $userId, string $rightName): bool
    {
        return $this->countUserGrants($userId, $rightName) > 0;
    }

    private function groupHasRight(int $groupId, string $rightName): bool
    {
        $result = $this->dbHandle->query(sprintf(
            'SELECT COUNT(*) AS c FROM faqgroup_right WHERE group_id = %d AND right_id = %d',
            $groupId,
            $this->rightId($rightName),
        ));
        $row = $this->dbHandle->fetchArray($result);

        return is_array($row) && (int) $row['c'] > 0;
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new ReflectionClass(Database::class);
        $databaseReflection->getProperty('databaseDriver')->setValue(null, $dbHandle);
        $databaseReflection->getProperty('dbType')->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }
}
