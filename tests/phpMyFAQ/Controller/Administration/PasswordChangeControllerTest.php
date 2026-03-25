<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Auth;
use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Encryption;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
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
#[CoversClass(PasswordChangeController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PasswordChangeControllerTest extends TestCase
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
        Token::resetInstanceForTests();

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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-password-controller-');
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
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('password'), 0, 10)]);

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

    private function createController(?Auth $auth = null): PasswordChangeController
    {
        return new PasswordChangeController($auth ?? $this->createStub(Auth::class));
    }

    /**
     * @throws \Exception
     */
    public function testIndexRendersInCurrentAnonymousAdminContext(): void
    {
        $response = $this->createController()->index(new Request());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateThrowsForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController($this->createStub(Auth::class));
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], ['pmf-csrf-token' => 'invalid-token']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid CSRF token');
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsErrorForTooShortPassword(): void
    {
        $authSource = $this->createMock(AuthDatabase::class);
        $authSource->method('getEncryptionContainer')->willReturn($this->createStub(Encryption::class));
        $authSource->method('disableReadOnly');
        $authSource->method('checkCredentials')->willReturn(true);

        $auth = $this->createMock(Auth::class);
        $auth->method('selectAuth')->willReturn($authSource);

        $controller = $this->createController($auth);
        $container = $this->createAuthenticatedContainer();
        $controller->setContainer($container);

        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'password');

        $response = $controller->update(
            new Request([], [
                'pmf-csrf-token' => $token,
                'faqpassword_old' => 'old-password',
                'faqpassword' => 'short',
                'faqpassword_confirm' => 'short',
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString(Translation::get('ad_passwd_fail'), (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsSuccessWhenPasswordChangeSucceeds(): void
    {
        $authSource = $this->createMock(AuthDatabase::class);
        $authSource->method('getEncryptionContainer')->willReturn($this->createStub(Encryption::class));
        $authSource->method('disableReadOnly');
        $authSource->method('checkCredentials')->willReturn(true);

        $auth = $this->createMock(Auth::class);
        $auth->method('selectAuth')->willReturn($authSource);

        $controller = $this->createController($auth);
        $container = $this->createAuthenticatedContainer(changePasswordResult: true);
        $controller->setContainer($container);

        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'password');

        $response = $controller->update(
            new Request([], [
                'pmf-csrf-token' => $token,
                'faqpassword_old' => 'old-password',
                'faqpassword' => 'new-password-123',
                'faqpassword_confirm' => 'new-password-123',
            ]),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString(Translation::get('ad_passwdsuc'), (string) $response->getContent());
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

    private function createAuthenticatedContainer(bool $changePasswordResult = false): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
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
        $currentUser->method('getAuthSource')->willReturn('database');
        $currentUser
            ->method('getAuthData')
            ->willReturnMap([
                ['encType',  'password_hash'],
                ['readOnly', false],
            ]);
        $currentUser->method('getLogin')->willReturn('test-user');
        $currentUser->method('changePassword')->willReturn($changePasswordResult);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(AdminMenuBuilder::class);
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
