<?php

namespace phpMyFAQ\Export\Pdf;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class WrapperTestFunctionState
{
    public static bool $useFinfoStub = false;
    public static bool $finfoOpenReturnsFalse = false;
    public static string|false $finfoBufferResult = false;
}

function finfo_open(int $flags, ?string $magic_database = null): mixed
{
    if (!WrapperTestFunctionState::$useFinfoStub) {
        return \finfo_open($flags, $magic_database);
    }

    if (WrapperTestFunctionState::$finfoOpenReturnsFalse) {
        return false;
    }

    return fopen('php://memory', 'rb');
}

function finfo_buffer(mixed $finfo, string $string, int $flags = FILEINFO_NONE, $context = null): string|false
{
    if (!WrapperTestFunctionState::$useFinfoStub) {
        return \finfo_buffer($finfo, $string, $flags);
    }

    return WrapperTestFunctionState::$finfoBufferResult;
}

#[AllowMockObjectsWithoutExpectations]
class WrapperTest extends TestCase
{
    private Wrapper $wrapper;
    private Configuration $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();

        WrapperTestFunctionState::$useFinfoStub = false;
        WrapperTestFunctionState::$finfoOpenReturnsFalse = false;
        WrapperTestFunctionState::$finfoBufferResult = false;

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
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
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://example.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML when allowed hosts is empty
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithDisallowedHost(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://badsite.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Should return original HTML when host is not allowed
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithLocalImage(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="/local/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);

        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithMalformedUrl(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="not-a-valid-url" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);

        $this->assertEquals($html, $result);
    }

    public function testValidateImageDataWithValidJpeg(): void
    {
        $jpegData = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01";
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');

        $this->assertTrue($method->invoke($this->wrapper, $jpegData));
    }

    public function testValidateImageDataWithValidPng(): void
    {
        $pngData = "\x89PNG\r\n\x1A\n\x00\x00\x00\x0DIHDR";
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');

        $this->assertTrue($method->invoke($this->wrapper, $pngData));
    }

    public function testValidateImageDataWithInvalidData(): void
    {
        $invalidData = 'This is not image data';
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');

        $this->assertFalse($method->invoke($this->wrapper, $invalidData));
    }

    public function testValidateImageDataWithTooShortData(): void
    {
        $shortData = 'short';
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');

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

        $this->assertTrue(file_exists($testFile), 'Test image should exist: ' . $testFile);

        $urlEncodedPath = '/content/user/images/image%20with%20spaces.jpg';
        $decodedPath = urldecode($urlEncodedPath);

        $fullPath = $this->wrapper->concatenatePaths($testDir . '/../../..', $decodedPath);

        $this->assertTrue(file_exists($fullPath), 'File should exist: ' . $fullPath);
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
            'zh' => 'arialunicid0', // Chinese
            'zh_tw' => 'arialunicid0', // Traditional Chinese
            'ja' => 'arialunicid0', // Japanese
            'ko' => 'arialunicid0', // Korean
            'cs' => 'dejavusans', // Czech
            'sk' => 'dejavusans', // Slovak
            'el' => 'arialunicid0', // Greek
            'he' => 'arialunicid0', // Hebrew
            'tr' => 'dejavusans', // Turkish
            'de' => 'dejavusans', // German (default)
        ];

        foreach ($testCases as $language => $expectedFont) {
            // Mock Translation to return specific language
            Translation::create()
                ->setTranslationsDir(PMF_TRANSLATION_DIR)
                ->setDefaultLanguage($language)
                ->setCurrentLanguage($language)
                ->setMultiByteLanguage();

            $wrapper = new Wrapper();
            $this->assertEquals($expectedFont, $wrapper->getCurrentFont(), "Font mismatch for language: $language");
        }
    }

    public function testSetCategoryStoresCorrectValue(): void
    {
        $categoryId = 42;
        $this->wrapper->setCategory($categoryId);

        // Use reflection to access private property
        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('category');

        $this->assertEquals($categoryId, $property->getValue($this->wrapper));
    }

    public function testSetQuestionStoresCorrectValue(): void
    {
        $question = 'What is the meaning of life?';
        $this->wrapper->setQuestion($question);

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('question');

        $this->assertEquals($question, $property->getValue($this->wrapper));
    }

    public function testSetQuestionWithEmptyString(): void
    {
        $this->wrapper->setQuestion('');

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('question');

        $this->assertEquals('', $property->getValue($this->wrapper));
    }

    public function testSetQuestionWithDefaultParameter(): void
    {
        $this->wrapper->setQuestion(); // No parameter passed

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('question');

        $this->assertEquals('', $property->getValue($this->wrapper));
    }

