<?php

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class ImageTest
 */
#[AllowMockObjectsWithoutExpectations]
class ImageTest extends TestCase
{
    private Image $image;
    private Configuration $configurationMock;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->image = new Image($this->configurationMock);
    }

    protected function tearDown(): void
    {
        // Clean up any test files that might have been created
        $testDir = PMF_CONTENT_DIR . '/user/images/';
        if (is_dir($testDir)) {
            $files = glob($testDir . 'category-test-*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Image::class, $this->image);
    }

    public function testSetUploadedFileWithValidFile(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->once())->method('isValid')->willReturn(true);

        $result = $this->image->setUploadedFile($uploadedFileMock);

        $this->assertInstanceOf(Image::class, $result);
        $this->assertSame($this->image, $result);
    }

    public function testSetUploadedFileWithInvalidFile(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->once())->method('isValid')->willReturn(false);

        $result = $this->image->setUploadedFile($uploadedFileMock);

        $this->assertInstanceOf(Image::class, $result);
    }

    public function testSetFileName(): void
    {
        $fileName = 'test-image.jpg';
        $result = $this->image->setFileName($fileName);

        $this->assertInstanceOf(Image::class, $result);
        $this->assertSame($this->image, $result);
    }

    public function testGetFileNameWithUploadJpeg(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->once())->method('getMimeType')->willReturn('image/jpeg');

        $this->image->setUploadedFile($uploadedFileMock);
        $fileName = $this->image->getFileName(123, 'test-category');

        $this->assertEquals('category-123-test-category.jpg', $fileName);
    }

    public function testGetFileNameWithUploadPng(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->once())->method('getMimeType')->willReturn('image/png');

        $this->image->setUploadedFile($uploadedFileMock);
        $fileName = $this->image->getFileName(123, 'test-category');

        $this->assertEquals('category-123-test-category.png', $fileName);
    }

    public function testGetFileNameWithUploadGif(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->once())->method('getMimeType')->willReturn('image/gif');

        $this->image->setUploadedFile($uploadedFileMock);
        $fileName = $this->image->getFileName(123, 'test-category');

        $this->assertEquals('category-123-test-category.gif', $fileName);
    }

    public function testGetFileNameWithUploadWebp(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->once())->method('getMimeType')->willReturn('image/webp');

        $this->image->setUploadedFile($uploadedFileMock);
        $fileName = $this->image->getFileName(123, 'test-category');

        $this->assertEquals('category-123-test-category.webp', $fileName);
    }

    public function testGetFileNameWithUploadUnknownMimeType(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->once())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->once())->method('getMimeType')->willReturn('unknown/mime');

        $this->image->setUploadedFile($uploadedFileMock);
        $fileName = $this->image->getFileName(123, 'test-category');

        $this->assertEquals('category-123-test-category.png', $fileName);
    }

    public function testGetFileNameWithoutUpload(): void
    {
        $fileName = $this->image->getFileName(123, 'test-category');
        $this->assertEquals('', $fileName);
    }

    public function testUploadValidImage(): void
    {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        file_put_contents($tempFile, 'fake image content');

        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->atLeastOnce())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->atLeastOnce())->method('getSize')->willReturn(1024); // 1 KB
        $uploadedFileMock->expects($this->once())->method('getClientMimeType')->willReturn('image/jpeg');
        $uploadedFileMock->expects($this->once())->method('move')->willReturnSelf(); // Return UploadedFile instance instead of bool

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('records.maxAttachmentSize')
            ->willReturn(2048); // 2KB limit

        $this->image->setUploadedFile($uploadedFileMock);
        $this->image->setFileName('test-image.jpg');

        $result = $this->image->upload();

        $this->assertTrue($result);

        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    public function testUploadWithInvalidFile(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->atLeastOnce())->method('isValid')->willReturn(false);

        $this->image->setUploadedFile($uploadedFileMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Uploaded image is too big');

        $this->image->upload();
    }

    public function testUploadWithFileTooLarge(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->atLeastOnce())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->once())->method('getSize')->willReturn(3072); // 3KB

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('records.maxAttachmentSize')
            ->willReturn(2048); // 2KB limit

        $this->image->setUploadedFile($uploadedFileMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Uploaded image is too big');

        $this->image->upload();
    }

    public function testUploadWithUndetectableSize(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->atLeastOnce())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->atLeastOnce())->method('getSize')->willReturn(false);

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('records.maxAttachmentSize')
            ->willReturn(2048);

        $this->image->setUploadedFile($uploadedFileMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot detect image size');

        $this->image->upload();
    }

    public function testUploadWithInvalidMimeType(): void
    {
        $uploadedFileMock = $this->createMock(UploadedFile::class);
        $uploadedFileMock->expects($this->atLeastOnce())->method('isValid')->willReturn(true);
        $uploadedFileMock->expects($this->atLeastOnce())->method('getSize')->willReturn(1024);
        $uploadedFileMock->expects($this->once())->method('getClientMimeType')->willReturn('text/plain');

        $this->configurationMock
            ->expects($this->once())
            ->method('get')
            ->with('records.maxAttachmentSize')
            ->willReturn(2048);

        $this->image->setUploadedFile($uploadedFileMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Image MIME type validation failed.');

        $this->image->upload();
    }

    public function testDeleteExistingFile(): void
    {
        // Create a test file
        $testDir = PMF_CONTENT_DIR . '/user/images/';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }

        $testFile = $testDir . 'category-test-delete.jpg';
        file_put_contents($testFile, 'test content');

        $this->image->setFileName('category-test-delete.jpg');
        $result = $this->image->delete();

        $this->assertTrue($result);
        $this->assertFalse(file_exists($testFile));
    }

    public function testDeleteNonExistingFile(): void
    {
        $this->image->setFileName('non-existing-file.jpg');
        $result = $this->image->delete();

        $this->assertTrue($result);
    }
}
