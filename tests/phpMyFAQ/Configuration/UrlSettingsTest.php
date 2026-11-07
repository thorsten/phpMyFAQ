<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlSettingsTest extends TestCase
{
    private function urlSettings(string $referenceUrl): UrlSettings
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['main.referenceURL', $referenceUrl],
            ]);

        return new UrlSettings($configuration);
    }

    public function testGetDefaultUrlAppendsTrailingSlash(): void
    {
        $this->assertSame('https://faq.example.test/', $this->urlSettings('https://faq.example.test')->getDefaultUrl());
    }

    public function testGetDefaultUrlKeepsExistingTrailingSlash(): void
    {
        $this->assertSame('http://faq.example.test/', $this->urlSettings('http://faq.example.test/')->getDefaultUrl());
    }

    public function testGetDefaultUrlAcceptsUpperCaseScheme(): void
    {
        $this->assertSame('HTTPS://faq.example.test/', $this->urlSettings('HTTPS://faq.example.test')->getDefaultUrl());
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
        $defaultUrl = $this->urlSettings($url)->getDefaultUrl();

        $this->assertMatchesRegularExpression('#^https?://[^/]+/$#', $defaultUrl);
        $this->assertStringNotContainsStringIgnoringCase('javascript', $defaultUrl);
        $this->assertStringNotContainsString('evil.example.test', $defaultUrl);
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
}
