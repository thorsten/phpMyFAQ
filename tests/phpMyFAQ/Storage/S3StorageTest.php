<?php

namespace phpMyFAQ\Storage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(S3Storage::class)]
class S3StorageTest extends TestCase
{
    public function testPutGetDeleteExistsUrlAndSize(): void
    {
        $client = new FakeS3Client();
        $storage = new S3Storage($client, 'pmf-bucket', 'tenant/attachments', 'https://cdn.example.com');

        $this->assertTrue($storage->put('docs/readme.txt', 'hello'));
        $this->assertSame('tenant/attachments/docs/readme.txt', $client->lastPutObject['Key']);

        $this->assertSame('hello', $storage->get('docs/readme.txt'));
        $this->assertTrue($storage->exists('docs/readme.txt'));
        $this->assertSame(5, $storage->size('docs/readme.txt'));
        $this->assertSame(
            'https://cdn.example.com/tenant/attachments/docs/readme.txt',
            $storage->url('docs/readme.txt'),
        );
        $this->assertTrue($storage->delete('docs/readme.txt'));
    }

    public function testPutStreamWritesObject(): void
    {
        $client = new FakeS3Client();
        $storage = new S3Storage($client, 'pmf-bucket', 'tenant/attachments');
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, 'stream-data');
        rewind($stream);

        $this->assertTrue($storage->putStream('stream.txt', $stream));
        $this->assertSame('stream-data', (string) $client->objects['tenant/attachments/stream.txt']);

        fclose($stream);
    }

    public function testGetAndSizeAcceptArrayAccessResults(): void
    {
        $client = new ArrayAccessS3Client();
        $storage = new S3Storage($client, 'pmf-bucket');

        $storage->put('array-access.txt', 'content');
        $this->assertSame('content', $storage->get('array-access.txt'));
        $this->assertSame(7, $storage->size('array-access.txt'));
    }

    public function testDoubleDotInFilenameIsAllowed(): void
    {
        $client = new FakeS3Client();
        $storage = new S3Storage($client, 'pmf-bucket');

        $this->assertTrue($storage->put('backups/file..backup.txt', 'data'));
        $this->assertSame('data', $storage->get('backups/file..backup.txt'));
    }

    public function testTraversalPathThrowsException(): void
    {
        $client = new FakeS3Client();
        $storage = new S3Storage($client, 'pmf-bucket');

        $this->expectException(StorageException::class);
        $storage->put('../escape.txt', 'x');
    }

    public function testEmptySegmentPathThrowsException(): void
    {
        $client = new FakeS3Client();
        $storage = new S3Storage($client, 'pmf-bucket');

        $this->expectException(StorageException::class);
        $storage->put('foo//bar.txt', 'x');
    }
}

final class FakeS3Client
{
    public array $objects = [];

    public array $lastPutObject = [];

    public function putObject(array $args): array
    {
        $this->lastPutObject = $args;
        $body = $args['Body'];
        if (is_resource($body)) {
            $body = stream_get_contents($body) ?: '';
        }

        $this->objects[$args['Key']] = $body;

        return ['ObjectURL' => $this->getObjectUrl($args['Bucket'], $args['Key'])];
    }

    public function getObject(array $args): array
    {
        return ['Body' => $this->objects[$args['Key']] ?? ''];
    }

    public function deleteObject(array $args): array
    {
        unset($this->objects[$args['Key']]);
        return [];
    }

    public function doesObjectExistV2(string $bucket, string $key): bool
    {
        return isset($this->objects[$key]);
    }

    public function getObjectUrl(string $bucket, string $key): string
    {
        return sprintf('https://%s.s3.local/%s', $bucket, $key);
    }

    public function headObject(array $args): array
    {
        return ['ContentLength' => strlen((string) ($this->objects[$args['Key']] ?? ''))];
    }
}

/**
 * S3 client that returns ArrayAccess results, mimicking Aws\Result.
 */
final class ArrayAccessS3Client
{
    public array $objects = [];

    public function putObject(array $args): ArrayAccessResult
    {
        $body = $args['Body'];
        if (is_resource($body)) {
            $body = stream_get_contents($body) ?: '';
        }

        $this->objects[$args['Key']] = $body;

        return new ArrayAccessResult([]);
    }

    public function getObject(array $args): ArrayAccessResult
    {
        return new ArrayAccessResult(['Body' => $this->objects[$args['Key']] ?? '']);
    }

    public function headObject(array $args): ArrayAccessResult
    {
        return new ArrayAccessResult([
            'ContentLength' => strlen((string) ($this->objects[$args['Key']] ?? '')),
        ]);
    }

    public function doesObjectExistV2(string $bucket, string $key): bool
    {
        return isset($this->objects[$key]);
    }

    public function deleteObject(array $args): ArrayAccessResult
    {
        unset($this->objects[$args['Key']]);
        return new ArrayAccessResult([]);
    }

    public function getObjectUrl(string $bucket, string $key): string
    {
        return sprintf('https://%s.s3.local/%s', $bucket, $key);
    }
}

/**
 * Minimal ArrayAccess implementation mimicking Aws\Result behavior.
 */
final class ArrayAccessResult implements \ArrayAccess
{
    public function __construct(private array $data)
    {
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}
