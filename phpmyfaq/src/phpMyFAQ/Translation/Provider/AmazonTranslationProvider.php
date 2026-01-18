<?php

/**
 * Amazon Translate translation provider implementation.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Translation\Provider;

use phpMyFAQ\Translation\AbstractTranslationProvider;
use phpMyFAQ\Translation\Exception\ApiException;
use phpMyFAQ\Translation\Exception\UnsupportedLanguageException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class AmazonTranslationProvider
 *
 * Implements translation using Amazon Translate API.
 * API Documentation: https://docs.aws.amazon.com/translate/
 */
class AmazonTranslationProvider extends AbstractTranslationProvider
{
    private const string API_ENDPOINT_TEMPLATE = 'https://translate.%s.amazonaws.com/';
    private const string API_VERSION = '20170701';
    private const string SERVICE_NAME = 'translate';

    /**
     * Performs the actual translation using Amazon Translate API.
     *
     * @param string $text Text to translate
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     * @return string Translated text
     * @throws ApiException
     */
    protected function doTranslate(string $text, string $sourceLang, string $targetLang): string
    {
        $region = $this->configuration->get('translation.amazonRegion') ?: 'us-east-1';
        $accessKeyId = $this->configuration->get('translation.amazonAccessKeyId');
        $secretAccessKey = $this->configuration->get('translation.amazonSecretAccessKey');

        if (empty($accessKeyId) || empty($secretAccessKey)) {
            throw new ApiException('Amazon Translate API credentials not configured');
        }

        $endpoint = sprintf(self::API_ENDPOINT_TEMPLATE, $region);

        // Map language codes if needed
        $sourceLang = $this->mapLanguageCode($sourceLang);
        $targetLang = $this->mapLanguageCode($targetLang);

        // Prepare request payload
        $payload = json_encode([
            'Text' => $text,
            'SourceLanguageCode' => $sourceLang,
            'TargetLanguageCode' => $targetLang,
        ]);

        // AWS Signature V4
        $headers = $this->getAwsSignedHeaders('POST', $payload, $region, $accessKeyId, $secretAccessKey);

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $headers,
                'body' => $payload,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new ApiException('Amazon Translate API error: HTTP ' . $statusCode);
            }

            $data = json_decode($response->getContent(), true);

            if (!isset($data['TranslatedText'])) {
                throw new ApiException('Invalid response from Amazon Translate API');
            }

