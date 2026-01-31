<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FileCopyOperationTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = $this->createMock(Filesystem::class);
    }

    public function testGetType(): void
    {
        $operation = new FileCopyOperation($this->filesystem, '/source/file.txt', '/dest/file.txt');

        $this->assertEquals('file_copy', $operation->getType());
    }

    public function testGetDescription(): void
    {
        $operation = new FileCopyOperation($this->filesystem, '/source/file.txt', '/dest/file.txt');

        $this->assertStringContainsString('Copy file:', $operation->getDescription());
        $this->assertStringContainsString('/source/file.txt', $operation->getDescription());
        $this->assertStringContainsString('/dest/file.txt', $operation->getDescription());
    }

    public function testGetSource(): void
    {
        $operation = new FileCopyOperation($this->filesystem, '/source/file.txt', '/dest/file.txt');

        $this->assertEquals('/source/file.txt', $operation->getSource());
    }

    public function testGetDestination(): void
    {
        $operation = new FileCopyOperation($this->filesystem, '/source/file.txt', '/dest/file.txt');

        $this->assertEquals('/dest/file.txt', $operation->getDestination());
    }

    public function testToArray(): void
    {
        $operation = new FileCopyOperation($this->filesystem, '/source/file.txt', '/dest/file.txt', true);

        $array = $operation->toArray();

        $this->assertEquals('file_copy', $array['type']);
        $this->assertArrayHasKey('description', $array);
        $this->assertEquals('/source/file.txt', $array['source']);
        $this->assertEquals('/dest/file.txt', $array['destination']);
        $this->assertTrue($array['onlyIfExists']);
    }

    public function testToArrayWithOnlyIfExistsFalse(): void
    {
        $operation = new FileCopyOperation($this->filesystem, '/source/file.txt', '/dest/file.txt', false);

        $array = $operation->toArray();
        $this->assertFalse($array['onlyIfExists']);
    }
}
