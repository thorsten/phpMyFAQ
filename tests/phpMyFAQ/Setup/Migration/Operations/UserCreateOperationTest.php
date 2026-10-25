<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Auth;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Auth\PasswordHasher;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\FilesystemConfigurationCache;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Database;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Encryption;
use phpMyFAQ\EncryptionTypes\Hash;
use phpMyFAQ\Environment;
use phpMyFAQ\Permission;
use phpMyFAQ\Permission\BasicPermissionRepository;
use phpMyFAQ\Permission\GroupCategoryPermissionRepository;
use phpMyFAQ\Permission\LanguagePermissionRepository;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Permission\MediumPermissionRepository;
use phpMyFAQ\Plugin\PluginDiscovery;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Setup\Migration\Operations\UserCreateOperation;
use phpMyFAQ\System;
use phpMyFAQ\Tenant\TenantContext;
use phpMyFAQ\Tenant\TenantContextResolver;
use phpMyFAQ\Tenant\TenantQuotaEnforcer;
use phpMyFAQ\Tenant\TenantQuotas;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\UserData;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(UserCreateOperation::class)]
#[UsesClass(User::class)]
#[UsesClass(Auth::class)]
#[UsesClass(AuthDatabase::class)]
#[UsesClass(PasswordHasher::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(LdapSettings::class)]
#[UsesClass(MailSettings::class)]
#[UsesClass(SearchSettings::class)]
#[UsesClass(SecuritySettings::class)]
#[UsesClass(ConfigurationStorageSettings::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
#[UsesClass(UrlSettings::class)]
#[UsesClass(Database::class)]
#[UsesClass(PdoSqlite::class)]
#[UsesClass(Encryption::class)]
#[UsesClass(Hash::class)]
#[UsesClass(Environment::class)]
#[UsesClass(Permission::class)]
#[UsesClass(BasicPermissionRepository::class)]
#[UsesClass(MediumPermission::class)]
#[UsesClass(MediumPermissionRepository::class)]
#[UsesClass(PluginManager::class)]
#[UsesClass(System::class)]
#[UsesClass(TenantContext::class)]
#[UsesClass(TenantContextResolver::class)]
#[UsesClass(TenantQuotaEnforcer::class)]
#[UsesClass(TenantQuotas::class)]
#[UsesClass(Translation::class)]
#[UsesClass(UserData::class)]
#[UsesClass(FilesystemConfigurationCache::class)]
#[UsesClass(GroupCategoryPermissionRepository::class)]
#[UsesClass(LanguagePermissionRepository::class)]
#[UsesClass(PluginDiscovery::class)]
final class UserCreateOperationTest extends TestCase
{
    private string $databaseFile;
    private PdoSqlite $database;
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_TIME'] = time();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-user-create-operation-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $this->database = new PdoSqlite();
        $this->database->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($this->database);
    }

    protected function tearDown(): void
    {
        $this->database->close();
        @unlink($this->databaseFile);
        unset($_SERVER['REQUEST_TIME']);

        parent::tearDown();
    }

    public function testGetType(): void
    {
        $operation = $this->createOperation();

        $this->assertSame('user_create', $operation->getType());
    }

    public function testGetDescription(): void
    {
        $operation = $this->createOperation(loginName: 'migrationAdmin', userId: 8001);

        $this->assertSame('Create user "migrationAdmin" (ID: 8001)', $operation->getDescription());
    }

    public function testExecuteCreatesUserWithMetadata(): void
    {
        $operation = $this->createOperation(
            loginName: 'migration_user',
            password: 'Secret123!',
            displayName: 'Migration User',
            email: 'migration@example.com',
            userId: 8101,
        );

        $this->assertTrue($operation->execute());

        $userRow = $this->fetchUserRow(8101);
        $this->assertSame('migration_user', $userRow['login']);
        $this->assertSame('protected', $userRow['account_status']);
        $this->assertSame(0, (int) $userRow['is_superadmin']);

        $userDataRow = $this->fetchUserDataRow(8101);
        $this->assertSame('Migration User', $userDataRow['display_name']);
        $this->assertSame('migration@example.com', $userDataRow['email']);

        $loginRow = $this->fetchUserLoginRow('migration_user');
        $this->assertNotSame('', $loginRow['pass']);
    }

    public function testExecuteCreatesSuperAdminUser(): void
    {
        $operation = $this->createOperation(
            loginName: 'migration_superadmin',
            password: 'Secret123!',
            displayName: 'Migration Superadmin',
            email: 'superadmin@example.com',
            userId: 8102,
            isSuperAdmin: true,
            status: 'active',
        );

        $this->assertTrue($operation->execute());

        $userRow = $this->fetchUserRow(8102);
        $this->assertSame('active', $userRow['account_status']);
        $this->assertSame(1, (int) $userRow['is_superadmin']);
    }

    public function testExecuteReturnsFalseWhenUserAlreadyExists(): void
    {
        $operation = $this->createOperation(loginName: 'duplicate_user', userId: 8103);
        $this->assertTrue($operation->execute());

        $duplicateOperation = $this->createOperation(loginName: 'duplicate_user', userId: 8104);

        $this->assertFalse($duplicateOperation->execute());
    }

    public function testExecuteReturnsFalseWhenStatusIsInvalid(): void
    {
        $operation = $this->createOperation(loginName: 'invalid_status_user', userId: 8105, status: 'unknown');

        $this->assertFalse($operation->execute());
    }

    public function testExecuteReturnsFalseWhenUserCreationThrows(): void
    {
        $operation = $this->createOperation(loginName: 'x', userId: 8106);

        $this->assertFalse($operation->execute());
    }

    public function testExecuteReturnsFalseWhenTheUserDataCannotBeStored(): void
    {
        $configuration = new Configuration($this->createDatabaseFailingOn('faquserdata'));
        $operation = new UserCreateOperation(
            $configuration,
            'userdata_failure_user',
            'Secret123!',
            'Userdata Failure',
            'userdata-failure@example.com',
            8108,
        );

        $this->assertFalse($operation->execute());
    }

    public function testExecuteReturnsFalseWhenTheSuperAdminFlagCannotBeStored(): void
    {
        $configuration = new Configuration($this->createDatabaseFailingOn('is_superadmin'));
        $operation = new UserCreateOperation(
            $configuration,
            'superadmin_failure_user',
            'Secret123!',
            'Superadmin Failure',
            'superadmin-failure@example.com',
            8109,
            true,
        );

        $this->assertFalse($operation->execute());
    }

    public function testToArray(): void
    {
        $operation = $this->createOperation(loginName: 'migration_superadmin', userId: 8107, isSuperAdmin: true);

        $this->assertSame(
            [
                'type' => 'user_create',
                'description' => 'Create user "migration_superadmin" (ID: 8107)',
                'login_name' => 'migration_superadmin',
                'user_id' => 8107,
                'is_super_admin' => true,
            ],
            $operation->toArray(),
        );
    }

    /**
     * Connects a second driver to the test database that rejects every query mentioning the given fragment.
     */
    private function createDatabaseFailingOn(string $fragment): PdoSqlite
    {
        $database = new class($fragment) extends PdoSqlite {
            public function __construct(
                private readonly string $fragment,
            ) {
                parent::__construct();
            }

            public function query(string $query, int $offset = 0, int $rowcount = 0): mixed
            {
                if (str_contains($query, $this->fragment) && str_contains($query, 'UPDATE')) {
                    return false;
                }

                return parent::query($query, $offset, $rowcount);
            }
        };
        $database->connect($this->databaseFile, '', '');

        return $database;
    }

    private function createOperation(
        string $loginName = 'migration_user',
        string $password = 'Secret123!',
        string $displayName = 'Migration User',
        string $email = 'migration@example.com',
        int $userId = 8100,
        bool $isSuperAdmin = false,
        string $status = 'protected',
    ): UserCreateOperation {
        return new UserCreateOperation(
            $this->configuration,
            $loginName,
            $password,
            $displayName,
            $email,
            $userId,
            $isSuperAdmin,
            $status,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchUserRow(int $userId): array
    {
        $result = $this->database->query(sprintf(
            'SELECT login, account_status, is_superadmin FROM faquser WHERE user_id = %d',
            $userId,
        ));

        return $this->database->fetchArray($result);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchUserDataRow(int $userId): array
    {
        $result = $this->database->query(sprintf(
            'SELECT display_name, email FROM faquserdata WHERE user_id = %d',
            $userId,
        ));

        return $this->database->fetchArray($result);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchUserLoginRow(string $loginName): array
    {
        $result = $this->database->query(sprintf(
            "SELECT pass FROM faquserlogin WHERE login = '%s'",
            $this->database->escape($loginName),
        ));

        return $this->database->fetchArray($result);
    }
}
