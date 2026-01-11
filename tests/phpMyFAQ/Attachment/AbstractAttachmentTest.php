<?php

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Class AbstractAttachmentTest
 *
 * @package phpMyFAQ\Attachment
 */
#[AllowMockObjectsWithoutExpectations]
class AbstractAttachmentTest extends TestCase
{
    private AbstractAttachment $attachment;
    private DatabaseDriver $mockDb;

    protected function setUp(): void
    {
        // Mock the DatabaseDriver
        $this->mockDb = $this->createMock(DatabaseDriver::class);

        // Create an anonymous class extending AbstractAttachment for testing
        $this->attachment = new class($this->mockDb) extends AbstractAttachment {
            public function __construct(DatabaseDriver $mockDb, mixed $attachmentId = null)
            {
                $this->databaseDriver = $mockDb;
                if (null !== $attachmentId) {
                    $this->id = $attachmentId;
                    $this->getMeta();
                }
            }

            // Make protected methods public for testing
            public function testGetMeta(): bool
            {
                return $this->getMeta();
            }

            public function testReadMimeType(): string
            {
                return $this->readMimeType();
            }

            public function testMkVirtualHash(): ?string
            {
                return $this->mkVirtualHash();
            }

            public function testLinkedRecords(): bool
            {
                return $this->linkedRecords();
            }

            public function testPostUpdateMeta(): void
            {
                $this->postUpdateMeta();
            }
        };
    }

    public function testConstructorWithoutAttachmentId(): void
    {
        $attachment = new class($this->mockDb) extends AbstractAttachment {
            public function __construct(DatabaseDriver $mockDb)
            {
                $this->databaseDriver = $mockDb;
            }
        };

        $this->assertEquals(0, $attachment->getId());
    }

    public function testConstructorWithAttachmentId(): void
    {
        // Mock successful getMeta call
        $mockResult = [
            'record_id' => 123,
            'record_lang' => 'en',
            'real_hash' => 'abc123',
            'virtual_hash' => 'def456',
            'password_hash' => 'pwd789',
            'filename' => 'test.pdf',
            'filesize' => 1024,
            'encrypted' => '1',
            'mime_type' => 'application/pdf',
        ];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn($mockResult);

        $attachment = new class($this->mockDb, 5) extends AbstractAttachment {
            public function __construct(DatabaseDriver $mockDb, mixed $attachmentId = null)
            {
                $this->databaseDriver = $mockDb;
                if (null !== $attachmentId) {
                    $this->id = $attachmentId;
                    $this->getMeta();
                }
            }
        };

        $this->assertEquals(5, $attachment->getId());
        $this->assertEquals(123, $attachment->getRecordId());
        $this->assertEquals('test.pdf', $attachment->getFilename());
        $this->assertEquals(1024, $attachment->getFilesize());
        $this->assertEquals('application/pdf', $attachment->getMimeType());
        $this->assertEquals('abc123', $attachment->getRealHash());
    }

    public function testGetMetaSuccessful(): void
    {
        $mockResult = [
            'record_id' => 456,
            'record_lang' => 'de',
            'real_hash' => 'hash123',
            'virtual_hash' => 'vhash456',
            'password_hash' => 'phash789',
            'filename' => 'document.docx',
            'filesize' => 2048,
            'encrypted' => '0',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn($mockResult);

        $result = $this->attachment->testGetMeta();

        $this->assertTrue($result);
        $this->assertEquals(456, $this->attachment->getRecordId());
        $this->assertEquals('document.docx', $this->attachment->getFilename());
    }

    public function testGetMetaNoResult(): void
    {
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn(null);

        $result = $this->attachment->testGetMeta();

        $this->assertFalse($result);
    }

    public function testGetMetaEmptyResult(): void
    {
        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn([]);

        $result = $this->attachment->testGetMeta();

        $this->assertFalse($result);
    }

    public function testGetMetaQueryFailure(): void
    {
        $this->mockDb->method('query')->willReturn(false);

        $result = $this->attachment->testGetMeta();

        $this->assertFalse($result);
    }

    public function testBuildUrl(): void
    {
        $reflection = new ReflectionClass($this->attachment);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->attachment, 42);

        $url = $this->attachment->buildUrl();

        $this->assertEquals('attachment/42', $url);
    }

