<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LayoutSettings::class)]
#[UsesClass(CustomCss::class)]
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

    public function testGetCustomCssCastsAMissingValueToAnEmptyString(): void
    {
        $this->assertSame('', $this->createSettings([])->getCustomCss());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validCssProvider(): iterable
    {
        yield 'plain rule' => ['body { color: red; }'];
        yield 'child combinator' => ['.parent > .child { margin: 0; }'];
        yield 'media query range syntax' => ['@media (400px < width < 700px) { body { margin: 0; } }'];
    }

    #[DataProvider('validCssProvider')]
    public function testGetCustomCssKeepsValidCssVerbatim(string $css): void
    {
        $this->assertSame($css, $this->createSettings(['layout.customCss' => $css])->getCustomCss());
    }

    /**
     * The stored value is sanitized on read as well as on write, so a payload that predates the
     * write-side check cannot break out of the <style> element either. CustomCssTest covers the
     * individual payloads; this only proves that the read path applies it.
     */
    public function testGetCustomCssSanitizesAValueAlreadyStoredInTheDatabase(): void
    {
        $customCss = $this->createSettings([
            'layout.customCss' => '</style><script>alert(document.domain)</script><style>',
        ])->getCustomCss();

        $this->assertDoesNotMatchRegularExpression('#</style(?=[\s/>])|</style$#i', $customCss);
    }
}
