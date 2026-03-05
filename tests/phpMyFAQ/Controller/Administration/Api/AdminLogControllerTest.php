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
#[CoversClass(AdminLogController::class)]
#[UsesNamespace('phpMyFAQ')]
final class AdminLogControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-adminlog-controller-');
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
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = new AdminLogController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testExportRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = new AdminLogController();

        $this->expectException(\Exception::class);
        $controller->export($request);
    }

    /**
     * @throws \Exception
     */
    public function testVerifyRequiresAuthentication(): void
    {
        $request = new Request(['csrf' => 'test-token']);
        $controller = new AdminLogController();

        $this->expectException(\Exception::class);
        $controller->verify($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($this->createStub(AdminLog::class)));

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testExportReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($this->createStub(AdminLog::class)));

        $response = $controller->export(new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testVerifyReturnsForbiddenForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($this->createStub(AdminLog::class)));

        $response = $controller->verify(new Request(['csrf' => 'invalid-token']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('Invalid CSRF token', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsSuccessWhenLogDeletionSucceeds(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('delete-adminlog');
        $this->setCsrfCookie('delete-adminlog', $csrfToken);

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog->expects($this->once())->method('delete')->willReturn(true);

        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($adminLog, $session));

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        $this->removeCsrfCookie('delete-adminlog');
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsBadRequestWhenLogDeletionFails(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('delete-adminlog');
        $this->setCsrfCookie('delete-adminlog', $csrfToken);

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog->expects($this->once())->method('delete')->willReturn(false);

        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($adminLog, $session));

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
        $this->removeCsrfCookie('delete-adminlog');
    }

    /**
     * @throws \Exception
     */
    public function testExportReturnsCsvWhenCsrfIsValid(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('export-adminlog');
        $this->setCsrfCookie('export-adminlog', $csrfToken);

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog->expects($this->once())->method('getAll')->willReturn([]);
        $adminLog->expects($this->once())->method('log');

        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($adminLog, $session));

        $response = $controller->export(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('text/csv', $response->headers->get('Content-Type'));
        self::assertStringContainsString('"User ID"', (string) $response->getContent());
        self::assertStringContainsString('"IP Address"', (string) $response->getContent());
        $this->removeCsrfCookie('export-adminlog');
    }

    /**
     * @throws \Exception
     */
    public function testVerifyReturnsSuccessWhenChainIsValid(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('admin-log-verify');
        $this->setCsrfCookie('admin-log-verify', $csrfToken);

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog
            ->expects($this->once())
            ->method('verifyChainIntegrity')
            ->willReturn([
                'valid' => true,
                'total' => 3,
                'verified' => 3,
                'errors' => [],
            ]);

        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($adminLog, $session));

        $response = $controller->verify(new Request(['csrf' => $csrfToken]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertTrue($payload['verification']['valid']);
        self::assertSame(0, $payload['verification']['failed']);
        $this->removeCsrfCookie('admin-log-verify');
    }

    /**
     * @throws \Exception
     */
    public function testVerifyReturnsConflictWhenChainIsInvalid(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('admin-log-verify');
        $this->setCsrfCookie('admin-log-verify', $csrfToken);

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog
            ->expects($this->once())
            ->method('verifyChainIntegrity')
            ->willReturn([
                'valid' => false,
                'total' => 3,
                'verified' => 2,
                'errors' => ['Invalid hash at entry #3'],
            ]);

        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($adminLog, $session));

        $response = $controller->verify(new Request(['csrf' => $csrfToken]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertFalse($payload['verification']['valid']);
        self::assertSame(1, $payload['verification']['failed']);
        $this->removeCsrfCookie('admin-log-verify');
    }

    /**
     * @throws \Exception
     */
    public function testVerifyReturnsInternalServerErrorWhenVerificationThrows(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('admin-log-verify');
        $this->setCsrfCookie('admin-log-verify', $csrfToken);

        $adminLog = $this->createMock(AdminLog::class);
        $adminLog->expects($this->once())->method('verifyChainIntegrity')->willThrowException(new \Exception('boom'));

        $controller = new AdminLogController();
        $controller->setContainer($this->createAuthenticatedContainer($adminLog, $session));

        $response = $controller->verify(new Request(['csrf' => $csrfToken]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame('boom', $payload['error']);
        $this->removeCsrfCookie('admin-log-verify');
    }

    private function setCsrfCookie(string $page, string $token): void
    {
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;
    }

    private function removeCsrfCookie(string $page): void
    {
        unset($_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)]);
    }

    private function createAuthenticatedContainer(AdminLog $adminLog, ?Session $session = null): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(static function (int $userId, mixed $right): bool {
                return $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::STATISTICS_VIEWLOGS->value,
                        PermissionType::STATISTICS_ADMINLOG->value,
                    ],
                    true,
                );
            });

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session ??= new Session(new MockArraySessionStorage());

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
}
