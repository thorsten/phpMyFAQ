<?php

/**
 * Data Transfer Object for translation results.
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
 * Class TranslationResult
 *
 * DTO for translation results containing translated fields and success status.
 */
readonly class TranslationResult
{
    /**
     * Constructor.
     *
     * @param array<string, string> $translatedFields Translated fields (fieldName => translatedValue)
     * @param bool $success Whether translation was successful
     * @param string|null $error Error message if translation failed
     */
    public function __construct(
        private array $translatedFields,
        private bool $success,
        private ?string $error = null,
    ) {
    }

    /**
     * Get translated fields.
     *
     * @return array<string, string>
     */
    public function getTranslatedFields(): array
    {
        return $this->translatedFields;
    }

    /**
     * Check if the translation was successful.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get error message.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }
}
