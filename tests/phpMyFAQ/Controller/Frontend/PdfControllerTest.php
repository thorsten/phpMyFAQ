<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(PdfController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PdfControllerTest extends TestCase
{
    private string $databasePath;
    private Sqlite3 $dbHandle;
    private ?Configuration $previousConfiguration = null;
    private mixed $previousAttachmentStorageType = null;
    private mixed $previousAttachmentEncryptionEnabled = null;
    private mixed $previousAttachmentDefaultKey = null;

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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-frontend-pdf-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $configuration = new Configuration($this->dbHandle);
        $configurationProperty->setValue(null, $configuration);

        Database::setTablePrefix('');
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');

        $language = new Language($configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $attachmentFactoryReflection = new \ReflectionClass(\phpMyFAQ\Attachment\AttachmentFactory::class);
        $storageTypeProperty = $attachmentFactoryReflection->getProperty('storageType');
        $encryptionEnabledProperty = $attachmentFactoryReflection->getProperty('encryptionEnabled');
        $defaultKeyProperty = $attachmentFactoryReflection->getProperty('defaultKey');
        $this->previousAttachmentStorageType = $storageTypeProperty->getValue();
        $this->previousAttachmentEncryptionEnabled = $encryptionEnabledProperty->getValue();
        $this->previousAttachmentDefaultKey = $defaultKeyProperty->getValue();
    }

    protected function tearDown(): void
    {
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

        $attachmentFactoryReflection = new \ReflectionClass(\phpMyFAQ\Attachment\AttachmentFactory::class);
        $storageTypeProperty = $attachmentFactoryReflection->getProperty('storageType');
        $encryptionEnabledProperty = $attachmentFactoryReflection->getProperty('encryptionEnabled');
        $defaultKeyProperty = $attachmentFactoryReflection->getProperty('defaultKey');
        $storageTypeProperty->setValue(null, $this->previousAttachmentStorageType);
        $encryptionEnabledProperty->setValue(null, $this->previousAttachmentEncryptionEnabled);
        $defaultKeyProperty->setValue(null, $this->previousAttachmentDefaultKey);

        if (is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testIndexRedirectsWhenParametersAreInvalid(): void
    {
        $controller = new PdfController(
            new Faq(Configuration::getConfigurationInstance()),
            new Tags(Configuration::getConfigurationInstance()),
        );

        $response = $controller->index(
            new Request(
                [],
                [],
                [
                    'categoryId' => 'not-an-int',
                    'faqId' => 'also-invalid',
                    'faqLanguage' => 'en',
                ],
            ),
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(
            Configuration::getConfigurationInstance()->getDefaultUrl(),
            $response->headers->get('Location'),
        );
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsPdfResponseForValidFaq(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configProperty = $configurationReflection->getProperty('config');
        $config = $configProperty->getValue($configuration);
        self::assertIsArray($config);
        $config['records.disableAttachments'] = true;
        $configProperty->setValue($configuration, $config);

        $controller = new PdfController(new Faq($configuration), new Tags($configuration));

        $response = $controller->index(
            new Request(
                [],
                [],
                [
                    'categoryId' => '1',
                    'faqId' => '1',
                    'faqLanguage' => 'en',
                ],
            ),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsPdfResponseWhenAttachmentLookupIsEnabled(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configProperty = $configurationReflection->getProperty('config');
        $config = $configProperty->getValue($configuration);
        self::assertIsArray($config);
        $config['records.disableAttachments'] = false;
        $configProperty->setValue($configuration, $config);

        $controller = new PdfController(new Faq($configuration), new Tags($configuration));

        $response = $controller->index(
            new Request(
                [],
                [],
                [
                    'categoryId' => '1',
                    'faqId' => '1',
                    'faqLanguage' => 'en',
                ],
            ),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsPdfResponseWhenAttachmentLookupThrowsException(): void
    {
        $configuration = Configuration::getConfigurationInstance();
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configProperty = $configurationReflection->getProperty('config');
        $config = $configProperty->getValue($configuration);
        self::assertIsArray($config);
        $config['records.disableAttachments'] = false;
        $configProperty->setValue($configuration, $config);

        $this->seedAttachmentRow(990001);

        $attachmentFactoryReflection = new \ReflectionClass(\phpMyFAQ\Attachment\AttachmentFactory::class);
        $storageTypeProperty = $attachmentFactoryReflection->getProperty('storageType');
        $storageTypeProperty->setValue(null, 999);

        $controller = new PdfController(new Faq($configuration), new Tags($configuration));

        $response = $controller->index(
            new Request(
                [],
                [],
                [
                    'categoryId' => '1',
                    'faqId' => '1',
                    'faqLanguage' => 'en',
                ],
            ),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    private function seedAttachmentRow(int $attachmentId): void
    {
        $this->dbHandle->query(sprintf('DELETE FROM faqattachment WHERE id = %d', $attachmentId));
        $this->dbHandle->query(sprintf(
            "INSERT INTO faqattachment
                (id, record_id, record_lang, real_hash, virtual_hash, password_hash, filename, filesize, encrypted, mime_type)
             VALUES
                (%d, 1, 'en', '11111111111111111111111111111111', '22222222222222222222222222222222', NULL, 'manual.pdf', 16, 0, 'application/pdf')",
            $attachmentId,
        ));
    }
}
