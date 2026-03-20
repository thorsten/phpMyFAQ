<?php

namespace phpMyFAQ\Attachment;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use phpMyFAQ\Attachment\Filesystem\AbstractFile as FilesystemFile;
use phpMyFAQ\Attachment\Filesystem\File\EncryptedFile;
use phpMyFAQ\Attachment\Filesystem\File\VanillaFile;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class FileTest
 *
 * @package phpMyFAQ\Attachment
 */
#[AllowMockObjectsWithoutExpectations]
class FileTest extends TestCase
{
    private File $file;
    private DatabaseDriver $mockDb;
    private vfsStreamDirectory $vfsRoot;

    protected function setUp(): void
    {
        if (!defined('PMF_ATTACHMENTS_DIR')) {
            define('PMF_ATTACHMENTS_DIR', '/tmp/attachments');
        }

        // Setup virtual file system
        $this->vfsRoot = vfsStream::setup('attachments', 0777);

        // Mock database driver
        $this->mockDb = $this->createStub(DatabaseDriver::class);

        // Create File instance with mocked database
        $this->file = new class($this->mockDb) extends File {
            public function __construct(DatabaseDriver $mockDb, mixed $attachmentId = null)
            {
                $this->databaseDriver = $mockDb;
                if (null !== $attachmentId) {
                    $this->id = $attachmentId;
                    $this->getMeta();
                }
            }

            // Make protected/private methods public for testing
            public function testBuildFilePath(): string
            {
                return $this->buildFilePath();
            }

            public function testMkVirtualHash(): ?string
            {
                return $this->mkVirtualHash();
            }

            public function testLinkedRecords(): bool
            {
                return $this->linkedRecords();
            }

            public function testGetFile(string $mode = FilesystemFile::MODE_READ): EncryptedFile|VanillaFile
            {
                // Use reflection to call a private method
                $reflection = new ReflectionClass($this);
                $method = $reflection->getMethod('getFile');
                return $method->invoke($this, $mode);
            }
        };
    }

    public function testBuildFilePathWithValidHash(): void
    {
        // Set up properties for hash generation
        $reflection = new ReflectionClass($this->file);

        $properties = [
            'encrypted' => false,
            'realHash' => 'abcdefghijklmnopqrstuvwxyz123456',
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setValue($this->file, $value);
        }

        // Mock PMF_ATTACHMENTS_DIR constant
        if (!defined('PMF_ATTACHMENTS_DIR')) {
            define('PMF_ATTACHMENTS_DIR', '/tmp/attachments');
        }

        $filePath = $this->file->testBuildFilePath();

        // Should create path with 3 subdirectories of 5 characters each
        $expectedPattern =
            '/tmp/attachments'
            . DIRECTORY_SEPARATOR
            . 'abcde'
            . DIRECTORY_SEPARATOR
            . 'fghij'
            . DIRECTORY_SEPARATOR
            . 'klmno'
            . DIRECTORY_SEPARATOR
            . 'pqrstuvwxyz123456';

        $this->assertEquals($expectedPattern, $filePath);
    }

    public function testCreateSubDirsSuccessful(): void
    {
        $testPath = vfsStream::url('attachments/test/deep/directory/file.txt');

        $result = $this->file->createSubDirs($testPath);

        $this->assertTrue($result);
        $this->assertTrue($this->vfsRoot->hasChild('test/deep/directory'));
    }

    public function testCreateSubDirsAlreadyExists(): void
    {
        // Create directory structure first
        vfsStream::create([
            'existing' => [
                'directory' => [],
            ],
        ], $this->vfsRoot);

        $testPath = vfsStream::url('attachments/existing/directory/file.txt');

        $result = $this->file->createSubDirs($testPath);

        $this->assertTrue($result);
    }

    public function testSaveWithNonExistentFile(): void
    {
        $nonExistentPath = vfsStream::url('attachments/nonexistent.txt');

        $result = $this->file->save($nonExistentPath);

        $this->assertFalse($result);
    }

