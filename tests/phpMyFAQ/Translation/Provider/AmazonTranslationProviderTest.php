<?php

namespace phpMyFAQ\Translation\Provider;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\Exception\ApiException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[AllowMockObjectsWithoutExpectations]
class AmazonTranslationProviderTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetProviderName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $this->assertEquals('Amazon Translate', $provider->getProviderName());
    }

    public function testTranslateSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.amazonAccessKeyId',     'test-access-key'],
                ['translation.amazonSecretAccessKey', 'test-secret-key'],
                ['translation.amazonRegion',          'us-east-1'],
            ]);

        $mockResponse = new MockResponse(json_encode([
            'TranslatedText' => 'Hallo Welt',
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('Hello World', 'en', 'de', false);

        $this->assertEquals('Hallo Welt', $result);
    }

    public function testTranslateWithHtmlPreservation(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.amazonAccessKeyId',     'test-access-key'],
                ['translation.amazonSecretAccessKey', 'test-secret-key'],
                ['translation.amazonRegion',          'us-east-1'],
            ]);

        $mockResponse = new MockResponse(json_encode([
            'TranslatedText' => '##HTML_TAG_0##Hallo ##HTML_TAG_1##Welt##HTML_TAG_2####HTML_TAG_3##',
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('<p>Hello <strong>World</strong></p>', 'en', 'de', true);

        $this->assertEquals('<p>Hallo <strong>Welt</strong></p>', $result);
    }

    public function testTranslateBatchSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.amazonAccessKeyId',     'test-access-key'],
                ['translation.amazonSecretAccessKey', 'test-secret-key'],
                ['translation.amazonRegion',          'us-east-1'],
            ]);

        $responses = [
            new MockResponse(json_encode(['TranslatedText' => 'Hallo'])),
            new MockResponse(json_encode(['TranslatedText' => 'Welt'])),
        ];

        $httpClient = new MockHttpClient($responses);
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translateBatch(['Hello', 'World'], 'en', 'de', false);

        $this->assertEquals(['Hallo', 'Welt'], $result);
    }

    public function testTranslateThrowsExceptionWithoutCredentials(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.amazonAccessKeyId',     ''],
                ['translation.amazonSecretAccessKey', ''],
                ['translation.amazonRegion',          'us-east-1'],
            ]);

        $httpClient = new MockHttpClient();
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Amazon Translate API credentials not configured');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateThrowsExceptionOnApiError(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.amazonAccessKeyId',     'test-access-key'],
                ['translation.amazonSecretAccessKey', 'test-secret-key'],
                ['translation.amazonRegion',          'us-east-1'],
            ]);

        $mockResponse = new MockResponse('', ['http_code' => 400]);

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Amazon Translate API error: HTTP 400');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateThrowsExceptionOnInvalidResponse(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.amazonAccessKeyId',     'test-access-key'],
                ['translation.amazonSecretAccessKey', 'test-secret-key'],
                ['translation.amazonRegion',          'us-east-1'],
            ]);

        $mockResponse = new MockResponse(json_encode(['error' => 'Invalid response']));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid response from Amazon Translate API');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testSupportsLanguagePair(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $this->assertTrue($provider->supportsLanguagePair('en', 'de'));
        $this->assertTrue($provider->supportsLanguagePair('en', 'fr'));
        $this->assertTrue($provider->supportsLanguagePair('zh', 'en'));
    }

    public function testGetSupportedLanguages(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        $languages = $provider->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('en', $languages);
        $this->assertContains('de', $languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('zh', $languages);
        $this->assertContains('ja', $languages);
        $this->assertGreaterThan(50, count($languages)); // Amazon supports 75+ languages
    }

    public function testLanguageCodeMapping(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.amazonAccessKeyId',     'test-access-key'],
                ['translation.amazonSecretAccessKey', 'test-secret-key'],
                ['translation.amazonRegion',          'us-east-1'],
            ]);

        $mockResponse = new MockResponse(json_encode([
            'TranslatedText' => 'Olá Mundo',
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new AmazonTranslationProvider($this->configuration, $httpClient);

        // Test that pt-BR is mapped to pt
        $result = $provider->translate('Hello World', 'en', 'pt-BR', false);

        $this->assertEquals('Olá Mundo', $result);
    }
}
