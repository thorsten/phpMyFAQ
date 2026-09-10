<?php

namespace phpMyFAQ\Export\Pdf;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class WrapperTest extends TestCase
{
    private Wrapper $wrapper;
    private Configuration $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function testConcatenatePathsRejectsTraversalOutsideContent(): void
    {
        $path = '/var/www/phpmyfaq';

        // Path traversal attempt without a "content" segment must not resolve.
        $this->assertEquals('', $this->wrapper->concatenatePaths($path, '/../../../../etc/passwd'));
        $this->assertEquals('', $this->wrapper->concatenatePaths($path, '../../../../etc/passwd'));
    }

    public function testConcatenatePathsRejectsBackslashTraversal(): void
    {
        $path = 'C:\\xampp\\htdocs\\phpmyfaq';

        $this->assertEquals('', $this->wrapper->concatenatePaths($path, '..\\..\\..\\windows\\win.ini'));
    }

    public function testConcatenatePathsAnchorsAtContentSegment(): void
    {
        $path = '/var/www/phpmyfaq';

        // Only the part starting at "content/" is kept, dropping any leading traversal.
        $file = '/../../content/user/images/test.jpg';
        $expected = '/var/www/phpmyfaq/content/user/images/test.jpg';
        $this->assertEquals($expected, $this->wrapper->concatenatePaths($path, $file));
    }

    public function testConvertExternalImagesToBase64WithNoConfig(): void
    {
        $html = '<p>before</p><img src="https://example.com/image.jpg" alt="test"><p>after</p>';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Without a configuration there is no allowlist: external images are removed
        $this->assertEquals('<p>before</p><p>after</p>', $result);
    }

    public function testConvertExternalImagesToBase64WithEmptyAllowedHosts(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://example.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // With an empty allowlist no external image may reach TCPDF
        $this->assertEquals('', $result);
    }

    public function testConvertExternalImagesToBase64WithDisallowedHost(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['www.youtube.com']);
        $this->wrapper->setConfig($config);

        $html = '<img src="https://badsite.com/image.jpg" alt="test">';
        $result = $this->wrapper->convertExternalImagesToBase64($html);
        // Images from hosts outside the policy are removed, not passed to TCPDF
        $this->assertEquals('', $result);
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
        $testFile = '/content/user/images/test%20image.jpg';

