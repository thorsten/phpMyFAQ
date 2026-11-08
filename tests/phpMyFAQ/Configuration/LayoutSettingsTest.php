<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LayoutSettingsTest extends TestCase
{
    private function layoutSettings(mixed $customCss): LayoutSettings
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['layout.customCss', $customCss],
            ]);

        return new LayoutSettings($configuration);
    }

    public function testGetCustomCssKeepsPlainCss(): void
    {
        $css = 'body { color: red; }';
        $this->assertSame($css, $this->layoutSettings($css)->getCustomCss());
    }

    public function testGetCustomCssKeepsChildCombinator(): void
    {
        $css = '.parent > .child { margin: 0; }';
        $this->assertSame($css, $this->layoutSettings($css)->getCustomCss());
    }

    public function testGetCustomCssCastsNullToEmptyString(): void
    {
        $this->assertSame('', $this->layoutSettings(null)->getCustomCss());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function xssPayloadProvider(): iterable
    {
        yield 'style breakout script' => ['</style><script>alert(document.domain)</script><style>'];
        yield 'uppercase style breakout' => ['</STYLE><SCRIPT>alert(1)</SCRIPT><STYLE>'];
        yield 'bare script tag' => ['<script>alert(1)</script>'];
        yield 'img onerror' => ['<img src=x onerror=alert(1)>'];
    }

    #[DataProvider('xssPayloadProvider')]
    public function testGetCustomCssStripsHtmlTags(string $payload): void
    {
        $result = $this->layoutSettings($payload)->getCustomCss();

        $this->assertStringNotContainsStringIgnoringCase('<script', $result);
        $this->assertStringNotContainsStringIgnoringCase('</style>', $result);
        $this->assertStringNotContainsString('<', $result);
    }
}
