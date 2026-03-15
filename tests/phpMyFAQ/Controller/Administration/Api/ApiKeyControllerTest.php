<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
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
#[CoversClass(ApiKeyController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ApiKeyControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-api-key-controller-');
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
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('api-key-create'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('api-key-update'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('api-key-delete'), 0, 10)]);

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

    private function createController(): ApiKeyController
    {
        return new ApiKeyController();
    }

    /**
     * @throws \Exception
     */
    public function testListRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->list();
    }

    /**
     * @throws \Exception
     */
    public function testCreateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateRequiresAuthentication(): void
    {
        $request = new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => 'test-token',
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request([], ['csrf' => 'test-token'], ['id' => 1]);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateWithInvalidJsonStillRequiresAuthenticationFirst(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testListReturnsApiKeysForAuthenticatedUser(): void
    {
        $this->seedApiKeyRow();

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->list();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertCount(1, $payload);
        self::assertSame('Test key', $payload[0]['name']);
        self::assertSame(['faq.read'], $payload[0]['scopes']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestForInvalidJsonWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $this->expectException(\JsonException::class);
        $controller->create(new Request([], [], [], [], [], [], 'invalid json'));
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->update(new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'name' => 'New name',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsCreatedApiKeyWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-create');
        $controller->setContainer($container);

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'name' => 'Generated key',
            'scopes' => ['faq.read', 'faq.write'],
            'expiresAt' => '2026-03-31 00:00:00',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('Generated key', $payload['name']);
        self::assertSame(['faq.read', 'faq.write'], $payload['scopes']);
        self::assertMatchesRegularExpression('/^pmf_[a-f0-9]{40}$/', $payload['apiKey']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsNotFoundForUnknownApiKeyWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-update');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], ['id' => 999], [], [], [], json_encode([
            'csrf' => $token,
            'name' => 'Updated key',
            'scopes' => ['faq.read'],
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('API key not found.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsSuccessWhenAuthenticated(): void
    {
        $this->seedApiKeyRow();

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-delete');
        $controller->setContainer($container);

        $response = $controller->delete(new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => $token,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsBadRequestForMissingIdWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-delete');
        $controller->setContainer($container);

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('API key ID is required.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestForInvalidExpiresAtWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-create');
        $controller->setContainer($container);

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'name' => 'Generated key',
            'scopes' => ['faq.read'],
            'expiresAt' => 'not-a-date',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Invalid expiresAt value.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'name' => 'Generated key',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestForMissingNameWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-create');
        $controller->setContainer($container);

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'name' => '',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('API key name is required.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsInternalServerErrorWhenInsertFails(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $db->method('nextId')->willReturn(5);
        $db->method('now')->willReturn("'2026-03-15 12:00:00'");
        $db->method('query')->willReturn(false);
        $db->method('error')->willReturn('insert failed');

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainerWithDb($db);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-create');
        $controller->setContainer($container);

        $response = $controller->create(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'name' => 'Generated key',
            'scopes' => ['faq.read'],
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('insert failed', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsBadRequestForMissingNameWhenAuthenticated(): void
    {
        $this->seedApiKeyRow();

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-update');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => $token,
            'name' => '',
            'scopes' => ['faq.read'],
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('API key name is required.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsUpdatedApiKeyWhenAuthenticated(): void
    {
        $this->seedApiKeyRow();

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-update');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => $token,
            'name' => 'Updated key',
            'scopes' => ['faq.read', 'faq.write'],
            'expiresAt' => '2026-04-01 12:00:00',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, $payload['id']);
        self::assertSame('Updated key', $payload['name']);
        self::assertSame(['faq.read', 'faq.write'], $payload['scopes']);
        self::assertSame('2026-04-01 12:00:00', $payload['expiresAt']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsBadRequestForMissingIdWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-update');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'name' => 'Updated key',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('API key ID is required.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsBadRequestForInvalidExpiresAtWhenAuthenticated(): void
    {
        $this->seedApiKeyRow();

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-update');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => $token,
            'name' => 'Updated key',
            'expiresAt' => 'not-a-date',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Invalid expiresAt value.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsInternalServerErrorWhenUpdateFails(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $db->method('query')->willReturnMap([
            [$this->stringContains('SELECT id FROM faqapi_keys'), 0, 0, new \stdClass()],
            [$this->stringContains('UPDATE faqapi_keys'), 0, 0, false],
        ]);
        $db->method('numRows')->willReturn(1);
        $db->method('error')->willReturn('update failed');

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainerWithDb($db);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-update');
        $controller->setContainer($container);

        $response = $controller->update(new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => $token,
            'name' => 'Updated key',
            'scopes' => ['faq.read'],
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('update failed', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->delete(new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsInternalServerErrorWhenDeleteFails(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('query')->willReturn(false);
        $db->method('error')->willReturn('delete failed');

        $controller = $this->createController();
        $container = $this->createAuthenticatedContainerWithDb($db);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'api-key-delete');
        $controller->setContainer($container);

        $response = $controller->delete(new Request([], [], ['id' => 1], [], [], [], json_encode([
            'csrf' => $token,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('delete failed', $payload['error']);
    }

    private function seedApiKeyRow(): void
    {
        $this->dbHandle->query('DELETE FROM faqapi_keys');
        $this->dbHandle->query(
            "INSERT INTO faqapi_keys (id, user_id, api_key, name, scopes, last_used_at, expires_at, created)
             VALUES (1, 42, 'hash', 'Test key', '[\"faq.read\"]', NULL, NULL, '2026-03-01 12:00:00')",
        );
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
        return $this->createAuthenticatedContainerWithDb($this->configuration->getDb());
    }

    private function createAuthenticatedContainerWithDb(DatabaseDriver $db): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => (
                    $userId === 42
                    && $right === PermissionType::USER_EDIT->value
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($db);
        $configuration->method('get')->willReturn(false);
        $configuration->method('getTemplateSet')->willReturn('default');

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog, $configuration) {
                return match ($id) {
                    'phpmyfaq.configuration' => $configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        return $container;
    }
}
