<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(UserController::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-user-page-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();

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

    /**
     * @throws \Exception
     */
    public function testIndexRendersUserAdministrationPage(): void
    {
        $controller = new UserController($this->createUserMock());
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->index(new Request());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('pmf-user-list-autocomplete', (string) $response->getContent());
        self::assertStringContainsString('pmf-csrf-token', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testEditRendersSelectedUserId(): void
    {
        $controller = new UserController($this->createUserMock());
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->edit(new Request([], [], ['userId' => '23']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('id="current_user_id" value="23"', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testListRendersUsersAndPagination(): void
    {
        $controller = new UserController($this->createUserMock(range(1, 11)));
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->list(new Request());
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('<td>User 1</td>', $content);
        self::assertStringContainsString('<td>User 10</td>', $content);
        self::assertStringContainsString('admin/user/list?page=2', $content);
        self::assertStringNotContainsString('<td>User 11</td>', $content);
    }

    /**
     * @throws \Exception
     */
    public function testListSecondPageShowsRemainingUsers(): void
    {
        $controller = new UserController($this->createUserMock(range(1, 11)));
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->list(new Request(['page' => '2']));
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('<td>User 11</td>', $content);
        self::assertStringNotContainsString('<td>User 10</td>', $content);
    }

    /**
     * @param list<int> $userIds
     */
    private function createUserMock(array $userIds = [1, 2]): User
    {
        $permission = $this->createMock(BasicPermission::class);
        $permission
            ->method('getAllRightsData')
            ->willReturn([
                ['right_id' => 1, 'name' => 'add_user'],
            ]);

        $currentUserId = 0;
        $user = $this->createMock(User::class);
        $user->perm = $permission;
        $user->method('getAllUsers')->willReturn($userIds);
        $user->method('getUserById')->willReturnCallback(function (int $userId) use (&$currentUserId): bool {
            $currentUserId = $userId;

            return true;
        });
        $user->method('getUserId')->willReturnCallback(static fn() => $currentUserId);
        $user->method('getUserData')->willReturnCallback(static function (string $field) use (&$currentUserId): mixed {
            return match ($field) {
                'display_name' => 'User ' . $currentUserId,
                'email' => 'user' . $currentUserId . '@example.com',
                'is_visible' => ($currentUserId % 2) === 1,
                default => null,
            };
        });
        $user->method('getStatus')->willReturnCallback(static fn() => $currentUserId === 2 ? 'blocked' : 'active');
        $user->method('isSuperAdmin')->willReturnCallback(static fn() => $currentUserId === 1);
        $user->method('getLogin')->willReturnCallback(static fn() => 'user' . $currentUserId);

        return $user;
    }

    private function createControllerContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('isSuperAdmin')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(99);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Admin User'],
                ['email',        'admin@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(AdminMenuBuilder::class);
        $adminHelper->method('canAccessContent')->willReturn(true);
        $adminHelper->method('addMenuEntry')->willReturn('');
        $adminHelper->method('setUser')->willReturnSelf();

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog, $adminHelper) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    'phpmyfaq.admin.helper' => $adminHelper,
                    default => null,
                };
            });

        return $container;
    }
}