    public function testSetAndGetId(): void
    {
        $this->attachment->setId(99);
        $this->assertEquals(99, $this->attachment->getId());
    }

    public function testSetAndGetRecordId(): void
    {
        $this->attachment->setRecordId(777);
        $this->assertEquals(777, $this->attachment->getRecordId());
    }

    public function testGetRecordIdWhenNull(): void
    {
        // Default should return 0 when recordId is null
        $this->assertEquals(0, $this->attachment->getRecordId());
    }

    public function testSetRecordLang(): void
    {
        $this->attachment->setRecordLang('fr');

        $reflection = new ReflectionClass($this->attachment);
        $property = $reflection->getProperty('recordLang');

        $this->assertEquals('fr', $property->getValue($this->attachment));
    }

    public function testSetKeyWithDefaultEncryption(): void
    {
        $this->attachment->setKey('secret123', true);

        $reflection = new ReflectionClass($this->attachment);
        $keyProperty = $reflection->getProperty('key');
        $encryptedProperty = $reflection->getProperty('encrypted');
        $passwordHashProperty = $reflection->getProperty('passwordHash');

        $this->assertEquals('secret123', $keyProperty->getValue($this->attachment));
        $this->assertTrue($encryptedProperty->getValue($this->attachment));
        $this->assertEquals('', $passwordHashProperty->getValue($this->attachment));
    }

    public function testSetKeyWithCustomEncryption(): void
    {
        $this->attachment->setKey('custom456', false);

        $reflection = new ReflectionClass($this->attachment);
        $keyProperty = $reflection->getProperty('key');
        $encryptedProperty = $reflection->getProperty('encrypted');
        $passwordHashProperty = $reflection->getProperty('passwordHash');

        $this->assertEquals('custom456', $keyProperty->getValue($this->attachment));
        $this->assertTrue($encryptedProperty->getValue($this->attachment));
        $this->assertEquals(sha1('custom456'), $passwordHashProperty->getValue($this->attachment));
    }

    public function testSetKeyWithNull(): void
    {
        $this->attachment->setKey(null, true);

        $reflection = new ReflectionClass($this->attachment);
        $keyProperty = $reflection->getProperty('key');
        $encryptedProperty = $reflection->getProperty('encrypted');

        $this->assertNull($keyProperty->getValue($this->attachment));
        $this->assertFalse($encryptedProperty->getValue($this->attachment));
    }

    /**
     * @throws \ReflectionException
     */ public function testSaveMetaNewAttachment(): void
    {
        // Setup properties
        $reflection = new ReflectionClass($this->attachment);
        $properties = [
            'recordId' => 123,
            'recordLang' => 'en',
            'realHash' => 'real123',
            'virtualHash' => 'virtual456',
            'passwordHash' => 'pwd789',
            'filename' => 'test.pdf',
            'filesize' => 1024,
            'encrypted' => true,
            'mimeType' => 'application/pdf',
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setValue($this->attachment, $value);
        }

        // Set id property to null to simulate a new attachment
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($this->attachment, null);

        // Mock database operations
        $this->mockDb->method('nextId')->willReturn(42);
        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb->method('query')->willReturn(true);

        $savedId = $this->attachment->saveMeta();

        $this->assertEquals(42, $savedId);
        $this->assertEquals(42, $this->attachment->getId());
    }

    public function testReadMimeType(): void
    {
        $reflection = new ReflectionClass($this->attachment);
        $filenameProperty = $reflection->getProperty('filename');
        $filenameProperty->setValue($this->attachment, 'document.pdf');

        $mimeType = $this->attachment->testReadMimeType();

        $this->assertIsString($mimeType);
        $this->assertEquals($mimeType, $this->attachment->getMimeType());
    }

