<?php

/**
 * The plurals class provides support for plural forms in phpMyFAQ translations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2023 Aurimas Fišeras and phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-07-30
 */

namespace phpMyFAQ\Language;

use phpMyFAQ\Translation;

/**
 * Class Plurals
 *
 * @package phpMyFAQ\Language
 */
class Plurals
{
    /**
     * The number of plural forms for current language $lang.
     */
    private readonly int $nPlurals;

    /**
     * The language code of current language.
     *
     * @var string
     */
    private readonly mixed $lang;

    /**
     * True when there is no support for plural forms in current language $lang.
     */
    private bool $useDefaultPluralForm;

    public function __construct()
    {
        $this->nPlurals = (int)Translation::get('nplurals');
        $this->lang = Translation::get('metaLanguage');

        if ($this->plural($this->lang, 0) != -1) {
            $this->useDefaultPluralForm = false;
        } else {
            $this->useDefaultPluralForm = true;
        }
    }

    /**
     * Returns the plural form for language $lang or -1 if language $lang is not supported.
     *
     * @link   https://www.gnu.org/software/gettext/manual/gettext.html#Plural-forms
     * @param  string $lang The language code
     * @param  int    $n    The number used to determine the plural form
     */
    private function plural(string $lang, int $n): int
    {
        return match ($lang) {
            'ar' => ($n == 0) ? 0 : ($n == 1 ? 1 : ($n == 2 ? 2 : (($n % 100 >= 3 && $n % 100 <= 10) ? 3 :
                (($n % 100 >= 11 && $n % 100 <= 99) || ($n % 100 == 1) || ($n % 100 == 2) ? 4 : 5)))),
            'bn', 'he', 'hi', 'id', 'ja', 'ko', 'th', 'tr', 'tw', 'vi', 'zh' => 0,
            'cy' => ($n == 1) ? 0 : ($n == 2 ? 1 : (($n != 8 && $n != 11) ? 2 : 3)),
            'cs' => ($n == 1) ? 0 : (($n >= 2 && $n <= 4) ? 1 : 2),
            'da', 'de', 'el', 'en', 'es', 'eu', 'fa', 'fi', 'it', 'nb', 'nl', 'hu', 'pt', 'sv' => $n != 1,
            'fr', 'pt-br' => $n > 1,
            'lt' => ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n % 10 >= 2 && ($n % 100 < 10 || $n % 100 >= 20) ?
                1 : 2),
            'lv' => ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n != 0 ? 1 : 2),
            'pl' => ($n == 1) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2),
            'ro' => ($n == 1) ? 0 : (($n == 0 || ($n % 100 > 0 && $n % 100 < 20)) ? 1 : 2),
            'ru', 'sr', 'uk' => ($n % 10 == 1 && $n % 100 != 11) ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 &&
            ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2),
            'sl' => ($n % 100 == 1) ? 0 : ($n % 100 == 2 ? 1 : ($n % 100 == 3 || $n % 100 == 4 ? 2 : 3)),
            default => -1,
        };
    }

    /**
     * Returns a translated string in the correct plural form,
     * produced according to the formatting of the message.
     *
     * @param string $translationKey Message identification
     * @param int    $number     The number used to determine the plural form
     */
    public function getMsg(string $translationKey, int $number): string
    {
        return sprintf($this->getMsgTemplate($translationKey, $number), $number);
    }

    /**
     * Helper function that returns the translation template in the correct plural form
     * If translation is missing, message in English plural form is returned.
     *
     * @param string $msgID Message identification
     * @param int    $n     The number used to determine the plural form
     */
    public function getMsgTemplate(string $msgID, int $n): string
    {
        $plural = $this->getPlural($n);
        return Translation::get($msgID)[$plural] ?? Translation::get($msgID)[1];
    }

    /**
     * Determines the correct plural form for integer $n
     * Returned integer is from interval [0, $nPlurals).
     *
     * @param int $number The number used to determine the plural form
     */
    private function getPlural(int $number): int
    {
        if ($this->useDefaultPluralForm) {
            // this means we have to fall back to English, so return correct English plural form
            return $this->plural('en', $number);
        }

        $plural = $this->plural($this->lang, $number);
        if ($plural > $this->nPlurals - 1) {
            // incorrectly defined plural function or wrong $nPlurals
            return $this->nPlurals - 1;
        } else {
            return $plural;
        }
    }
}
