<?php

/**
 * HTML tag preservation utility for translation services.
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

/**
 * Class HtmlPreserver
 *
 * Replaces HTML tags with placeholders before translation and restores them after,
 * preventing translation services from breaking HTML structure.
 */
class HtmlPreserver
{
    private const string PLACEHOLDER_PREFIX = '##HTML_TAG_';
    private const string PLACEHOLDER_SUFFIX = '##';

    /**
     * Replace HTML tags with placeholders.
     *
     * @param string $html HTML content with tags
     * @return array{string, array<string, string>} [text with placeholders, tag map]
     */
    public function replaceTags(string $html): array
    {
        $tagMap = [];
        $counter = 0;

        // Replace all HTML tags (opening, closing, self-closing) with placeholders
        $textWithPlaceholders = preg_replace_callback(
            '/<[^>]+>/',
            static function ($matches) use (&$tagMap, &$counter) {
                $placeholder = self::PLACEHOLDER_PREFIX . $counter . self::PLACEHOLDER_SUFFIX;
                $tagMap[$placeholder] = $matches[0];
                $counter++;
                return $placeholder;
            },
            $html,
        );

        return [$textWithPlaceholders ?? $html, $tagMap];
    }

    /**
     * Restore HTML tags from placeholders.
     *
     * @param string $text Text containing placeholders
     * @param array<string, string> $tagMap Map of placeholders to original tags
     * @return string Text with HTML tags restored
     */
    public function restoreTags(string $text, array $tagMap): string
    {
        return str_replace(array_keys($tagMap), array_values($tagMap), $text);
    }
}
