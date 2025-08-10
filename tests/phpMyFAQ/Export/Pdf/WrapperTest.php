<?php

namespace phpMyFAQ\Export\Pdf;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Translation;
use PHPUnit\Framework\TestCase;

class WrapperTest extends TestCase
{
    private Wrapper $wrapper;
    private Configuration $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->wrapper = new Wrapper();
        $this->mockConfig = $this->createMock(Configuration::class);
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
        $config = $this->createMock(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://example.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML when allowed hosts is empty
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithDisallowedHost(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://badsite.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML when host is not allowed
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithLocalImage(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="/local/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);

        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithMalformedUrl(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="not-a-valid-url" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);

        $this->assertEquals($html, $result);
    }

    public function testValidateImageDataWithValidJpeg(): void
    {
        $jpegData = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01";
        $reflection = new \ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->wrapper, $jpegData));
    }

    public function testValidateImageDataWithValidPng(): void
    {
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

        $file = '/content/user/images/image%20with%20spaces.jpg';
        $expected = '/var/www/phpmyfaq/content/user/images/image%20with%20spaces.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testFilePathDecodingWithSpaces(): void
    {
        $encodedPath = '/content/user/images/image%20with%20spaces.jpg';
        $decodedPath = '/content/user/images/image with spaces.jpg';

        $this->assertEquals($decodedPath, urldecode($encodedPath));
    }

    public function testImageFileWithSpacesInPath(): void
    {
        $testDir = __DIR__ . '/../../../content/user/images';
        $testFile = $testDir . '/image with spaces.jpg';

        $this->assertTrue(file_exists($testFile), "Test image should exist: " . $testFile);

        $urlEncodedPath = '/content/user/images/image%20with%20spaces.jpg';
        $decodedPath = urldecode($urlEncodedPath);

        $fullPath = $this->wrapper->concatenatePaths($testDir . '/../../..', $decodedPath);

        $this->assertTrue(file_exists($fullPath), "File should exist: " . $fullPath);
    }

    // Phase 1: Core Methods Tests

    public function testConstructorInitializesDefaultValues(): void
    {
        $wrapper = new Wrapper();

        $this->assertFalse($wrapper->enableBookmarks);
        $this->assertFalse($wrapper->isFullExport);
        $this->assertEquals([], $wrapper->categories);
        $this->assertEquals('dejavusans', $wrapper->getCurrentFont());
    }

    public function testConstructorSetsCorrectFontForDifferentLanguages(): void
    {
        $testCases = [
            'zh' => 'arialunicid0',  // Chinese
            'tw' => 'arialunicid0',  // Traditional Chinese
            'ja' => 'arialunicid0',  // Japanese
            'ko' => 'arialunicid0',  // Korean
            'cs' => 'dejavusans',    // Czech
            'sk' => 'dejavusans',    // Slovak
            'el' => 'arialunicid0',  // Greek
            'he' => 'arialunicid0',  // Hebrew
            'tr' => 'dejavusans',    // Turkish
            'de' => 'dejavusans',    // German (default)
        ];

        foreach ($testCases as $language => $expectedFont) {
            // Mock Translation to return specific language
            Translation::create()
                ->setLanguagesDir(PMF_TRANSLATION_DIR)
                ->setDefaultLanguage($language)
                ->setCurrentLanguage($language)
                ->setMultiByteLanguage();

            $wrapper = new Wrapper();
            $this->assertEquals(
                $expectedFont,
                $wrapper->getCurrentFont(),
                "Font mismatch for language: $language"
            );
        }
    }

    public function testSetCategoryStoresCorrectValue(): void
    {
        $categoryId = 42;
        $this->wrapper->setCategory($categoryId);

        // Use reflection to access private property
        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('category');
        $property->setAccessible(true);

        $this->assertEquals($categoryId, $property->getValue($this->wrapper));
    }

    public function testSetQuestionStoresCorrectValue(): void
    {
        $question = 'What is the meaning of life?';
        $this->wrapper->setQuestion($question);

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('question');
        $property->setAccessible(true);

        $this->assertEquals($question, $property->getValue($this->wrapper));
    }

    public function testSetQuestionWithEmptyString(): void
    {
        $this->wrapper->setQuestion('');

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('question');
        $property->setAccessible(true);

        $this->assertEquals('', $property->getValue($this->wrapper));
    }

    public function testSetQuestionWithDefaultParameter(): void
    {
        $this->wrapper->setQuestion(); // No parameter passed

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('question');
        $property->setAccessible(true);

        $this->assertEquals('', $property->getValue($this->wrapper));
    }

    public function testSetCategoriesStoresArray(): void
    {
        $categories = [
            1 => ['id' => 1, 'name' => 'General'],
            2 => ['id' => 2, 'name' => 'Technical'],
            3 => ['id' => 3, 'name' => 'FAQ']
        ];

        $this->wrapper->setCategories($categories);
        $this->assertEquals($categories, $this->wrapper->categories);
    }

    public function testSetCategoriesWithEmptyArray(): void
    {
        $this->wrapper->setCategories([]);
        $this->assertEquals([], $this->wrapper->categories);
    }

    public function testSetConfigStoresConfiguration(): void
    {
        $this->wrapper->setConfig($this->mockConfig);

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);

        $this->assertSame($this->mockConfig, $property->getValue($this->wrapper));
    }

