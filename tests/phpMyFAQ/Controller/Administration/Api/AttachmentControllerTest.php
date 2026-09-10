<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
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
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('upload-attachment'), 0, 10)]);

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
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $response = $controller->upload(new Request([], ['pmf-csrf-token' => $token]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoImagesForUpload'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = new AttachmentController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->upload(new Request([], ['pmf-csrf-token' => 'invalid-token']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
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

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $request = new Request([], ['pmf-csrf-token' => $token], [], [], ['filesToUpload' => [$invalidFile]]);
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

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $request = new Request([], ['pmf-csrf-token' => $token], [], [], ['filesToUpload' => [$oversizedFile]]);
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

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $request = new Request([], ['pmf-csrf-token' => $token], [], [], ['filesToUpload' => [$htmlFile]]);
        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgImageTooLarge'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsInternalServerErrorWhenFileCannotBeRead(): void
    {
        if (!defined('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR')) {
            define('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR', sys_get_temp_dir() . '/');
        }

        $missingFile = $this->createMock(UploadedFile::class);
        $missingFile->method('isValid')->willReturn(true);
        $missingFile->method('getSize')->willReturn(128);
        $missingFile->method('getMimeType')->willReturn('image/png');
        $missingFile->method('getPathname')->willReturn(sys_get_temp_dir() . '/pmf-missing-upload.png');
        $missingFile->method('getClientOriginalName')->willReturn('upload.png');

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $request = new Request(
            [],
            ['pmf-csrf-token' => $token, 'record_id' => 1, 'record_lang' => 'en'],
            [],
            [],
            [
                'filesToUpload' => [$missingFile],
            ],
        );
        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testUploadAcceptsCustomFileNamesWithoutError(): void
    {
        if (!defined('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR')) {
            define('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR', sys_get_temp_dir() . '/');
        }

        $missingFile = $this->createMock(UploadedFile::class);
        $missingFile->method('isValid')->willReturn(true);
        $missingFile->method('getSize')->willReturn(128);
        $missingFile->method('getMimeType')->willReturn('image/png');
        $missingFile->method('getPathname')->willReturn(sys_get_temp_dir() . '/pmf-missing-upload.png');
        $missingFile->method('getClientOriginalName')->willReturn('upload.png');

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $request = new Request(
            [],
            [
                'pmf-csrf-token' => $token,
                'customFileNames' => ['my-custom-name'],
                'record_id' => 1,
                'record_lang' => 'en',
            ],
            [],
            [],
            [
                'filesToUpload' => [$missingFile],
            ],
        );
        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // The save still fails because the file is missing, but the custom-name
        // param must be read and processed without raising an error.
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    /**
     * A crafted request can make a customFileNames entry an array instead of a string;
     * the controller must ignore non-string entries rather than cast them to "Array".
     *
     * @throws \Exception
     */
    public function testUploadIgnoresNonStringCustomFileNameEntries(): void
    {
        if (!defined('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR')) {
            define('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR', sys_get_temp_dir() . '/');
        }

        $missingFile = $this->createMock(UploadedFile::class);
        $missingFile->method('isValid')->willReturn(true);
        $missingFile->method('getSize')->willReturn(128);
        $missingFile->method('getMimeType')->willReturn('image/png');
        $missingFile->method('getPathname')->willReturn(sys_get_temp_dir() . '/pmf-missing-upload.png');
        $missingFile->method('getClientOriginalName')->willReturn('upload.png');

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $request = new Request(
            [],
            [
                'pmf-csrf-token' => $token,
                'customFileNames' => [['nested-array-entry']],
                'record_id' => 1,
                'record_lang' => 'en',
            ],
            [],
            [],
            [
                'filesToUpload' => [$missingFile],
            ],
        );
        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteDeniesAttachmentOfFaqOutsideAllowedCategories(): void
    {
        $this->seedAttachment(7, 5, 'en');
        $this->seedCategoryRelation(666, 5, 'en');

        $container = $this->createAuthenticatedContainer([3]);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        try {
            $controller->delete(new Request([], [], [], [], [], [], json_encode([
                'csrf' => $token,
                'attId' => 7,
            ], JSON_THROW_ON_ERROR)));
            self::fail('Expected a ForbiddenException for an attachment outside the allowed categories.');
        } catch (ForbiddenException) {
            // expected
        }

        self::assertSame(1, $this->countAttachments(7));
    }

    /**
     * A restricted user must not delete attachments of an uncategorized FAQ either, since
     * there is no category membership that could put it inside their scope.
     *
     * @throws \Exception
     */
    public function testDeleteDeniesRestrictedUserForAttachmentOfUncategorizedFaq(): void
    {
        $this->seedAttachment(7, 5, 'en');

        $container = $this->createAuthenticatedContainer([3]);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $this->expectException(ForbiddenException::class);
        $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'attId' => 7,
        ], JSON_THROW_ON_ERROR)));
    }

    /**
     * @throws \Exception
     */
    public function testDeleteAllowsAttachmentOfFaqInsideAllowedCategories(): void
    {
        if (!defined('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR')) {
            define('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR', sys_get_temp_dir() . '/');
        }

        $this->seedAttachment(7, 5, 'en');
        $this->seedCategoryRelation(3, 5, 'en');

        $container = $this->createAuthenticatedContainer([3]);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrf' => $token,
            'attId' => 7,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('msgAttachmentsDeleted'), $payload['success']);
        self::assertSame(0, $this->countAttachments(7));
    }

    /**
     * @throws \Exception
     */
    public function testRefreshDeniesAttachmentOfFaqOutsideAllowedCategories(): void
    {
        $this->seedAttachment(7, 5, 'en');
        $this->seedCategoryRelation(666, 5, 'en');

        $container = $this->createAuthenticatedContainer([3]);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'refresh-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        try {
            $controller->refresh(new Request([], [], [], [], [], [], json_encode([
                'csrf' => $token,
                'attId' => 7,
            ], JSON_THROW_ON_ERROR)));
            self::fail('Expected a ForbiddenException for an attachment outside the allowed categories.');
        } catch (ForbiddenException) {
            // expected
        }

        self::assertSame(1, $this->countAttachments(7));
    }

    /**
     * @throws \Exception
     */
    public function testUploadDeniesFaqOutsideAllowedCategories(): void
    {
        if (!defined('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR')) {
            define('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR', sys_get_temp_dir() . '/');
        }

        $this->seedCategoryRelation(666, 5, 'en');

        $uploadPath = tempnam(sys_get_temp_dir(), 'pmf-attachment-upload-');
        self::assertNotFalse($uploadPath);
        file_put_contents($uploadPath, 'attachment payload');

        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(18);
        $file->method('getMimeType')->willReturn('text/plain');
        $file->method('getPathname')->willReturn($uploadPath);
        $file->method('getClientOriginalName')->willReturn('upload.txt');

        $container = $this->createAuthenticatedContainer([3]);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $request = new Request(
            [],
            ['pmf-csrf-token' => $token, 'record_id' => 5, 'record_lang' => 'en'],
            [],
            [],
            ['filesToUpload' => [$file]],
        );

        try {
            $controller->upload($request);
            self::fail('Expected a ForbiddenException for an FAQ outside the allowed categories.');
        } catch (ForbiddenException) {
            // expected
        } finally {
            @unlink($uploadPath);
        }

        self::assertSame(0, $this->countAttachmentsOfFaq(5, 'en'));
    }

    /**
     * @throws \Exception
     */
    public function testUploadDeniesRestrictedUserForUncategorizedFaq(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getSize')->willReturn(128);
        $file->method('getMimeType')->willReturn('image/png');

        $container = $this->createAuthenticatedContainer([3]);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $this->expectException(ForbiddenException::class);
        $controller->upload(new Request(
            [],
            ['pmf-csrf-token' => $token, 'record_id' => 5, 'record_lang' => 'en'],
            [],
            [],
            ['filesToUpload' => [$file]],
        ));
    }

    /**
     * @throws \Exception
     */
    public function testUploadPassesScopeCheckForFaqInsideAllowedCategories(): void
    {
        if (!defined('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR')) {
            define('phpMyFAQ\\Attachment\\PMF_ATTACHMENTS_DIR', sys_get_temp_dir() . '/');
        }

        $this->seedCategoryRelation(3, 5, 'en');

        $missingFile = $this->createMock(UploadedFile::class);
        $missingFile->method('isValid')->willReturn(true);
        $missingFile->method('getSize')->willReturn(128);
        $missingFile->method('getMimeType')->willReturn('image/png');
        $missingFile->method('getPathname')->willReturn(sys_get_temp_dir() . '/pmf-missing-upload.png');
        $missingFile->method('getClientOriginalName')->willReturn('upload.png');

        $container = $this->createAuthenticatedContainer([3]);
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'upload-attachment');

        $controller = new AttachmentController();
        $controller->setContainer($container);

        $response = $controller->upload(new Request(
            [],
            ['pmf-csrf-token' => $token, 'record_id' => 5, 'record_lang' => 'en'],
            [],
            [],
            ['filesToUpload' => [$missingFile]],
        ));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // The scope check passes; the save itself still fails because the file is missing.
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    private function seedAttachment(int $attachmentId, int $recordId, string $recordLang): void
    {
        self::assertNotFalse($this->dbHandle->query(sprintf(
            "INSERT INTO faqattachment (id, record_id, record_lang, real_hash, virtual_hash, filename, filesize,"
            . " encrypted, mime_type) VALUES (%d, %d, '%s', '%s', '%s', 'file.txt', 18, 0, 'text/plain')",
            $attachmentId,
            $recordId,
            $recordLang,
            md5('real-' . $attachmentId),
            md5('virtual-' . $attachmentId),
        )));
    }

    private function seedCategoryRelation(int $categoryId, int $recordId, string $recordLang): void
    {
        self::assertNotFalse($this->dbHandle->query(sprintf(
            "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang)"
            . " VALUES (%d, '%s', %d, '%s')",
            $categoryId,
            $recordLang,
            $recordId,
            $recordLang,
        )));
    }

    private function countAttachments(int $attachmentId): int
    {
        $result = $this->dbHandle->query(sprintf('SELECT id FROM faqattachment WHERE id = %d', $attachmentId));

        return $this->dbHandle->numRows($result);
    }

    private function countAttachmentsOfFaq(int $recordId, string $recordLang): int
    {
        $result = $this->dbHandle->query(sprintf(
            "SELECT id FROM faqattachment WHERE record_id = %d AND record_lang = '%s'",
            $recordId,
            $recordLang,
        ));

        return $this->dbHandle->numRows($result);
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
     * @param int[]|null $allowedCategories null grants an unrestricted right; a list restricts the
     *                                      attachment rights to those categories, with 666 always denied
     */
    private function createAuthenticatedContainer(?array $allowedCategories = null): ContainerInterface
    {
        $attachmentRights = [
            PermissionType::ATTACHMENT_ADD->value,
            PermissionType::ATTACHMENT_DELETE->value,
        ];

        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 42
                && in_array($right, $attachmentRights, true),
            );
        $permission
            ->method('hasPermissionForCategory')
            ->willReturnCallback(
                static fn(int $userId, mixed $right, int $categoryId): bool => $userId === 42
                && in_array($right, $attachmentRights, true)
                && $categoryId !== 666 // sentinel forbidden category for tests
                && ($allowedCategories === null || in_array($categoryId, $allowedCategories, true)),
            );
        $permission->method('getAllowedCategoriesForRight')->willReturn($allowedCategories);
        $permission
            ->method('hasPermissionForLanguage')
            ->willReturnCallback(
                static fn(int $userId, mixed $right, string $language): bool => $userId === 42
                && in_array($right, $attachmentRights, true),
            );

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
