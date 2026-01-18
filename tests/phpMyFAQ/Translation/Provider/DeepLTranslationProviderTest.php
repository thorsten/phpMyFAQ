<?php

namespace phpMyFAQ\Translation\Provider;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\Exception\ApiException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[AllowMockObjectsWithoutExpectations]
class DeepLTranslationProviderTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetProviderName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);

        $this->assertEquals('DeepL', $provider->getProviderName());
    }

    public function testTranslateSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.deeplApiKey',     'test-api-key'],
                ['translation.deeplUseFreeApi', true],
            ]);

        $mockResponse = new MockResponse(json_encode([
            'translations' => [
                ['text' => 'Hallo Welt'],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('Hello World', 'en', 'de', false);

        $this->assertEquals('Hallo Welt', $result);
    }

    public function testTranslateWithFreeApi(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.deeplApiKey',     'test-api-key'],
                ['translation.deeplUseFreeApi', 'true'],
            ]);

        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            $this->assertStringContainsString('api-free.deepl.com', $url);
            return new MockResponse(json_encode([
                'translations' => [
                    ['text' => 'Hallo'],
                ],
            ]));
        });

        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);
        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateWithProApi(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.deeplApiKey',     'test-api-key'],
                ['translation.deeplUseFreeApi', false],
            ]);

        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            $this->assertStringContainsString('api.deepl.com', $url);
            $this->assertStringNotContainsString('api-free', $url);
            return new MockResponse(json_encode([
                'translations' => [
                    ['text' => 'Hallo'],
                ],
            ]));
        });

        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);
        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateBatchSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.deeplApiKey',     'test-api-key'],
                ['translation.deeplUseFreeApi', true],
            ]);

        $mockResponse = new MockResponse(json_encode([
            'translations' => [
                ['text' => 'Hallo'],
                ['text' => 'Welt'],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translateBatch(['Hello', 'World'], 'en', 'de', false);

        $this->assertEquals(['Hallo', 'Welt'], $result);
    }

    public function testTranslateEmptyString(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('', 'en', 'de', false);

        $this->assertEquals('', $result);
    }

    public function testTranslateWithoutApiKey(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.deeplApiKey')
            ->willReturn('');

        $httpClient = new MockHttpClient();
        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('DeepL API key not configured');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testSupportsLanguagePair(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);

        $this->assertTrue($provider->supportsLanguagePair('en', 'de'));
        $this->assertTrue($provider->supportsLanguagePair('fr', 'es'));

        // DeepL has limited language support, but we return true for common pairs
        $this->assertTrue($provider->supportsLanguagePair('en', 'ja'));
    }

    public function testGetSupportedLanguages(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);

        $languages = $provider->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('en', $languages);
        $this->assertContains('de', $languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('ja', $languages);
    }

    public function testLanguageCodeMapping(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.deeplApiKey',     'test-api-key'],
                ['translation.deeplUseFreeApi', true],
            ]);

        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            // DeepL uses uppercase codes, verify mapping
            $body = $options['body'];
            parse_str($body, $params);
            $this->assertEquals('EN-US', $params['target_lang']);
            return new MockResponse(json_encode([
                'translations' => [
                    ['text' => 'Hello'],
                ],
            ]));
        });

        $provider = new DeepLTranslationProvider($this->configuration, $httpClient);
        $provider->translate('Hallo', 'de', 'en', false);
    }
}