    public function testDeleteWithLinkedRecords(): void
    {
        // Setup file properties
        $reflection = new ReflectionClass($this->file);
        $properties = [
            'encrypted' => false,
            'realHash' => 'testhash12345',
            'id' => 1,
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setValue($this->file, $value);
        }

        // Mock database to return linked records exist
        $this->mockDb->method('fetchArray')->willReturn(['count' => 2]);
        $this->mockDb->method('query')->willReturn(true);

        $result = $this->file->delete();

        $this->assertTrue($result);
    }

    public function testBuildFilePathWithLongHash(): void
    {
        // Test with a very long hash
        $reflection = new ReflectionClass($this->file);
        $properties = [
            'encrypted' => false,
            'realHash' => 'verylonghashstringwithmorethan30charactersinthehashvalue123456789',
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setValue($this->file, $value);
        }

        // Mock PMF_ATTACHMENTS_DIR constant
        if (!defined('PMF_ATTACHMENTS_DIR')) {
            define('PMF_ATTACHMENTS_DIR', '/tmp/attachments');
        }

        $filePath = $this->file->testBuildFilePath();

        // Should create proper subdirectories even with long hash
        $expectedPattern =
            '/tmp/attachments'
            . DIRECTORY_SEPARATOR
            . 'veryl'
            . DIRECTORY_SEPARATOR
            . 'ongha'
            . DIRECTORY_SEPARATOR
            . 'shstr'
            . DIRECTORY_SEPARATOR
            . 'ingwithmorethan30charactersinthehashvalue123456789';

        $this->assertEquals($expectedPattern, $filePath);
    }

    public function testMkVirtualHashMethod(): void
    {
        // Setup file properties
        $reflection = new ReflectionClass($this->file);
        $properties = [
            'encrypted' => false,
            'realHash' => 'test123456789',
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setValue($this->file, $value);
        }

        $hash = $this->file->testMkVirtualHash();

        $this->assertNotNull($hash);
        $this->assertIsString($hash);
    }

    public function testBuildStoragePath(): void
    {
        $reflection = new ReflectionClass($this->file);
        $properties = [
            'encrypted' => false,
            'realHash' => 'abcdefghijklmnopqrstuvwxyz123456',
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setValue($this->file, $value);
        }

        $method = $reflection->getMethod('buildStoragePath');
        $storagePath = $method->invoke($this->file);

        $this->assertEquals('abcde/fghij/klmno/pqrstuvwxyz123456', $storagePath);
    }

