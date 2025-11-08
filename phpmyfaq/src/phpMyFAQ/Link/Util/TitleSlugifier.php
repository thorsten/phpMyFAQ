<?php

/**
 * Extracts SEO slug generation from Link to reduce complexity.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Link\Util;

use phpMyFAQ\Strings;

final class TitleSlugifier
{
    private const string REGEX_MULTI_DASH = '/-{2,}/m';
    private const string REGEX_INNER_DASH = '/(?<=\w)-(?=\w)/m';
    private const string REGEX_WHITESPACE = '/\s+/m';
    private const array PUNCTUATION = [
        '+',
        ',',
        ';',
        ':',
        '.',
        '?',
        '!',
        '"',
        '(',
        ')',
        '[',
        ']',
        '{',
        '}',
        '<',
        '>',
        '%',
    ];
    private const array UMLAUTS = [
        'à',
        'è',
        'é',
        'ì',
        'ò',
        'ù',
        'ä',
        'ö',
        'ü',
        'ß',
        'Ä',
        'Ö',
        'Ü',
        'č',
        'ę',
        'ė',
        'į',
        'š',
        'ų',
        'ū',
        'ž',
    ];
    private const array UMLAUTS_REPLACEMENTS = [
        'a',
        'e',
        'e',
        'i',
        'o',
        'u',
        'ae',
        'oe',
        'ue',
        'ss',
        'Ae',
        'Oe',
        'Ue',
        'c',
        'e',
        'e',
        'i',
        's',
        'u',
        'u',
        'z',
    ];

    public static function slug(string $title): string
    {
        $itemTitle = trim($title);
        $itemTitle = Strings::strtolower($itemTitle);
        // replace apostrophe and slash with underscore
        $itemTitle = str_replace(["'", '/', '&#39'], '_', $itemTitle);
        // collapse multiple dashes to a space (will become '-')
        $itemTitle = Strings::preg_replace(self::REGEX_MULTI_DASH, ' ', $itemTitle);
        // replace single inner dashes with underscores (legacy: HD-Ready => hd_ready)
        $itemTitle = Strings::preg_replace(self::REGEX_INNER_DASH, '_', $itemTitle);
        // whitespace to '-'
        $itemTitle = Strings::preg_replace(self::REGEX_WHITESPACE, '-', $itemTitle);
        // strip punctuation
        $itemTitle = str_replace(self::PUNCTUATION, '', $itemTitle);
        // map umlauts and accents
        $itemTitle = str_replace(self::UMLAUTS, self::UMLAUTS_REPLACEMENTS, $itemTitle);
        // reduce multiple separators
        $itemTitle = Strings::preg_replace('/_{2,}/m', '_', $itemTitle);
        $itemTitle = Strings::preg_replace(self::REGEX_MULTI_DASH, '-', $itemTitle);
        // trim edge separators
        $itemTitle = trim($itemTitle, '-_');
        return $itemTitle;
    }
}