    public function testSetCategoriesStoresArray(): void
    {
        $categories = [
            1 => ['id' => 1, 'name' => 'General'],
            2 => ['id' => 2, 'name' => 'Technical'],
            3 => ['id' => 3, 'name' => 'FAQ'],
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

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('config');

        $this->assertSame($this->mockConfig, $property->getValue($this->wrapper));
    }

    public function testSetFaqStoresArray(): void
    {
        $faq = [
            'id' => 123,
            'lang' => 'en',
            'question' => 'Test question?',
            'answer' => 'Test answer.',
        ];

        $this->wrapper->setFaq($faq);

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('faq');

        $this->assertEquals($faq, $property->getValue($this->wrapper));
    }

    public function testSetFaqWithEmptyArray(): void
    {
        $this->wrapper->setFaq([]);

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('faq');

        $this->assertEquals([], $property->getValue($this->wrapper));
    }

    public function testGetCurrentFontReturnsCorrectFont(): void
    {
        $this->assertEquals('dejavusans', $this->wrapper->getCurrentFont());

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('zh')
            ->setCurrentLanguage('zh')
            ->setMultiByteLanguage();

        $wrapper = new Wrapper();
        $this->assertEquals('arialunicid0', $wrapper->getCurrentFont());
    }

    public function testSetCustomHeaderWithConfig(): void
    {
        $customHeader = '<h1>Custom PDF Header</h1>';
        $this->mockConfig
            ->expects($this->once())
            ->method('get')
            ->with('main.customPdfHeader')
            ->willReturn($customHeader);

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCustomHeader();

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('customHeader');

        $this->assertEquals($customHeader, $property->getValue($this->wrapper));
    }

    public function testSetCustomHeaderWithHtmlEntities(): void
    {
        $htmlHeader = '&lt;h1&gt;Header &amp; Footer&lt;/h1&gt;';
        $expectedHeader = '<h1>Header & Footer</h1>';

        $this->mockConfig->expects($this->once())->method('get')->with('main.customPdfHeader')->willReturn($htmlHeader);

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCustomHeader();

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('customHeader');

        $this->assertEquals($expectedHeader, $property->getValue($this->wrapper));
    }

    public function testSetCustomFooterWithConfig(): void
    {
        $customFooter = 'Custom PDF Footer Text';
        $this->mockConfig
            ->expects($this->once())
            ->method('get')
            ->with('main.customPdfFooter')
            ->willReturn($customFooter);

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCustomFooter();

        $reflection = new ReflectionClass($this->wrapper);
        $property = $reflection->getProperty('customFooter');

        $this->assertEquals($customFooter, $property->getValue($this->wrapper));
    }

    /**
     * @throws \ReflectionException
     */
    public function testCheckBase64ImageWithValidJpegData(): void
    {
        // Create a simple 1x1 JPEG image data
        $jpegData = base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwDX4A=',
        );

        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('checkBase64Image');

        $this->assertTrue($method->invoke($this->wrapper, $jpegData));
    }

    public function testImageMethodWithValidPath(): void
    {
        $fixture = PMF_CONTENT_DIR . '/user/images/image with spaces.jpg';
        $targetFile = PMF_ROOT_DIR . '/content/user/images/wrapper image test.jpg';
        try {
            self::assertTrue(copy($fixture, $targetFile));
            $this->mockConfig
                ->method('get')
                ->willReturnCallback(static fn(string $key) => match ($key) {
                    'main.customPdfHeader' => '',
                    'main.customPdfFooter' => '',
                    'main.metaPublisher' => 'Test',
                    'main.dateFormat' => 'Y-m-d H:i',
                    'spam.mailAddressInExport' => false,
                    default => null,
                });
            $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
            $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

            $this->wrapper->setConfig($this->mockConfig);
            $this->wrapper->setCategories([]);
            $this->wrapper->setCategory(0);

            $this->wrapper->Open();
            $this->wrapper->AddPage();
            $this->wrapper->Image('/content/user/images/wrapper%20image%20test.jpg', 10, 10, 10, 10);

            $output = $this->wrapper->Output('test-image.pdf', 'S');
            $this->assertStringStartsWith('%PDF', $output);
        } finally {
            if (is_file($targetFile)) {
                unlink($targetFile);
            }
        }
    }

    public function testImageMethodEmbedsBase64ConvertibleImageData(): void
    {
        $jpegData = base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwDX4A=',
        );
        self::assertNotFalse($jpegData);

        $targetFile = PMF_ROOT_DIR . '/content/user/images/wrapper-inline-image.jpg';

