<?php

declare(strict_types=1);

namespace phpMyFAQ\Seo;

use phpMyFAQ\Configuration;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Faq\Statistics as FaqStatistics;
use PHPUnit\Framework\TestCase;

class SitemapXmlServiceTest extends TestCase
{
    private Configuration $configuration;

    private FaqStatistics $faqStatistics;

    private CustomPage $customPage;

    private SitemapXmlService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createStub(Configuration::class);
        $this->faqStatistics = $this->createStub(FaqStatistics::class);
        $this->customPage = $this->createStub(CustomPage::class);

        $this->service = new SitemapXmlService(
            $this->configuration,
            $this->faqStatistics,
            $this->customPage,
        );
    }

    public function testIsEnabledReturnsTrueWhenConfigEnabled(): void
    {
        $this->configuration->method('get')->willReturn(true);

        $this->assertTrue($this->service->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenConfigDisabled(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $this->assertFalse($this->service->isEnabled());
    }

    public function testGenerateXmlReturnsNullWhenDisabled(): void
    {
        $this->configuration->method('get')->willReturn(false);

        $this->assertNull($this->service->generateXml());
    }

    public function testCollectUrlsReturnsFaqUrlsWithCorrectPriority(): void
    {
        $this->faqStatistics->method('getTopTenData')
            ->willReturn([
                1 => [
                    'url' => 'https://example.com/faq/1',
                    'date' => '2026-01-01T00:00:00+00:00',
                    'question' => 'Test FAQ',
                    'answer' => 'Test answer',
                    'visits' => 100,
                    'last_visit' => '2026-01-01T00:00:00+00:00',
                ],
            ]);

        $this->customPage->method('getAllPages')->willReturn([]);

        $urls = $this->service->collectUrls();

        $this->assertCount(1, $urls);
        $this->assertSame('https://example.com/faq/1', $urls[0]['loc']);
        $this->assertSame('1.00', $urls[0]['priority']);
        $this->assertSame('2026-01-01T00:00:00+00:00', $urls[0]['lastmod']);
    }

    public function testCollectUrlsFiltersInactiveCustomPages(): void
    {
        $this->faqStatistics->method('getTopTenData')->willReturn([]);
        $this->configuration->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $this->customPage->method('getAllPages')->willReturn([
            [
                'id' => 1,
                'slug' => 'active-page',
                'active' => 'y',
                'created' => '2026-01-01 00:00:00',
                'updated' => '2026-01-15 00:00:00',
            ],
            [
                'id' => 2,
                'slug' => 'inactive-page',
                'active' => 'n',
                'created' => '2026-01-01 00:00:00',
                'updated' => null,
            ],
        ]);

        $urls = $this->service->collectUrls();

        $this->assertCount(1, $urls);
        $this->assertSame('https://example.com/page/active-page.html', $urls[0]['loc']);
    }

    public function testCollectUrlsBuildsCorrectCustomPageUrl(): void
    {
        $this->faqStatistics->method('getTopTenData')->willReturn([]);
        $this->configuration->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $this->customPage->method('getAllPages')->willReturn([
            [
                'id' => 1,
                'slug' => 'my-custom-page',
                'active' => 'y',
                'created' => '2026-01-01 00:00:00',
                'updated' => '2026-02-01 00:00:00',
            ],
        ]);

        $urls = $this->service->collectUrls();

        $this->assertCount(1, $urls);
        $this->assertSame('https://example.com/page/my-custom-page.html', $urls[0]['loc']);
        $this->assertSame('0.80', $urls[0]['priority']);
        $this->assertSame('2026-02-01 00:00:00', $urls[0]['lastmod']);
    }

    public function testCollectUrlsUsesCreatedDateWhenUpdatedIsNull(): void
    {
        $this->faqStatistics->method('getTopTenData')->willReturn([]);
        $this->configuration->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $this->customPage->method('getAllPages')->willReturn([
            [
                'id' => 1,
                'slug' => 'page-no-update',
                'active' => 'y',
                'created' => '2026-01-01 00:00:00',
                'updated' => null,
            ],
        ]);

        $urls = $this->service->collectUrls();

        $this->assertSame('2026-01-01 00:00:00', $urls[0]['lastmod']);
    }
}
