<?php

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\AttachmentStorageType;
use phpMyFAQ\Language;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Class AttachmentFactoryTest
 *
 * @package phpMyFAQ\Attachment
 */
class AttachmentFactoryTest extends TestCase
{
    private Configuration $mockConfiguration;
    private DatabaseDriver $mockDb;

    protected function setUp(): void
    {
        // Reset static state before each test
        $this->resetFactoryState();

        // Mock Configuration and Database
        $this->mockConfiguration = $this->createMock(Configuration::class);
        $this->mockDb = $this->createMock(DatabaseDriver::class);
        $this->mockConfiguration->method('getDb')->willReturn($this->mockDb);

        // Set default Language for tests
        Language::$language = 'en';
    }

    protected function tearDown(): void
    {
        // Reset static state after each test
        $this->resetFactoryState();
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

        $attachment = AttachmentFactory::create(123, 'testkey');

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
        AttachmentFactory::init('default_secret', true);

        $attachment = AttachmentFactory::create(123, 'custom_key');

        $this->assertInstanceOf(File::class, $attachment);
    }

    public function testCreateWithEncryptionEnabledUsingDefaultKey(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        // Initialize factory with encryption enabled
        AttachmentFactory::init('default_secret', true);

        // Call create without a custom key - should use the default key
        $attachment = AttachmentFactory::create(123);

        $this->assertInstanceOf(File::class, $attachment);
    }

    public function testCreateWithEncryptionDisabled(): void
    {
        $this->setStorageType(AttachmentStorageType::FILESYSTEM->value);

        // Initialize factory with encryption disabled
        AttachmentFactory::init('default_secret', false);

        $attachment = AttachmentFactory::create(123, 'ignored_key');

        $this->assertInstanceOf(File::class, $attachment);
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

        $this->mockDb->expects($this->once())
            ->method('query')
            ->willReturnCallback(function($query) use ($expectedQueryPattern) {
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

    public function testInitSecondTimeDoesNotOverwrite(): void
    {
        // First initialization
        AttachmentFactory::init('secret123', true);

        // Second initialization with different values
        AttachmentFactory::init('different_secret', false);

        // Verify that original values are preserved
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

        $this->mockDb->expects($this->once())
            ->method('query')
            ->willReturnCallback(function($query) {
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
        AttachmentFactory::init('integration_key', true);

        // Create attachment - should use default key when none provided
        $attachment1 = AttachmentFactory::create(111);
        $this->assertInstanceOf(File::class, $attachment1);

        // Create attachment with custom key
        $attachment2 = AttachmentFactory::create(222, 'custom_key');
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
}
