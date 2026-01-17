<?php

/**
 * Translation provider interface for multi-provider translation support.
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

namespace phpMyFAQ\Translation;

use phpMyFAQ\Translation\Exception\TranslationException;

/**
 * Interface TranslationProviderInterface
 *
 * Defines the contract for translation service providers (Google, DeepL, Azure, LibreTranslate).
 */
interface TranslationProviderInterface
{
    /**
     * Translate a single text string.
     *
     * @param string $text Text to translate
     * @param string $sourceLang Source language code (ISO 639-1)
     * @param string $targetLang Target language code (ISO 639-1)
     * @param bool $preserveHtml Whether to preserve HTML tags in the text
     * @return string Translated text
     * @throws TranslationException
     */
    public function translate(string $text, string $sourceLang, string $targetLang, bool $preserveHtml = false): string;

    /**
     * Translate multiple texts in a single batch request.
     *
     * @param array<string> $texts Array of texts to translate
     * @param string $sourceLang Source language code (ISO 639-1)
     * @param string $targetLang Target language code (ISO 639-1)
     * @param bool $preserveHtml Whether to preserve HTML tags
     * @return array<string> Array of translated texts
     * @throws TranslationException
     */
    public function translateBatch(
        array $texts,
        string $sourceLang,
        string $targetLang,
        bool $preserveHtml = false,
    ): array;

    /**
     * Check if this provider supports a language pair.
     *
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     * @return bool True if the language pair is supported
     */
    public function supportsLanguagePair(string $sourceLang, string $targetLang): bool;

    /**
     * Get a list of supported language codes.
     *
     * @return array<string> Array of ISO 639-1 language codes
     */
    public function getSupportedLanguages(): array;

    /**
     * Get the provider name.
     *
     * @return string Provider name (e.g., "Google Cloud Translation", "DeepL")
     */
    public function getProviderName(): string;
}