            return $data['TranslatedText'];
        } catch (TransportExceptionInterface $e) {
            throw new ApiException('Amazon Translate API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Performs batch translation (Amazon Translate doesn't have a native batch API, so we loop).
     *
     * @param array $texts Array of texts to translate
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     * @return array Array of translated texts
     * @throws ApiException
     */
    protected function doTranslateBatch(array $texts, string $sourceLang, string $targetLang): array
    {
        $results = [];
        foreach ($texts as $text) {
            $results[] = $this->doTranslate($text, $sourceLang, $targetLang);
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function translateBatch(
        array $texts,
        string $sourceLang,
        string $targetLang,
        bool $preserveHtml = false,
    ): array {
        if ($preserveHtml) {
            // Process each text individually with HTML preservation
            return array_map(fn($text) => $this->translate($text, $sourceLang, $targetLang, true), $texts);
        }

        return $this->doTranslateBatch($texts, $sourceLang, $targetLang);
    }

    /**
     * Map language codes to Amazon Translate format.
     * Amazon uses standard ISO 639-1 codes with some exceptions.
     *
     * @param string $languageCode Language code
     * @return string Mapped language code
     */
    protected function mapLanguageCode(string $languageCode): string
    {
        // Amazon Translate uses mostly standard ISO 639-1 codes
        // Special cases:
        $mapping = [
            'zh' => 'zh', // Chinese (simplified)
            'zh-TW' => 'zh-TW', // Chinese (traditional)
            'no' => 'no', // Norwegian
            'pt' => 'pt', // Portuguese
            'pt-BR' => 'pt', // Brazilian Portuguese -> Portuguese
        ];

        return $mapping[$languageCode] ?? $languageCode;
    }

    /**
     * Check if the provider supports the given language pair.
     *
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     * @return bool True if supported
     */
    public function supportsLanguagePair(string $sourceLang, string $targetLang): bool
    {
        $supportedLanguages = $this->getSupportedLanguages();
        return in_array($sourceLang, $supportedLanguages, true) && in_array($targetLang, $supportedLanguages, true);
    }

    /**
     * Get a list of supported languages.
     * Amazon Translate supports 75+ languages.
     *
     * @return array Array of supported language codes
     */
    public function getSupportedLanguages(): array
    {
        return [
            'af',
            'sq',
            'am',
            'ar',
            'hy',
            'az',
            'bn',
            'bs',
            'bg',
            'ca',
            'zh',
            'zh-TW',
            'hr',
            'cs',
            'da',
            'fa-AF',
            'nl',
            'en',
            'et',
            'fa',
            'tl',
            'fi',
            'fr',
            'fr-CA',
            'ka',
            'de',
            'el',
            'gu',
            'ht',
            'ha',
            'he',
            'hi',
            'hu',
            'is',
            'id',
            'ga',
            'it',
            'ja',
            'kn',
            'kk',
            'ko',
            'lv',
            'lt',
            'mk',
            'ms',
            'ml',
            'mt',
            'mr',
            'mn',
            'no',
            'ps',
            'pl',
            'pt',
            'pa',
            'ro',
            'ru',
            'sr',
            'si',
            'sk',
            'sl',
            'so',
            'es',
            'es-MX',
            'sw',
            'sv',
            'ta',
            'te',
            'th',
            'tr',
            'uk',
            'ur',
            'uz',
            'vi',
            'cy',
        ];
    }

    /**
     * Get the provider name.
     *
     * @return string Provider name
     */
    public function getProviderName(): string
    {
        return 'Amazon Translate';
    }

    /**
     * Generate AWS Signature Version 4 headers for authentication.
     *
     * @param string $method HTTP method (POST)
     * @param string $payload Request payload
     * @param string $region AWS region
     * @param string $accessKeyId AWS access key ID
     * @param string $secretAccessKey AWS secret access key
     * @return array Headers array
     */
    private function getAwsSignedHeaders(
        string $method,
        string $payload,
        string $region,
        string $accessKeyId,
        string $secretAccessKey,
    ): array {
        $service = self::SERVICE_NAME;
        $host = sprintf('translate.%s.amazonaws.com', $region);
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        // Task 1: Create canonical request
        $canonicalUri = '/';
        $canonicalQuerystring = '';
        $canonicalHeaders =
            "content-type:application/x-amz-json-1.1\n"
            . "host:$host\n"
            . "x-amz-date:$amzDate\n"
            . "x-amz-target:AWSShineFrontendService_20170701.TranslateText\n";
        $signedHeaders = 'content-type;host;x-amz-date;x-amz-target';
        $payloadHash = hash('sha256', $payload);

        $canonicalRequest =
            "$method\n$canonicalUri\n$canonicalQuerystring\n" . "$canonicalHeaders\n$signedHeaders\n$payloadHash";

        // Task 2: Create string to sign
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "$dateStamp/$region/$service/aws4_request";
        $stringToSign = "$algorithm\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

        // Task 3: Calculate signature
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretAccessKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        // Task 4: Add signing information to the request
        $authorizationHeader =
            "$algorithm Credential=$accessKeyId/$credentialScope, "
            . "SignedHeaders=$signedHeaders, Signature=$signature";

        return [
            'Content-Type' => 'application/x-amz-json-1.1',
            'Host' => $host,
            'X-Amz-Date' => $amzDate,
            'X-Amz-Target' => 'AWSShineFrontendService_20170701.TranslateText',
            'Authorization' => $authorizationHeader,
        ];
    }
}
