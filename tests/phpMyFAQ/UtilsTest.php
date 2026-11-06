<?php

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
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

    public function testParseUrlWithSimpleUrl(): void
    {
        $result = Utils::parseUrl('Visit https://www.example.com for info');
        $this->assertStringContainsString('<a href="https://www.example.com">https://www.example.com</a>', $result);
    }

    public function testParseUrlEscapesDoubleQuotesInUrl(): void
    {
        $result = Utils::parseUrl('https://www.evil.com/"onmouseover="alert(1)');
        $this->assertStringNotContainsString('" onmouseover="', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testParseUrlStopsUrlAtTagStart(): void
    {
        $result = Utils::parseUrl('https://www.evil.com/<br />next');
        $this->assertStringContainsString(
            '<a href="https://www.evil.com/">https://www.evil.com/</a><br />next',
            $result,
        );
    }

    public function testParseUrlEscapesSingleQuotesInUrl(): void
    {
        $result = Utils::parseUrl("https://www.evil.com/'onmouseover='alert(1)");
        $this->assertStringNotContainsString("' onmouseover='", $result);
        $this->assertStringContainsString('&#039;', $result);
    }

    public function testParseUrlDoesNotRewriteExistingHrefAttributes(): void
    {
        $input = '<a href="https://javascript:443/alert(document.domain)">Click for the fix</a>';
        $result = Utils::parseUrl($input);
        $this->assertStringNotContainsString('href="javascript:', $result);
        $this->assertSame($input, $result);
    }

    public function testParseUrlKeepsAbsoluteUrlScheme(): void
    {
        $result = Utils::parseUrl('See https://example.com/x now');
        $this->assertStringContainsString('<a href="https://example.com/x">https://example.com/x</a>', $result);
        $this->assertStringNotContainsString('>example.com/x<', $result);
    }

    public function testParseUrlLinkifiesBareWwwUrl(): void
    {
        $result = Utils::parseUrl('Go to www.example.com today');
        $this->assertStringContainsString('<a href="https://www.example.com">www.example.com</a>', $result);
    }

    public function testParseUrlDoesNotTouchUrlsInsideTags(): void
    {
        $input = '<a href="https://example.com">https://example.com</a> and <br /> text';
        $result = Utils::parseUrl($input);
        $this->assertSame($input, $result);
    }

    public function testRedactSensitiveQueryStringRedactsResetToken(): void
    {
        $result = Utils::redactSensitiveQueryString(
            'action=resetpw&u=1&exp=1785766994&sig=308f52b5546849fae74be8ab15164c8c',
        );

        $this->assertStringContainsString('action=resetpw', $result);
        $this->assertStringNotContainsString('308f52b5546849fae74be8ab15164c8c', $result);
        $this->assertStringNotContainsString('sig=308f52b5', $result);
        $this->assertStringContainsString('sig=[redacted]', $result);
        $this->assertStringContainsString('u=[redacted]', $result);
        $this->assertStringContainsString('exp=[redacted]', $result);
    }

    public function testRedactSensitiveQueryStringIsCaseInsensitive(): void
    {
        $result = Utils::redactSensitiveQueryString('Sig=abcdef&TOKEN=secretvalue');

        $this->assertStringNotContainsString('abcdef', $result);
        $this->assertStringNotContainsString('secretvalue', $result);
        $this->assertStringContainsString('Sig=[redacted]', $result);
        $this->assertStringContainsString('TOKEN=[redacted]', $result);
    }

    public function testRedactSensitiveQueryStringKeepsHarmlessParameters(): void
    {
        $result = Utils::redactSensitiveQueryString('action=faq&cat=3&id=42');

        $this->assertSame('action=faq&cat=3&id=42', $result);
    }

    public function testRedactSensitiveQueryStringHandlesEmptyString(): void
    {
        $this->assertSame('', Utils::redactSensitiveQueryString(''));
    }

    public function testRedactSensitiveUrlRedactsQueryButKeepsPath(): void
    {
        $result = Utils::redactSensitiveUrl('https://example.org/index.php?action=resetpw&sig=deadbeef');

        $this->assertStringStartsWith('https://example.org/index.php?', $result);
        $this->assertStringNotContainsString('deadbeef', $result);
        $this->assertStringContainsString('sig=[redacted]', $result);
    }

    public function testRedactSensitiveUrlLeavesUrlWithoutQueryUnchanged(): void
    {
        $url = 'https://example.org/index.php';

        $this->assertSame($url, Utils::redactSensitiveUrl($url));
    }
}
