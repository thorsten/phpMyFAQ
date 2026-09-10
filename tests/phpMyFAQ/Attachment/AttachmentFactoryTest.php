<?php

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\AttachmentStorageType;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Class AttachmentFactoryTest
 *
 * @package phpMyFAQ\Attachment
 */
#[AllowMockObjectsWithoutExpectations]
class AttachmentFactoryTest extends TestCase
{
    private const string KEY_16 = '0123456789abcdef';
    private const string KEY_32 = '0123456789abcdef0123456789abcdef';

    private Configuration $mockConfiguration;
    private DatabaseDriver $mockDb;

    /** @var int[] Attachment rows inserted into the test database */
    private array $insertedRows = [];

    protected function setUp(): void
    {
        // Reset static state before each test
        $this->resetFactoryState();

        // Mock Configuration and Database
        $this->mockConfiguration = $this->createStub(Configuration::class);
        $this->mockDb = $this->createMock(DatabaseDriver::class);
        $this->mockConfiguration->method('getDb')->willReturn($this->mockDb);

        // Set default Language for tests
        Language::$language = 'en';
    }

    protected function tearDown(): void
    {
        // Reset static state after each test
        $this->resetFactoryState();

        foreach ($this->insertedRows as $id) {
            Database::getInstance()->query(sprintf(
                'DELETE FROM %sfaqattachment WHERE id = %d',
                Database::getTablePrefix(),
                $id,
            ));
        }

        $this->insertedRows = [];
    }

    /**
     * Inserts a persisted attachment row so create($id) loads real metadata.
     */
    private function insertAttachmentRow(int $id, bool $encrypted): void
    {
        Database::getInstance()->query(sprintf(
            'INSERT INTO %sfaqattachment (id, record_id, record_lang, real_hash, virtual_hash, filename, '
            . "filesize, encrypted, mime_type) VALUES (%d, 1, 'en', 'realhash', 'virtualhash', 'file.txt', "
            . "10, %d, 'text/plain')",
            Database::getTablePrefix(),
            $id,
            $encrypted ? 1 : 0,
        ));

        $this->insertedRows[] = $id;
    }

    private function getKey(File $file): ?string
    {
        $reflection = new ReflectionClass($file);

        return $reflection->getProperty('key')->getValue($file);
    }

    private function resetFactoryState(): void
    {
        $reflection = new ReflectionClass(AttachmentFactory::class);

        $defaultKeyProperty = $reflection->getProperty('defaultKey');
        $defaultKeyProperty->setValue(null, null);

        $storageTypeProperty = $reflection->getProperty('storageType');
        $storageTypeProperty->setValue(null, 0);

        $encryptionEnabledProperty = $reflection->getProperty('encryptionEnabled');
        $encryptionEnabledProperty->setValue(null, null);
    }

    private function setStorageType(int $storageType): void
    {
        $reflection = new ReflectionClass(AttachmentFactory::class);
        $storageTypeProperty = $reflection->getProperty('storageType');
        $storageTypeProperty->setValue(null, $storageType);
    }

    public function testCreateWithFilesystemStorageType(): void
    {
        // Set storage type to FILESYSTEM
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        $attachment = AttachmentFactory::create(123, self::KEY_16);

        $this->assertInstanceOf(File::class, $attachment);
    }

    public function testCreateWithoutAttachmentId(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        $attachment = AttachmentFactory::create();

        $this->assertInstanceOf(File::class, $attachment);
    }

    public function testCreateWithUnknownStorageType(): void
    {
        // Set an invalid storage type
        $this->setStorageType(999);

        $this->expectException(AttachmentException::class);
        $this->expectExceptionMessage('Unknown attachment storage type');

        AttachmentFactory::create(123);
    }

    public function testCreateWithEncryptionEnabled(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        // Initialize factory with encryption enabled
        AttachmentFactory::init(self::KEY_32, true);

        $attachment = AttachmentFactory::create(123, self::KEY_16);

        $this->assertInstanceOf(File::class, $attachment);
    }

    public function testCreateWithEncryptionEnabledUsingDefaultKey(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        // Initialize factory with encryption enabled
        AttachmentFactory::init(self::KEY_32, true);

        // Call create without a custom key - should use the default key
        $attachment = AttachmentFactory::create(123);

        $this->assertInstanceOf(File::class, $attachment);
        $this->assertTrue($attachment->isEncrypted());
        $this->assertSame(self::KEY_32, $this->getKey($attachment));
    }

