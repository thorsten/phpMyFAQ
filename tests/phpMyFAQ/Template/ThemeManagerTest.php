<?php

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use phpMyFAQ\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

#[CoversClass(ThemeManager::class)]
class ThemeManagerTest extends TestCase
{
    public function testUploadThemeStoresArchiveContentsInStorage(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive is not available in this environment.');
        }

        $archivePath = $this->createThemeArchive([
            'tenant-theme/index.twig' => '<h1>Hello</h1>',
            'tenant-theme/assets/theme.css' => 'body { color: #000; }',
        ]);

        try {
            $configuration = $this->createStub(Configuration::class);
            $storage = new InMemoryStorage();
            $manager = new ThemeManager($configuration, $storage, 'themes');

            $uploadedFiles = $manager->uploadTheme('tenant-theme', $archivePath);

            $this->assertSame(2, $uploadedFiles);
            $this->assertTrue($storage->exists('themes/tenant-theme/index.twig'));
            $this->assertTrue($storage->exists('themes/tenant-theme/assets/theme.css'));
            $this->assertSame('<h1>Hello</h1>', $storage->get('themes/tenant-theme/index.twig'));
        } finally {
            @unlink($archivePath);
        }
    }

    public function testUploadThemeRejectsArchiveWithoutIndexTwig(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive is not available in this environment.');
        }

        $archivePath = $this->createThemeArchive([
            'tenant-theme/assets/theme.css' => 'body { color: #000; }',
        ]);

        try {
            $configuration = $this->createStub(Configuration::class);
            $storage = new InMemoryStorage();
            $manager = new ThemeManager($configuration, $storage, 'themes');

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('missing required "index.twig"');

            $manager->uploadTheme('tenant-theme', $archivePath);
        } finally {
            @unlink($archivePath);
        }
    }

    public function testUploadThemeRejectsDisallowedFileExtension(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive is not available in this environment.');
        }

        $archivePath = $this->createThemeArchive([
            'tenant-theme/index.twig' => '<h1>Hello</h1>',
            'tenant-theme/payload.php' => '<?php echo "x";',
        ]);

        try {
            $configuration = $this->createStub(Configuration::class);
            $storage = new InMemoryStorage();
            $manager = new ThemeManager($configuration, $storage, 'themes');

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Theme file type is not allowed');

            $manager->uploadTheme('tenant-theme', $archivePath);
        } finally {
            @unlink($archivePath);
        }
    }

    public function testActivateThemePersistsTemplateSetConfig(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->expects($this->once())
            ->method('set')
            ->with('layout.templateSet', 'tenant-theme')
            ->willReturn(true);

        $manager = new ThemeManager($configuration, new InMemoryStorage(), 'themes');
        $this->assertTrue($manager->activateTheme('tenant-theme'));
    }

    public function testActivateDefaultThemePersistsDefaultTemplateSetConfig(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())->method('set')->with('layout.templateSet', 'default')->willReturn(true);

        $manager = new ThemeManager($configuration, new InMemoryStorage(), 'themes');
        $this->assertTrue($manager->activateDefaultTheme());
    }

    public function testUploadThemeRejectsInvalidThemeName(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $manager = new ThemeManager($configuration, new InMemoryStorage(), 'themes');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid theme name');

        $manager->activateTheme('../invalid');
    }

    /**
     * @param array<string, string> $entries
     */
    private function createThemeArchive(array $entries): string
    {
        $archivePath = tempnam(sys_get_temp_dir(), 'pmf-theme-');
        if ($archivePath === false) {
            $this->fail('Failed to create temp archive file.');
        }

        $zip = new ZipArchive();
        $opened = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            $this->fail('Failed to create zip archive.');
        }

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
        return $archivePath;
    }
}

class InMemoryStorage implements StorageInterface
{
    /** @var array<string, string> */
    private array $files = [];

    public function put(string $path, string $contents): bool
    {
        $this->files[$path] = $contents;
        return true;
    }

    public function putStream(string $path, mixed $stream): bool
    {
        $contents = stream_get_contents($stream);
        if (!is_string($contents)) {
            return false;
        }

        $this->files[$path] = $contents;
        return true;
    }

    public function get(string $path): string
    {
        if (!isset($this->files[$path])) {
            throw new RuntimeException('File not found in in-memory storage.');
        }

        return $this->files[$path];
    }

    public function delete(string $path): bool
    {
        unset($this->files[$path]);
        return true;
    }

    public function exists(string $path): bool
    {
        return isset($this->files[$path]);
    }

    public function url(string $path): string
    {
        return 'memory://' . $path;
    }

    public function size(string $path): int
    {
        return strlen($this->get($path));
    }
}
