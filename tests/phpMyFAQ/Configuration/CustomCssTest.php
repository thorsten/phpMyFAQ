<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CustomCss::class)]
final class CustomCssTest extends TestCase
{
    /** Matches what an HTML parser accepts as the end of a <style> element. */
    private const string STYLE_TERMINATOR = '#</style(?=[\s/>])|</style$#i';

    public function testNullBecomesAnEmptyString(): void
    {
        $this->assertSame('', (string) new CustomCss(null));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validCssProvider(): iterable
    {
        yield 'plain rule' => ['body { color: red; }'];
        yield 'child combinator' => ['.parent > .child { margin: 0; }'];
        yield 'nested child combinators' => ['nav > ul > li { color: red; }'];
        yield 'media query range syntax' => ['@media (400px < width < 700px) { body { margin: 0; } }'];
        // strip_tags() truncates this one at the "<=", which is why it is not used here.
        yield 'media query with lower or equal' => ['@media (width <= 600px) { .a { display: none; } }'];
        // strip_tags() empties this content string.
        yield 'content string with angle brackets' => ['a::after { content: "<click>"; }'];
        yield 'attribute selector' => ['input[type="text"] { border: 1px solid #000; }'];
        yield 'the word style on its own' => ['.style { display: none; }'];
        yield 'style in a comment' => ['/* the style element */ .a { color: red; }'];
    }

    #[DataProvider('validCssProvider')]
    public function testValidCssIsKeptVerbatim(string $css): void
    {
        $this->assertSame($css, (string) new CustomCss($css));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function breakoutProvider(): iterable
    {
        yield 'style breakout script' => ['</style><script>alert(document.domain)</script><style>'];
        yield 'uppercase style breakout' => ['</STYLE><SCRIPT>alert(1)</SCRIPT><STYLE>'];
        yield 'mixed case style breakout' => ['</StYlE><script>alert(1)</script>'];
        yield 'whitespace before the bracket' => ["</style\n><script>alert(1)</script>"];
        yield 'self closing variant' => ['</style/><script>alert(1)</script>'];
        yield 'truncated at the end of the value' => ['body { color: red; }</style'];
        yield 'reassembled after a single removal' => ['</sty</style>le ><script>alert(1)</script>'];
        yield 'nested twice' => ['</sty</sty</style>le></style >le ><script>alert(1)</script>'];
        yield 'bare script tag' => ['<script>alert(1)</script>'];
        yield 'img onerror' => ['<img src=x onerror=alert(1)>'];
    }

    /**
     * A <style> element is an HTML raw text element: a "<script>" or "<img>" inside it is CSS text
     * and never becomes an element, so the only property that matters is that the value cannot
     * terminate the element. That is why this asserts on "</style" and not on "<".
     */
    #[DataProvider('breakoutProvider')]
    public function testAPayloadCannotTerminateTheStyleElement(string $payload): void
    {
        $this->assertDoesNotMatchRegularExpression(self::STYLE_TERMINATOR, (string) new CustomCss($payload));
    }

    #[DataProvider('breakoutProvider')]
    public function testSanitizingIsIdempotent(string $payload): void
    {
        $once = (string) new CustomCss($payload);

        $this->assertSame($once, (string) new CustomCss($once));
    }

    public function testTheSanitizedValueIsAlsoReadableAsAProperty(): void
    {
        $customCss = new CustomCss('</style><script>alert(1)</script>');

        $this->assertSame($customCss->css, (string) $customCss);
        $this->assertStringNotContainsString('</style>', $customCss->css);
    }
}
