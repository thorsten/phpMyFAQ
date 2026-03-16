<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Attachment\AttachmentService;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\AttachmentStorageType;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(AttachmentController::class)]
#[UsesNamespace('phpMyFAQ')]
final class AttachmentControllerTest extends TestCase
{
    private string $databasePath;
    private Sqlite3 $dbHandle;
    private Configuration $configuration;
    private ?Configuration $previousConfiguration = null;
    private mixed $previousAttachmentStorageType = null;
    private mixed $previousAttachmentEncryptionEnabled = null;
    private mixed $previousAttachmentDefaultKey = null;

    /** @var list<string> */
    private array $createdFiles = [];

    /** @var list<int> */
    private array $createdAttachmentIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-frontend-attachment-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $this->configuration);

        $this->initializeDatabaseStatics($this->dbHandle);
        $this->initializeLanguage();
        $this->backupAttachmentFactoryState();
        $this->setAttachmentFactoryState();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdAttachmentIds as $attachmentId) {
            $this->dbHandle->query(sprintf('DELETE FROM faqattachment WHERE id = %d', $attachmentId));
        }

        foreach (array_reverse($this->createdFiles) as $path) {
            if (is_file($path)) {
                unlink($path);
            }

            $directory = dirname($path);
            $attachmentsRoot = $this->getAttachmentsRoot();
            while (str_starts_with($directory, $attachmentsRoot) && $directory !== $attachmentsRoot) {
                if (!is_dir($directory) || scandir($directory) !== ['.', '..']) {
                    break;
                }

                rmdir($directory);
                $directory = dirname($directory);
            }
        }

        $this->restoreAttachmentFactoryState();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        Database::setTablePrefix('');

        if (is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsErrorPageWhenAttachmentIdIsInvalid(): void
    {
        $controller = $this->createController();

        $response = $controller->index(new Request([], [], ['attachmentId' => 'invalid']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString(
            Translation::get(key: 'msgAttachmentInvalid'),
            (string) $response->getContent(),
        );
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsErrorPageWhenAttachmentDoesNotExist(): void
    {
        $this->enableGuestDownloads();
        $controller = $this->createController();

        $response = $controller->index(new Request([], [], ['attachmentId' => '999999']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString(
            Translation::get(key: 'msgAttachmentInvalid'),
            (string) $response->getContent(),
        );
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsErrorPageWhenAttachmentLookupThrowsException(): void
    {
        $this->enableGuestDownloads();

        $reflection = new \ReflectionClass(AttachmentFactory::class);
        $storageTypeProperty = $reflection->getProperty('storageType');
        $storageTypeProperty->setValue(null, 999);

        $controller = $this->createController();
        $response = $controller->index(new Request([], [], ['attachmentId' => '1']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString(
            Translation::get(key: 'msgAttachmentInvalid'),
            (string) $response->getContent(),
        );
    }

    /**
     * @throws \Exception
     */
    public function testIndexStreamsPdfAttachmentInline(): void
    {
        $attachmentId = $this->seedAttachment(900001, 'manual.pdf', 'application/pdf', '%PDF-test attachment');
        $this->enableGuestDownloads();
        $attachmentService = new AttachmentService(
            $this->configuration,
            new CurrentUser($this->configuration),
            new Permission($this->configuration),
        );
        self::assertTrue((bool) $this->configuration->get('records.allowDownloadsForGuests'));
        $attachment = $attachmentService->getAttachment($attachmentId);
        self::assertSame(1, $attachment->getRecordId());
        self::assertTrue($attachmentService->canDownloadAttachment($attachment));

        $controller = $this->createController();

        ob_start();
        $response = $controller->index(new Request([], [], ['attachmentId' => (string) $attachmentId]));
        ob_end_clean();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringNotContainsString(
            Translation::get(key: 'msgAttachmentInvalid'),
            (string) $response->getContent(),
        );
    }

    /**
     * @throws \Exception
     */
    public function testIndexStreamsNonPdfAttachmentAsDownload(): void
    {
        $attachmentId = $this->seedAttachment(900002, 'notes.txt', 'text/plain', 'plain-text attachment');
        $this->enableGuestDownloads();
        $attachmentService = new AttachmentService(
            $this->configuration,
            new CurrentUser($this->configuration),
            new Permission($this->configuration),
        );
        self::assertTrue((bool) $this->configuration->get('records.allowDownloadsForGuests'));
        $attachment = $attachmentService->getAttachment($attachmentId);
        self::assertSame(1, $attachment->getRecordId());
        self::assertTrue($attachmentService->canDownloadAttachment($attachment));

        $controller = $this->createController();

        ob_start();
        $response = $controller->index(new Request([], [], ['attachmentId' => (string) $attachmentId]));
        ob_end_clean();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringNotContainsString(
            Translation::get(key: 'msgAttachmentInvalid'),
            (string) $response->getContent(),
        );
    }

    private function createController(): AttachmentController
    {
        $this->enableGuestDownloads();
        $controller = new AttachmentController(new Permission($this->configuration));

        $configurationProperty = new \ReflectionProperty($controller, 'configuration');
        $configurationProperty->setValue($controller, $this->configuration);

        $currentUserProperty = new \ReflectionProperty($controller, 'currentUser');
        $currentUserProperty->setValue($controller, new CurrentUser($this->configuration));

        return $controller;
    }

    private function enableGuestDownloads(): void
    {
        $this->dbHandle->query(
            "UPDATE faqconfig SET config_value = 'true' WHERE config_name = 'records.allowDownloadsForGuests'",
        );
        $this->dbHandle->query(
            "UPDATE faqconfig SET config_value = 'false' WHERE config_name = 'security.enableLoginOnly'",
        );
        $this->dbHandle->query("UPDATE faqconfig SET config_value = 'filesystem' WHERE config_name = 'storage.type'");

        $this->overrideConfigurationValues([
            'records.allowDownloadsForGuests' => 'true',
            'security.enableLoginOnly' => 'false',
            'storage.type' => 'filesystem',
        ]);
    }

    private function initializeLanguage(): void
    {
        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
        Language::$language = 'en';
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }

    /**
     * @param array<string, mixed> $values
     */
    private function overrideConfigurationValues(array $values): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $config = $configProperty->getValue($this->configuration);
        self::assertIsArray($config);
        $configProperty->setValue($this->configuration, array_merge($config, $values));
    }

    private function backupAttachmentFactoryState(): void
    {
        $reflection = new \ReflectionClass(AttachmentFactory::class);

        $storageTypeProperty = $reflection->getProperty('storageType');
        $this->previousAttachmentStorageType = $storageTypeProperty->getValue();

        $encryptionEnabledProperty = $reflection->getProperty('encryptionEnabled');
        $this->previousAttachmentEncryptionEnabled = $encryptionEnabledProperty->getValue();

        $defaultKeyProperty = $reflection->getProperty('defaultKey');
        $this->previousAttachmentDefaultKey = $defaultKeyProperty->getValue();
    }

    private function setAttachmentFactoryState(): void
    {
        $reflection = new \ReflectionClass(AttachmentFactory::class);

        $storageTypeProperty = $reflection->getProperty('storageType');
        $storageTypeProperty->setValue(null, AttachmentStorageType::FILESYSTEM->value);

        $encryptionEnabledProperty = $reflection->getProperty('encryptionEnabled');
        $encryptionEnabledProperty->setValue(null, false);

        $defaultKeyProperty = $reflection->getProperty('defaultKey');
        $defaultKeyProperty->setValue(null, '');
    }

    private function restoreAttachmentFactoryState(): void
    {
        $reflection = new \ReflectionClass(AttachmentFactory::class);

        $storageTypeProperty = $reflection->getProperty('storageType');
        $storageTypeProperty->setValue(null, $this->previousAttachmentStorageType);

        $encryptionEnabledProperty = $reflection->getProperty('encryptionEnabled');
        $encryptionEnabledProperty->setValue(null, $this->previousAttachmentEncryptionEnabled);

        $defaultKeyProperty = $reflection->getProperty('defaultKey');
        $defaultKeyProperty->setValue(null, $this->previousAttachmentDefaultKey);
    }

    private function seedAttachment(int $attachmentId, string $filename, string $mimeType, string $contents): int
    {
        $realHash = md5($contents);
        $virtualHash = $realHash;
        $storagePath = $this->buildStoragePath($realHash);
        $absolutePath = $this->getAttachmentsRoot() . '/0/attachments/' . $storagePath;

        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($absolutePath, $contents);

        $this->createdFiles[] = $absolutePath;
        $this->createdAttachmentIds[] = $attachmentId;

        $query = sprintf(
            "INSERT INTO faqattachment (id, record_id, record_lang, real_hash, virtual_hash, password_hash, filename, filesize, encrypted, mime_type)
            VALUES (%d, %d, 'en', '%s', '%s', '', '%s', %d, 0, '%s')",
            $attachmentId,
            1,
            $this->dbHandle->escape($realHash),
            $this->dbHandle->escape($virtualHash),
            $this->dbHandle->escape($filename),
            strlen($contents),
            $this->dbHandle->escape($mimeType),
        );
        $this->dbHandle->query($query);

        return $attachmentId;
    }

    private function buildStoragePath(string $virtualHash): string
    {
        return implode('/', [
            substr($virtualHash, 0, 5),
            substr($virtualHash, 5, 5),
            substr($virtualHash, 10, 5),
            substr($virtualHash, 15),
        ]);
    }

    private function getAttachmentsRoot(): string
    {
        if (defined('PMF_ATTACHMENTS_DIR') && PMF_ATTACHMENTS_DIR !== false) {
            return rtrim((string) PMF_ATTACHMENTS_DIR, '/');
        }

        return PMF_ROOT_DIR . '/content/user/attachments';
    }
}
