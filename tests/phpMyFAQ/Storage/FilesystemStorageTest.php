<?php

namespace phpMyFAQ\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FilesystemStorage::class)]
class FilesystemStorageTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/phpmyfaq-storage-' . uniqid('', true);
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->tmpDir);
    }

    public function testPutGetExistsSizeDeleteAndUrl(): void
    {
        $storage = new FilesystemStorage($this->tmpDir, 'https://cdn.example.com/storage');

        $this->assertTrue($storage->put('foo/bar.txt', 'hello world'));
        $this->assertTrue($storage->exists('foo/bar.txt'));
        $this->assertSame('hello world', $storage->get('foo/bar.txt'));
        $this->assertSame(strlen('hello world'), $storage->size('foo/bar.txt'));
        $this->assertSame('https://cdn.example.com/storage/foo/bar.txt', $storage->url('foo/bar.txt'));
        $this->assertTrue($storage->delete('foo/bar.txt'));
        $this->assertFalse($storage->exists('foo/bar.txt'));
    }

    public function testPutStreamWritesContents(): void
    {
        $storage = new FilesystemStorage($this->tmpDir);
        $stream = fopen('php://memory', 'rb+');
        $this->assertIsResource($stream);

        try {
            fwrite($stream, 'stream content');
            rewind($stream);

            $this->assertTrue($storage->putStream('stream/file.txt', $stream));
            $this->assertSame('stream content', $storage->get('stream/file.txt'));
        } finally {
            fclose($stream);
        }
    }

    public function testPutStreamThrowsForInvalidStream(): void
    {
        $storage = new FilesystemStorage($this->tmpDir);

        $this->expectException(StorageException::class);
        $storage->putStream('invalid/file.txt', 'not-a-stream');
    }

    public function testUrlAlwaysUsesForwardSlashes(): void
    {
        $storage = new FilesystemStorage($this->tmpDir, 'https://cdn.example.com/files');

        // Even with backslashes in the input path, the URL should use forward slashes
        $this->assertSame(
            'https://cdn.example.com/files/attachments/123/file.pdf',
            $storage->url('attachments\\123\\file.pdf'),
        );
    }

    public function testUrlWithoutBaseUrlUsesForwardSlashes(): void
    {
        $storage = new FilesystemStorage($this->tmpDir);

        $url = $storage->url('sub/dir/file.txt');

        $this->assertStringNotContainsString('\\', $url);
        $this->assertStringContainsString('sub/dir/file.txt', $url);
    }

    public function testUrlWithTrailingSlashInBaseUrl(): void
    {
        $storage = new FilesystemStorage($this->tmpDir, 'https://cdn.example.com/storage/');

        $this->assertSame('https://cdn.example.com/storage/foo/bar.txt', $storage->url('foo/bar.txt'));
    }

    public function testConstructorTrimsTrailingSlashes(): void
    {
        // Forward slash trailing
        $storage = new FilesystemStorage($this->tmpDir . '/', 'https://cdn.example.com');
        $this->assertTrue($storage->put('trim-test.txt', 'ok'));
        $this->assertSame('ok', $storage->get('trim-test.txt'));
    }

    public function testDoubleDotInFilenameIsAllowed(): void
    {
        $storage = new FilesystemStorage($this->tmpDir);

        $this->assertTrue($storage->put('backups/file..backup.txt', 'data'));
        $this->assertSame('data', $storage->get('backups/file..backup.txt'));
    }

    public function testTraversalPathThrowsException(): void
    {
        $storage = new FilesystemStorage($this->tmpDir);

        $this->expectException(StorageException::class);
        $storage->put('../escape.txt', 'invalid');
    }

    public function testEmptySegmentPathThrowsException(): void
    {
        $storage = new FilesystemStorage($this->tmpDir);

        $this->expectException(StorageException::class);
        $storage->put('foo//bar.txt', 'invalid');
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
