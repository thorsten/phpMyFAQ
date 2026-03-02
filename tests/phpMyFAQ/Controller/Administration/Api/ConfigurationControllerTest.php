<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Mail;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\RedisSessionHandler;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ConfigurationController::class)]
#[UsesNamespace('phpMyFAQ')]
class ConfigurationControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private Mail $mail;
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-config-controller-');
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

        $this->mail = $this->createStub(Mail::class);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('configuration'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('activate-maintenance-mode'), 0, 10)]);

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
        $permission->method('hasPermission')->willReturnCallback(
            static fn (int $userId, mixed $right): bool => $userId === 42 && $right === PermissionType::CONFIGURATION_EDIT->value
        );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(\phpMyFAQ\Administration\AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog) {
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
     * @throws Exception
     */
    public function testSendTestMailRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->sendTestMail($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateMaintenanceModeRequiresAuthentication(): void
    {
        $requestData = json_encode(['csrf' => 'test-token']);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->activateMaintenanceMode($request);
    }

    /**
     * @throws Exception
     */
    public function testSendTestMailWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->sendTestMail($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateMaintenanceModeWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->activateMaintenanceMode($request);
    }

    /**
     * @throws Exception
     */
    public function testSendTestMailWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->sendTestMail($request);
    }

    /**
     * @throws \Exception
     */
    public function testActivateMaintenanceModeWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->activateMaintenanceMode($request);
    }

    /**
     * @throws Exception
     */
    public function testTestRedisConnectionRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'csrf' => 'test-token',
            'redisDsn' => 'tcp://redis:6379?database=1',
            'timeout' => 1,
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->testRedisConnection($request);
    }

    /**
     * @throws Exception
     */
    public function testTestRedisConnectionWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->testRedisConnection($request);
    }

    /**
     * @throws Exception
     */
    public function testTestRedisConnectionWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new ConfigurationController($this->mail);

        $this->expectException(\Exception::class);
        $controller->testRedisConnection($request);
    }

    /**
     * @throws Exception
     */
    public function testSendTestMailReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'invalid-token'], JSON_THROW_ON_ERROR));
        $controller = new ConfigurationController($this->mail);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->sendTestMail($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testActivateMaintenanceModeReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'invalid-token'], JSON_THROW_ON_ERROR));
        $controller = new ConfigurationController($this->mail);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->activateMaintenanceMode($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testTestRedisConnectionReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'redisDsn' => 'tcp://redis:6379?database=1',
            'timeout' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = new ConfigurationController($this->mail);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->testRedisConnection($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testSendTestMailReturnsSuccessForValidCsrfWhenAuthenticated(): void
    {
        $mail = $this->createMock(Mail::class);
        $mail->expects($this->once())->method('addTo');
        $mail->expects($this->once())->method('setReplyTo');
        $mail->expects($this->once())->method('send')->willReturn(1);

        $controller = new ConfigurationController($mail);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'configuration');
        $controller->setContainer($container);

        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => $token], JSON_THROW_ON_ERROR));
        $response = $controller->sendTestMail($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, $payload['success']);
    }

    /**
     * @throws Exception
     */
    public function testActivateMaintenanceModeReturnsSuccessForValidCsrfWhenAuthenticated(): void
    {
        $controller = new ConfigurationController($this->mail);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'activate-maintenance-mode');
        $controller->setContainer($container);

        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => $token], JSON_THROW_ON_ERROR));
        $response = $controller->activateMaintenanceMode($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('healthCheckOkay'), $payload['success']);
    }

    /**
     * @throws Exception
     */
    public function testSendTestMailReturnsBadRequestWhenMailSendFails(): void
    {
        $mail = $this->createMock(Mail::class);
        $mail->expects($this->once())->method('addTo');
        $mail->expects($this->once())->method('setReplyTo');
        $mail->expects($this->once())->method('send')->willThrowException(new \RuntimeException('Mail transport failed.'));

        $controller = new ConfigurationController($mail);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'configuration');
        $controller->setContainer($container);

        $response = $controller->sendTestMail(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Mail transport failed.', $payload['error']);
    }

    /**
     * @throws Exception
     */
    public function testTestRedisConnectionReturnsBadRequestForInvalidDsnWhenAuthenticated(): void
    {
        $controller = new ConfigurationController($this->mail);
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'configuration');
        $controller->setContainer($container);

        $response = $controller->testRedisConnection(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'redisDsn' => 'invalid://redis',
            'timeout' => 1,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            'Unsupported Redis DSN scheme "invalid" for sessions.',
            $payload['error'],
        );
    }
}
