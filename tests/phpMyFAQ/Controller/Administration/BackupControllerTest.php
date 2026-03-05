<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Backup\BackupExportResult;
use phpMyFAQ\Administration\Helper;
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
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(BackupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class BackupControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-backup-page-controller-');
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
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('restore'), 0, 10)]);

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

    private function createController(): BackupController
    {
        return new BackupController($this->createStub(Backup::class));
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
    public function testExportReturnsBadRequestForInvalidType(): void
    {
        $controller = $this->createAuthenticatedController();

        $response = $controller->export(new Request([], [], ['type' => 'invalid']));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testExportReturnsAttachmentForValidContentType(): void
    {
        $backup = $this->createMock(Backup::class);
        $backup
            ->expects($this->once())
            ->method('export')
            ->willReturn(new BackupExportResult('backup.zip', 'backup-content'));

        $controller = $this->createAuthenticatedController($backup);

        $response = $controller->export(new Request([], [], ['type' => 'content']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('backup-content', $response->getContent());
        self::assertSame('application/octet-stream; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('backup.zip', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * @throws \Exception
     */
    public function testExportReturnsInternalServerErrorWhenBackupExportFails(): void
    {
        $backup = $this->createMock(Backup::class);
        $backup->expects($this->once())->method('export')->willThrowException(new \SodiumException('export failed'));

        $controller = $this->createAuthenticatedController($backup);

        $response = $controller->export(new Request([], [], ['type' => 'content']));

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testRestoreThrowsForInvalidCsrfToken(): void
    {
        $controller = $this->createAuthenticatedController();

        $this->expectException(UnauthorizedHttpException::class);
        $controller->restore(new Request(['csrf' => 'invalid-token']));
    }

    /**
     * @throws \Exception
     */
    public function testRestoreReturnsErrorWhenNoFileWasUploaded(): void
    {
        $container = $this->createControllerContainer();
        $controller = new BackupController($this->createStub(Backup::class));
        $controller->setContainer($container);

        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $csrfToken = $this->createValidCsrfToken($session, 'restore');

        $response = $controller->restore(new Request(['csrf' => $csrfToken]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('No file was uploaded', (string) $response->getContent());
    }

    private function createAuthenticatedController(?Backup $backup = null): BackupController
    {
        $controller = new BackupController($backup ?? $this->createStub(Backup::class));
        $controller->setContainer($this->createControllerContainer());

        return $controller;
    }

    private function createValidCsrfToken(Session $session, string $page): string
    {
        Token::resetInstanceForTests();
        $token = Token::getInstance($session)->getTokenString($page);
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;

        return $token;
    }

    private function createControllerContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 42
                && in_array($right, [PermissionType::BACKUP->value, PermissionType::RESTORE->value], true),
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Test User'],
                ['email',        'test@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
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
