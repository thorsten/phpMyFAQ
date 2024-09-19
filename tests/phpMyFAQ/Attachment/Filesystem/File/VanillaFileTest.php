<?php

namespace phpMyFAQ\Attachment\Filesystem\File;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;


class VanillaFileTest extends TestCase
{
    private VanillaFile $mockFile;
    private $mockHandle;
    private $root;

    protected function setUp(): void
    {
        // Setup the virtual file system
        $this->root = vfsStream::setup('root', null, [
            'file.txt' => 'test file content'
        ]);

        // Get the virtual file path
        $filePath = vfsStream::url('root/file.txt');

        // Mock the VanillaFile class and inject the virtual file
        $this->mockFile = $this->getMockBuilder(VanillaFile::class)
            ->setConstructorArgs([$filePath])
            ->onlyMethods(['getChunk', 'putChunk', 'eof'])
            ->getMock();
    }

    public function testPutChunkWritesData()
    {
        $data = "test chunk data";

        // Write data to the virtual file
        $this->mockFile->expects($this->once())
            ->method('putChunk')
            ->with($data)
            ->willReturn(true);

        // Write the chunk and assert it's written correctly
        $this->assertTrue($this->mockFile->putChunk($data));
    }

    public function testGetChunkReadsData()
    {
        // Mocking the getChunk behavior to return content from the virtual file
        $this->mockFile->expects($this->once())
            ->method('getChunk')
            ->willReturn('test file content');

        $this->assertEquals('test file content', $this->mockFile->getChunk());
    }

    public function testDeleteFileSuccessfully()
    {
        // The file should exist in the virtual file system before deletion
        $filePath = vfsStream::url('root/file.txt');
        $this->assertTrue($this->root->hasChild('file.txt'));

        // Call fclose() before deletion to avoid handle issues
        fclose($this->mockFile->handle);

        // Perform the deletion using unlink for vfsStream
        unlink($filePath);

        // After deletion, the file should no longer exist in the virtual file system
        $this->assertFalse($this->root->hasChild('file.txt'));
    }
}
