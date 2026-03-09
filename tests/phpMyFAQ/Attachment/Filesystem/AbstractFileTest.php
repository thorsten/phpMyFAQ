<?php

declare(strict_types=1);

namespace phpMyFAQ\Attachment\Filesystem;

use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Attachment\Filesystem\File\VanillaFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractFile::class)]
#[CoversClass(VanillaFile::class)]
final class AbstractFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/pmf-abstract-file-test-' . bin2hex(random_bytes(4));
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    private function createTempFile(string $content = 'test content'): string
    {
        $path = $this->tempDir . '/testfile.bin';
        file_put_contents($path, $content);
        return $path;
    }

    public function testConstructorOpensFileHandle(): void
    {
        $path = $this->createTempFile();

        $file = new VanillaFile($path);

        self::assertTrue($file->isOk());
    }

    public function testConstructorThrowsOnInvalidPath(): void
    {
        $this->expectException(FileException::class);
        set_error_handler(static fn() => true);
        try {
            new VanillaFile($this->tempDir . '/nonexistent-file.bin');
        } finally {
            restore_error_handler();
        }
    }

    public function testEofReturnsFalseOnFreshFile(): void
    {
        $path = $this->createTempFile('hello');

        $file = new VanillaFile($path);

        self::assertFalse($file->eof());
    }

    public function testEofReturnsTrueAfterReadingAll(): void
    {
        $path = $this->createTempFile('hi');

        $file = new VanillaFile($path);
        $file->getChunk();
        $file->getChunk();

        self::assertTrue($file->eof());
    }

    public function testGetModeReturnsInitialMode(): void
    {
        $path = $this->createTempFile();

        $file = new VanillaFile($path);

        self::assertSame(AbstractFile::MODE_READ, $file->getMode());
    }

    public function testSetModeReopensFileInNewMode(): void
    {
        $path = $this->createTempFile();

        $file = new VanillaFile($path);
        self::assertSame(AbstractFile::MODE_READ, $file->getMode());

        $result = $file->setMode(AbstractFile::MODE_APPEND);

        self::assertTrue($result);
        self::assertTrue($file->isOk());
    }

    public function testSetModeReturnsFalseForInvalidMode(): void
    {
        $path = $this->createTempFile();

        $file = new VanillaFile($path);

        $result = $file->setMode('invalid-mode');

        self::assertFalse($result);
    }

    public function testIsOkReturnsTrueForOpenedFile(): void
    {
        $path = $this->createTempFile();

        $file = new VanillaFile($path);

        self::assertTrue($file->isOk());
    }

    public function testPutChunkWritesData(): void
    {
        $path = $this->tempDir . '/write-test.bin';
        file_put_contents($path, '');

        $file = new VanillaFile($path, AbstractFile::MODE_WRITE);
        $result = $file->putChunk('hello world');
        unset($file);

        self::assertNotFalse($result);
        self::assertSame('hello world', file_get_contents($path));
    }

    public function testGetChunkReadsData(): void
    {
        $content = 'chunk test data';
        $path = $this->createTempFile($content);

        $file = new VanillaFile($path);
        $chunk = $file->getChunk();

        self::assertSame($content, $chunk);
    }

    public function testCopyToSimpleCopiesFile(): void
    {
        $sourcePath = $this->createTempFile('copy me');
        $targetPath = $this->tempDir . '/copy-target.bin';

        $file = new VanillaFile($sourcePath);
        $result = $file->copyToSimple($targetPath);

        self::assertTrue($result);
        self::assertFileExists($targetPath);
        self::assertSame('copy me', file_get_contents($targetPath));
    }

    public function testDeleteDirRemovesDirectoryRecursively(): void
    {
        $dir = $this->tempDir . '/deleteme';
        mkdir($dir . '/sub/deep', 0o777, true);
        file_put_contents($dir . '/sub/deep/file.txt', 'data');
        file_put_contents($dir . '/sub/file2.txt', 'data2');

        $dummyPath = $this->createTempFile();
        $file = new VanillaFile($dummyPath);

        $result = $file->deleteDir($dir);

        self::assertTrue($result);
        self::assertDirectoryDoesNotExist($dir);
    }

    public function testDeleteDirThrowsForNonExistentDirectory(): void
    {
        $dummyPath = $this->createTempFile();
        $file = new VanillaFile($dummyPath);

        $this->expectException(FileException::class);
        $file->deleteDir($this->tempDir . '/does-not-exist');
    }

    public function testCopyToWithStringTargetUsesSimpleCopy(): void
    {
        $sourcePath = $this->createTempFile('source data');
        $targetPath = $this->tempDir . '/string-target.bin';

        $file = new VanillaFile($sourcePath);
        $result = $file->copyTo($targetPath);

        self::assertTrue($result);
        self::assertFileExists($targetPath);
        self::assertSame('source data', file_get_contents($targetPath));
    }

    public function testCopyToWithVanillaFileTargetUsesSimpleCopy(): void
    {
        $sourcePath = $this->createTempFile('vanilla source');
        $targetPath = $this->tempDir . '/vanilla-target.bin';
        file_put_contents($targetPath, '');

        $source = new VanillaFile($sourcePath);
        $target = new VanillaFile($targetPath, AbstractFile::MODE_WRITE);

        $result = $source->copyTo($target);

        self::assertTrue($result);
    }

    public function testDestructorClosesHandle(): void
    {
        $path = $this->createTempFile();

        $file = new VanillaFile($path);
        $handle = $file->handle;
        self::assertTrue(is_resource($handle));

        unset($file);

        self::assertFalse(is_resource($handle));
    }
}
