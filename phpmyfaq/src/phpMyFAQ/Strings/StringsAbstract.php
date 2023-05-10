<?php
// phpcs:ignoreFile
/**
 * Abstract parent for the string wrapper classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */

namespace phpMyFAQ\Strings;

/**
 * Class StringsAbstract
 *
 * @package phpMyFAQ\Strings
 */
abstract class StringsAbstract
{
    /**
     * Default encoding.
     *
     * @var string
     */
    public const DEFAULT_ENCODING = 'utf-8';

    /**
     * Default language.
     *
     * @var string
     */
    public const DEFAULT_LANGUAGE = 'en';

    /**
     * Encoding.
     */
    protected string $encoding = self::DEFAULT_ENCODING;

    /**
     * Language.
     */
    protected string $language = self::DEFAULT_LANGUAGE;

    /**
     * Check if the string is a unicode string.
     *
     * @param string $str String
     */
    public static function isUTF8(string $str): bool
    {
        if (function_exists('mb_detect_encoding')) {
            return mb_detect_encoding($str, self::DEFAULT_ENCODING, true);
        } else {
            $regex = '/^([\x00-\x7f]|'
                . '[\xc2-\xdf][\x80-\xbf]|'
                . '\xe0[\xa0-\xbf][\x80-\xbf]|'
                . '[\xe1-\xec][\x80-\xbf]{2}|'
                . '\xed[\x80-\x9f][\x80-\xbf]|'
                . '[\xee-\xef][\x80-\xbf]{2}|'
                . '\xf0[\x90-\xbf][\x80-\xbf]{2}|'
                . '[\xf1-\xf3][\x80-\xbf]{3}|'
                . '\xf4[\x80-\x8f][\x80-\xbf]{2})*$/';

            return preg_match($regex, $str) === 1;
        }
    }

    /**
     * Get current encoding.
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * Set current encoding.
     */
    public function setEncoding(string $encoding): void
    {
        $this->encoding = $encoding;
    }
}
