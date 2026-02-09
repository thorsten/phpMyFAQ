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
        fwrite($stream, 'stream content');
        rewind($stream);

        $this->assertTrue($storage->putStream('stream/file.txt', $stream));
        $this->assertSame('stream content', $storage->get('stream/file.txt'));

        fclose($stream);
    }

    public function testPutStreamThrowsForInvalidStream(): void
    {
        $storage = new FilesystemStorage($this->tmpDir);

        $this->expectException(StorageException::class);
        $storage->putStream('invalid/file.txt', 'not-a-stream');
    }

    public function testInvalidPathThrowsException(): void
    {
        $storage = new FilesystemStorage($this->tmpDir);

        $this->expectException(StorageException::class);
        $storage->put('../escape.txt', 'invalid');
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
