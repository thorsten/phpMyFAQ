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
use phpMyFAQ\Session\Token;
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
            session_write_close();
            session_id($this->sessionId);
            session_start();
            $_SESSION = [];
            session_destroy();
        }
        session_id('');
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

    private function createController(): UserController
    {
        return new UserController($this->createStub(CurrentUser::class));
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        return $this->createAuthenticatedContainerWithAdminLog($this->createStub(AdminLog::class));
    }

    private function createAuthenticatedContainerWithAdminLog(AdminLog $adminLog): ContainerInterface
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

    private function seedManagedUser(
        string $login = 'managed-user',
        string $status = 'active',
        int $isSuperAdmin = 0,
        int $twoFactorEnabled = 0,
        string $lastModified = '20260101010101',
    ): int {
        $userId = (int) $this->dbHandle->nextId('faquser', 'user_id');

        self::assertNotFalse($this->dbHandle->query(sprintf(
            "INSERT INTO faquser (
                user_id, login, session_id, session_timestamp, ip, account_status, last_login, auth_source, member_since,
                remember_me, success, is_superadmin, login_attempts, refresh_token, access_token, code_verifier, jwt, webauthnkeys
            ) VALUES (
                %d, '%s', '', 0, '', '%s', '20230101120000', 'local', '20230101120000',
                '', 1, %d, 0, NULL, NULL, NULL, NULL, NULL
            )",
            $userId,
            $this->dbHandle->escape($login),
            $this->dbHandle->escape($status),
            $isSuperAdmin,
        )));

        self::assertNotFalse($this->dbHandle->query(sprintf(
            "INSERT INTO faquserdata (user_id, last_modified, display_name, email, is_visible, twofactor_enabled, secret)
             VALUES (%d, '%s', '%s', '%s', 1, %d, 'test-secret')",
            $userId,
            $this->dbHandle->escape($lastModified),
            $this->dbHandle->escape('Display ' . $login),
            $this->dbHandle->escape($login . '@example.com'),
            $twoFactorEnabled,
        )));

        return $userId;
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
    public function testAddUserRequiresUserPermission(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'test-token',
            'userName' => 'new-user',
            'realName' => 'New User',
            'email' => 'new-user@example.com',
            'automaticPassword' => true,
            'password' => '',
            'passwordConfirm' => '',
            'isSuperAdmin' => false,
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->addUser($request);
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

    /**
     * @throws \Exception
     */
    public function testListReturnsFilteredUsersForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->list(new Request(['filter' => 'adm']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotEmpty($payload);
        self::assertSame('admin', $payload[0]['label']);
        self::assertSame(1, $payload[0]['value']);
    }

    /**
     * @throws \Exception
     */
    public function testUserPermissionsReturnsRightsForAuthenticatedUser(): void
    {
        $this->seedCurrentUserSession();

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->userPermissions(new Request([], [], ['userId' => 1]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($payload);
    }

    /**
     * @throws \Exception
     */
    public function testOverwritePasswordReturnsBadRequestForShortPasswordWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'overwrite-password');

        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'csrf' => $token,
            'newPassword' => 'short',
            'passwordRepeat' => 'short',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->overwritePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgPasswordTooShort'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsBadRequestForAlreadyActiveUserWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();
        $managedUserId = $this->seedManagedUser(login: 'active-user', status: 'active');

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'activate-user');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'userId' => $managedUserId,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->activate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('active', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testOverwritePasswordReturnsBadRequestForMismatchedPasswordsWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'overwrite-password');

        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'csrf' => $token,
            'newPassword' => 'password123',
            'passwordRepeat' => 'password124',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->overwritePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgPasswordsMustBeEqual'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testAddUserReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $this->seedCurrentUserSession();

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'userName' => 'new-user',
            'realName' => 'New User',
            'email' => 'new-user@example.com',
            'automaticPassword' => true,
            'password' => '',
            'passwordConfirm' => '',
            'isSuperAdmin' => false,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->addUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testAddUserReturnsValidationErrorsWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'add-user');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'userName' => '***',
            'realName' => '',
            'email' => 'invalid-email',
            'automaticPassword' => false,
            'password' => 'short',
            'passwordConfirm' => 'tiny',
            'isSuperAdmin' => false,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->addUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertContains(Translation::get('ad_user_error_loginInvalid'), $payload);
        self::assertContains(Translation::get('ad_user_error_noRealName'), $payload);
        self::assertContains(Translation::get('ad_user_error_noEmail'), $payload);
        self::assertContains(Translation::get('ad_passwd_fail'), $payload);
    }

    /**
     * @throws \Exception
     */
    public function testOverwritePasswordReturnsSuccessWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'overwrite-password');

        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'csrf' => $token,
            'newPassword' => 'updatedPassword123',
            'passwordRepeat' => 'updatedPassword123',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->overwritePassword($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_passwdsuc'), $payload['success']);
    }

    /**
     * @throws \Exception
     */
    public function testEditUserReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $this->seedCurrentUserSession();

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'invalid-token',
            'userId' => 2,
            'display_name' => 'Editor User',
            'email' => 'editor@example.com',
            'last_modified' => '20260101010101',
            'user_status' => 'active',
            'is_superadmin' => false,
            'overwrite_twofactor' => 'off',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->editUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testEditUserReturnsBadRequestForMissingUserIdWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-user-data');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'userId' => 0,
            'display_name' => 'Editor User',
            'email' => 'editor@example.com',
            'last_modified' => '20260101010101',
            'user_status' => 'active',
            'is_superadmin' => false,
            'overwrite_twofactor' => 'off',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->editUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_user_error_noId'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testEditUserReturnsSuccessWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();
        $managedUserId = $this->seedManagedUser(login: 'test-user-edit', status: 'active');

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-user-data');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'userId' => $managedUserId,
            'display_name' => 'Edited Test User',
            'email' => 'edited-test-user@example.com',
            'last_modified' => '20260101010101',
            'user_status' => 'active',
            'is_superadmin' => false,
            'overwrite_twofactor' => 'off',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->editUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        self::assertStringContainsString('test-user-edit', $payload['success']);
    }

    /**
     * @throws \Exception
     */
    public function testEditUserResetsTwoFactorAndLogsPrivilegeChanges(): void
    {
        $this->seedCurrentUserSession();
        $managedUserId = $this->seedManagedUser(
            login: 'privileged-user',
            status: 'active',
            isSuperAdmin: 0,
            twoFactorEnabled: 1,
            lastModified: '20260228093848',
        );

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog
            ->expects($this->exactly(4))
            ->method('log')
            ->with(
                $this->anything(),
                $this->callback(static function (string $message): bool {
                    static $expectedFragments = [
                        'auth-2fa-reset:',
                        'user-status-changed:',
                        'user-superadmin-granted:',
                        'user-edit:',
                    ];

                    $expectedFragment = array_shift($expectedFragments);
                    return $expectedFragment !== null && str_contains($message, $expectedFragment);
                }),
            );

        $container = $this->createAuthenticatedContainerWithAdminLog($adminLog);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-user-data');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'userId' => $managedUserId,
            'display_name' => 'Privileged Test User',
            'email' => 'privileged-test-user@example.com',
            'last_modified' => '20260228093848',
            'user_status' => 'protected',
            'is_superadmin' => true,
            'overwrite_twofactor' => 'on',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->editUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateUserRightsReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $this->seedCurrentUserSession();

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'invalid-token',
            'userId' => 2,
            'userRights' => [1, 2],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->updateUserRights($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateUserRightsReturnsBadRequestForMissingUserIdWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-user-rights');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'userId' => 0,
            'userRights' => [1, 2],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->updateUserRights($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_user_error_noId'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteUserReturnsBadRequestForProtectedUserWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-user');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'userId' => 1,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->deleteUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_user_error_protectedAccount'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testAddUserReturnsDuplicateLoginErrorWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'add-user');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'userName' => 'admin',
            'realName' => 'Admin Duplicate',
            'email' => 'duplicate-admin@example.com',
            'automaticPassword' => false,
            'password' => 'password123',
            'passwordConfirm' => 'password123',
            'isSuperAdmin' => false,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->addUser($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertContains(Translation::get('ad_adus_exerr'), $payload);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateUserRightsReturnsSuccessWithValidCsrf(): void
    {
        $this->seedCurrentUserSession();

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-user-rights');

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $token,
            'userId' => 2,
            'userRights' => [1, 2],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->updateUserRights($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
    }

}
