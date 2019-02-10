<?php

/**
 * Abstract parent for the string wrapper classes.
 *
 * PHP Version 5.5.0
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_String_Abstract.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-04-06
 */
abstract class PMF_String_Abstract
{
    /**
     * Default encoding.
     *
     * @var string
     */
    const DEFAULT_ENCODING = 'utf-8';

    /**
     * Default language.
     *
     * @var string
     */
    const DEFAULT_LANGUAGE = 'en';

    /**
     * Encoding.
     *
     * @var string
     */
    protected $encoding = self::DEFAULT_ENCODING;

    /**
     * Language.
     *
     * @var string
     */
    protected $language = self::DEFAULT_LANGUAGE;

    /**
     * Check if the string is a unicode string.
     *
     * @param string $str String
     *
     * @return bool
     */
    public static function isUTF8($str)
    {
        if (function_exists('mb_detect_encoding')) {
            return mb_detect_encoding($str, 'UTF-8', true);
        } else {
            $regex = '/^([\x00-\x7f]|'
                   .'[\xc2-\xdf][\x80-\xbf]|'
                   .'\xe0[\xa0-\xbf][\x80-\xbf]|'
                   .'[\xe1-\xec][\x80-\xbf]{2}|'
                   .'\xed[\x80-\x9f][\x80-\xbf]|'
                   .'[\xee-\xef][\x80-\xbf]{2}|'
                   .'\xf0[\x90-\xbf][\x80-\xbf]{2}|'
                   .'[\xf1-\xf3][\x80-\xbf]{3}|'
                   .'\xf4[\x80-\x8f][\x80-\xbf]{2})*$/';

            return preg_match($regex, $str) === 1;
        }
    }

    /**
     * Set current encoding.
     *
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     * Get current encoding.
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }
}
