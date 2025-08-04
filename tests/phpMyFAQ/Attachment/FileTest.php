<?php

namespace phpMyFAQ\Attachment;

use phpMyFAQ\Attachment\Filesystem\AbstractFile as FilesystemFile;
use phpMyFAQ\Attachment\Filesystem\File\EncryptedFile;
use phpMyFAQ\Attachment\Filesystem\File\VanillaFile;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Class FileTest
 *
 * @package phpMyFAQ\Attachment
 */
class FileTest extends TestCase
{
    private File $file;
    private DatabaseDriver $mockDb;
    private vfsStreamDirectory $vfsRoot;

    protected function setUp(): void
    {
        // Setup virtual file system
        $this->vfsRoot = vfsStream::setup('attachments', 0777);

        // Mock database driver
        $this->mockDb = $this->createMock(DatabaseDriver::class);

        // Create File instance with mocked database
        $this->file = new class($this->mockDb) extends File {
            public function __construct(DatabaseDriver $mockDb, mixed $attachmentId = null)
            {
                parent::__construct();
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
                // Use reflection to call private method
                $reflection = new \ReflectionClass($this);
                $method = $reflection->getMethod('getFile');
                $method->setAccessible(true);
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
            'realHash' => 'abcdefghijklmnopqrstuvwxyz123456'
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($this->file, $value);
        }

        // Mock PMF_ATTACHMENTS_DIR constant
        if (!defined('PMF_ATTACHMENTS_DIR')) {
            define('PMF_ATTACHMENTS_DIR', '/tmp/attachments');
        }

        $filePath = $this->file->testBuildFilePath();

        // Should create path with 3 subdirectories of 5 characters each
        $expectedPattern = '/tmp/attachments' . DIRECTORY_SEPARATOR . 'abcde' .
                          DIRECTORY_SEPARATOR . 'fghij' . DIRECTORY_SEPARATOR . 'klmno' .
                          DIRECTORY_SEPARATOR . 'pqrstuvwxyz123456';

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
                'directory' => []
            ]
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
            'id' => 1
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
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
            'realHash' => 'verylonghashstringwithmorethan30charactersinthehashvalue123456789'
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($this->file, $value);
        }

        // Mock PMF_ATTACHMENTS_DIR constant
        if (!defined('PMF_ATTACHMENTS_DIR')) {
            define('PMF_ATTACHMENTS_DIR', '/tmp/attachments');
        }

        $filePath = $this->file->testBuildFilePath();

        // Should create proper subdirectories even with long hash
        $expectedPattern = '/tmp/attachments' . DIRECTORY_SEPARATOR . 'veryl' .
                          DIRECTORY_SEPARATOR . 'ongha' . DIRECTORY_SEPARATOR . 'shstr' .
                          DIRECTORY_SEPARATOR . 'ingwithmorethan30charactersinthehashvalue123456789';

        $this->assertEquals($expectedPattern, $filePath);
    }

    public function testMkVirtualHashMethod(): void
    {
        // Setup file properties
        $reflection = new ReflectionClass($this->file);
        $properties = [
            'encrypted' => false,
            'realHash' => 'test123456789'
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($this->file, $value);
        }

        $hash = $this->file->testMkVirtualHash();

        $this->assertNotNull($hash);
        $this->assertIsString($hash);
    }
}
