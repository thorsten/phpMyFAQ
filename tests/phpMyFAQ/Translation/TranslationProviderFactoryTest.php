<?php

namespace phpMyFAQ\Translation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\Provider\AmazonTranslationProvider;
use phpMyFAQ\Translation\Provider\AzureTranslationProvider;
use phpMyFAQ\Translation\Provider\DeepLTranslationProvider;
use phpMyFAQ\Translation\Provider\GoogleTranslationProvider;
use phpMyFAQ\Translation\Provider\LibreTranslationProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

#[AllowMockObjectsWithoutExpectations]
class TranslationProviderFactoryTest extends TestCase
{
    private Configuration $configuration;
    private MockHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->httpClient = new MockHttpClient();
    }

    public function testCreateGoogleProvider(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.provider')
            ->willReturn('google');

        $provider = TranslationProviderFactory::create($this->configuration, $this->httpClient);

        $this->assertInstanceOf(GoogleTranslationProvider::class, $provider);
    }

    public function testCreateDeepLProvider(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.provider')
            ->willReturn('deepl');

        $provider = TranslationProviderFactory::create($this->configuration, $this->httpClient);

        $this->assertInstanceOf(DeepLTranslationProvider::class, $provider);
    }

    public function testCreateAzureProvider(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.provider')
            ->willReturn('azure');

        $provider = TranslationProviderFactory::create($this->configuration, $this->httpClient);

        $this->assertInstanceOf(AzureTranslationProvider::class, $provider);
    }

    public function testCreateAmazonProvider(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.provider')
            ->willReturn('amazon');

        $provider = TranslationProviderFactory::create($this->configuration, $this->httpClient);

        $this->assertInstanceOf(AmazonTranslationProvider::class, $provider);
    }

    public function testCreateLibreTranslateProvider(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.provider')
            ->willReturn('libretranslate');

        $provider = TranslationProviderFactory::create($this->configuration, $this->httpClient);

        $this->assertInstanceOf(LibreTranslationProvider::class, $provider);
    }

    public function testCreateWithNoProvider(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.provider')
            ->willReturn('none');

        $provider = TranslationProviderFactory::create($this->configuration, $this->httpClient);

        $this->assertNull($provider);
    }

    public function testCreateWithEmptyProvider(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.provider')
            ->willReturn('');

        $provider = TranslationProviderFactory::create($this->configuration, $this->httpClient);

        $this->assertNull($provider);
    }

    public function testCreateWithInvalidProvider(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.provider')
            ->willReturn('invalid-provider');

        $provider = TranslationProviderFactory::create($this->configuration, $this->httpClient);

        $this->assertNull($provider);
    }
}