    public function testCreateWithEncryptionDisabled(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        // Initialize factory with encryption disabled
        AttachmentFactory::init(self::KEY_32, false);

        $attachment = AttachmentFactory::create(123, 'ignored_key');

        $this->assertInstanceOf(File::class, $attachment);
        $this->assertFalse($attachment->isEncrypted());
        $this->assertNull($this->getKey($attachment));
    }

    public function testFetchByRecordIdWithResults(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        // Mock database results
        $mockResult1 = new stdClass();
        $mockResult1->id = 1;
        $mockResult2 = new stdClass();
        $mockResult2->id = 2;
        $mockResults = [$mockResult1, $mockResult2];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchAll')->willReturn($mockResults);

        $attachments = AttachmentFactory::fetchByRecordId($this->mockConfiguration, 123);

        $this->assertCount(2, $attachments);
        $this->assertInstanceOf(File::class, $attachments[0]);
        $this->assertInstanceOf(File::class, $attachments[1]);
    }

    public function testFetchByRecordIdWithNoResults(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchAll')->willReturn(null);

        $attachments = AttachmentFactory::fetchByRecordId($this->mockConfiguration, 123);

        $this->assertEmpty($attachments);
    }

    public function testFetchByRecordIdWithEmptyResults(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchAll')->willReturn([]);

        $attachments = AttachmentFactory::fetchByRecordId($this->mockConfiguration, 123);

        $this->assertEmpty($attachments);
    }

    public function testFetchByRecordIdQueryStructure(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);
        Language::$language = 'de';