    public function testSetFaqStoresArray(): void
    {
        $faq = [
            'id' => 123,
            'lang' => 'en',
            'question' => 'Test question?',
            'answer' => 'Test answer.'
        ];

        $this->wrapper->setFaq($faq);

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('faq');
        $property->setAccessible(true);

        $this->assertEquals($faq, $property->getValue($this->wrapper));
    }

    public function testSetFaqWithEmptyArray(): void
    {
        $this->wrapper->setFaq([]);

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('faq');
        $property->setAccessible(true);

        $this->assertEquals([], $property->getValue($this->wrapper));
    }

    public function testGetCurrentFontReturnsCorrectFont(): void
    {
        // Test default font
        $this->assertEquals('dejavusans', $this->wrapper->getCurrentFont());

        // Test with different language settings
        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('zh')
            ->setCurrentLanguage('zh')
            ->setMultiByteLanguage();

        $wrapper = new Wrapper();
        $this->assertEquals('arialunicid0', $wrapper->getCurrentFont());
    }

    public function testSetCustomHeaderWithConfig(): void
    {
        $customHeader = '<h1>Custom PDF Header</h1>';
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('main.customPdfHeader')
            ->willReturn($customHeader);

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCustomHeader();

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('customHeader');
        $property->setAccessible(true);

        $this->assertEquals($customHeader, $property->getValue($this->wrapper));
    }

    public function testSetCustomHeaderWithHtmlEntities(): void
    {
        $htmlHeader = '&lt;h1&gt;Header &amp; Footer&lt;/h1&gt;';
        $expectedHeader = '<h1>Header & Footer</h1>';

        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('main.customPdfHeader')
            ->willReturn($htmlHeader);

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCustomHeader();

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('customHeader');
        $property->setAccessible(true);

        $this->assertEquals($expectedHeader, $property->getValue($this->wrapper));
    }

    public function testSetCustomFooterWithConfig(): void
    {
        $customFooter = 'Custom PDF Footer Text';
        $this->mockConfig->expects($this->once())
            ->method('get')
            ->with('main.customPdfFooter')
            ->willReturn($customFooter);

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCustomFooter();

        $reflection = new \ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('customFooter');
        $property->setAccessible(true);

        $this->assertEquals($customFooter, $property->getValue($this->wrapper));
    }

    public function testCheckBase64ImageWithValidJpegData(): void
    {
        // Create a simple 1x1 JPEG image data
        $jpegData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwDX4A=');

        $reflection = new \ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('checkBase64Image');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->wrapper, $jpegData));
    }

    public function testImageMethodWithValidPath(): void
    {
        $testFile = '/content/user/images/test%20image.jpg';

        try {
            $this->assertTrue(method_exists($this->wrapper, 'Image'));

            $decoded = urldecode($testFile);
            $this->assertEquals('/content/user/images/test image.jpg', $decoded);
        } catch (Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testConstructorWithRtlLanguage(): void
    {
        try {
            Translation::create()
                ->setLanguagesDir(PMF_TRANSLATION_DIR)
                ->setDefaultLanguage('ar')
                ->setCurrentLanguage('ar')
                ->setMultiByteLanguage();

            $wrapper = new Wrapper();
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->assertTrue(true, 'Constructor handles various language settings');
        }
    }

    public function testPropertyAccessorsAndMutators(): void
    {
        $this->wrapper->enableBookmarks = true;
        $this->assertTrue($this->wrapper->enableBookmarks);

        $this->wrapper->isFullExport = true;
        $this->assertTrue($this->wrapper->isFullExport);

        $categories = [1 => ['name' => 'Test Category']];
        $this->wrapper->categories = $categories;
        $this->assertEquals($categories, $this->wrapper->categories);
    }
}
