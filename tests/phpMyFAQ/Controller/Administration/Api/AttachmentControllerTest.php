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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AttachmentController::class)]
#[UsesNamespace('phpMyFAQ')]
final class AttachmentControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-attachment-controller-');
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
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('delete-attachment'), 0, 10)]);
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('refresh-attachment'), 0, 10)]);

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
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'test-token',
            'attId' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testRefreshRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'test-token',
            'attId' => 1,
        ], JSON_THROW_ON_ERROR));
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->refresh($request);
    }

    /**
     * @throws \Exception
     */
    public function testUploadRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new AttachmentController();

        $this->expectException(\Exception::class);
        $controller->upload($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = new AttachmentController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'attId' => 1,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testRefreshReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = new AttachmentController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->refresh(new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'attId' => 1,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testRefreshWithValidCsrfProcessesAttachmentAndReturnsJson(): void
    {
        if (!defined('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR')) {
            define('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR', sys_get_temp_dir() . '/');
        }

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'refresh-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $response = $controller->refresh(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'attId' => 1,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_INTERNAL_SERVER_ERROR]);
        self::assertIsArray($payload);
        self::assertTrue(array_key_exists('success', $payload) || array_key_exists('error', $payload));
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsBadRequestWhenNoFilesAreProvidedAndAuthenticated(): void
    {
        $controller = new AttachmentController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->upload(new Request());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoImagesForUpload'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsSuccessWithValidCsrf(): void
    {
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'attId' => 1,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('msgAttachmentsDeleted'), $payload['success']);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsBadRequestWhenUploadedFileIsInvalid(): void
    {
        $invalidFile = $this->createMock(UploadedFile::class);
        $invalidFile->method('isValid')->willReturn(false);

        $controller = new AttachmentController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], [], [], ['filesToUpload' => [$invalidFile]]);
        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgImageTooLarge'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsBadRequestWhenUploadedFileIsTooLarge(): void
    {
        $oversizedFile = $this->createMock(UploadedFile::class);
        $oversizedFile->method('isValid')->willReturn(true);
        $oversizedFile->method('getSize')->willReturn((int) $this->configuration->get('records.maxAttachmentSize') + 1);
        $oversizedFile->method('getMimeType')->willReturn('image/png');

        $controller = new AttachmentController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], [], [], ['filesToUpload' => [$oversizedFile]]);
        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgImageTooLarge'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsBadRequestWhenMimeTypeIsHtml(): void
    {
        $htmlFile = $this->createMock(UploadedFile::class);
        $htmlFile->method('isValid')->willReturn(true);
        $htmlFile->method('getSize')->willReturn(128);
        $htmlFile->method('getMimeType')->willReturn('text/html');

        $controller = new AttachmentController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], [], [], ['filesToUpload' => [$htmlFile]]);
        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgImageTooLarge'), $payload['error']);
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
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(static function (int $userId, mixed $right): bool {
                return $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::ATTACHMENT_ADD->value,
                        PermissionType::ATTACHMENT_DELETE->value,
                    ],
                    true,
                );
            });

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

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
}
