<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(LayoutSettings::class)]
#[CoversClass(SearchSettings::class)]
#[CoversClass(SecuritySettings::class)]
#[CoversClass(UrlSettings::class)]
class SettingsValueObjectsTest extends TestCase
{
    public function testLayoutSettingsReturnConfiguredTemplateAndCustomCss(): void
    {
        $configuration = $this->createConfigurationStub([
            'layout.templateSet' => 'modern',
            'layout.customCss' => 'body { color: red; }',
        ]);

        $settings = new LayoutSettings($configuration);

        $this->assertSame('modern', $settings->getTemplateSet());
        $this->assertSame('body { color: red; }', $settings->getCustomCss());
    }

    public function testLayoutSettingsFallsBackToDefaultTemplate(): void
    {
        $configuration = $this->createConfigurationStub([]);

        $settings = new LayoutSettings($configuration);

        $this->assertSame('default', $settings->getTemplateSet());
    }

    public function testSearchSettingsDetectsElasticsearchFlag(): void
    {
        $settings = new SearchSettings($this->createConfigurationStub([
            'search.enableElasticsearch' => '1',
        ]));

        $this->assertTrue($settings->isElasticsearchActive());
    }

    public function testSecuritySettingsDetectsMicrosoftSignInFlag(): void
    {
        $settings = new SecuritySettings($this->createConfigurationStub([
            'security.enableSignInWithMicrosoft' => true,
        ]));

        $this->assertTrue($settings->isSignInWithMicrosoftActive());
    }

    public function testSecuritySettingsDetectsKeycloakSignInFlag(): void
    {
        $settings = new SecuritySettings($this->createConfigurationStub([
            'keycloak.enable' => true,
        ]));

        $this->assertTrue($settings->isSignInWithKeycloakActive());
    }

    public function testUrlSettingsNormalizeReferenceUrlAndAllowedMediaHosts(): void
    {
        $settings = new UrlSettings($this->createConfigurationStub([
            'main.referenceURL' => 'https://example.org/faq',
            'records.allowedMediaHosts' => 'youtube.com,vimeo.com',
        ]));

        $this->assertSame('https://example.org/faq/', $settings->getDefaultUrl());
        $this->assertSame(['youtube.com', 'vimeo.com'], $settings->getAllowedMediaHosts());
    }

    public function testUrlSettingsHandleExistingTrailingSlashAndEmptyMediaHosts(): void
    {
        $settings = new UrlSettings($this->createConfigurationStub([
            'main.referenceURL' => 'https://example.org/faq/',
            'records.allowedMediaHosts' => '',
        ]));

        $this->assertSame('https://example.org/faq/', $settings->getDefaultUrl());
        $this->assertSame([], $settings->getAllowedMediaHosts());
    }

    private function createConfigurationStub(array $values): \phpMyFAQ\Configuration
    {
        $configuration = $this->createMock(\phpMyFAQ\Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $values[$item] ?? null);

        return $configuration;
    }
}
