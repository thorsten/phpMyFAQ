<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Helper;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\MediumPermission;
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
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(GroupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class GroupControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-group-page-controller-');
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
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('add-group'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('delete-group'), 0, 10)]);

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

    private function createController(): GroupController
    {
        $user = new User($this->configuration);
        $user->addPerm(new MediumPermission($this->configuration));

        return new GroupController($user);
    }

    /**
     * @throws \Exception
     */
    public function testIndexRendersInCurrentAnonymousAdminContext(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $response = $controller->index($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testAddRendersInCurrentAnonymousAdminContext(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $response = $controller->add($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testCreateThrowsForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], ['csrf' => 'invalid-token'], [], [], [], '');

        $this->expectException(UnauthorizedHttpException::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteThrowsForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [
            'group_id' => 1,
            'pmf-csrf-token' => 'invalid-token',
        ]);

        $this->expectException(UnauthorizedHttpException::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateWithValidCsrfReturnsResponse(): void
    {
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'add-group');

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->create(
            new Request(
                [],
                ['group_name' => 'Unit Test Group', 'group_description' => 'Test', 'group_auto_join' => 'n'],
                ['csrf' => $token],
            ),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithValidCsrfReturnsResponse(): void
    {
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-group');

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->delete(
            new Request([], [
                'group_id' => 1,
                'pmf-csrf-token' => $token,
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsResponse(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->update(
            new Request([], [
                'group_id' => 1,
                'name' => 'Updated Group',
                'description' => 'Updated Description',
                'auto_join' => 'n',
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateMembersReturnsResponse(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->updateMembers(
            new Request([], [
                'group_id' => 1,
                'group_members' => [1, 2],
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdatePermissionsReturnsResponse(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->updatePermissions(
            new Request([], [
                'group_id' => 1,
                'group_rights' => [1, 2],
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    private function createValidCsrfToken(Session $session, string $page): string
    {
        Token::resetInstanceForTests();
        $token = Token::getInstance($session)->getTokenString($page);
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;

        return $token;
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        /** @var MediumPermission&PermissionInterface $permission */
        $permission = $this->getMockBuilder(MediumPermission::class)->disableOriginalConstructor()->getMock();
        $permission->method('hasPermission')->willReturn(true);
        $permission->method('deleteGroup')->willReturn(false);

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Test User'],
                ['email',        'test@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
        Token::resetInstanceForTests();
        Token::getInstance($session);
        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(Helper::class);
        $adminHelper->method('canAccessContent')->willReturn(true);
        $adminHelper->method('addMenuEntry')->willReturn('');

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
