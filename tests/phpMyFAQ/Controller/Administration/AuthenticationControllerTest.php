<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AuthenticationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class AuthenticationControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-auth-page-controller-');
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
     * @throws Exception
     * @throws \Exception
     */
    private function createController(): AuthenticationController
    {
        return new AuthenticationController(new CurrentUser($this->configuration), $this->createStub(TwoFactor::class));
    }

    /**
     * @throws \Exception
     */
    public function testAuthenticateRedirectsToDashboardWhenCredentialsAreMissing(): void
    {
        $request = new Request([], ['faqusername' => '', 'faqpassword' => '']);
        $controller = $this->createController();

        $response = $controller->authenticate($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('./', $response->getTargetUrl());
    }

    /**
     * @throws \Exception
     */
    public function testLoginReturnsResponse(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $response = $controller->login($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testLogoutRequiresAuthentication(): void
    {
        $request = new Request(['csrf' => 'test-token']);
        $controller = $this->createController();

        $this->expectException(UnauthorizedHttpException::class);
        $controller->logout($request);
    }

    /**
     * @throws \Exception
     */
    public function testTokenReturnsResponse(): void
    {
        $request = new Request(['user-id' => '1']);
        $controller = $this->createController();

        $response = $controller->token($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testTokenRedirectsToDashboardWhenUserIsAlreadyLoggedIn(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createControllerContainer(currentUser: $this->createLoggedInCurrentUser(), configurationValues: []));

        $response = $controller->token(new Request(['user-id' => '1']));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('./', $response->getTargetUrl());
    }

    /**
     * @throws \Exception
     */
    public function testLoginRedirectsToAuthenticateWhenSsoUserIsPresent(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createControllerContainer(currentUser: $this->createLoggedOutCurrentUser(), configurationValues: [
            'security.ssoSupport' => true,
        ]));

        $request = new Request(server: ['REMOTE_USER' => 'thorsten']);

        $response = $controller->login($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('./authenticate', $response->getTargetUrl());
    }

    /**
     * @throws \Exception
     */
    public function testCheckRedirectsBackToTokenWhenTokenLengthIsInvalid(): void
    {
        $currentUserService = $this->createMock(CurrentUser::class);
        $currentUserService->expects(self::once())->method('getUserById')->with(42);

        $twoFactor = $this->createMock(TwoFactor::class);
        $twoFactor->expects(self::never())->method('validateToken');

        $controller = new AuthenticationController($currentUserService, $twoFactor);
        $controller->setContainer($this->createControllerContainer(currentUser: $this->createLoggedOutCurrentUser(), configurationValues: []));

        $request = new Request([], ['token' => '123', 'user-id' => '42']);

        $response = $controller->check($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('./token?user-id=42', $response->getTargetUrl());
    }

    /**
     * @throws \Exception
     */
    public function testCheckRedirectsToDashboardWhenTwoFactorTokenIsValid(): void
    {
        $currentUserService = $this->createMock(CurrentUser::class);
        $currentUserService->method('getUserById')->with(42);
        $currentUserService->expects(self::once())->method('twoFactorSuccess');
        $currentUserService->method('getLogin')->willReturn('admin');

        $twoFactor = $this->createMock(TwoFactor::class);
        $twoFactor->expects(self::once())->method('validateToken')->with('123456', 42)->willReturn(true);

        $controller = new AuthenticationController($currentUserService, $twoFactor);
        $controller->setContainer($this->createControllerContainer(currentUser: $this->createLoggedOutCurrentUser(), configurationValues: []));

        $request = new Request([], ['token' => '123456', 'user-id' => '42']);

        $response = $controller->check($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('./', $response->getTargetUrl());
    }

    private function createControllerContainer(CurrentUser $currentUser, array $configurationValues): ContainerInterface
    {
        $this->configuration->getAll();

        $reflection = new \ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('config');
        $config = $property->getValue($this->configuration);
        foreach ($configurationValues as $key => $value) {
            $config[$key] = $value;
        }
        $property->setValue($this->configuration, $config);

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

    private function createLoggedOutCurrentUser(): CurrentUser
    {
        $permission = $this->createMock(PermissionInterface::class);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(false);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(0);
        $currentUser->method('getUserData')->willReturn('');
        $currentUser->method('getLogin')->willReturn('');

        return $currentUser;
    }

    private function createLoggedInCurrentUser(): CurrentUser
    {
        $permission = $this->createMock(PermissionInterface::class);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserData')->willReturn('');
        $currentUser->method('getLogin')->willReturn('admin');

        return $currentUser;
    }
}
