<?php

/**
 * Azure Translator API provider.
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

use phpMyFAQ\Translation\AbstractTranslationProvider;
use phpMyFAQ\Translation\Exception\ApiException;

/**
 * Class AzureTranslationProvider
 *
 * Azure Translator API implementation.
 */
class AzureTranslationProvider extends AbstractTranslationProvider
{
    private const string API_URL = 'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0';

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'Azure Translator';
    }

    /**
     * @inheritDoc
     */
    protected function doTranslate(string $text, string $sourceLang, string $targetLang): string
    {
        $results = $this->doTranslateBatch([$text], $sourceLang, $targetLang);
        return $results[0] ?? '';
    }

    /**
     * @inheritDoc
     */
    protected function doTranslateBatch(array $texts, string $sourceLang, string $targetLang): array
    {
        $apiKey = $this->configuration->get('translation.azureKey');
        $region = $this->configuration->get('translation.azureRegion');

        if (empty($apiKey)) {
            throw new ApiException('Azure Translator API key not configured');
        }

        if (empty($region)) {
            throw new ApiException('Azure Translator region not configured');
        }

        $url =
            self::API_URL
            . '&from='
            . $this->mapLanguageCode($sourceLang)
            . '&to='
            . $this->mapLanguageCode($targetLang);

        $body = array_map(fn($text) => ['text' => $text], $texts);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => $apiKey,
                    'Ocp-Apim-Subscription-Region' => $region,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            $data = $response->toArray();
            return array_map(fn($item) => $item['translations'][0]['text'] ?? '', $data);
        } catch (\Exception $e) {
            throw new ApiException('Azure Translator API error: ' . $e->getMessage());
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
        // Azure Translator supports most language pairs
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedLanguages(): array
    {
        // Common languages supported by Azure Translator
        return [
            'ar',
            'bn',
            'bs',
            'cs',
            'cy',
            'da',
            'de',
            'el',
            'en',
            'es',
            'eu',
            'fa',
            'fi',
            'fr',
            'he',
            'hi',
            'hu',
            'id',
            'it',
            'ja',
            'ko',
            'lt',
            'lv',
            'mn',
            'ms',
            'nb',
            'nl',
            'pl',
            'pt',
            'ro',
            'ru',
            'sk',
            'sl',
            'sr',
            'sv',
            'th',
            'tr',
            'uk',
            'ur',
            'vi',
            'zh',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function mapLanguageCode(string $pmfLangCode): string
    {
        // Azure uses standard ISO 639-1
        return $pmfLangCode;
    }
}
