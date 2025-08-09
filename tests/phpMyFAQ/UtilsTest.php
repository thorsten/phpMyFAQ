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

    /**
     * Test isLikeOnPMFDate with valid date formats
     */
    public function testIsLikeOnPMFDateWithValidDates(): void
    {
        $validDates = [
            '20250809123000',
            '%20250809123000%',
            '20250809123000%',
            '%20250809123000'
        ];

        foreach ($validDates as $date) {
            $this->assertTrue(Utils::isLikeOnPMFDate($date), "Failed for date: $date");
        }
    }

    /**
     * Test isLikeOnPMFDate with invalid date formats
     */
    public function testIsLikeOnPMFDateWithInvalidDates(): void
    {
        $invalidDates = [
            '2025-08-09 12:30:00',
            'invalid-date',
            '2025/08/09',
            'abc123def456',
            ''
        ];

        foreach ($invalidDates as $date) {
            $this->assertFalse(Utils::isLikeOnPMFDate($date), "Failed for date: $date");
        }
    }

    /**
     * Test resolveMarkers method
     */
    public function testResolveMarkers(): void
    {
        $text = 'Welcome to %sitename%! This is %sitename% FAQ system.';
        $result = Utils::resolveMarkers($text, $this->configuration);

        $expected = 'Welcome to phpMyFAQ Test! This is phpMyFAQ Test FAQ system.';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test resolveMarkers with no markers
     */
    public function testResolveMarkersWithNoMarkers(): void
    {
        $text = 'This text has no markers.';
        $result = Utils::resolveMarkers($text, $this->configuration);

        $this->assertEquals($text, $result);
    }

    /**
     * Test chopString method
     */
    public function testChopString(): void
    {
        $string = 'This is a test string with many words';
        $result = Utils::chopString($string, 4);

        $this->assertEquals('This is a test ', $result);
    }

    /**
     * Test chopString with more words than available
     */
    public function testChopStringWithMoreWordsThanAvailable(): void
    {
        $string = 'Short string';
        $result = Utils::chopString($string, 10);

        $this->assertEquals('Short string ', $result);
    }

    /**
     * Test chopString with zero words
     */
    public function testChopStringWithZeroWords(): void
    {
        $string = 'This is a test';
        $result = Utils::chopString($string, 0);

        $this->assertEquals('', $result);
    }

    /**
     * Test setHighlightedString method
     */
    public function testSetHighlightedString(): void
    {
        $string = 'This is a test string with search term';
        $highlight = 'test';
        $result = Utils::setHighlightedString($string, $highlight);

        $this->assertStringContainsString('<mark class="pmf-highlighted-string">test</mark>', $result);
    }

    /**
     * Test setHighlightedString with no matches
     */
    public function testSetHighlightedStringWithNoMatches(): void
    {
        $string = 'This is a sample string';
        $highlight = 'notfound';
        $result = Utils::setHighlightedString($string, $highlight);

        $this->assertEquals($string, $result);
    }

    /**
     * Test isForbiddenElement method
     */
    public function testIsForbiddenElement(): void
    {
        $forbiddenElements = ['img', 'picture', 'mark'];

        foreach ($forbiddenElements as $element) {
            $this->assertTrue(Utils::isForbiddenElement($element));
        }
    }

    /**
     * Test isForbiddenElement with allowed elements
     */
    public function testIsForbiddenElementWithAllowedElements(): void
    {
        $allowedElements = ['div', 'span', 'h1', 'strong', 'bold'];

        foreach ($allowedElements as $element) {
            $this->assertFalse(Utils::isForbiddenElement($element));
        }
    }

    /**
     * Test highlightNoLinks callback method
     */
    public function testHighlightNoLinks(): void
    {
        $matches = ['', '', '', 'test', 'content'];
        $result = Utils::highlightNoLinks($matches);

        $this->assertEquals('<mark class="pmf-highlighted-string">testcontent</mark>', $result);
    }

    /**
     * Test highlightNoLinks with forbidden element
     */
    public function testHighlightNoLinksWithForbiddenElement(): void
    {
        $matches = ['original', '', '', 'img', ''];
        $result = Utils::highlightNoLinks($matches);

        $this->assertEquals('original', $result);
    }

    /**
     * Test makeShorterText with multiple spaces
     */
    public function testMakeShorterTextWithMultipleSpaces(): void
    {
        $string = 'This  is   a    string   with     multiple spaces';
        $result = Utils::makeShorterText($string, 5);

        $this->assertEquals('This is a string with ...', $result);
    }

    /**
     * Test isLanguage with various valid formats
     */
    public function testIsLanguageWithVariousValidFormats(): void
    {
        $validLanguages = [
            'en',
            'de',
            'en-US',
            'en-GB',
            'zh-CN',
            'pt-BR',
            'fr-CA'
        ];

        foreach ($validLanguages as $lang) {
            $this->assertTrue(Utils::isLanguage($lang), "Failed for language: $lang");
        }
    }

    /**
     * Test isLanguage with invalid formats
     */
    public function testIsLanguageWithInvalidFormats(): void
    {
        $invalidLanguages = [
            'en_US',
            '123',
            'en@US',
            'en.US',
            'en US',
            'en/US',
            ''
        ];

        foreach ($invalidLanguages as $lang) {
            $this->assertFalse(Utils::isLanguage($lang), "Failed for language: $lang");
        }
    }
}
