<?php

/**
 * Google Cloud Translation API provider.
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
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GoogleTranslationProvider
 *
 * Google Cloud Translation API implementation.
 */
class GoogleTranslationProvider extends AbstractTranslationProvider
{
    private const string API_URL = 'https://translation.googleapis.com/language/translate/v2';

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'Google Cloud Translation';
    }

    /**
     * @inheritDoc
     */
    protected function doTranslate(string $text, string $sourceLang, string $targetLang): string
    {
        $apiKey = $this->configuration->get('translation.googleApiKey');

        if (empty($apiKey)) {
            throw new ApiException('Google Cloud Translation API key not configured');
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'query' => ['key' => $apiKey],
                'json' => [
                    'q' => $text,
                    'source' => $this->mapLanguageCode($sourceLang),
                    'target' => $this->mapLanguageCode($targetLang),
                    'format' => 'text',
                ],
            ]);

            $data = $response->toArray();
            return $data['data']['translations'][0]['translatedText'] ?? '';
        } catch (Exception|TransportExceptionInterface $e) {
            throw new ApiException('Google Translation API error: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    protected function doTranslateBatch(array $texts, string $sourceLang, string $targetLang): array
    {
        $apiKey = $this->configuration->get('translation.googleApiKey');

        if (empty($apiKey)) {
            throw new ApiException('Google Cloud Translation API key not configured');
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'query' => ['key' => $apiKey],
                'json' => [
                    'q' => $texts,
                    'source' => $this->mapLanguageCode($sourceLang),
                    'target' => $this->mapLanguageCode($targetLang),
                    'format' => 'text',
                ],
            ]);

            $data = $response->toArray();
            return array_map(fn($translation) => $translation['translatedText'], $data['data']['translations']);
        } catch (Exception|TransportExceptionInterface $e) {
            throw new ApiException('Google Translation API error: ' . $e->getMessage());
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
        // Google Cloud Translation supports most language pairs
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedLanguages(): array
    {
        // Common languages supported by Google Cloud Translation
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
        // Google uses standard ISO 639-1, with special cases
        return match ($pmfLangCode) {
            'zh' => 'zh-CN',
            'pt' => 'pt-BR',
            'nb' => 'no',
            default => $pmfLangCode,
        };
    }
}
