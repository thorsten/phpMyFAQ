<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(PushController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PushControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-push-controller-');
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

    private function createAuthenticatedContainer(?Session $session = null): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => (
                    $userId === 42
                    && $right === PermissionType::CONFIGURATION_EDIT->value
                ),
            );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session ??= new Session(new MockArraySessionStorage());

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    default => null,
                };
            });

        return $container;
    }

    /**
     * @throws \Exception
     */
    public function testGenerateVapidKeysRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = new PushController();

        $this->expectException(\Exception::class);
        $controller->generateVapidKeys($request);
    }

    /**
     * @throws \Exception
     */
    public function testGenerateVapidKeysReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'invalid-token'], JSON_THROW_ON_ERROR));
        $controller = new PushController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->generateVapidKeys($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testGenerateVapidKeysReturnsUnauthorizedForMissingCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([], JSON_THROW_ON_ERROR));
        $controller = new PushController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->generateVapidKeys($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testGenerateVapidKeysProcessesRequestWithValidCsrfWhenAuthenticated(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => $csrfToken], JSON_THROW_ON_ERROR));
        $controller = new PushController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        set_error_handler(static function (int $severity, string $message): bool {
            return $severity === E_WARNING && str_contains($message, 'openssl_pkey_new()');
        });
        $response = $controller->generateVapidKeys($request);
        restore_error_handler();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_INTERNAL_SERVER_ERROR]);
        self::assertArrayHasKey('success', $payload);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            self::assertTrue($payload['success']);
            self::assertArrayHasKey('publicKey', $payload);
        } else {
            self::assertFalse($payload['success']);
            self::assertArrayHasKey('error', $payload);
        }

        $this->removeCsrfCookie('pmf-csrf-token');
    }

    private function setCsrfCookie(string $page, string $token): void
    {
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;
    }

    private function removeCsrfCookie(string $page): void
    {
        unset($_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)]);
    }
}
