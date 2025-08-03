<?php

namespace phpMyFAQ\Export\Pdf;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;

class WrapperTest extends TestCase
{
    private Wrapper $wrapper;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->wrapper = new Wrapper();
    }

    public function testConcatenatePathsWithUnixPaths(): void
    {
        $path = '/var/www/phpmyfaq';

        $file = '/content/user/images/test.jpg';
        $expected = '/var/www/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testConcatenatePathsWithWindowsPaths(): void
    {
        $path = 'C:\\xampp\\htdocs\\phpmyfaq';

        $file = '/content/user/images/test.jpg';
        $expected = 'C:/xampp/htdocs/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testConcatenatePathsWithMixedPaths(): void
    {
        $path = 'C:\\xampp\\htdocs\\phpmyfaq';

        $file = '/content/user/images/test.jpg';
        $expected = 'C:/xampp/htdocs/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testConcatenatePathsWithDuplicateRoot(): void
    {
        $path = 'C:\\xampp\\htdocs\\phpmyfaq';

        $file = '/phpmyfaq/content/user/images/test.jpg';
        $expected = 'C:/xampp/htdocs/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testConvertExternalImagesToBase64WithNoConfig(): void
    {
        $html = '<img src="https://example.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML when no config is set
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithEmptyAllowedHosts(): void
    {
        $config = $this->createMock(\phpMyFAQ\Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://example.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML when allowed hosts is empty
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithDisallowedHost(): void
    {
        $config = $this->createMock(\phpMyFAQ\Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://badsite.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML when host is not allowed
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithLocalImage(): void
    {
        $config = $this->createMock(\phpMyFAQ\Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="/local/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML for local images (no protocol/host)
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithMalformedUrl(): void
    {
        $config = $this->createMock(\phpMyFAQ\Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="not-a-valid-url" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML for malformed URLs
        $this->assertEquals($html, $result);
    }

    public function testValidateImageDataWithValidJpeg(): void
    {
        // JPEG file signature
        $jpegData = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01";
        $reflection = new \ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->wrapper, $jpegData));
    }

    public function testValidateImageDataWithValidPng(): void
    {
        // PNG file signature
        $pngData = "\x89PNG\r\n\x1A\n\x00\x00\x00\x0DIHDR";
        $reflection = new \ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->wrapper, $pngData));
    }

    public function testValidateImageDataWithInvalidData(): void
    {
        $invalidData = "This is not image data";
        $reflection = new \ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');
        $method->setAccessible(true);
        
        $this->assertFalse($method->invoke($this->wrapper, $invalidData));
    }

    public function testValidateImageDataWithTooShortData(): void
    {
        $shortData = "short";
        $reflection = new \ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');
        $method->setAccessible(true);
        
        $this->assertFalse($method->invoke($this->wrapper, $shortData));
    }

    public function testConcatenatePathsWithUrlEncodedSpaces(): void
    {
        $path = '/var/www/phpmyfaq';
        
        // Test with URL-encoded spaces (%20)
        $file = '/content/user/images/image%20with%20spaces.jpg';
        $expected = '/var/www/phpmyfaq/content/user/images/image%20with%20spaces.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testFilePathDecodingWithSpaces(): void
    {
        // Test URL decoding of file paths with spaces
        $encodedPath = '/content/user/images/image%20with%20spaces.jpg';
        $decodedPath = '/content/user/images/image with spaces.jpg';
        
        $this->assertEquals($decodedPath, urldecode($encodedPath));
    }

    public function testImageFileWithSpacesInPath(): void
    {
        // Create a test directory and file with spaces
        $testDir = __DIR__ . '/../../../../content/user/images';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        $testFile = $testDir . '/test image with spaces.jpg';
        file_put_contents($testFile, 'fake image content');
        
        // Test that we can read the file when the path contains spaces
        $urlEncodedPath = '/content/user/images/test%20image%20with%20spaces.jpg';
        $decodedPath = urldecode($urlEncodedPath);
        
        $fullPath = $this->wrapper->concatenatePaths(__DIR__ . '/../../../../', $decodedPath);
        
        $this->assertTrue(file_exists($fullPath), "File should exist: " . $fullPath);
        
        // Clean up
        unlink($testFile);
    }
}
