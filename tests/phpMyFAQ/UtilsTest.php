<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.titleFAQ', 'phpMyFAQ Test');
        $this->configuration->getAll();
    }

    public function testMakeShorterTextWithLongString(): void
    {
        $longString = 'This is a long string that needs to be shortened to fit within a certain number of characters.';
        $words = 10;
        $expectedResult = 'This is a long string that needs to be shortened ...';
        $this->assertEquals($expectedResult, Utils::makeShorterText($longString, $words));
    }

    public function testMakeShorterTextWithShortString(): void
    {
        $shortString = 'This is a short string.';
        $words = 10;
        $expectedResult = 'This is a short string.';
        $this->assertEquals($expectedResult, Utils::makeShorterText($shortString, $words));
    }

    public function testIsLanguageWithValidInput(): void
    {
        $validLang = 'en-US';
        $this->assertTrue(Utils::isLanguage($validLang));
    }

    public function testIsLanguageWithInvalidInput(): void
    {
        $invalidLang = 'en_US';
        $this->assertFalse(Utils::isLanguage($invalidLang));
    }

    public function testIsForbiddenElement(): void
    {
        $this->assertTrue(Utils::isForbiddenElement('img'));
        $this->assertTrue(Utils::isForbiddenElement('picture'));
        $this->assertTrue(Utils::isForbiddenElement('mark'));
        $this->assertFalse(Utils::isForbiddenElement('div'));
    }

    public function testMoveToTop(): void
    {
        // Arrange
        $array = [
            'foo' => 1,
            'bar' => 2,
            'baz' => 3,
        ];
        $key = 'bar';

        // Act
        Utils::moveToTop($array, $key);

        // Assert
        $this->assertEquals([
            'bar' => 2,
            'foo' => 1,
            'baz' => 3,
        ], $array);
    }

    public function testHighlightNoLinks(): void
    {
        $matches = ['<a href="https://example.com">Example</a>', '', ''];
        $result = Utils::highlightNoLinks($matches);
        $expected = '<a href="https://example.com">Example</a>';
        $this->assertEquals($expected, $result);

        $matches = ['', '', '', '', 'Example', ''];
        $result = Utils::highlightNoLinks($matches);
        $expected = '<mark class="pmf-highlighted-string">Example</mark>';
        $this->assertEquals($expected, $result);
    }

    public function testResolveMarkersWithSingleMarker(): void
    {
        $text = 'Welcome to %sitename%';
        $expectedResult = 'Welcome to phpMyFAQ Test';
        $this->assertEquals($expectedResult, Utils::resolveMarkers($text, $this->configuration));
    }

    public function testResolveMarkersWithMultipleMarkers(): void
    {
        $text = 'Hello %username%, this is %sitename%.';
        $expectedResult = 'Hello John, this is phpMyFAQ Test.';
        $textWithMarkers = str_replace('%username%', 'John', $text);
        $resolvedText = Utils::resolveMarkers($textWithMarkers, $this->configuration);
        $this->assertEquals($expectedResult, $resolvedText);
    }

    public function testResolveMarkersWithUnknownMarker(): void
    {
        $text = 'This text contains an unknown %marker%.';
        $expectedResult = 'This text contains an unknown %marker%.';
        $this->assertEquals($expectedResult, Utils::resolveMarkers($text, $this->configuration));
    }

    public function testParseUrl(): void
    {
        $testCases = [
            // Input string, expected output
            ['http://www.example.com', '<a href="https://www.example.com">https://www.example.com</a>'],
            ['https://www.example.com', '<a href="https://www.example.com">https://www.example.com</a>'],
            ['www.example.com', '<a href="https://www.example.com">https://www.example.com</a>'],
            [
                'Lorem ipsum http://www.example.com dolor sit amet',
                'Lorem ipsum <a href="https://www.example.com">https://www.example.com</a> dolor sit amet'
            ],
        ];

        foreach ($testCases as $testCase) {
            $input = $testCase[0];
            $expectedOutput = $testCase[1];

            $this->assertEquals($expectedOutput, Utils::parseUrl($input));
        }
    }

    public function testGetHostFromValidUrl(): void
    {
        $url = 'https://example.com/path?query=param';
        $host = Utils::getHostFromUrl($url);
        $this->assertEquals('example.com', $host);
    }

    public function testGetHostFromUrlWithSubdomain(): void
    {
        $url = 'https://sub.example.com/path';
        $host = Utils::getHostFromUrl($url);
        $this->assertEquals('sub.example.com', $host);
    }

    public function testGetHostFromUrlWithoutScheme(): void
    {
        $url = 'example.com/path';
        $host = Utils::getHostFromUrl($url);
        $this->assertNull($host); // parse_url won't recognize 'example.com' without scheme
    }

    public function testGetHostFromInvalidUrl(): void
    {
        $url = 'not_a_url';
        $host = Utils::getHostFromUrl($url);
        $this->assertNull($host);
    }

    public function testGetHostFromUrlWithoutHost(): void
    {
        $url = 'ftp://user:password@';
        $host = Utils::getHostFromUrl($url);
        $this->assertNull($host);
    }

    public function testChopString(): void
    {
        // Test case 1: String has more words than desired length
        $string1 = 'The quick brown fox jumps over the lazy dog';
        $words1 = 4;
        $expected1 = 'The quick brown fox ';
        $this->assertEquals($expected1, Utils::chopString($string1, $words1));

        // Test case 2: String has exactly the desired length
        $string2 = 'The quick brown fox jumps over the lazy dog';
        $words2 = 9;
        $expected2 = 'The quick brown fox jumps over the lazy dog ';
        $this->assertEquals($expected2, Utils::chopString($string2, $words2));

        // Test case 3: String has less words than desired length
        $string3 = 'The quick brown fox';
        $words3 = 6;
        $expected3 = 'The quick brown fox ';
        $this->assertEquals($expected3, Utils::chopString($string3, $words3));
    }

    public function testSetHighlightedString(): void
    {
        $string = '<p>Some text with a <strong>highlighted</strong> word.</p>';
        $highlight = 'highlighted';

        $expected =
            '<p>Some text with a <strong><mark class="pmf-highlighted-string">highlighted</mark></strong> word.</p>';
        $actual = Utils::setHighlightedString($string, $highlight);

        $this->assertEquals($expected, $actual);
    }

    public function testIsLikeOnPhpMyFaqDateWithValidInput(): void
    {
        $validDate = '%20220301235959%';
        $this->assertTrue(Utils::isLikeOnPMFDate($validDate));
    }

    public function testIsLikeOnPhpMyFaqDateWithInvalidInput(): void
    {
        $invalidDate = '%2022/03/01 23:59:59%';
        $this->assertFalse(Utils::isLikeOnPMFDate($invalidDate));
    }

    public function testFormatBytes(): void
    {
        // Test with bytes less than 1KB
        $this->assertEquals('500 B', Utils::formatBytes(500));
        $this->assertEquals('1023 B', Utils::formatBytes(1023));

        // Test with bytes between 1KB and 1MB
        $this->assertEquals('1 KB', Utils::formatBytes(1024));
        $this->assertEquals('2.5 KB', Utils::formatBytes(2560));
        $this->assertEquals('1023 KB', Utils::formatBytes(1023 * 1024));

        // Test with bytes between 1MB and 1GB
        $this->assertEquals('1 MB', Utils::formatBytes(1024 * 1024));
        $this->assertEquals('2.5 MB', Utils::formatBytes(2560 * 1024));
        $this->assertEquals('1023 MB', Utils::formatBytes(1023 * 1024 * 1024));

        // Test with bytes between 1GB and 1TB
        $this->assertEquals('1 GB', Utils::formatBytes(1024 * 1024 * 1024));
        $this->assertEquals('2.5 GB', Utils::formatBytes(2560 * 1024 * 1024));
        $this->assertEquals('1023 GB', Utils::formatBytes(1023 * 1024 * 1024 * 1024));

        // Test with bytes greater than 1TB
        $this->assertEquals('1 TB', Utils::formatBytes(1024 * 1024 * 1024 * 1024));
        $this->assertEquals('2.5 TB', Utils::formatBytes(2560 * 1024 * 1024 * 1024));
        $this->assertEquals('1023 TB', Utils::formatBytes(1023 * 1024 * 1024 * 1024 * 1024));
    }

    /**
     * Test edge cases and error handling
     */
    public function testEdgeCases(): void
    {
        // Test makeShorterText with empty string
        $this->assertEquals('', Utils::makeShorterText('', 10));

        // Test isLanguage with various inputs
        $this->assertTrue(Utils::isLanguage('en'));
        $this->assertTrue(Utils::isLanguage('en-US'));
        $this->assertFalse(Utils::isLanguage('en_US'));
        $this->assertFalse(Utils::isLanguage('123'));

        // Test isLikeOnPMFDate with various inputs
        $this->assertTrue(Utils::isLikeOnPMFDate('%20220301235959%'));
        $this->assertFalse(Utils::isLikeOnPMFDate('invalid-date'));
    }
}
