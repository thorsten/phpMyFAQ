<?php

namespace phpMyFAQ\Translation\Provider;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\Exception\ApiException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[AllowMockObjectsWithoutExpectations]
class LibreTranslationProviderTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetProviderName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new LibreTranslationProvider($this->configuration, $httpClient);

        $this->assertEquals('LibreTranslate', $provider->getProviderName());
    }

    public function testTranslateSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.libreTranslateUrl',    'https://libretranslate.com'],
                ['translation.libreTranslateApiKey', ''],
            ]);

        $mockResponse = new MockResponse(json_encode([
            'translatedText' => 'Hallo Welt',
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new LibreTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('Hello World', 'en', 'de', false);

        $this->assertEquals('Hallo Welt', $result);
    }

    public function testTranslateWithApiKey(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.libreTranslateUrl',    'https://libretranslate.com'],
                ['translation.libreTranslateApiKey', 'test-key-123'],
            ]);

        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            $body = json_decode($options['body'], true);
            $this->assertEquals('test-key-123', $body['api_key']);

            return new MockResponse(json_encode([
                'translatedText' => 'Hallo',
            ]));
        });

        $provider = new LibreTranslationProvider($this->configuration, $httpClient);
        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateWithoutApiKey(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.libreTranslateUrl',    'https://libretranslate.com'],
                ['translation.libreTranslateApiKey', ''],
            ]);

        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            $body = json_decode($options['body'], true);
            $this->assertArrayNotHasKey('api_key', $body);

            return new MockResponse(json_encode([
                'translatedText' => 'Hallo',
            ]));
        });

        $provider = new LibreTranslationProvider($this->configuration, $httpClient);
        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateBatchCallsTranslateMultipleTimes(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.libreTranslateUrl',    'https://libretranslate.com'],
                ['translation.libreTranslateApiKey', ''],
            ]);

        $callCount = 0;
        $httpClient = new MockHttpClient(function () use (&$callCount) {
            $callCount++;
            return new MockResponse(json_encode([
                'translatedText' => $callCount === 1 ? 'Hallo' : 'Welt',
            ]));
        });

        $provider = new LibreTranslationProvider($this->configuration, $httpClient);
        $result = $provider->translateBatch(['Hello', 'World'], 'en', 'de', false);

        $this->assertEquals(['Hallo', 'Welt'], $result);
        $this->assertEquals(2, $callCount);
    }

    public function testTranslateEmptyString(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new LibreTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('', 'en', 'de', false);

        $this->assertEquals('', $result);
    }

    public function testTranslateWithoutServerUrl(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.libreTranslateUrl',    ''],
                ['translation.libreTranslateApiKey', ''],
            ]);

        $httpClient = new MockHttpClient();
        $provider = new LibreTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('LibreTranslate server URL not configured');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateApiError(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.libreTranslateUrl',    'https://libretranslate.com'],
                ['translation.libreTranslateApiKey', ''],
            ]);

        $mockResponse = new MockResponse('', ['http_code' => 500]);

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new LibreTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/LibreTranslate API error/');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testCorrectApiUrl(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.libreTranslateUrl',    'https://example.com/'],
                ['translation.libreTranslateApiKey', ''],
            ]);

        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            // Verify the URL is correctly formed with /translate appended
            $this->assertEquals('https://example.com/translate', $url);

            return new MockResponse(json_encode([
                'translatedText' => 'Hallo',
            ]));
        });

        $provider = new LibreTranslationProvider($this->configuration, $httpClient);
        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testSupportsLanguagePair(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new LibreTranslationProvider($this->configuration, $httpClient);

        $this->assertTrue($provider->supportsLanguagePair('en', 'de'));
        $this->assertTrue($provider->supportsLanguagePair('fr', 'es'));
    }

    public function testGetSupportedLanguages(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new LibreTranslationProvider($this->configuration, $httpClient);

        $languages = $provider->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('en', $languages);
        $this->assertContains('de', $languages);
        $this->assertContains('fr', $languages);
    }
}
