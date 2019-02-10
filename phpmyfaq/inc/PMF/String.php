<?php

/**
 * The main string wrapper class. 
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
 * String.
 *
 * The class uses mbstring extension if available. It's strongly recommended
 * to use and extend this class instead of using direct string functions. Doing so
 * you garantee your code is upwards compatible with UTF-8 improvements. All
 * the string methods behaviour is identical to that of the same named 
 * single byte string functions.
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
class PMF_String
{
    /**
     * Instance.
     *
     * @var PMF_String
     */
    private static $instance;

    /**
     * Constructor.
     */
    final private function __construct()
    {
    }

    /** 
     * Init.
     *
     * @param string $language Language
     */
    public static function init($language = 'en')
    {
        if (!self::$instance) {
            if (extension_loaded('mbstring') && function_exists('mb_regex_encoding')) {
                self::$instance = PMF_String_Mbstring::getInstance($language);
            } elseif (self::isLangUTF8ToLatinConvertable($language)) {
                self::$instance = PMF_String_UTF8ToLatinConvertable::getInstance($language);
            } else {
                self::$instance = PMF_String_Basic::getInstance($language);
            }
        }
    }

    /**
     * Get current encoding.
     *
     * @return string
     */
    public static function getEncoding()
    {
        return self::$instance->getEncoding();
    }

    /**
     * Get string character count.
     *
     * @param string $str String
     *
     * @return int
     */
    public static function strlen($str)
    {
        return self::$instance->strlen($str);
    }

    /**
     * Get a part of string.
     *
     * @param string $string String
     * @param int    $start  Start
     * @param int    $length Length
     *
     * @return string
     */
    public static function substr($string, $start, $length = 0)
    {
        return self::$instance->substr($string, $start, $length);
    }

    /**
     * Get position of the first occurence of a string.
     *
     * @param string $haystack Haystack
     * @param string $needle   Needle
     * @param int    $offset   Offset
     *
     * @return int
     */
    public static function strpos($haystack, $needle, $offset = 0)
    {
        return self::$instance->strpos($haystack, $needle, $offset);
    }

    /**
     * Make a string lower case.
     *
     * @param string $str String
     *
     * @return string
     */
    public static function strtolower($str)
    {
        return self::$instance->strtolower($str);
    }

    /**
     * Make a string upper case.
     *
     * @param string $str String
     *
     * @return string
     */
    public static function strtoupper($str)
    {
        return self::$instance->strtoupper($str);
    }

    /**
     * Get occurence of a string within another.
     *
     * @param string $haystack Haystack
     * @param string $needle   Needle
     * @param bool   $part     Part
     *
     * @return string|false
     */
    public static function strstr($haystack, $needle, $part = false)
    {
        return self::$instance->strstr($haystack, $needle, $part);
    }

    /**
     * Set current encoding.
     *
     * @param string $encoding
     */
    public static function setEncoding($encoding)
    {
        self::$instance->setEncoding($encoding);
    }

    /**
     * Check if a language could be converted to iso-8859-1.
     *
     * @param string $language
     *
     * @return bool
     */
    public static function isLangUTF8ToLatinConvertable($language)
    {
        $iso_languages = array('af', 'sq', 'br', 'ca', 'da', 'en', 'fo', 'gl', 'de', 'is', 'it',
                               'ku', 'la', 'lb', 'nb', 'oc', 'pt', 'es', 'sw', 'sv', 'wa', 'eu',
                               // NOTE this languages are not fully supported by latin1 
                               'nl', 'fr', 'et', 'fi', 'cy',
        );

        return in_array($language, $iso_languages);
    }

    /**
     * Get last occurence of a string within another.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return string
     */
    public static function strrchr($haystack, $needle)
    {
        return self::$instance->strrchr($haystack, $needle);
    }

    /**
     * Count substring occurences.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return int
     */
    public static function substr_count($haystack, $needle)
    {
        return self::$instance->substr_count($haystack, $needle);
    }

    /**
     * Find position of last occurrence of a char in a string.
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     *
     * @return int
     */
    public static function strrpos($haystack, $needle, $offset = 0)
    {
        return self::$instance->strrpos($haystack, $needle, $offset);
    }

    /**
     * Match a regexp.
     *
     * @param string $pattern
     * @param string $subject
     * @param array  &$matches
     * @param int    $flags
     * @param int    $offset
     *
     * @return int
     */
    public static function preg_match($pattern, $subject, &$matches = null, $flags = 0, $offset = 0)
    {
        return self::$instance->preg_match($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Match a regexp globally.
     *
     * @param string $pattern
     * @param string $subject
     * @param array  &$matches
     * @param int    $flags
     * @param int    $offset
     *
     * @return int
     */
    public static function preg_match_all($pattern, $subject, &$matches = null, $flags = 0, $offset = 0)
    {
        return self::$instance->preg_match_all($pattern, $subject, $matches, $flags, $offset);
    }

    /**
     * Split string by a regexp.
     *
     * @param string $pattern
     * @param string $subject
     * @param int    $limit
     * @param int    $flags
     *
     * @return array
     */
    public static function preg_split($pattern, $subject, $limit = -1, $flags = 0)
    {
        return self::$instance->preg_split($pattern, $subject, $limit, $flags);
    }

    /**
     * Search and replace by a regexp using a callback.
     *
     * @param string|array $pattern
     * @param function     $callback
     * @param string|array $subject
     * @param int          $limit
     * @param int          &$count
     *
     * @return array|string
     */
    public static function preg_replace_callback($pattern, $callback, $subject, $limit = -1, &$count = 0)
    {
        return self::$instance->preg_replace_callback($pattern, $callback, $subject, $limit, $count);
    }

    /**
     * Search and replace by a regexp.
     *
     * @param string|array $pattern
     * @param string|array $replacement
     * @param string|array $subject
     * @param int          $limit
     * @param int          &$count
     *
     * @return array|string|null
     */
    public static function preg_replace($pattern, $replacement, $subject, $limit = -1, &$count = 0)
    {
        return self::$instance->preg_replace($pattern, $replacement, $subject, $limit, $count);
    }

    /**
     * Check if the string is a unicode string.
     *
     * @param string $str String
     *
     * @return bool
     */
    public static function isUTF8($str)
    {
        return PMF_String_Basic::isUTF8($str);
    }

    /**
     * Convert special chars to html entities.
     *
     * @param string $string       The input string.
     * @param int    $quoteStyle   Quote style
     * @param string $charset      Character set, UTF-8 by default
     * @param bool   $doubleEncode If set to false, no encoding of existing entities
     *
     * @return string
     */
    public static function htmlspecialchars($string, $quoteStyle = ENT_COMPAT, $charset = 'utf-8', $doubleEncode = false)
    {
        return htmlspecialchars(
            $string,
            $quoteStyle,
            $charset,
            $doubleEncode
        );
    }

    /**
     * Convert all applicable characters to HTML entities.
     *
     * @param string $string       The input string.
     * @param int    $quoteStyle   Quote style
     * @param string $charset      Character set, UTF-8 by default
     * @param bool   $doubleEncode If set to false, no encoding of existing entities
     *
     * @return string
     */
    public static function htmlentities($string, $quoteStyle = ENT_COMPAT, $charset = 'utf-8', $doubleEncode = true)
    {
        return htmlentities(
            $string,
            $quoteStyle,
            $charset,
            $doubleEncode
        );
    }
}
