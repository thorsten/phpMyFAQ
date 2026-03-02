<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(GroupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class GroupControllerTest extends TestCase
{
    private const int TEST_GROUP_ID = 4242;

    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;
    private string $sessionId = '';

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-group-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->configuration->set('security.permLevel', 'medium');

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->sessionId = 'pmf-admin-group-' . bin2hex(random_bytes(8));
        session_id($this->sessionId);
        session_start();
        $_SERVER['REQUEST_TIME'] = time();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 1
                && in_array(
                    $right,
                    [
                        PermissionType::USER_ADD->value,
                        PermissionType::USER_EDIT->value,
                        PermissionType::USER_DELETE->value,
                        PermissionType::GROUP_EDIT->value,
                    ],
                    true,
                ),
            );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        return $container;
    }

    private function seedCurrentUserSession(): void
    {
        $_SESSION[CurrentUser::SESSION_CURRENT_USER] = 1;
        $_SESSION[CurrentUser::SESSION_ID_TIMESTAMP] = (int) $_SERVER['REQUEST_TIME'];

        $this->dbHandle->query(sprintf(
            "UPDATE faquser SET session_id = '%s', session_timestamp = %d, ip = '127.0.0.1' WHERE user_id = 1",
            $this->sessionId,
            (int) $_SERVER['REQUEST_TIME'],
        ));
    }

    private function seedGroupFixtures(): void
    {
        $this->dbHandle->query(sprintf(
            "INSERT INTO faqgroup (group_id, name, description, auto_join) VALUES (%d, 'Editors', 'Editorial team', 0)",
            self::TEST_GROUP_ID,
        ));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faquser_group (user_id, group_id) VALUES (1, %1$d), (2, %1$d)',
            self::TEST_GROUP_ID,
        ));
        $this->dbHandle->query(sprintf(
            'INSERT INTO faqgroup_right (group_id, right_id) VALUES (%1$d, 1), (%1$d, 2)',
            self::TEST_GROUP_ID,
        ));
    }

    /**
     * @throws \Exception
     */
    public function testListGroupsRequiresGroupPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listGroups();
    }

    /**
     * @throws \Exception
     */
    public function testListUsersRequiresGroupPermission(): void
    {
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listUsers();
    }

    /**
     * @throws \Exception
     */
    public function testGroupDataRequiresGroupPermission(): void
    {
        $request = new Request([], [], ['groupId' => 1]);
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->groupData($request);
    }

    /**
     * @throws \Exception
     */
    public function testListMembersRequiresGroupPermission(): void
    {
        $request = new Request([], [], ['groupId' => 1]);
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listMembers($request);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsRequiresGroupPermission(): void
    {
        $request = new Request([], [], ['groupId' => 1]);
        $controller = new GroupController();

        $this->expectException(\Exception::class);
        $controller->listPermissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testListGroupsReturnsSeededGroupsForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listGroups();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertContains(['group_id' => self::TEST_GROUP_ID, 'name' => 'Editors'], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testListUsersReturnsAvailableUsersForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listUsers();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($payload);
        self::assertContains(['user_id' => 1, 'login' => 'admin'], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testGroupDataReturnsSeededGroupForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->groupData(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(self::TEST_GROUP_ID, $payload['group_id']);
        self::assertSame('Editors', $payload['name']);
        self::assertSame('Editorial team', $payload['description']);
    }

    /**
     * @throws \Exception
     */
    public function testListMembersReturnsGroupMembersForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listMembers(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertContains(['user_id' => 1, 'login' => 'admin'], $payload);
        self::assertContains(['user_id' => 2, 'login' => 'testUser'], $payload);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsReturnsGroupRightsForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();
        $this->seedGroupFixtures();

        $controller = new GroupController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listPermissions(new Request([], [], ['groupId' => self::TEST_GROUP_ID]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([1, 2], $payload);
    }
}
