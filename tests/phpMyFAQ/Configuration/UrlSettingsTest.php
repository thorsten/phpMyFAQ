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
     * @return iterable<string, array{string, string}>
     */
    public static function defaultUrlProvider(): iterable
    {
        yield 'already slash-terminated' => ['https://faq.example.org/', 'https://faq.example.org/'];
        yield 'slash is appended' => ['https://faq.example.org', 'https://faq.example.org/'];
        yield 'sub directory' => ['https://example.org/faq', 'https://example.org/faq/'];
        yield 'plain http' => ['http://faq.example.test/', 'http://faq.example.test/'];
        yield 'upper case scheme' => ['HTTPS://faq.example.test', 'HTTPS://faq.example.test/'];
    }

    #[DataProvider('defaultUrlProvider')]
    public function testGetDefaultUrlAlwaysEndsWithASlash(string $configured, string $expected): void
    {
        $this->assertSame($expected, $this->createSettings(['main.referenceURL' => $configured])->getDefaultUrl());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonHttpUrlProvider(): iterable
    {
        yield 'javascript authority form' => ["javascript://x/%0Avoid(document.body.dataset.pmfPoc='executed')//"];
        yield 'javascript mixed case' => ['JavaScript://x/%0Aalert(1)//'];
        yield 'javascript leading whitespace' => [' javascript://x/%0Aalert(1)//'];
        yield 'bare javascript' => ['javascript:alert(1)'];
        yield 'data url' => ['data:text/html,<script>alert(1)</script>'];
        yield 'ftp url' => ['ftp://files.example.test/'];
        yield 'scheme relative' => ['//evil.example.test/'];
        yield 'empty string' => [''];
    }

    #[DataProvider('nonHttpUrlProvider')]
    public function testGetDefaultUrlFallsBackToRequestOriginForNonHttpUrl(string $url): void
    {
        $defaultUrl = $this->createSettings(['main.referenceURL' => $url])->getDefaultUrl();

        $this->assertMatchesRegularExpression('#^https?://[^/]+/$#', $defaultUrl);
        $this->assertStringNotContainsStringIgnoringCase('javascript', $defaultUrl);
        $this->assertStringNotContainsString('evil.example.test', $defaultUrl);
    }

    public function testGetDefaultUrlFallsBackToRequestOriginWhenNotConfigured(): void
    {
        $this->assertMatchesRegularExpression('#^https?://[^/]+/$#', $this->createSettings([])->getDefaultUrl());
    }

    #[DataProvider('nonHttpUrlProvider')]
    public function testIsAllowedReferenceUrlRejectsNonHttpUrl(string $url): void
    {
        $this->assertFalse(UrlSettings::isAllowedReferenceUrl($url));
    }

    public function testIsAllowedReferenceUrlAcceptsHttpAndHttps(): void
    {
        $this->assertTrue(UrlSettings::isAllowedReferenceUrl('http://faq.example.test/'));
        $this->assertTrue(UrlSettings::isAllowedReferenceUrl('https://faq.example.test/sub/dir'));
        $this->assertTrue(UrlSettings::isAllowedReferenceUrl('HTTPS://faq.example.test'));
    }

    public function testIsAllowedReferenceUrlRejectsUrlWithoutHost(): void
    {
        $this->assertFalse(UrlSettings::isAllowedReferenceUrl('https:///path'));
        $this->assertFalse(UrlSettings::isAllowedReferenceUrl('https:'));
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
        $this->assertSame(
            ['example.org'],
            $this->createSettings(['records.allowedMediaHosts' => 'example.org'])->getAllowedMediaHosts(),
        );
    }
}
