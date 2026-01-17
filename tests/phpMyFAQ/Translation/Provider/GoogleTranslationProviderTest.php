<?php

namespace phpMyFAQ\Translation\Provider;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\Exception\ApiException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[AllowMockObjectsWithoutExpectations]
class GoogleTranslationProviderTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetProviderName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $this->assertEquals('Google Cloud Translation', $provider->getProviderName());
    }

    public function testTranslateSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.googleApiKey')
            ->willReturn('test-api-key');

        $mockResponse = new MockResponse(json_encode([
            'data' => [
                'translations' => [
                    ['translatedText' => 'Hallo Welt'],
                ],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('Hello World', 'en', 'de', false);

        $this->assertEquals('Hallo Welt', $result);
    }

    public function testTranslateWithHtmlPreservation(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.googleApiKey')
            ->willReturn('test-api-key');

        $mockResponse = new MockResponse(json_encode([
            'data' => [
                'translations' => [
                    ['translatedText' => '##HTML_TAG_0##Hallo ##HTML_TAG_1##Welt##HTML_TAG_2####HTML_TAG_3##'],
                ],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('<p>Hello <strong>World</strong></p>', 'en', 'de', true);

        $this->assertEquals('<p>Hallo <strong>Welt</strong></p>', $result);
    }

    public function testTranslateBatchSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.googleApiKey')
            ->willReturn('test-api-key');

        $mockResponse = new MockResponse(json_encode([
            'data' => [
                'translations' => [
                    ['translatedText' => 'Hallo'],
                    ['translatedText' => 'Welt'],
                ],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translateBatch(['Hello', 'World'], 'en', 'de', false);

        $this->assertEquals(['Hallo', 'Welt'], $result);
    }

    public function testTranslateEmptyString(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('', 'en', 'de', false);

        $this->assertEquals('', $result);
    }

    public function testTranslateWithoutApiKey(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.googleApiKey')
            ->willReturn('');

        $httpClient = new MockHttpClient();
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Google Cloud Translation API key not configured');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateApiError(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.googleApiKey')
            ->willReturn('test-api-key');

        $mockResponse = new MockResponse('', ['http_code' => 401]);

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessageMatches('/Google Translation API error/');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testSupportsLanguagePair(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $this->assertTrue($provider->supportsLanguagePair('en', 'de'));
        $this->assertTrue($provider->supportsLanguagePair('fr', 'es'));
    }

    public function testGetSupportedLanguages(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);

        $languages = $provider->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('en', $languages);
        $this->assertContains('de', $languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('es', $languages);
    }

    public function testLanguageCodeMapping(): void
    {
        $this->configuration
            ->method('get')
            ->with('translation.googleApiKey')
            ->willReturn('test-api-key');

        $httpClient = new MockHttpClient(function ($method, $url, $options) use (&$requestBody) {
            // Verify that 'zh' is mapped to 'zh-CN' in the JSON body
            $body = json_decode($options['body'], true);
            $this->assertEquals('zh-CN', $body['target']);
            return new MockResponse(json_encode([
                'data' => [
                    'translations' => [
                        ['translatedText' => '你好'],
                    ],
                ],
            ]));
        });

        $provider = new GoogleTranslationProvider($this->configuration, $httpClient);
        $result = $provider->translate('Hello', 'en', 'zh', false);

        $this->assertEquals('你好', $result);
    }
}
