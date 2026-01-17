<?php

/**
 * Abstract base class for translation providers.
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

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation\Exception\TranslationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class AbstractTranslationProvider
 *
 * Base class providing common translation logic with HTML preservation support.
 */
abstract class AbstractTranslationProvider implements TranslationProviderInterface
{
    protected HtmlPreserver $htmlPreserver;

    /**
     * Constructor.
     *
     * @param Configuration $configuration phpMyFAQ configuration
     * @param HttpClientInterface $httpClient HTTP client for API requests
     */
    public function __construct(
        protected readonly Configuration $configuration,
        protected readonly HttpClientInterface $httpClient,
    ) {
        $this->htmlPreserver = new HtmlPreserver();
    }

    /**
     * Translate text with optional HTML preservation.
     *
     * @param string $text Text to translate
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     * @param bool $preserveHtml Whether to preserve HTML tags
     * @return string Translated text
     * @throws TranslationException
     */
    public function translate(string $text, string $sourceLang, string $targetLang, bool $preserveHtml = false): string
    {
        if (empty($text)) {
            return '';
        }

        if ($preserveHtml) {
            [$textWithPlaceholders, $htmlMap] = $this->htmlPreserver->replaceTags($text);
            $translated = $this->doTranslate($textWithPlaceholders, $sourceLang, $targetLang);
            return $this->htmlPreserver->restoreTags($translated, $htmlMap);
        }

        return $this->doTranslate($text, $sourceLang, $targetLang);
    }

    /**
     * Provider-specific translation implementation.
     *
     * @param string $text Text to translate (without HTML placeholders if preserveHtml was true)
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     * @return string Translated text
     * @throws TranslationException
     */
    abstract protected function doTranslate(string $text, string $sourceLang, string $targetLang): string;

    /**
     * Provider-specific batch translation implementation.
     *
     * @param array<string> $texts Texts to translate
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     * @return array<string> Translated texts
     * @throws TranslationException
     */
    abstract protected function doTranslateBatch(array $texts, string $sourceLang, string $targetLang): array;

    /**
     * Map phpMyFAQ language codes to provider-specific codes.
     *
     * @param string $pmfLangCode phpMyFAQ language code (e.g., 'en', 'de', 'zh')
     * @return string Provider-specific language code
     */
    abstract protected function mapLanguageCode(string $pmfLangCode): string;
}