    public function testIsStorageOkReturnsTrueForCloudStorage(): void
    {
        $mockConfig = $this->createMock(Configuration::class);
        $mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'storage.type' => 's3',
                default => null,
            });

        $configRef = new ReflectionClass(Configuration::class);
        $configProp = $configRef->getProperty('configuration');
        $previousConfig = $configProp->getValue(null);
        $configProp->setValue(null, $mockConfig);

        try {
            $reflection = new ReflectionClass($this->file);
            $reflection->getProperty('encrypted')->setValue($this->file, false);
            $reflection->getProperty('realHash')->setValue($this->file, 'abcdefghijklmnopqrstuvwxyz123456');

            self::assertTrue($this->file->isStorageOk());
        } finally {
            $configProp->setValue(null, $previousConfig);
        }
    }

    public function testIsStorageOkReturnsFalseWhenDirMissing(): void
    {
        $mockConfig = $this->createMock(Configuration::class);
        $mockConfig->method('get')->willReturn('filesystem');

        $configRef = new ReflectionClass(Configuration::class);
        $configProp = $configRef->getProperty('configuration');
        $previousConfig = $configProp->getValue(null);
        $configProp->setValue(null, $mockConfig);

        try {
            $reflection = new ReflectionClass($this->file);
            $reflection->getProperty('encrypted')->setValue($this->file, false);
            $reflection->getProperty('realHash')->setValue($this->file, 'missing-storage-hash-987654321');

            self::assertFalse($this->file->isStorageOk());
        } finally {
            $configProp->setValue(null, $previousConfig);
        }
    }

    public function testDeleteWithNoLinkedRecordsUnencrypted(): void
    {
        $mockStorage = $this->createMock(StorageInterface::class);
        $mockStorage->method('delete')->willReturn(true);

        $mockConfig = $this->createMock(Configuration::class);
        $mockConfig->method('get')->willReturn('filesystem');

        $configRef = new ReflectionClass(Configuration::class);
        $configProp = $configRef->getProperty('configuration');
        $previousConfig = $configProp->getValue(null);
        $configProp->setValue(null, $mockConfig);

        try {
            $reflection = new ReflectionClass($this->file);
            $reflection->getProperty('encrypted')->setValue($this->file, false);
            $reflection->getProperty('realHash')->setValue($this->file, 'abcdefghijklmnopqrstuvwxyz123456');
            $reflection->getProperty('id')->setValue($this->file, 1);

            $storageProp = new ReflectionClass(File::class)->getProperty('storage');
            $storageProp->setValue($this->file, $mockStorage);

            $this->mockDb->method('fetchArray')->willReturn(['count' => 0]);
            $this->mockDb->method('query')->willReturn(true);

            $result = $this->file->delete();

            self::assertTrue($result);
        } finally {
            $configProp->setValue(null, $previousConfig);
        }
    }

    public function testSaveWithCustomFilename(): void
    {
        $testContent = 'save test content';
        $filePath = vfsStream::url('attachments/upload.txt');
        file_put_contents($filePath, $testContent);

        $reflection = new ReflectionClass($this->file);
        $reflection->getProperty('encrypted')->setValue($this->file, false);

        $mockStorage = $this->createMock(StorageInterface::class);
        $mockStorage->method('put')->willReturn(true);

        $storageProp = new ReflectionClass(File::class)->getProperty('storage');
        $storageProp->setValue($this->file, $mockStorage);

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn(['count' => 0]);

        $result = $this->file->save($filePath, 'custom-name.txt');

        self::assertTrue($result);
        self::assertSame('custom-name.txt', $reflection->getProperty('filename')->getValue($this->file));
    }

    public function testSaveUsesBasenameWhenNoCustomFilename(): void
    {
        $filePath = vfsStream::url('attachments/original.txt');
        file_put_contents($filePath, 'content');

        $reflection = new ReflectionClass($this->file);
        $reflection->getProperty('encrypted')->setValue($this->file, false);

        $mockStorage = $this->createMock(StorageInterface::class);
        $mockStorage->method('put')->willReturn(true);
        $storageProp = new ReflectionClass(File::class)->getProperty('storage');
        $storageProp->setValue($this->file, $mockStorage);

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn(['count' => 0]);

        $result = $this->file->save($filePath);

        self::assertTrue($result);
        self::assertSame('original.txt', $reflection->getProperty('filename')->getValue($this->file));
    }

    public function testGetRetrievesContentFromStorage(): void
    {
        $mockStorage = $this->createMock(StorageInterface::class);
        $mockStorage->method('get')->willReturn('stored content');

        $mockConfig = $this->createMock(Configuration::class);
        $mockConfig->method('get')->willReturn('filesystem');

        $configRef = new ReflectionClass(Configuration::class);
        $configProp = $configRef->getProperty('configuration');
        $previousConfig = $configProp->getValue(null);
        $configProp->setValue(null, $mockConfig);

        try {
            $reflection = new ReflectionClass($this->file);
            $reflection->getProperty('encrypted')->setValue($this->file, false);
            $reflection->getProperty('realHash')->setValue($this->file, 'abcdefghijklmnopqrstuvwxyz123456');

            $storageProp = new ReflectionClass(File::class)->getProperty('storage');
            $storageProp->setValue($this->file, $mockStorage);

            $content = $this->file->get();

            self::assertSame('stored content', $content);
        } finally {
            $configProp->setValue(null, $previousConfig);
        }
    }

    public function testRawOutOutputsUnencryptedStorageContent(): void
    {
        $mockStorage = $this->createMock(StorageInterface::class);
        $mockStorage->method('get')->willReturn('raw attachment content');

        $mockConfig = $this->createMock(Configuration::class);
        $mockConfig->method('get')->willReturn('filesystem');

        $configRef = new ReflectionClass(Configuration::class);
        $configProp = $configRef->getProperty('configuration');
        $previousConfig = $configProp->getValue(null);
        $configProp->setValue(null, $mockConfig);

        try {
            $reflection = new ReflectionClass($this->file);
            $reflection->getProperty('encrypted')->setValue($this->file, false);
            $reflection->getProperty('realHash')->setValue($this->file, 'abcdefghijklmnopqrstuvwxyz123456');

            $storageProp = new ReflectionClass(File::class)->getProperty('storage');
            $storageProp->setValue($this->file, $mockStorage);

            ob_start();
            $this->file->rawOut();
            $output = ob_get_clean();

            self::assertSame('raw attachment content', $output);
        } finally {
            $configProp->setValue(null, $previousConfig);
        }
    }

    public function testGetStorageReturnsInjectedStorageInstance(): void
    {
        $mockStorage = $this->createMock(StorageInterface::class);
        $storageProp = new ReflectionClass(File::class)->getProperty('storage');
        $storageProp->setValue($this->file, $mockStorage);

        $method = new ReflectionMethod(File::class, 'getStorage');
        $storage = $method->invoke($this->file);

        self::assertSame($mockStorage, $storage);
    }

    public function testUsesCloudStorageReturnsFalseForFilesystemConfiguration(): void
    {
        $mockConfig = $this->createMock(Configuration::class);
        $mockConfig->expects($this->once())->method('get')->with('storage.type')->willReturn('filesystem');

        $configRef = new ReflectionClass(Configuration::class);
        $configProp = $configRef->getProperty('configuration');
        $previousConfig = $configProp->getValue(null);
        $configProp->setValue(null, $mockConfig);

        try {
            $method = new ReflectionMethod(File::class, 'usesCloudStorage');
            $result = $method->invoke($this->file);

            self::assertFalse($result);
        } finally {
            $configProp->setValue(null, $previousConfig);
        }
    }

    public function testUsesCloudStorageReturnsTrueForS3Configuration(): void
    {
        $mockConfig = $this->createMock(Configuration::class);
        $mockConfig->expects($this->once())->method('get')->with('storage.type')->willReturn('S3');

        $configRef = new ReflectionClass(Configuration::class);
        $configProp = $configRef->getProperty('configuration');
        $previousConfig = $configProp->getValue(null);
        $configProp->setValue(null, $mockConfig);

        try {
            $method = new ReflectionMethod(File::class, 'usesCloudStorage');
            $result = $method->invoke($this->file);

            self::assertTrue($result);
        } finally {
            $configProp->setValue(null, $previousConfig);
        }
    }

    public function testGetFileReturnsVanillaFileForUnencryptedAttachment(): void
    {
        $reflection = new ReflectionClass($this->file);
        $reflection->getProperty('encrypted')->setValue($this->file, false);
        $reflection->getProperty('realHash')->setValue($this->file, 'abcdefghijklmnopqrstuvwxyz123456');

        $path = $this->file->testBuildFilePath();
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'vanilla');

        $file = $this->file->testGetFile();

        self::assertInstanceOf(VanillaFile::class, $file);
    }

    public function testGetFileReturnsEncryptedFileForEncryptedAttachment(): void
    {
        $reflection = new ReflectionClass($this->file);
        $reflection->getProperty('encrypted')->setValue($this->file, true);
        $reflection->getProperty('id')->setValue($this->file, 1);
        $reflection->getProperty('recordId')->setValue($this->file, 99);
        $reflection->getProperty('realHash')->setValue($this->file, 'abcdefghijklmnopqrstuvwxyz123456');
        $reflection->getProperty('filename')->setValue($this->file, 'encrypted.txt');
        $reflection->getProperty('key')->setValue($this->file, '12345678901234567890123456789012');

        $path = $this->file->testBuildFilePath();
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'encrypted');

        $file = $this->file->testGetFile(FilesystemFile::MODE_WRITE);

        self::assertInstanceOf(EncryptedFile::class, $file);
    }

    public function testSaveWithLinkedRecordsSkipsFileWrite(): void
    {
        $filePath = vfsStream::url('attachments/linked.txt');
        file_put_contents($filePath, 'linked content');

        $reflection = new ReflectionClass($this->file);
        $reflection->getProperty('encrypted')->setValue($this->file, false);
        $reflection->getProperty('realHash')->setValue($this->file, 'abcdefghijklmnopqrstuvwxyz123456');

        $this->mockDb->method('query')->willReturn(true);
        $this->mockDb->method('fetchArray')->willReturn(['count' => 2]);

        $result = $this->file->save($filePath);

        self::assertTrue($result);
    }
}
