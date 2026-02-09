<?php

namespace phpMyFAQ\Storage;

use phpMyFAQ\Configuration;
use phpMyFAQ\Tenant\TenantContext;
use phpMyFAQ\Tenant\TenantQuotas;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StorageFactory::class)]
#[UsesClass(FilesystemStorage::class)]
#[UsesClass(S3Storage::class)]
#[UsesClass(TenantScopedStorage::class)]
#[UsesClass(TenantContext::class)]
#[UsesClass(TenantQuotas::class)]
class StorageFactoryTest extends TestCase
{
    public function testCreateReturnsFilesystemStorageByDefault(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['storage.type',                     null],
                ['storage.filesystem.root',          null],
                ['storage.filesystem.publicBaseUrl', null],
            ]);

        $factory = new StorageFactory($configuration, $this->createTenantContext(7));
        $storage = $factory->create();

        $this->assertInstanceOf(TenantScopedStorage::class, $storage);
    }

    public function testCreateReturnsFilesystemStorageWhenConfigured(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['storage.type',                     'filesystem'],
                ['storage.filesystem.root',          '/tmp/phpmyfaq-storage'],
                ['storage.filesystem.publicBaseUrl', 'https://cdn.example.com/files'],
            ]);

        $factory = new StorageFactory($configuration, $this->createTenantContext(12));
        $storage = $factory->create();

        $this->assertInstanceOf(TenantScopedStorage::class, $storage);
    }

    public function testCreateThrowsForUnsupportedStorageType(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['storage.type', 'unsupported'],
            ]);

        $factory = new StorageFactory($configuration, $this->createTenantContext(5));

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Unsupported storage type: unsupported');
        $factory->create();
    }

    public function testCreateReturnsS3StorageWhenConfigured(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['storage.type',             's3'],
                ['storage.s3.bucket',        'pmf-bucket'],
                ['storage.s3.prefix',        null],
                ['storage.s3.publicBaseUrl', null],
                ['storage.s3.region',        null],
                ['storage.s3.endpoint',      null],
                ['storage.s3.key',           null],
                ['storage.s3.secret',        null],
                ['storage.s3.usePathStyle',  null],
            ]);

        $factory = new StorageFactory($configuration, $this->createTenantContext(8));
        $storage = $factory->create();
        $this->assertInstanceOf(TenantScopedStorage::class, $storage);
    }

    public function testCreateThrowsWhenFilesystemRootIsNotWritable(): void
    {
        // Use a path under /proc (Linux) or /System (macOS) that exists but is not writable
        $readOnlyDir = PHP_OS_FAMILY === 'Darwin' ? '/System' : '/proc';
        if (!is_dir($readOnlyDir) || is_writable($readOnlyDir)) {
            $this->markTestSkipped('No read-only directory available for this test.');
        }

        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['storage.type', 'filesystem'],
                ['storage.filesystem.root', $readOnlyDir . '/phpmyfaq-test-unwritable'],
                ['storage.filesystem.publicBaseUrl', null],
            ]);

        $factory = new StorageFactory($configuration, $this->createTenantContext(10));

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Storage root directory');
        $factory->create();
    }

    public function testCreateCreatesFilesystemRootIfMissing(): void
    {
        $tmpDir = sys_get_temp_dir() . '/phpmyfaq-factory-test-' . uniqid('', true);

        try {
            $configuration = $this->createStub(Configuration::class);
            $configuration
                ->method('get')
                ->willReturnMap([
                    ['storage.type',                     'filesystem'],
                    ['storage.filesystem.root',          $tmpDir],
                    ['storage.filesystem.publicBaseUrl', null],
                ]);

            $factory = new StorageFactory($configuration, $this->createTenantContext(9));
            $storage = $factory->create();

            $this->assertInstanceOf(TenantScopedStorage::class, $storage);
            $this->assertDirectoryExists($tmpDir);
        } finally {
            if (is_dir($tmpDir)) {
                rmdir($tmpDir);
            }
        }
    }

    public function testCreateThrowsWhenOnlyS3KeyProvided(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['storage.type',             's3'],
                ['storage.s3.bucket',        'pmf-bucket'],
                ['storage.s3.prefix',        null],
                ['storage.s3.publicBaseUrl', null],
                ['storage.s3.region',        null],
                ['storage.s3.endpoint',      null],
                ['storage.s3.key',           'AKIAIOSFODNN7EXAMPLE'],
                ['storage.s3.secret',        null],
                ['storage.s3.usePathStyle',  null],
            ]);

        $factory = new StorageFactory($configuration, $this->createTenantContext(11));

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Both storage.s3.key and storage.s3.secret must be provided together.');
        $factory->create();
    }

    public function testCreateThrowsWhenOnlyS3SecretProvided(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['storage.type',             's3'],
                ['storage.s3.bucket',        'pmf-bucket'],
                ['storage.s3.prefix',        null],
                ['storage.s3.publicBaseUrl', null],
                ['storage.s3.region',        null],
                ['storage.s3.endpoint',      null],
                ['storage.s3.key',           null],
                ['storage.s3.secret',        'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'],
                ['storage.s3.usePathStyle',  null],
            ]);

        $factory = new StorageFactory($configuration, $this->createTenantContext(11));

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Both storage.s3.key and storage.s3.secret must be provided together.');
        $factory->create();
    }

    public function testCreatePrefixesFilesystemPathsWithTenantPath(): void
    {
        $tmpDir = sys_get_temp_dir() . '/phpmyfaq-factory-prefix-' . uniqid('', true);

        try {
            $configuration = $this->createStub(Configuration::class);
            $configuration
                ->method('get')
                ->willReturnMap([
                    ['storage.type',                     'filesystem'],
                    ['storage.filesystem.root',          $tmpDir],
                    ['storage.filesystem.publicBaseUrl', null],
                ]);

            $factory = new StorageFactory($configuration, $this->createTenantContext(42));
            $storage = $factory->create();
            $this->assertTrue($storage->put('my/file.txt', 'content'));

            $this->assertFileExists($tmpDir . '/42/attachments/my/file.txt');
        } finally {
            $this->removeDirectory($tmpDir);
        }
    }

    private function createTenantContext(int $tenantId): TenantContext
    {
        return new TenantContext(
            tenantId: $tenantId,
            hostname: 'tenant.example.com',
            tablePrefix: '',
            configDir: '/tmp',
            plan: 'free',
            quotas: new TenantQuotas(),
        );
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
