<?php

namespace phpMyFAQ\Translation\Provider;

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\Exception\ApiException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[AllowMockObjectsWithoutExpectations]
class AzureTranslationProviderTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
    }

    public function testGetProviderName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $this->assertEquals('Azure Translator', $provider->getProviderName());
    }

    public function testTranslateSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.azureKey',    'test-api-key'],
                ['translation.azureRegion', 'eastus'],
            ]);

        $mockResponse = new MockResponse(json_encode([
            [
                'translations' => [
                    ['text' => 'Hallo Welt'],
                ],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('Hello World', 'en', 'de', false);

        $this->assertEquals('Hallo Welt', $result);
    }

    public function testTranslateBatchSuccess(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.azureKey',    'test-api-key'],
                ['translation.azureRegion', 'eastus'],
            ]);

        $mockResponse = new MockResponse(json_encode([
            [
                'translations' => [
                    ['text' => 'Hallo'],
                ],
            ],
            [
                'translations' => [
                    ['text' => 'Welt'],
                ],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translateBatch(['Hello', 'World'], 'en', 'de', false);

        $this->assertEquals(['Hallo', 'Welt'], $result);
    }

    public function testTranslateEmptyString(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('', 'en', 'de', false);

        $this->assertEquals('', $result);
    }

    public function testTranslateWithoutApiKey(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.azureKey',    ''],
                ['translation.azureRegion', 'eastus'],
            ]);

        $httpClient = new MockHttpClient();
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Azure Translator API key not configured');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateWithoutRegion(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.azureKey',    'test-api-key'],
                ['translation.azureRegion', ''],
            ]);

        $httpClient = new MockHttpClient();
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Azure Translator region not configured');

        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateWithDifferentRegion(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.azureKey',    'test-api-key'],
                ['translation.azureRegion', 'westeurope'],
            ]);

        $mockResponse = new MockResponse(json_encode([
            [
                'translations' => [
                    ['text' => 'Bonjour'],
                ],
            ],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translate('Hello', 'en', 'fr', false);

        $this->assertEquals('Bonjour', $result);
    }

    public function testRequestUrlIncludesFromAndToLanguages(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.azureKey',    'test-api-key'],
                ['translation.azureRegion', 'eastus'],
            ]);

        $httpClient = new MockHttpClient(function ($method, $url, $options) {
            $this->assertStringContainsString('from=en', $url);
            $this->assertStringContainsString('to=de', $url);

            return new MockResponse(json_encode([
                [
                    'translations' => [
                        ['text' => 'Hallo'],
                    ],
                ],
            ]));
        });

        $provider = new AzureTranslationProvider($this->configuration, $httpClient);
        $provider->translate('Hello', 'en', 'de', false);
    }

    public function testTranslateWrapsTransportFailuresInAnApiException(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.azureKey',    'test-api-key'],
                ['translation.azureRegion', 'eastus'],
            ]);

        $httpClient = new MockHttpClient(static function (): never {
            throw new TransportException('Connection refused');
        });
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Azure Translator API error: Connection refused');
        $provider->translate('Hello World', 'en', 'de', false);
    }

    public function testTranslateBatchPreservingHtmlTranslatesEveryTextIndividually(): void
    {
        $this->configuration
            ->method('get')
            ->willReturnMap([
                ['translation.azureKey',    'test-api-key'],
                ['translation.azureRegion', 'eastus'],
            ]);

        $requestBodies = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use (
            &$requestBodies,
        ): MockResponse {
            $body = json_decode((string) $options['body'], true);
            $requestBodies[] = $body;

            return new MockResponse(json_encode([
                ['translations' => [['text' => 'Übersetzt: ' . $body[0]['text']]]],
            ]));
        });
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $result = $provider->translateBatch(['<b>Hello</b>', 'World'], 'en', 'de', true);

        $this->assertCount(2, $requestBodies);
        $this->assertStringContainsString('Übersetzt: ', $result[0]);
        $this->assertStringContainsString('<b>', $result[0]);
        $this->assertSame('Übersetzt: World', $result[1]);
    }

    public function testSupportsLanguagePair(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $this->assertTrue($provider->supportsLanguagePair('en', 'de'));
        $this->assertTrue($provider->supportsLanguagePair('fr', 'es'));
    }

    public function testGetSupportedLanguages(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new AzureTranslationProvider($this->configuration, $httpClient);

        $languages = $provider->getSupportedLanguages();

        $this->assertIsArray($languages);
        $this->assertContains('en', $languages);
        $this->assertContains('de', $languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('ja', $languages);
    }
}
