<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DirectoryCopyOperationTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filesystem = $this->createMock(Filesystem::class);
    }

    public function testGetType(): void
    {
        $operation = new DirectoryCopyOperation($this->filesystem, '/source/dir', '/dest/dir');

        $this->assertEquals('directory_copy', $operation->getType());
    }

    public function testGetDescription(): void
    {
        $operation = new DirectoryCopyOperation($this->filesystem, '/source/dir', '/dest/dir');

        $this->assertStringContainsString('Copy directory:', $operation->getDescription());
        $this->assertStringContainsString('/source/dir', $operation->getDescription());
        $this->assertStringContainsString('/dest/dir', $operation->getDescription());
    }

    public function testGetSource(): void
    {
        $operation = new DirectoryCopyOperation($this->filesystem, '/source/dir', '/dest/dir');

        $this->assertEquals('/source/dir', $operation->getSource());
    }

    public function testGetDestination(): void
    {
        $operation = new DirectoryCopyOperation($this->filesystem, '/source/dir', '/dest/dir');

        $this->assertEquals('/dest/dir', $operation->getDestination());
    }

    public function testExecuteSkipsAMissingSourceWhenOnlyIfExistsIsSet(): void
    {
        $this->filesystem->expects($this->never())->method('recursiveCopy');
        $operation = new DirectoryCopyOperation(
            $this->filesystem,
            sys_get_temp_dir() . '/pmf-missing-' . uniqid(),
            '/dest',
        );

        $this->assertTrue($operation->execute());
    }

    public function testExecuteDelegatesTheCopyToTheFilesystem(): void
    {
        $this->filesystem
            ->expects($this->once())
            ->method('recursiveCopy')
            ->with('/source/dir', '/dest/dir')
            ->willReturn(true);
        $operation = new DirectoryCopyOperation($this->filesystem, '/source/dir', '/dest/dir', false);

        $this->assertTrue($operation->execute());
    }

    public function testExecuteReturnsFalseWhenTheCopyThrows(): void
    {
        $this->filesystem->method('recursiveCopy')->willThrowException(new \RuntimeException('disk full'));
        $operation = new DirectoryCopyOperation($this->filesystem, '/source/dir', '/dest/dir', false);

        $this->assertFalse($operation->execute());
    }

    public function testToArray(): void
    {
        $operation = new DirectoryCopyOperation($this->filesystem, '/source/dir', '/dest/dir', true);

        $array = $operation->toArray();

        $this->assertEquals('directory_copy', $array['type']);
        $this->assertArrayHasKey('description', $array);
        $this->assertEquals('/source/dir', $array['source']);
        $this->assertEquals('/dest/dir', $array['destination']);
        $this->assertTrue($array['onlyIfExists']);
    }

    public function testToArrayWithOnlyIfExistsFalse(): void
    {
        $operation = new DirectoryCopyOperation($this->filesystem, '/source/dir', '/dest/dir', false);

        $array = $operation->toArray();
        $this->assertFalse($array['onlyIfExists']);
    }
}
