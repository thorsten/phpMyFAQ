<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\TestCase;

class AssetVersionTwigExtensionTest extends TestCase
{
    private string $rootDir;

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir() . '/pmf-asset-version-' . bin2hex(random_bytes(8));
        mkdir($this->rootDir . '/assets/public', 0o777, true);
        file_put_contents($this->rootDir . '/assets/public/frontend.js', 'console.log("test");');
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->rootDir));
    }

    public function testAppendsModificationTimeAsVersionWhenFileExists(): void
    {
        touch($this->rootDir . '/assets/public/frontend.js', 1_752_480_000);

        $result = AssetVersionTwigExtension::versionedPath('assets/public/frontend.js', $this->rootDir);

        $this->assertSame('assets/public/frontend.js?v=1752480000', $result);
    }

    public function testReturnsPathUnchangedWhenFileIsMissing(): void
    {
        $result = AssetVersionTwigExtension::versionedPath('assets/public/missing.js', $this->rootDir);

        $this->assertSame('assets/public/missing.js', $result);
    }

    public function testReturnsPathUnchangedForPathTraversalAttempts(): void
    {
        $result = AssetVersionTwigExtension::versionedPath('../outside/file.js', $this->rootDir);

        $this->assertSame('../outside/file.js', $result);
    }
}