    public function testMkVirtualHashUnencrypted(): void
    {
        $reflection = new ReflectionClass($this->attachment);

        $realHashProperty = $reflection->getProperty('realHash');
        $realHashProperty->setValue($this->attachment, 'real_hash_123');

        $encryptedProperty = $reflection->getProperty('encrypted');
        $encryptedProperty->setValue($this->attachment, false);

        $virtualHash = $this->attachment->testMkVirtualHash();

        $this->assertEquals('real_hash_123', $virtualHash);
    }

    public function testMkVirtualHashEncrypted(): void
    {
        $reflection = new ReflectionClass($this->attachment);

        $properties = [
            'id' => 1,
            'recordId' => 123,
            'realHash' => 'real123',
            'filename' => 'test.pdf',
            'key' => 'secret',
            'encrypted' => true,
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setValue($this->attachment, $value);
        }

        $virtualHash = $this->attachment->testMkVirtualHash();
        $expectedHash = md5('1123real123test.pdfsecret');

        $this->assertEquals($expectedHash, $virtualHash);
    }

    public function testMkVirtualHashEncryptedMissingData(): void
    {
        $reflection = new ReflectionClass($this->attachment);
        $encryptedProperty = $reflection->getProperty('encrypted');
        $encryptedProperty->setValue($this->attachment, true);

        $this->expectException(AttachmentException::class);
        $this->expectExceptionMessage(
            'All of id, recordId, hash, filename, key is needed to generate fs hash for encrypted files',
        );

        $this->attachment->testMkVirtualHash();
    }

    public function testLinkedRecordsSingleRecord(): void
    {
        $mockResult = ['count' => 1];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn($mockResult);
        $this->mockDb->method('escape')->willReturnArgument(0);

        $result = $this->attachment->testLinkedRecords();

        $this->assertFalse($result);
    }

    public function testLinkedRecordsMultipleRecords(): void
    {
        $mockResult = ['count' => 3];

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn($mockResult);
        $this->mockDb->method('escape')->willReturnArgument(0);

        $result = $this->attachment->testLinkedRecords();

        $this->assertTrue($result);
    }

    public function testLinkedRecordsQueryFailure(): void
    {
        $this->mockDb->method('query')->willReturn(false);
        $this->mockDb->method('escape')->willReturnArgument(0);
        // Return array with count=0 to avoid the "Undefined array key" warning
        // This simulates what a real COUNT query would return even on failure
        $this->mockDb->method('fetchArray')->willReturn(['count' => 0]);

        $result = $this->attachment->testLinkedRecords();

        $this->assertFalse($result);
    }

    public function testDeleteMeta(): void
    {
        $this->attachment->setId(99);

        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM'))
            ->willReturn(true);

        $this->attachment->deleteMeta();

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function testPostUpdateMeta(): void
    {
        // Setup properties
        $reflection = new ReflectionClass($this->attachment);
        $properties = [
            'id' => 42,
            'virtualHash' => 'virtual123',
            'filename' => 'test.pdf',
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setValue($this->attachment, $value);
        }

        $this->mockDb->method('escape')->willReturnArgument(0);
        $this->mockDb
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('UPDATE'))
            ->willReturn(true);

        $this->attachment->testPostUpdateMeta();

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function testGettersWithDefaultValues(): void
    {
        // Initialize properties to avoid typed property errors
        $reflection = new ReflectionClass($this->attachment);

        $filenameProperty = $reflection->getProperty('filename');
        $filenameProperty->setValue($this->attachment, '');

        $mimeTypeProperty = $reflection->getProperty('mimeType');
        $mimeTypeProperty->setValue($this->attachment, '');

        $filesizeProperty = $reflection->getProperty('filesize');
        $filesizeProperty->setValue($this->attachment, 0);

        $realHashProperty = $reflection->getProperty('realHash');
        $realHashProperty->setValue($this->attachment, '');

        // Test getters when properties are initialized with default values
        $this->assertEquals('', $this->attachment->getFilename());
        $this->assertEquals('', $this->attachment->getMimeType());
        $this->assertEquals(0, $this->attachment->getFilesize());
        $this->assertEquals('', $this->attachment->getRealHash());
    }
}
