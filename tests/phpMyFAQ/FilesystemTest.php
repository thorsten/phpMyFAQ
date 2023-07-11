<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem(PMF_TEST_DIR);
    }

    public function testDeleteDirectory(): void
    {
        $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/delete-this-directory');

        $actual = $this->filesystem->deleteDirectory(PMF_CONTENT_DIR . '/delete-this-directory');
        $this->assertTrue($actual);
    }

    public function testDeleteDirectoryWithEmptyPath(): void
    {
        $actual = $this->filesystem->deleteDirectory('');
        $this->assertFalse($actual);
    }

    public function testCreateDirectory(): void
    {
        $actual = $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/create-this-directory');
        $this->assertTrue($actual);
    }

    public function testCreateDirectoryDuplicateDirectory(): void
    {
        $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/create-this-directory');
        $actual = $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/create-this-directory');
        $this->assertTrue($actual);
    }

    public function testCopy(): void
    {
        $this->markTestSkipped();
    }

    public function testSetPath(): void
    {
        $this->markTestSkipped();
    }

    public function testMoveDirectory(): void
    {
        $this->markTestSkipped();
    }

    public function testRecursiveCopy(): void
    {
        $this->markTestSkipped();
    }

    public function testGetPath(): void
    {
        $this->markTestSkipped();
    }

    public function testGetRootPath(): void
    {
        $this->markTestSkipped();
    }
}