        $expectedQueryPattern = "SELECT id FROM %sfaqattachment WHERE record_id = 456 AND record_lang = 'de'";

        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function ($query) use ($expectedQueryPattern) {
                $this->assertStringContainsString('SELECT id FROM', $query);
                $this->assertStringContainsString('faqattachment', $query);
                $this->assertStringContainsString('record_id = 456', $query);
                $this->assertStringContainsString("record_lang = 'de'", $query);
                return true;
            });

        $this->mockDb->method('fetchAll')->willReturn([]);

        AttachmentFactory::fetchByRecordId($this->mockConfiguration, 456);
    }

    public function testInitFirstTime(): void
    {
        AttachmentFactory::init('secret123', true);

        // Verify that static properties were set
        $reflection = new ReflectionClass(AttachmentFactory::class);

        $defaultKeyProperty = $reflection->getProperty('defaultKey');
        $this->assertEquals('secret123', $defaultKeyProperty->getValue());

        $encryptionEnabledProperty = $reflection->getProperty('encryptionEnabled');
        $this->assertTrue($encryptionEnabledProperty->getValue());
    }

    public function testInitSecondTimeAppliesCurrentSettings(): void
    {
        // First initialization, e.g. before encryption was configured
        AttachmentFactory::init('', false);

        // Second initialization with the current configuration values
        AttachmentFactory::init('secret123', true);

        // A long-running process must not keep a stale plaintext configuration
        $reflection = new ReflectionClass(AttachmentFactory::class);

        $defaultKeyProperty = $reflection->getProperty('defaultKey');
        $this->assertEquals('secret123', $defaultKeyProperty->getValue());

        $encryptionEnabledProperty = $reflection->getProperty('encryptionEnabled');
        $this->assertTrue($encryptionEnabledProperty->getValue());
    }

    public function testInitWithEncryptionDisabled(): void
    {
        AttachmentFactory::init('secret456', false);

        $reflection = new ReflectionClass(AttachmentFactory::class);

        $defaultKeyProperty = $reflection->getProperty('defaultKey');
        $this->assertEquals('secret456', $defaultKeyProperty->getValue());

        $encryptionEnabledProperty = $reflection->getProperty('encryptionEnabled');
        $this->assertFalse($encryptionEnabledProperty->getValue());
    }

    public function testFetchByRecordIdWithDifferentLanguages(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        // Test with French language
        Language::$language = 'fr';

        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function ($query) {
                $this->assertStringContainsString("record_lang = 'fr'", $query);
                return true;
            });

        $this->mockDb->method('fetchAll')->willReturn([]);

        AttachmentFactory::fetchByRecordId($this->mockConfiguration, 789);
    }

    public function testCreateIntegrationWithInit(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        // Initialize factory
        AttachmentFactory::init(self::KEY_32, true);

        // Create attachment - should use default key when none provided
        $attachment1 = AttachmentFactory::create(111);
        $this->assertInstanceOf(File::class, $attachment1);

        // Create attachment with custom key
        $attachment2 = AttachmentFactory::create(222, self::KEY_16);
        $this->assertInstanceOf(File::class, $attachment2);
    }

    public function testStaticStateIsolation(): void
    {
        // Ensure that each test starts with clean state
        $reflection = new ReflectionClass(AttachmentFactory::class);

        $defaultKeyProperty = $reflection->getProperty('defaultKey');
        $this->assertNull($defaultKeyProperty->getValue());

        $storageTypeProperty = $reflection->getProperty('storageType');
        $this->assertEquals(0, $storageTypeProperty->getValue());

        $encryptionEnabledProperty = $reflection->getProperty('encryptionEnabled');
        $this->assertNull($encryptionEnabledProperty->getValue());
    }

    public function testCreateNewAttachmentRejectsUnsupportedKeyLengthBeforeAnythingIsWritten(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);
        AttachmentFactory::init('123456789012345', true); // 15 bytes

        $this->expectException(AttachmentException::class);
        $this->expectExceptionMessage('not 16, 24 or 32 bytes long');

        AttachmentFactory::create();
    }

    public function testCreateNewAttachmentRejectsEmptyKeyWhenEncryptionIsEnabled(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);
        AttachmentFactory::init('', true);

        $this->expectException(AttachmentException::class);

        AttachmentFactory::create();
    }

    public function testCreateNewAttachmentWithEncryptionEnabledUsesKey(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);
        AttachmentFactory::init(self::KEY_32, true);

        $attachment = AttachmentFactory::create();

        $this->assertTrue($attachment->isEncrypted());
        $this->assertSame(self::KEY_32, $this->getKey($attachment));
    }

    public function testCreateNewAttachmentWithEncryptionDisabledIsPlaintext(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);
        AttachmentFactory::init(self::KEY_32, false);

        $attachment = AttachmentFactory::create();

        $this->assertFalse($attachment->isEncrypted());
        $this->assertNull($this->getKey($attachment));
    }

    public function testCreateExistingEncryptedAttachmentKeepsKeyWhenEncryptionIsDisabled(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);
        $this->insertAttachmentRow(9001, true);

        // Encryption switched off afterwards: the record must stay readable.
        AttachmentFactory::init(self::KEY_32, false);

        $attachment = AttachmentFactory::create(9001);

        $this->assertTrue($attachment->hasMeta());
        $this->assertTrue($attachment->isEncrypted(), 'The persisted flag decides how an existing record is read');
        $this->assertSame(self::KEY_32, $this->getKey($attachment), 'The configured key must remain available');
    }

    public function testCreateExistingPlaintextAttachmentIgnoresKeyWhenEncryptionIsEnabled(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);
        $this->insertAttachmentRow(9002, false);

        // Encryption switched on afterwards: the plaintext record must stay readable.
        AttachmentFactory::init(self::KEY_32, true);

        $attachment = AttachmentFactory::create(9002);

        $this->assertTrue($attachment->hasMeta());
        $this->assertFalse($attachment->isEncrypted(), 'A plaintext record must not be read as encrypted');
        $this->assertNull($this->getKey($attachment));
    }

    public function testCreateExistingEncryptedAttachmentDoesNotValidateKeyLength(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);
        $this->insertAttachmentRow(9003, true);

        // Listing attachments must not fail; the key is validated when the file is opened.
        AttachmentFactory::init('short', false);

        $attachment = AttachmentFactory::create(9003);

        $this->assertTrue($attachment->isEncrypted());
        $this->assertSame('short', $this->getKey($attachment));
    }

    public function testIsSupportedKey(): void
    {
        $this->assertTrue(AttachmentFactory::isSupportedKey(str_repeat('a', 16)));
        $this->assertTrue(AttachmentFactory::isSupportedKey(str_repeat('a', 24)));
        $this->assertTrue(AttachmentFactory::isSupportedKey(str_repeat('a', 32)));
        $this->assertFalse(AttachmentFactory::isSupportedKey(''));
        $this->assertFalse(AttachmentFactory::isSupportedKey(str_repeat('a', 15)));
        $this->assertFalse(AttachmentFactory::isSupportedKey(str_repeat('a', 33)));
    }

    public function testInitFromConfigurationAppliesCurrentSettings(): void
    {
        AttachmentFactory::init('', false);

        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['records.defaultAttachmentEncKey', self::KEY_16],
                ['records.enableAttachmentEncryption', true],
            ]);

        AttachmentFactory::initFromConfiguration($configuration);

        $reflection = new ReflectionClass(AttachmentFactory::class);
        $this->assertSame(self::KEY_16, $reflection->getProperty('defaultKey')->getValue());
        $this->assertTrue($reflection->getProperty('encryptionEnabled')->getValue());
    }
}
