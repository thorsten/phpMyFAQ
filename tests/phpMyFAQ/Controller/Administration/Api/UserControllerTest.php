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
use phpMyFAQ\User\UserData;
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-user-controller-');
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

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->sessionId = 'pmf-admin-user-' . bin2hex(random_bytes(8));
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

    private function createController(): UserController
    {
        return new UserController($this->createStub(CurrentUser::class));
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
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
                    ],
                    true,
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
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

    /**
     * @throws \Exception
     */
    public function testListRequiresUserPermission(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->list($request);
    }

    /**
     * @throws \Exception
     */
    public function testCsvExportRequiresUserPermission(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->csvExport();
    }

    /**
     * @throws \Exception
     */
    public function testUserDataRequiresUserPermission(): void
    {
        $request = new Request([], [], ['userId' => 1]);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->userData($request);
    }

    /**
     * @throws \Exception
     */
    public function testUserPermissionsRequiresUserPermission(): void
    {
        $request = new Request([], [], ['userId' => 1]);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->userPermissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateRequiresUserPermission(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'test-token',
            'userId' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
    }

    /**
     * @throws \Exception
     */
    public function testOverwritePasswordRequiresUserPermission(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'csrf' => 'test-token',
            'newPassword' => 'password123',
            'passwordRepeat' => 'password123',
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->overwritePassword($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteUserRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'test-token',
            'userId' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->deleteUser($request);
    }

    /**
     * @throws \Exception
     */
    public function testListReturnsUsersForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->list(new Request());
        $payload = json_decode((string) $response->getContent(), false, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotEmpty($payload);
        self::assertSame('admin', $payload[1]->userName);
    }

    /**
     * @throws \Exception
     */
    public function testCsvExportReturnsCsvForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->csvExport();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('text/csv', $response->headers->get('Content-Type'));
        self::assertStringContainsString('Username', (string) $response->getContent());
        self::assertStringContainsString('admin', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testUserDataReturnsMappedUserDataForAuthenticatedUser(): void
    {
        $userService = $this->createMock(CurrentUser::class);
        $userData = $this->createMock(UserData::class);
        $userData
            ->method('get')
            ->with('*')
            ->willReturn([
                'display_name' => 'Admin User',
                'is_visible' => 1,
                'twofactor_enabled' => 0,
                'last_modified' => '20260101010101',
            ]);
        $userService->userdata = $userData;
        $userService->method('getUserById')->willReturn(true);
        $userService->method('getUserId')->willReturn(1);
        $userService->method('getStatus')->willReturn('protected');
        $userService->method('getLogin')->willReturn('admin');
        $userService->method('isSuperAdmin')->willReturn(true);
        $userService->method('getUserAuthSource')->willReturn('local');

        $controller = new UserController($userService);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->userData(new Request([], [], ['userId' => 1]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, $payload['userId']);
        self::assertSame('admin', $payload['login']);
        self::assertSame('Admin User', $payload['displayName']);
        self::assertTrue($payload['isSuperadmin']);
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $this->seedCurrentUserSession();

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'invalid-token',
            'userId' => 1,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->activate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testOverwritePasswordReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $this->seedCurrentUserSession();

        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'csrf' => 'invalid-token',
            'newPassword' => 'password123',
            'passwordRepeat' => 'password123',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->overwritePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteUserReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $this->seedCurrentUserSession();

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'invalid-token',
            'userId' => 2,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->deleteUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }
}