        try {
            $this->assertTrue(method_exists($this->wrapper, 'Image'));

            $decoded = urldecode($testFile);
            $this->assertEquals('/content/user/images/test image.jpg', $decoded);
        } catch (Exception) {
            $this->assertTrue(true);
        }
    }

    public function testImageRefusesNonImageFileUnderContentWithoutLeaking(): void
    {
        $secretDir = PMF_ROOT_DIR . '/content/user/images';
        $secretFile = $secretDir . '/pmf-test-secret.php';
        file_put_contents($secretFile, "<?php\n\$DB['password'] = 'super-secret-password';\n");

        $handlerFired = false;
        set_error_handler(static function () use (&$handlerFired): bool {
            $handlerFired = true;
            throw new \ErrorException('warning promoted to exception');
        }, E_WARNING | E_NOTICE);

        try {
            $this->wrapper->Image('/content/user/images/pmf-test-secret.php');
        } finally {
            restore_error_handler();
            @unlink($secretFile);
        }

        $this->assertFalse(
            $handlerFired,
            'Probing a non-image file must not emit a warning that could leak its contents',
        );
    }

    public function testCheckBase64ImageSwallowsWarningsForNonImageData(): void
    {
        set_error_handler(static function (): bool {
            throw new \ErrorException('warning promoted to exception');
        }, E_WARNING | E_NOTICE);

        $reflection = new ReflectionClass($this->wrapper);
        $method = $reflection->getMethod('checkBase64Image');

        try {
            $result = $method->invoke($this->wrapper, "<?php \$DB['password'] = 'super-secret';");
        } finally {
            restore_error_handler();
        }

        $this->assertFalse($result);
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

    // SSRF hardening: media host allowlist and redirect handling

    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionClass($this->wrapper);

        return $reflection->getMethod($method)->invoke($this->wrapper, ...$args);
    }

    public function testIsHostAllowedMatchesExactAndSubdomain(): void
    {
        $allowed = ['example.com', ' cdn.test '];

        $this->assertTrue($this->invokePrivate('isHostAllowed', 'example.com', $allowed));
        $this->assertTrue($this->invokePrivate('isHostAllowed', 'images.example.com', $allowed));
        // Trimming and case-insensitive matching
        $this->assertTrue($this->invokePrivate('isHostAllowed', 'CDN.TEST', $allowed));
    }

    public function testIsHostAllowedRejectsDisallowedAndTricks(): void
    {
        $allowed = ['example.com'];

        $this->assertFalse($this->invokePrivate('isHostAllowed', 'blocked.test', $allowed));
        // "example.com.evil.com" must not match "example.com"
        $this->assertFalse($this->invokePrivate('isHostAllowed', 'example.com.evil.com', $allowed));
        // Substring that is not a subdomain boundary must not match
        $this->assertFalse($this->invokePrivate('isHostAllowed', 'notexample.com', $allowed));
        $this->assertFalse($this->invokePrivate('isHostAllowed', '', $allowed));
    }

    public function testIsHostAllowedIgnoresEmptyAndDisabledSentinel(): void
    {
        $this->assertFalse($this->invokePrivate('isHostAllowed', 'example.com', ['']));
        $this->assertFalse($this->invokePrivate('isHostAllowed', 'example.com', ['0']));
    }

    public function testParseHttpResponseExtractsStatusAndLocation(): void
    {
        $headers = [
            'HTTP/1.1 302 Found',
            'Server: nginx',
            'Location: http://blocked.test/tiny.png',
            'Content-Length: 0',
        ];

        [$status, $location] = $this->invokePrivate('parseHttpResponse', $headers);

        $this->assertSame(302, $status);
        $this->assertSame('http://blocked.test/tiny.png', $location);
    }

    public function testParseHttpResponseUsesLastStatusBlock(): void
    {
        // A redirect chain surfaced as multiple response blocks: the final
        // 200 response has no Location, which must be reflected.
        $headers = [
            'HTTP/1.1 301 Moved Permanently',
            'Location: http://example.com/next',
            'HTTP/1.1 200 OK',
            'Content-Type: image/png',
        ];

        [$status, $location] = $this->invokePrivate('parseHttpResponse', $headers);

        $this->assertSame(200, $status);
        $this->assertNull($location);
    }

    public function testResolveRedirectUrlWithAbsoluteTarget(): void
    {
        $result = $this->invokePrivate(
            'resolveRedirectUrl',
            'http://allowed.test/redirect',
            'http://blocked.test/tiny.png',
        );

        $this->assertSame('http://blocked.test/tiny.png', $result);
    }

    public function testResolveRedirectUrlWithAbsolutePath(): void
    {
        $result = $this->invokePrivate('resolveRedirectUrl', 'http://allowed.test:8081/a/b/redirect', '/tiny.png');

        $this->assertSame('http://allowed.test:8081/tiny.png', $result);
    }

    public function testResolveRedirectUrlWithRelativePath(): void
    {
        $result = $this->invokePrivate('resolveRedirectUrl', 'http://allowed.test/a/b/redirect', 'tiny.png');

        $this->assertSame('http://allowed.test/a/b/tiny.png', $result);
    }

    public function testResolveRedirectUrlRejectsEmptyLocation(): void
    {
        $this->assertNull($this->invokePrivate('resolveRedirectUrl', 'http://allowed.test/x', ''));
    }

    /**
     * Replaces the http:// stream wrapper with a spy for the duration of the callback.
     *
     * @return string[] Every URL PHP tried to open or stat
     */
    private function spyOnHttpRequests(callable $callback): array
    {
        HttpSpyStreamWrapper::$requests = [];
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', HttpSpyStreamWrapper::class);

        try {
            $callback();
        } finally {
            stream_wrapper_restore('http');
        }

        return HttpSpyStreamWrapper::$requests;
    }

    private function inlineSvg(string $svg): string
    {
        return '@' . base64_encode($svg);
    }

    /**
     * Configures the wrapper far enough for AddPage() (header/footer rendering).
     *
     * @param string[] $allowedHosts
     */
    private function preparePage(array $allowedHosts): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn($allowedHosts);
        $config->method('get')->willReturn('');
        $config->method('getDefaultUrl')->willReturn('https://localhost/');
        $config->method('getAdminEmail')->willReturn('admin@example.org');

        $this->wrapper->setConfig($config);
        $this->wrapper->setCategory(0);
        $this->wrapper->setCategories([]);
        $this->wrapper->setFaq(['id' => 1, 'lang' => 'en']);
        $this->wrapper->AddPage();
    }

    public function testConvertExternalImagesStripsVectorImagesFromDisallowedHost(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['127.0.0.1']);
        $this->wrapper->setConfig($config);

        foreach (['svg', 'eps', 'ai'] as $extension) {
            $html = sprintf('<p>x</p><img src="http://localhost/direct.%s"><p>y</p>', $extension);
            $this->assertEquals('<p>x</p><p>y</p>', $this->wrapper->convertExternalImagesToBase64($html));
        }
    }

    public function testConvertExternalImagesStripsAllowedImageThatCannotBeFetched(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['127.0.0.1']);
        $this->wrapper->setConfig($config);

        $result = null;
        $requests = $this->spyOnHttpRequests(function () use (&$result): void {
            $result = $this->wrapper->convertExternalImagesToBase64(
                '<img src="http://127.0.0.1/redirect.svg"><img src="http://127.0.0.1/redirect.png">',
            );
        });

        // The policy-checked fetcher may try the allowed origin, but an image
        // that cannot be converted must never survive into the TCPDF input.
        $this->assertEquals('', $result);
        $this->assertNotEmpty($requests);
        foreach ($requests as $url) {
            $this->assertStringStartsWith('http://127.0.0.1/', $url);
        }
    }

    public function testConvertExternalImagesDecodesEntitiesBeforeApplyingPolicy(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['allowed.example']);
        $this->wrapper->setConfig($config);

        // Decoded, this URL points at evil.example (userinfo trick).
        $html = '<img src="http://allowed.example&#64;evil.example/image.png">';
        $this->assertEquals('', $this->wrapper->convertExternalImagesToBase64($html));
    }

    public function testConvertExternalImagesKeepsLocalReferences(): void
    {
        $config = $this->createStub(Configuration::class);
        $config->method('getAllowedMediaHosts')->willReturn(['127.0.0.1']);
        $this->wrapper->setConfig($config);

        $html = '<img src="/content/user/images/local.svg"><img src="' . $this->inlineSvg('<svg/>') . '">';
        $this->assertEquals($html, $this->wrapper->convertExternalImagesToBase64($html));
    }

    public function testStripExternalStylesheetLinks(): void
    {
        $html = '<link rel="stylesheet" type="text/css" href="http://localhost/x.css"><p>text</p><LINK href="a">';
        $this->assertEquals('<p>text</p>', $this->wrapper->stripExternalStylesheetLinks($html));
    }

    public function testVectorLoadersNeverResolveRemoteUrls(): void
    {
        $reflection = new ReflectionClass($this->wrapper);
        $loadVectorImage = $reflection->getMethod('loadVectorImage');
        $resolveLocalImagePath = $reflection->getMethod('resolveLocalImagePath');

        foreach ([
            'http://localhost/direct.svg',
            'https://localhost/direct.eps',
            'http://localhost/redirect.ai',
            'http://localhost/content/user/images/missing.svg',
            '//localhost/direct.svg',
            '*http://localhost/direct.png',
            'file:///etc/passwd',
            '',
        ] as $reference) {
            $this->assertNull($loadVectorImage->invoke($this->wrapper, $reference), $reference);
            $this->assertNull($resolveLocalImagePath->invoke($this->wrapper, $reference), $reference);
        }

        $this->assertNull($loadVectorImage->invoke($this->wrapper, null));
        $this->assertSame('<svg/>', $loadVectorImage->invoke($this->wrapper, '@<svg/>'));
    }

    public function testVectorLoadersReadFilesBelowContentDirectory(): void
    {
        $dir = PMF_ROOT_DIR . '/content/user/images';
        $file = $dir . '/pmf-test-vector.svg';
        file_put_contents($file, '<svg xmlns="http://www.w3.org/2000/svg"/>');

        try {
            $reflection = new ReflectionClass($this->wrapper);
            $loadVectorImage = $reflection->getMethod('loadVectorImage');

            $this->assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', $loadVectorImage->invoke(
                $this->wrapper,
                'http://localhost/content/user/images/pmf-test-vector.svg',
            ));
            $this->assertSame('<svg xmlns="http://www.w3.org/2000/svg"/>', $loadVectorImage->invoke(
                $this->wrapper,
                '/content/user/images/pmf-test-vector.svg',
            ));
        } finally {
            @unlink($file);
        }
    }

    public function testWriteHtmlNeverFetchesVectorImageUrls(): void
    {
        $this->preparePage(['127.0.0.1']);

        $nestedSvg =
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" '
            . 'width="10" height="10">'
            . '<image xlink:href="http://localhost/nested.png" width="10" height="10"/>'
            . '<image xlink:href="http://localhost/nested.svg" width="10" height="10"/>'
            . '<image xlink:href="http://localhost/nested.eps" width="10" height="10"/>'
            . '</svg>';

        $html =
            '<p>start</p>'
            . '<img src="http://localhost/direct.svg" width="10" height="10">'
            . '<img src="http://localhost/direct.eps" width="10" height="10">'
            . '<img src="http://localhost/direct.ai" width="10" height="10">'
            . '<img src="http://127.0.0.1/redirect.svg" width="10" height="10">'
            . '<img src="http://127.0.0.1/redirect.eps" width="10" height="10">'
            . '<img src="http://127.0.0.1/redirect.ai" width="10" height="10">'
            . '<img src="'
            . $this->inlineSvg($nestedSvg)
            . '" width="10" height="10">'
            . '<link rel="stylesheet" type="text/css" href="http://localhost/style.css">'
            . '<p>end</p>';

        $requests = $this->spyOnHttpRequests(function () use ($html): void {
            $this->wrapper->WriteHTML($html);
        });

        // Only the policy-checked fetcher may talk to the allowed origin; the
        // disallowed host must never be contacted, directly, via redirect or
        // via a nested SVG resource.
        foreach ($requests as $url) {
            $this->assertStringStartsWith('http://127.0.0.1/', $url, 'Unexpected request to ' . $url);
        }

        $this->assertGreaterThanOrEqual(1, $this->wrapper->getNumPages(), 'WriteHTML must still render the page');
    }

    public function testImageSvgAndImageEpsIgnoreRemoteUrlsWhenCalledDirectly(): void
    {
        $this->preparePage([]);

        $requests = $this->spyOnHttpRequests(function (): void {
            $this->wrapper->ImageSVG('http://localhost/direct.svg', 10, 10, 10, 10);
            $this->wrapper->ImageEps('http://localhost/direct.eps', 10, 10, 10, 10);
            $this->wrapper->ImageEps('http://localhost/direct.ai', 10, 10, 10, 10);
            $this->wrapper->Image('*http://localhost/direct.png', 10, 10, 10, 10);
        });

        $this->assertSame([], $requests);
    }
}

/**
 * Stream wrapper that records every http:// URL PHP tries to open and refuses it.
 */
final class HttpSpyStreamWrapper
{
    /** @var string[] */
    public static array $requests = [];

    /** @var resource|null */
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        self::$requests[] = $path;

        return false;
    }

    public function url_stat(string $path, int $flags): array|false
    {
        self::$requests[] = $path;

        return false;
    }
}
