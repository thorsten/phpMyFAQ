<?php

/**
 * The plurals class provides support for plural forms in phpMyFAQ translations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2021 Aurimas Fišeras and phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-07-30
 */

namespace phpMyFAQ\Language;

/**
 * Class Plurals
 *
 * @package phpMyFAQ\Language
 */
class Plurals
{
    /**
     * The currently loaded PMF translations.
     *
     * @var array
     */
    private $PMF_TRANSL = [];

    /**
     * The number of plural forms for current language $lang.
     *
     * @var int
     */
    private $nPlurals;

    /**
     * The language code of current language.
     *
     * @var string
     */
    private $lang;

    /**
     * True when there is no support for plural forms in current language $lang.
     *
     * @var bool
     */
    private $useDefaultPluralForm;

    /**
     * Constructor.
     *
     * @param array $translation PMF translation array for current language
     */
    public function __construct($translation)
    {
        $this->PMF_TRANSL = $translation;
        $this->nPlurals = (int)$this->PMF_TRANSL['nplurals'];
        $this->lang = $this->PMF_TRANSL['metaLanguage'];

        if ($this->plural($this->lang, 0) != -1) {
            $this->useDefaultPluralForm = false;
        } else {
            //  @todo update $this->PMF_TRANSL with English plural messages for fall-back
            $this->useDefaultPluralForm = true;
        }
    }

    /**
     * Returns the plural form for language $lang or -1 if language $lang is not supported.
     *
     * @link   http://www.gnu.org/software/gettext/manual/gettext.html#Plural-forms
     * @param  string $lang The language code
     * @param  int    $n    The number used to determine the plural form
     * @return int
     */
    private function plural(string $lang, int $n): int
    {
        switch ($lang) {
            // Note: expressions in .po files are not strict C expressions, so extra braces might be
            // needed for that expression to work here (for example see 'lt')
            case 'ar':
                return ($n == 0) ? 0 : ($n == 1 ? 1 : ($n == 2 ? 2 : (($n % 100 >= 3 && $n % 100 <= 10) ? 3 :
                    (($n % 100 >= 11 && $n % 100 <= 99) || ($n % 100 == 1) || ($n % 100 == 2) ? 4 : 5))));
            case 'bn':
            case 'he':
            case 'hi':
            case 'id':
            case 'ja':
            case 'ko':
            case 'th':
            case 'tr':
            case 'tw':
            case 'vi':
            case 'zh':
                return 0;
            case 'cy':
                return ($n == 1) ? 0 : ($n == 2 ? 1 : (($n != 8 && $n != 11) ? 2 : 3));
            case 'cs':
                return ($n == 1) ? 0 : (($n >= 2 && $n <= 4) ? 1 : 2);
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'es':
            case 'eu':
            case 'fa':
            case 'fi':
            case 'it':
            case 'nb':
            case 'nl':
            case 'hu':
            case 'pt':
            case 'sv':
                return $n != 1;
            case 'fr':
            case 'pt-br':
                return $n > 1;
            case 'lt':
                return ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n % 10 >= 2 && ($n % 100 < 10 || $n % 100 >= 20) ?
                    1 : 2);
            case 'lv':
                return ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n != 0 ? 1 : 2);
            case 'pl':
                return ($n == 1) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
            case 'ro':
                return ($n == 1) ? 0 : (($n == 0 || ($n % 100 > 0 && $n % 100 < 20)) ? 1 : 2);
            case 'ru':
            case 'sr':
            case 'uk':
                return ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 &&
                ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
            case 'sl':
                return ($n % 100 == 1) ? 0 : ($n % 100 == 2 ? 1 : ($n % 100 == 3 || $n % 100 == 4 ? 2 : 3));
            default:
                //plural expressions can't return negative values, so use -1 to signal unsupported language
                return -1;
        }
    }

    /**
     * Returns a translated string in the correct plural form,
     * produced according to the formatting of the message.
     *
     * @param string $msgID Message identification
     * @param int    $n     The number used to determine the plural form
     *
     * @return string
     */
    public function getMsg($msgID, $n)
    {
        return sprintf($this->getMsgTemplate($msgID, $n), $n);
    }

    /**
     * Helper function that returns the translation template in the correct plural form
     * If translation is missing, message in English plural form is returned.
     *
     * @param string $msgID Message identification
     * @param int    $n     The number used to determine the plural form
     *
     * @return string
     */
    public function getMsgTemplate($msgID, $n)
    {
        $plural = $this->getPlural($n);
        if (isset($this->PMF_TRANSL[$msgID][$plural])) {
            return $this->PMF_TRANSL[$msgID][$plural];
        } else {
            // translation for current plural form (>2, since we allways have 2 English plural forms)
            // in current language is missing, so as a last resort return default English plural form
            return $this->PMF_TRANSL[$msgID][1];
        }
    }

    /**
     * Determines the correct plural form for integer $n
     * Returned integer is from interval [0, $nPlurals).
     *
     * @param int $n The number used to determine the plural form
     *
     * @return int
     */
    private function getPlural($n)
    {
        if ($this->useDefaultPluralForm) {
            // this means we have to fallback to English, so return correct English plural form
            return $this->plural('en', $n);
        }

        $plural = $this->plural($this->lang, $n);
        if ($plural > $this->nPlurals - 1) {
            // incorrectly defined plural function or wrong $nPlurals
            return $this->nPlurals - 1;
        } else {
            return $plural;
        }
    }
}
