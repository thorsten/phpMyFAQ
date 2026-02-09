<?php

namespace phpMyFAQ\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TenantScopedStorage::class)]
class TenantScopedStorageTest extends TestCase
{
    public function testPrefixesAllOperationsWithTenantScope(): void
    {
        $inner = new RecordingStorage();
        $storage = new TenantScopedStorage($inner, '15/attachments');

        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, 'stream');
        rewind($stream);

        $this->assertTrue($storage->put('a.txt', 'A'));
        $this->assertTrue($storage->putStream('b.txt', $stream));
        $storage->get('c.txt');
        $storage->delete('d.txt');
        $storage->exists('e.txt');
        $storage->url('f.txt');
        $storage->size('g.txt');

        $this->assertSame(
            [
                ['put', '15/attachments/a.txt'],
                ['putStream', '15/attachments/b.txt'],
                ['get', '15/attachments/c.txt'],
                ['delete', '15/attachments/d.txt'],
                ['exists', '15/attachments/e.txt'],
                ['url', '15/attachments/f.txt'],
                ['size', '15/attachments/g.txt'],
            ],
            $inner->calls,
        );

        fclose($stream);
    }
}

final class RecordingStorage implements StorageInterface
{
    public array $calls = [];

    public function put(string $path, string $contents): bool
    {
        $this->calls[] = ['put', $path];
        return true;
    }

    public function putStream(string $path, mixed $stream): bool
    {
        $this->calls[] = ['putStream', $path];
        return true;
    }

    public function get(string $path): string
    {
        $this->calls[] = ['get', $path];
        return 'x';
    }

    public function delete(string $path): bool
    {
        $this->calls[] = ['delete', $path];
        return true;
    }

    public function exists(string $path): bool
    {
        $this->calls[] = ['exists', $path];
        return true;
    }

    public function url(string $path): string
    {
        $this->calls[] = ['url', $path];
        return 'https://example.com/' . $path;
    }

    public function size(string $path): int
    {
        $this->calls[] = ['size', $path];
        return 1;
    }
}
