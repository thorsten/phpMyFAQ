<?php

/**
 * DeepL Translation API provider.
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
 * @since     2026-01-17
 */

namespace phpMyFAQ\Translation\Provider;

use Exception;
use phpMyFAQ\Translation\AbstractTranslationProvider;
use phpMyFAQ\Translation\Exception\ApiException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class DeepLTranslationProvider
 *
 * DeepL Translation API implementation.
 */
class DeepLTranslationProvider extends AbstractTranslationProvider
{
    private const string API_URL_FREE = 'https://api-free.deepl.com/v2/translate';
    private const string API_URL_PRO = 'https://api.deepl.com/v2/translate';

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'DeepL';
    }

    /**
     * Get the appropriate API URL based on configuration.
     *
     * @return string API URL
     */
    private function getApiUrl(): string
    {
        $useFreeApi = $this->configuration->get('translation.deeplUseFreeApi');
        return $useFreeApi === 'true' || $useFreeApi === true ? self::API_URL_FREE : self::API_URL_PRO;
    }

    /**
     * @inheritDoc
     */
    protected function doTranslate(string $text, string $sourceLang, string $targetLang): string
    {
        $apiKey = $this->configuration->get('translation.deeplApiKey');

        if (empty($apiKey)) {
            throw new ApiException('DeepL API key not configured');
        }

        try {
            $response = $this->httpClient->request('POST', $this->getApiUrl(), [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'text' => $text,
                    'source_lang' => $this->mapLanguageCode($sourceLang),
                    'target_lang' => $this->mapLanguageCode($targetLang),
                ],
            ]);

            $data = $response->toArray();
            return $data['translations'][0]['text'] ?? '';
        } catch (DecodingExceptionInterface|Exception|TransportExceptionInterface $e) {
            throw new ApiException('DeepL API error: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    protected function doTranslateBatch(array $texts, string $sourceLang, string $targetLang): array
    {
        $apiKey = $this->configuration->get('translation.deeplApiKey');

        if (empty($apiKey)) {
            throw new ApiException('DeepL API key not configured');
        }

        try {
            $response = $this->httpClient->request('POST', $this->getApiUrl(), [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'text' => $texts,
                    'source_lang' => $this->mapLanguageCode($sourceLang),
                    'target_lang' => $this->mapLanguageCode($targetLang),
                ],
            ]);

            $data = $response->toArray();
            return array_map(static fn($t) => $t['text'], $data['translations']);
        } catch (DecodingExceptionInterface|Exception|TransportExceptionInterface $e) {
            throw new ApiException('DeepL API error: ' . $e->getMessage());
        }
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
     * @inheritDoc
     */
    public function supportsLanguagePair(string $sourceLang, string $targetLang): bool
    {
        // DeepL has limited language support
        $supported = $this->getSupportedLanguages();
        return in_array($sourceLang, $supported) && in_array($targetLang, $supported);
    }

    /**
     * @inheritDoc
     */
    public function getSupportedLanguages(): array
    {
        // Languages supported by DeepL
        return [
            'ar',
            'bg',
            'cs',
            'da',
            'de',
            'el',
            'en',
            'es',
            'et',
            'fi',
            'fr',
            'hu',
            'id',
            'it',
            'ja',
            'ko',
            'lt',
            'lv',
            'nb',
            'nl',
            'pl',
            'pt',
            'ro',
            'ru',
            'sk',
            'sl',
            'sv',
            'tr',
            'uk',
            'zh',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function mapLanguageCode(string $pmfLangCode): string
    {
        // DeepL uses uppercase language codes with regional variants
        return match ($pmfLangCode) {
            'en' => 'EN-US',
            'pt' => 'PT-BR',
            'zh' => 'ZH',
            default => strtoupper($pmfLangCode),
        };
    }
}
