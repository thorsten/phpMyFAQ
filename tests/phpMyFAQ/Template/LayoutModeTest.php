<?php

declare(strict_types=1);

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LayoutMode::class)]
class LayoutModeTest extends TestCase
{
    /**
     * @return array<string, array{mixed, ?string}>
     */
    public static function forcedModeProvider(): array
    {
        return [
            'auto does not force' => ['auto', null],
            'missing key does not force' => [null, null],
            'empty value does not force' => ['', null],
            'light' => ['light', 'light'],
            'dark' => ['dark', 'dark'],
            'high contrast' => ['high-contrast', 'high-contrast'],
        ];
    }

    #[DataProvider('forcedModeProvider')]
    public function testForcedModeComesFromTheForceSetting(mixed $force, ?string $expectedForced): void
    {
        $layoutMode = LayoutMode::fromConfiguration($this->configurationWith('dark', 'true', $force));

        self::assertSame($expectedForced, $layoutMode->forced());
        self::assertSame($expectedForced, $layoutMode->toTemplateData()['forcedLayoutMode']);
    }

    public function testDefaultModeAndVisitorChoiceDoNotForceAMode(): void
    {
        $layoutMode = LayoutMode::fromConfiguration($this->configurationWith('light', 'false', 'auto'));

        self::assertNull($layoutMode->forced());
        self::assertSame('light', $layoutMode->default);
        self::assertFalse($layoutMode->allowUserChoice);
    }

    public function testTemplateDataExposesDefaultVisitorChoiceAndForcedMode(): void
    {
        $layoutMode = LayoutMode::fromConfiguration($this->configurationWith('dark', true, 'light'));

        self::assertSame(
            ['defaultLayoutMode' => 'dark', 'allowUserLayoutMode' => true, 'forcedLayoutMode' => 'light'],
            $layoutMode->toTemplateData(),
        );
    }

    public function testMissingDefaultModeIsAuto(): void
    {
        self::assertSame('auto', LayoutMode::fromConfiguration($this->configurationWith(null, null, null))->default);
    }

    public function testInitialThemePrefersForcedThenDefaultThenFallback(): void
    {
        self::assertSame('light', LayoutMode::fromConfiguration($this->configurationWith('auto', 'true', 'auto'))->initialTheme());
        self::assertSame(
            'dark',
            LayoutMode::fromConfiguration($this->configurationWith('auto', 'true', 'auto'))->initialTheme('dark'),
        );
        self::assertSame(
            'high-contrast',
            LayoutMode::fromConfiguration($this->configurationWith('high-contrast', 'true', 'auto'))->initialTheme('dark'),
        );
        self::assertSame(
            'light',
            LayoutMode::fromConfiguration($this->configurationWith('high-contrast', 'true', 'light'))->initialTheme('dark'),
        );
    }

    private function configurationWith(mixed $default, mixed $allowUserChoice, mixed $force): Configuration
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnMap([
            ['layout.defaultLayoutMode', $default],
            ['layout.allowUserLayoutMode', $allowUserChoice],
            ['layout.forceLayoutMode', $force],
        ]);

        return $configuration;
    }
}
