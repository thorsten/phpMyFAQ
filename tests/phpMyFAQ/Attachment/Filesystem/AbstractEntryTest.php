<?php

declare(strict_types=1);

namespace phpMyFAQ\Attachment\Filesystem;

use phpMyFAQ\Attachment\Filesystem\File\VanillaFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractEntry::class)]
#[CoversClass(VanillaFile::class)]
final class AbstractEntryTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/pmf-entry-test-' . bin2hex(random_bytes(4));
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

    private function createTempFile(string $content = 'test'): string
    {
        $path = $this->tempDir . '/entry-' . bin2hex(random_bytes(4)) . '.bin';
        file_put_contents($path, $content);
        return $path;
    }

    public function testGetPathReturnsFilePath(): void
    {
        $path = $this->createTempFile();
        $file = new VanillaFile($path);

        self::assertSame($path, $file->getPath());
    }

    public function testToStringReturnsFilePath(): void
    {
        $path = $this->createTempFile();
        $file = new VanillaFile($path);

        self::assertSame($path, (string) $file);
    }

    public function testIsEncryptedReturnsFalseForVanillaFile(): void
    {
        $path = $this->createTempFile();
        $file = new VanillaFile($path);

        self::assertFalse($file->isEncrypted());
    }

    public function testMoveTocopiesAndDeletesSource(): void
    {
        $sourcePath = $this->createTempFile('move me');
        $targetPath = $this->tempDir . '/move-target.bin';

        $source = new VanillaFile($sourcePath);
        $result = $source->moveTo($targetPath);

        self::assertTrue($result);
        self::assertFileExists($targetPath);
        self::assertSame('move me', file_get_contents($targetPath));
    }
}
