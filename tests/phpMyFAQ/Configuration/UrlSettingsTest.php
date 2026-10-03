<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration as CoreConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlSettings::class)]
final class UrlSettingsTest extends TestCase
{
    /**
     * @param array<string, mixed> $values
     */
    private function createSettings(array $values): UrlSettings
    {
        $configuration = $this->createStub(CoreConfiguration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $values[$item] ?? null);

        return new UrlSettings($configuration);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function defaultUrlProvider(): iterable
    {
        yield 'already slash-terminated' => ['https://faq.example.org/', 'https://faq.example.org/'];
        yield 'slash is appended' => ['https://faq.example.org', 'https://faq.example.org/'];
        yield 'sub directory' => ['https://example.org/faq', 'https://example.org/faq/'];
        yield 'missing value' => [null, '/'];
    }

    #[DataProvider('defaultUrlProvider')]
    public function testGetDefaultUrlAlwaysEndsWithASlash(mixed $configured, string $expected): void
    {
        $this->assertSame($expected, $this->createSettings(['main.referenceURL' => $configured])->getDefaultUrl());
    }

    public function testGetAllowedMediaHostsSplitsCommaSeparatedList(): void
    {
        $settings = $this->createSettings(['records.allowedMediaHosts' => 'youtube.com,vimeo.com']);

        $this->assertSame(['youtube.com', 'vimeo.com'], $settings->getAllowedMediaHosts());
    }

    public function testGetAllowedMediaHostsReturnsEmptyListWhenNotConfigured(): void
    {
        $this->assertSame([], $this->createSettings([])->getAllowedMediaHosts());
        $this->assertSame([], $this->createSettings(['records.allowedMediaHosts' => ''])->getAllowedMediaHosts());
    }

    public function testGetAllowedMediaHostsKeepsSingleHost(): void
    {
        $this->assertSame(['example.org'], $this->createSettings(['records.allowedMediaHosts' => 'example.org'])->getAllowedMediaHosts());
    }
}