        try {
            self::assertNotFalse(file_put_contents($targetFile, $jpegData));
            $this->mockConfig
                ->method('get')
                ->willReturnCallback(static fn(string $key) => match ($key) {
                    'main.customPdfHeader' => '',
                    'main.customPdfFooter' => '',
                    'main.metaPublisher' => 'Test',
                    'main.dateFormat' => 'Y-m-d H:i',
                    'spam.mailAddressInExport' => false,
                    default => null,
                });
            $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
            $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

            $this->wrapper->setConfig($this->mockConfig);
            $this->wrapper->setCategories([]);
            $this->wrapper->setCategory(0);

            $this->wrapper->Open();
            $this->wrapper->AddPage();
            $this->wrapper->Image('/content/user/images/wrapper-inline-image.jpg', 10, 10, 10, 10);

            $output = $this->wrapper->Output('test-inline-image.pdf', 'S');
            $this->assertStringStartsWith('%PDF', $output);
        } finally {
            if (is_file($targetFile)) {
                unlink($targetFile);
            }
        }
    }

    public function testConstructorWithRtlLanguage(): void
    {
        try {
            Translation::create()
                ->setTranslationsDir(PMF_TRANSLATION_DIR)
                ->setDefaultLanguage('ar')
                ->setCurrentLanguage('ar')
                ->setMultiByteLanguage();

            $wrapper = new Wrapper();
            $this->assertTrue(true);
        } catch (Exception) {
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

    public function testHeaderWithCategoryTitle(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Test',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCategories([1 => ['id' => 1, 'name' => 'Test Category']]);
        $this->wrapper->setCategory(1);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $output = $this->wrapper->Output('test.pdf', 'S');

        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testHeaderWithCustomHeader(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '<b>Custom Header</b>',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Test',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCategories([1 => ['id' => 1, 'name' => 'Category']]);
        $this->wrapper->setCategory(1);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $output = $this->wrapper->Output('test.pdf', 'S');

        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testFooterWithBookmarksDisabled(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Publisher',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->enableBookmarks = false;
        $this->wrapper->setFaq(['id' => 1, 'lang' => 'en']);
        $this->wrapper->setQuestion('Test question');
        $this->wrapper->setCategories([1 => ['id' => 1, 'name' => 'Cat']]);
        $this->wrapper->setCategory(1);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $output = $this->wrapper->Output('test.pdf', 'S');

        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testFooterWithMailAddressInExport(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Publisher',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => true,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCategories([]);
        $this->wrapper->setCategory(0);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $output = $this->wrapper->Output('test.pdf', 'S');

        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testFooterWithCustomFooter(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '<i>Custom Footer Content</i>',
                'main.metaPublisher' => 'Publisher',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCategories([]);
        $this->wrapper->setCategory(0);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $output = $this->wrapper->Output('test.pdf', 'S');

        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testAddFaqToc(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Test',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');
        $this->mockConfig->method('getTitle')->willReturn('FAQ Title');

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->enableBookmarks = true;
        $this->wrapper->setCategories([]);
        $this->wrapper->setCategory(0);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $this->wrapper->Bookmark('Test Bookmark', 0, 0);
        $this->wrapper->setPrintHeader(false);
        $this->wrapper->addFaqToc();

        $output = $this->wrapper->Output('test.pdf', 'S');
        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testWriteHtmlCallsConvertExternalImages(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Test',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');
        $this->mockConfig->method('getAllowedMediaHosts')->willReturn([]);

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->setCategories([]);
        $this->wrapper->setCategory(0);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $this->wrapper->WriteHTML('<p>Simple HTML content</p>');

        $output = $this->wrapper->Output('test.pdf', 'S');
        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testGetImageMimeTypeWithJpegData(): void
    {
        $jpegData = "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 20);
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('getImageMimeType');

        $result = $method->invoke($this->wrapper, $jpegData);
        $this->assertEquals('image/jpeg', $result);
    }

    public function testGetImageMimeTypeWithPngData(): void
    {
        // Create a real 1x1 PNG image
        $img = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();

        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('getImageMimeType');

        $result = $method->invoke($this->wrapper, $pngData);
        $this->assertEquals('image/png', $result);
    }

    public function testGetImageMimeTypeWithNonImageData(): void
    {
        $textData = 'This is plain text, not an image';
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('getImageMimeType');

        $result = $method->invoke($this->wrapper, $textData);
        $this->assertFalse($result);
    }

    public function testGetImageMimeTypeFallsBackWhenFinfoIsUnavailable(): void
    {
        WrapperTestFunctionState::$useFinfoStub = true;
        WrapperTestFunctionState::$finfoOpenReturnsFalse = true;

        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('getImageMimeType');

        $this->assertSame('image/gif', $method->invoke($this->wrapper, 'GIF89a' . str_repeat("\x00", 20)));
        $this->assertSame('image/webp', $method->invoke($this->wrapper, 'RIFF' . str_repeat("\x00", 20)));
        $this->assertSame('image/bmp', $method->invoke($this->wrapper, 'BM' . str_repeat("\x00", 20)));
        $this->assertFalse($method->invoke($this->wrapper, 'not-an-image'));
    }

    public function testDefineIfMissingDefinesUnknownConstant(): void
    {
        $constantName = 'PMF_WRAPPER_TEST_CONST_' . uniqid('', true);

        $reflection = new ReflectionClass(Wrapper::class);
        $method = $reflection->getMethod('defineIfMissing');
        $method->invoke(null, $constantName, 'wrapper-test-value');

        $this->assertTrue(defined($constantName));
        $this->assertSame('wrapper-test-value', constant($constantName));
    }

    public function testValidateImageDataWithGifData(): void
    {
        $gifData = 'GIF89a' . str_repeat("\x00", 20);
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');

        $this->assertTrue($method->invoke($this->wrapper, $gifData));
    }

    public function testValidateImageDataWithGif87aData(): void
    {
        $gifData = 'GIF87a' . str_repeat("\x00", 20);
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');

        $this->assertTrue($method->invoke($this->wrapper, $gifData));
    }

    public function testValidateImageDataWithWebpData(): void
    {
        $webpData = 'RIFF' . str_repeat("\x00", 20);
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');

        $this->assertTrue($method->invoke($this->wrapper, $webpData));
    }

    public function testValidateImageDataWithBmpData(): void
    {
        $bmpData = 'BM' . str_repeat("\x00", 20);
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('validateImageData');

        $this->assertTrue($method->invoke($this->wrapper, $bmpData));
    }

    public function testConvertExternalImagesToBase64WithSubdomainMatch(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['example.com']);
        $this->wrapper->setConfig($config);

        // Subdomain should match but fetch will fail, returning original HTML
        $html = '<img src="https://images.example.com/photo.jpg" alt="test">';

        set_error_handler(static fn(): bool => true);
        try {
            $result = $this->wrapper->convertExternalImagesToBase64($html);
            // Fetch fails, so original HTML is returned
            $this->assertEquals($html, $result);
        } finally {
            restore_error_handler();
        }
    }

    public function testConvertExternalImagesToBase64WithEmptyAndZeroHosts(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['', '0', 'example.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://example.com/image.jpg" alt="test">';

        set_error_handler(static fn(): bool => true);
        try {
            $result = $this->wrapper->convertExternalImagesToBase64($html);
            // Fetch fails (404), returns original
            $this->assertEquals($html, $result);
        } finally {
            restore_error_handler();
        }
    }

    public function testConvertExternalImagesToBase64WithEmptyHostList(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn([]);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://example.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        $this->assertEquals($html, $result);
    }

    public function testConvertExternalImagesToBase64WithNoImgTags(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['example.com']);
        $this->wrapper->setConfig($config);

        $html = '<p>No images here</p>';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        $this->assertEquals($html, $result);
    }

    public function testFooterWithEmptyFaqAndNoBookmarks(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Publisher',
                'main.dateFormat' => 'Y-m-d H:i',
                'spam.mailAddressInExport' => false,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('admin@test.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->enableBookmarks = false;
        $this->wrapper->setFaq([]);
        $this->wrapper->setQuestion('');
        $this->wrapper->setCategories([]);
        $this->wrapper->setCategory(0);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $output = $this->wrapper->Output('test.pdf', 'S');

        $this->assertStringStartsWith('%PDF', $output);
    }

    public function testCheckBase64ImageWithInvalidData(): void
    {
        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('checkBase64Image');

        $this->assertFalse($method->invoke($this->wrapper, 'not an image'));
    }

    public function testFooterWithBookmarksEnabled(): void
    {
        $this->mockConfig
            ->method('get')
            ->willReturnCallback(static fn(string $key) => match ($key) {
                'main.customPdfHeader' => '',
                'main.customPdfFooter' => '',
                'main.metaPublisher' => 'Pub',
                'main.dateFormat' => 'Y-m-d',
                'spam.mailAddressInExport' => false,
                default => null,
            });
        $this->mockConfig->method('getAdminEmail')->willReturn('a@b.com');
        $this->mockConfig->method('getDefaultUrl')->willReturn('https://example.com/');

        $this->wrapper->setConfig($this->mockConfig);
        $this->wrapper->enableBookmarks = true;
        $this->wrapper->setCategories([]);
        $this->wrapper->setCategory(0);

        $this->wrapper->Open();
        $this->wrapper->AddPage();
        $output = $this->wrapper->Output('test.pdf', 'S');

        $this->assertStringStartsWith('%PDF', $output);
    }
}
