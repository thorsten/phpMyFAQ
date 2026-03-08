<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\TwoFactor;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

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

        $_COOKIE = [];

        Strings::init('en');
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-front-auth-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $this->configuration);

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
        $_COOKIE = [];

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
    public function testLoginRendersFlashErrorMessage(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('error', 'Wrong username or password.');

        $controller = $this->createController();
        $controller->setContainer($this->createControllerContainer($session, $this->createLoggedOutCurrentUser()));

        $response = $controller->login(new Request());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Wrong username or password.', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAuthenticateRedirectsHomeWhenUserIsAlreadyLoggedIn(): void
    {
        $controller = $this->createController();
        $controller->setContainer(
            $this->createControllerContainer(new Session(new MockArraySessionStorage()), $this->createLoggedInCurrentUser())
        );

        $response = $controller->authenticate(new Request([], ['faqusername' => 'admin', 'faqpassword' => 'secret']));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('./', $response->getTargetUrl());
    }

    /**
     * @throws \Exception
     */
    public function testLogoutWithInvalidCsrfAddsErrorFlashMessage(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $controller = $this->createController();
        $controller->setContainer($this->createControllerContainer($session, $this->createLoggedOutCurrentUser()));

        $response = $controller->logout(new Request(['csrf' => 'invalid-token']));

        self::assertSame($this->configuration->getDefaultUrl(), $response->getTargetUrl());
        self::assertSame(
            ['CSRF Problem detected: invalid-token'],
            $session->getFlashBag()->get('error')
        );
    }

    /**
     * @throws \Exception
     */
    public function testLogoutRedirectsToAzureLogoutWhenAzureUserLogsOut(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        Token::resetInstanceForTests();
        $token = Token::getInstance($session)->getTokenString('logout');
        $_COOKIE['pmf-csrf-token-' . substr(md5('logout'), 0, 10)] = $token;

        $currentUser = $this->createLoggedInCurrentUser('azure');
        $currentUser->expects(self::once())->method('deleteFromSession')->with(true);

        $controller = $this->createController();
        $controller->setContainer(
            $this->createControllerContainer(
                $session,
                $currentUser,
                ['security.enableSignInWithMicrosoft' => true]
            )
        );

        $response = $controller->logout(new Request(['csrf' => $token]));

        self::assertSame(
            $this->configuration->getDefaultUrl() . 'auth/azure/logout',
            $response->getTargetUrl()
        );
        self::assertNotEmpty($session->getFlashBag()->get('success'));
    }

    /**
     * @throws \Exception
     */
    public function testCheckRedirectsBackWhenUserIdIsInvalid(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();
        $controller = $this->createController();
        $controller->setContainer($this->createControllerContainer($session, $this->createLoggedOutCurrentUser()));

        $response = $controller->check(new Request([], ['token' => '123456', 'user-id' => '0']));

        self::assertSame('./token?user-id=0', $response->getTargetUrl());
        self::assertNotEmpty($session->getFlashBag()->get('error'));
    }

    /**
     * @throws \Exception
     */
    public function testCheckRedirectsHomeWhenTwoFactorTokenIsValid(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $currentUserService = $this->createMock(CurrentUser::class);
        $currentUserService->expects(self::once())->method('getUserById')->with(42)->willReturn(true);
        $currentUserService->expects(self::once())->method('twoFactorSuccess');

        $twoFactor = $this->createMock(TwoFactor::class);
        $twoFactor->expects(self::once())->method('validateToken')->with('123456', 42)->willReturn(true);

        $controller = new AuthenticationController($this->createStub(UserSession::class), $currentUserService, $twoFactor);
        $controller->setContainer($this->createControllerContainer($session, $this->createLoggedOutCurrentUser()));

        $response = $controller->check(new Request([], ['token' => '123456', 'user-id' => '42']));

        self::assertSame('./', $response->getTargetUrl());
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    private function createController(): AuthenticationController
    {
        return new AuthenticationController(
            $this->createStub(UserSession::class),
            new CurrentUser($this->configuration),
            $this->createStub(TwoFactor::class)
        );
    }

    private function createControllerContainer(
        SessionInterface $session,
        CurrentUser $currentUser,
        array $configurationValues = []
    ): ContainerInterface {
        $this->configuration->getAll();

        $reflection = new \ReflectionClass(Configuration::class);
        $property = $reflection->getProperty('config');
        $config = $property->getValue($this->configuration);
        foreach ($configurationValues as $key => $value) {
            $config[$key] = $value;
        }
        $property->setValue($this->configuration, $config);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($currentUser, $session): mixed {
            return match ($id) {
                'phpmyfaq.configuration' => $this->configuration,
                'phpmyfaq.user.current_user' => $currentUser,
                'session' => $session,
                'phpmyfaq.system' => new System(),
                'phpmyfaq.seo' => new Seo($this->configuration),
                default => null,
            };
        });

        return $container;
    }

    private function createLoggedOutCurrentUser(): CurrentUser
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(false);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(false);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(-1);
        $currentUser->method('getLogin')->willReturn('');
        $currentUser->method('getUserData')->willReturn('');

        return $currentUser;
    }

    private function createLoggedInCurrentUser(string $authSource = 'local'): CurrentUser
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(false);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('isSuperAdmin')->willReturn(false);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getLogin')->willReturn('admin');
        $currentUser->method('getUserAuthSource')->willReturn($authSource);
        $currentUser->method('getUserData')->willReturnMap([
            ['display_name', 'Admin User'],
            ['email', 'admin@example.com'],
        ]);

        return $currentUser;
    }
}
