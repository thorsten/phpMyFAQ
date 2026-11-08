<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LayoutSettings::class)]
final class LayoutSettingsTest extends TestCase
{
    /**
     * @param array<string, mixed> $values
     */
    private function createSettings(array $values): LayoutSettings
    {
        $configuration = $this->createStub(CoreConfiguration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $values[$item] ?? null);

        return new LayoutSettings($configuration);
    }

    public function testGetTemplateSetReturnsTheConfiguredSet(): void
    {
        $this->assertSame('mytheme', $this->createSettings(['layout.templateSet' => 'mytheme'])->getTemplateSet());
    }

    public function testGetTemplateSetFallsBackToDefault(): void
    {
        $this->assertSame('default', $this->createSettings([])->getTemplateSet());
    }

    public function testGetCustomCssReturnsAnEmptyStringWhenUnset(): void
    {
        $this->assertSame('', $this->createSettings([])->getCustomCss());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validCssProvider(): iterable
    {
        yield 'child combinator' => ['nav > ul > li { color: red; }'];
        yield 'media query range syntax' => ['@media (400px < width < 700px) { body { margin: 0; } }'];
        yield 'content string with angle brackets' => ['a::after { content: "<click>"; }'];
        yield 'attribute selector' => ['input[type="text"] { border: 1px solid #000; }'];
        yield 'the word style on its own' => ['.style { display: none; }'];
    }

    #[DataProvider('validCssProvider')]
    public function testGetCustomCssKeepsValidCssVerbatim(string $css): void
    {
        $this->assertSame($css, $this->createSettings(['layout.customCss' => $css])->getCustomCss());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function breakoutProvider(): iterable
    {
        yield 'plain closing tag' => ['</style><script>alert(1)</script><style>'];
        yield 'upper case closing tag' => ['</STYLE><script>alert(1)</script>'];
        yield 'mixed case closing tag' => ['</StYlE><script>alert(1)</script>'];
        yield 'whitespace before the bracket' => ["</style\n><script>alert(1)</script>"];
        yield 'self closing variant' => ['</style/><script>alert(1)</script>'];
        yield 'truncated at the end of the value' => ['body { color: red; }</style'];
        yield 'reassembled after a single removal' => ['</sty</style>le ><script>alert(1)</script>'];
        yield 'nested twice' => ['</sty</sty</style>le></style >le ><script>alert(1)</script>'];
    }

    #[DataProvider('breakoutProvider')]
    public function testGetCustomCssCannotTerminateTheStyleElement(string $payload): void
    {
        $sanitized = $this->createSettings(['layout.customCss' => $payload])->getCustomCss();

        $this->assertDoesNotMatchRegularExpression('#</style(?=[\s/>])|</style$#i', $sanitized);
    }
}
