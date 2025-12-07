<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    private Configuration $mockConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockConfiguration = $this->createStub(Configuration::class);
    }

    public function testIsLanguageWithValidLanguages(): void
    {
        $this->assertTrue(Utils::isLanguage('en'));
        $this->assertTrue(Utils::isLanguage('de'));
        $this->assertTrue(Utils::isLanguage('en-US'));
        $this->assertTrue(Utils::isLanguage('zh-CN'));
        $this->assertTrue(Utils::isLanguage('pt-BR'));
    }

    public function testIsLanguageWithInvalidLanguages(): void
    {
        $this->assertFalse(Utils::isLanguage('123'));
        $this->assertFalse(Utils::isLanguage('en_US'));
        $this->assertFalse(Utils::isLanguage('en@US'));
        $this->assertFalse(Utils::isLanguage('en US'));
        $this->assertFalse(Utils::isLanguage(''));
        $this->assertFalse(Utils::isLanguage('en#US'));
    }

    public function testIsLikeOnPMFDateWithValidDates(): void
    {
        $this->assertTrue(Utils::isLikeOnPMFDate('20231225143000'));
        $this->assertTrue(Utils::isLikeOnPMFDate('%20231225143000%'));
        $this->assertTrue(Utils::isLikeOnPMFDate('%20231225143000'));
        $this->assertTrue(Utils::isLikeOnPMFDate('20231225143000%'));
        $this->assertTrue(Utils::isLikeOnPMFDate('20240101000000'));
    }

    public function testIsLikeOnPMFDateWithInvalidDates(): void
    {
        $this->assertFalse(Utils::isLikeOnPMFDate('2023-12-25 14:30:00'));
        $this->assertFalse(Utils::isLikeOnPMFDate('20231225abc000'));
        $this->assertFalse(Utils::isLikeOnPMFDate('invalid-date'));
        $this->assertFalse(Utils::isLikeOnPMFDate(''));
        $this->assertFalse(Utils::isLikeOnPMFDate('2023/12/25'));
    }

    public function testMakeShorterTextWithLongString(): void
    {
        $longText = 'This is a very long text that should be shortened to a specific number of words for display purposes';
        $result = Utils::makeShorterText($longText, 5);
        $this->assertEquals('This is a very long ...', $result);
    }

    public function testMakeShorterTextWithShortString(): void
    {
        $shortText = 'Short text here';
        $result = Utils::makeShorterText($shortText, 5);
        $this->assertEquals('Short text here', $result);
    }

    public function testMakeShorterTextWithExactWordCount(): void
    {
        $text = 'Exactly five words here today';
        $result = Utils::makeShorterText($text, 4);
        $this->assertEquals('Exactly five words here ...', $result);
    }

    public function testMakeShorterTextWithMultipleSpaces(): void
    {
        $text = 'Text    with     multiple   spaces    between   words';
        $result = Utils::makeShorterText($text, 4);
        $this->assertEquals('Text with multiple spaces ...', $result);
    }

    public function testMakeShorterTextWithEmptyString(): void
    {
        $result = Utils::makeShorterText('', 5);
        $this->assertEquals('', $result);
    }

    public function testMakeShorterTextWithSingleWord(): void
    {
        $result = Utils::makeShorterText('Word', 5);
        $this->assertEquals('Word', $result);
    }

    public function testResolveMarkersWithSitename(): void
    {
        $this->mockConfiguration->method('getTitle')->willReturn('My FAQ Site');

        $text = 'Welcome to %sitename%!';
        $result = Utils::resolveMarkers($text, $this->mockConfiguration);

        $this->assertEquals('Welcome to My FAQ Site!', $result);
    }

    public function testResolveMarkersWithMultipleMarkers(): void
    {
        $this->mockConfiguration->method('getTitle')->willReturn('Test Site');

        $text = 'Visit %sitename% - the best %sitename% around!';
        $result = Utils::resolveMarkers($text, $this->mockConfiguration);

        $this->assertEquals('Visit Test Site - the best Test Site around!', $result);
    }

    public function testResolveMarkersWithNoMarkers(): void
    {
        $this->mockConfiguration->method('getTitle')->willReturn('Test Site');

        $text = 'This text has no markers';
        $result = Utils::resolveMarkers($text, $this->mockConfiguration);

        $this->assertEquals('This text has no markers', $result);
    }

    public function testResolveMarkersWithEmptyString(): void
    {
        $this->mockConfiguration->method('getTitle')->willReturn('Test Site');

        $result = Utils::resolveMarkers('', $this->mockConfiguration);

        $this->assertEquals('', $result);
    }

    public function testResolveMarkersWithUnknownMarker(): void
    {
        $this->mockConfiguration->method('getTitle')->willReturn('Test Site');

        $text = 'Unknown %unknown% marker';
        $result = Utils::resolveMarkers($text, $this->mockConfiguration);

        $this->assertEquals('Unknown %unknown% marker', $result);
    }
}
