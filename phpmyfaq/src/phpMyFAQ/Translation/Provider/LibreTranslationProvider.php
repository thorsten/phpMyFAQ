<?php

/**
 * LibreTranslate API provider.
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

/**
 * Class LibreTranslationProvider
 *
 * LibreTranslate API implementation (self-hosted or public instance).
 */
class LibreTranslationProvider extends AbstractTranslationProvider
{
    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'LibreTranslate';
    }

    /**
     * Get the API URL from the configuration.
     *
     * @return string API translate endpoint URL
     * @throws ApiException
     */
    private function getApiUrl(): string
    {
        $baseUrl = $this->configuration->get('translation.libreTranslateUrl');

        if (empty($baseUrl)) {
            throw new ApiException('LibreTranslate server URL not configured');
        }

        return rtrim($baseUrl, '/') . '/translate';
    }

    /**
     * @inheritDoc
     */
    protected function doTranslate(string $text, string $sourceLang, string $targetLang): string
    {
        $apiKey = $this->configuration->get('translation.libreTranslateApiKey'); // Optional

        $body = [
            'q' => $text,
            'source' => $this->mapLanguageCode($sourceLang),
            'target' => $this->mapLanguageCode($targetLang),
            'format' => 'text',
        ];

        if (!empty($apiKey)) {
            $body['api_key'] = $apiKey;
        }

        try {
            $response = $this->httpClient->request('POST', $this->getApiUrl(), [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $body,
            ]);

            $data = $response->toArray();
            return $data['translatedText'] ?? '';
        } catch (Exception $e) {
            throw new ApiException('LibreTranslate API error: ' . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    protected function doTranslateBatch(array $texts, string $sourceLang, string $targetLang): array
    {
        // LibreTranslate doesn't support batch translation natively, translate one by one
        return array_map(fn($text) => $this->doTranslate($text, $sourceLang, $targetLang), $texts);
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
        // LibreTranslate support depends on installed models, assume common languages are supported
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedLanguages(): array
    {
        // Common languages typically available in LibreTranslate
        return ['ar', 'de', 'en', 'es', 'fr', 'it', 'ja', 'nl', 'pl', 'pt', 'ru', 'tr', 'zh'];
    }

    /**
     * @inheritDoc
     */
    protected function mapLanguageCode(string $pmfLangCode): string
    {
        // LibreTranslate uses standard ISO 639-1 codes
        return $pmfLangCode;
    }
}
