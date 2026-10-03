<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(WritablePathScanner::class)]
final class WritablePathScannerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/pmf-writable-scan-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/content/user', 0777, true);
        mkdir($this->root . '/excluded/deep', 0777, true);
        file_put_contents($this->root . '/content/user/file.txt', 'x');
        file_put_contents($this->root . '/excluded/deep/file.txt', 'x');
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->chmod($this->root, 0777, recursive: true);
        $filesystem->remove($this->root);
    }

    public function testReturnsEmptyListWhenEverythingIsWritable(): void
    {
        $this->assertSame([], WritablePathScanner::getNonWritablePaths($this->root, $this->root . '/does-not-exist'));
    }

    public function testReportsReadOnlyPathsAndSkipsExcludedDirectory(): void
    {
        chmod($this->root . '/content/user/file.txt', 0444);
        chmod($this->root . '/excluded/deep/file.txt', 0444);

        if (is_writable($this->root . '/content/user/file.txt')) {
            $this->markTestSkipped('The current user ignores file permissions (probably root).');
        }

        $paths = WritablePathScanner::getNonWritablePaths($this->root, $this->root . '/excluded');

        $this->assertSame([$this->root . '/content/user/file.txt'], $paths);
    }

    public function testExcludedDirectoryItselfIsSkipped(): void
    {
        chmod($this->root . '/excluded', 0555);

        if (is_writable($this->root . '/excluded')) {
            $this->markTestSkipped('The current user ignores file permissions (probably root).');
        }

        $this->assertSame([], WritablePathScanner::getNonWritablePaths($this->root, $this->root . '/excluded'));
        $this->assertSame(
            [$this->root . '/excluded'],
            WritablePathScanner::getNonWritablePaths($this->root, $this->root . '/does-not-exist'),
        );
    }

    public function testFormatPathListJoinsUpToFivePaths(): void
    {
        $this->assertSame('', WritablePathScanner::formatPathList([]));
        $this->assertSame('/a', WritablePathScanner::formatPathList(['/a']));
        $this->assertSame('/a, /b, /c, /d, /e', WritablePathScanner::formatPathList(['/a', '/b', '/c', '/d', '/e']));
    }

    public function testFormatPathListTruncatesAndCountsTheRest(): void
    {
        $this->assertSame(
            '/a, /b, /c, /d, /e and 2 more',
            WritablePathScanner::formatPathList(['/a', '/b', '/c', '/d', '/e', '/f', '/g']),
        );
    }
}
