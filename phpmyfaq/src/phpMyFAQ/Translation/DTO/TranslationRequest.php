<?php

/**
 * Data Transfer Object for translation requests.
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

namespace phpMyFAQ\Translation\DTO;

/**
 * Class TranslationRequest
 *
 * DTO for translation requests containing content type, languages, and fields to translate.
 */
readonly class TranslationRequest
{
    /**
     * Constructor.
     *
     * @param string $contentType Content type ('faq', 'customPage', 'category', 'news')
     * @param string $sourceLang Source language code (ISO 639-1)
     * @param string $targetLang Target language code (ISO 639-1)
     * @param array<string, string> $fields Fields to translate (fieldName => value)
     */
    public function __construct(
        private string $contentType,
        private string $sourceLang,
        private string $targetLang,
        private array $fields,
    ) {
    }

    /**
     * Get content type.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Get source language.
     *
     * @return string
     */
    public function getSourceLang(): string
    {
        return $this->sourceLang;
    }

    /**
     * Get target language.
     *
     * @return string
     */
    public function getTargetLang(): string
    {
        return $this->targetLang;
    }

    /**
     * Get fields to translate.
     *
     * @return array<string, string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
