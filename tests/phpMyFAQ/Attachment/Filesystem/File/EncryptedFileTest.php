<?php

declare(strict_types=1);

namespace phpMyFAQ\Attachment\Filesystem\File;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(EncryptedFile::class)]
#[UsesClass(VanillaFile::class)]
final class EncryptedFileTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/pmf-encrypted-file-test-' . bin2hex(random_bytes(4));
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

    public function testConstructorInitializesAes(): void
    {
        $path = $this->tempDir . '/test-encrypted.bin';
        file_put_contents($path, '');

        $file = new EncryptedFile($path, 'wb', str_repeat('k', 16));

        $ref = new ReflectionClass($file);
        $aesProp = $ref->getProperty('aes');
        $aes = $aesProp->getValue($file);

        self::assertNotNull($aes);
    }

    public function testPutChunkWritesEncryptedData(): void
    {
        $key = str_repeat('k', 16);
        $path = $this->tempDir . '/write.bin';
        file_put_contents($path, '');

        $file = new EncryptedFile($path, 'wb', $key);
        $result = $file->putChunk('hello');
        unset($file);

        self::assertIsInt($result);
        self::assertGreaterThan(0, $result);

        $raw = file_get_contents($path);
        self::assertNotSame('hello', $raw);
        self::assertNotEmpty($raw);
    }

    public function testPutChunkAndGetChunkRoundtrip(): void
    {
        $key = str_repeat('k', 16);
        $path = $this->tempDir . '/roundtrip.bin';
        file_put_contents($path, '');

        $writeFile = new EncryptedFile($path, 'wb', $key);
        $writeFile->putChunk('hello encrypted world');
        unset($writeFile);

        $readFile = new EncryptedFile($path, 'rb', $key);
        $decrypted = $readFile->getChunk();

        self::assertSame('hello encrypted world', $decrypted);
    }

    public function testMultipleChunksEncryptDecrypt(): void
    {
        $key = str_repeat('m', 16);
        $path = $this->tempDir . '/multi.bin';
        file_put_contents($path, '');

        $writeFile = new EncryptedFile($path, 'wb', $key);
        $writeFile->putChunk('chunk one');
        $writeFile->putChunk('chunk two');
        unset($writeFile);

        $readFile = new EncryptedFile($path, 'rb', $key);

        self::assertSame('chunk one', $readFile->getChunk());
        self::assertSame('chunk two', $readFile->getChunk());
    }

    public function testCopyToDecryptsToTarget(): void
    {
        $key = str_repeat('c', 16);
        $encPath = $this->tempDir . '/enc-src.bin';
        $decPath = $this->tempDir . '/dec-target.bin';
        file_put_contents($encPath, '');
        file_put_contents($decPath, '');

        $writeFile = new EncryptedFile($encPath, 'wb', $key);
        $writeFile->putChunk('secret data');
        unset($writeFile);

        $readFile = new EncryptedFile($encPath, 'rb', $key);

        $result = $readFile->copyTo($decPath);

        self::assertTrue($result);
        self::assertSame('secret data', file_get_contents($decPath));
    }

    public function testGetChunkReturnsEmptyStringAtEof(): void
    {
        $key = str_repeat('e', 16);
        $path = $this->tempDir . '/empty-read.bin';
        file_put_contents($path, '');

        $file = new EncryptedFile($path, 'rb', $key);

        self::assertSame('', $file->getChunk());
    }
}
