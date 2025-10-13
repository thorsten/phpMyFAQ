<?php

declare(strict_types=1);

/**
 * The plural class provides support for plural forms in phpMyFAQ translations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2025 Aurimas Fišeras and phpMyFAQ Team
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
readonly class Plurals
{
    /**
     * The number of plural forms for the current language $lang.
     */
    private int $nPlurals;

    /**
     * The language code of the current language.
     *
     * @var string
     */
    private mixed $lang;

    /**
     * True when there is no support for plural forms in the current language $lang.
     */
    private bool $useDefaultPluralForm;

    public function __construct()
    {
        $this->nPlurals = (int) Translation::get(languageKey: 'nplurals');
        $this->lang = Translation::get(languageKey: 'metaLanguage');

        $this->useDefaultPluralForm = $this->plural(language: $this->lang, number: 0) === -1;
    }

    /**
     * Returns the plural form for language $lang or -1 if language $lang is not supported.
     *
     * @link   https://www.gnu.org/software/gettext/manual/gettext.html#Plural-forms
     * @param  string $language The language code
     * @param  int    $number   The number used to determine the plural form
     */
    private function plural(string $language, int $number): int
    {
        switch ($language) {
            case 'ar':
                if ($number === 0) {
                    return 0;
                }
                if ($number === 1) {
                    return 1;
                }
                if ($number === 2) {
                    return 2;
                }
                $n100 = $number % 100;
                if ($n100 >= 3 && $n100 <= 10) {
                    return 3;
                }
                if (($n100 >= 11) || $n100 === 1 || $n100 === 2) {
                    return 4;
                }
                return 5;

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
                if ($number === 1) {
                    return 0;
                }
                if ($number === 2) {
                    return 1;
                }
                if ($number !== 8 && $number !== 11) {
                    return 2;
                }
                return 3;

            case 'cs':
                if ($number === 1) {
                    return 0;
                }
                if ($number >= 2 && $number <= 4) {
                    return 1;
                }
                return 2;

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
                if ($number !== 1) {
                    return 1;
                }
                return 0;

            case 'fr':
            case 'pt_br':
                if ($number > 1) {
                    return 1;
                }
                return 0;

            case 'lt':
                $n10 = $number % 10;
                $n100 = $number % 100;
                if ($n10 === 1 && $n100 !== 11) {
                    return 0;
                }
                if ($n10 >= 2 && ($n100 < 10 || $n100 >= 20)) {
                    return 1;
                }
                return 2;

            case 'lv':
                $n10 = $number % 10;
                $n100 = $number % 100;
                if ($n10 === 1 && $n100 !== 11) {
                    return 0;
                }
                if ($number !== 0) {
                    return 1;
                }
                return 2;

            case 'pl':
                $n10 = $number % 10;
                $n100 = $number % 100;
                if ($number === 1) {
                    return 0;
                }
                if ($n10 >= 2 && $n10 <= 4 && ($n100 < 10 || $n100 >= 20)) {
                    return 1;
                }
                return 2;

            case 'ro':
                $n100 = $number % 100;
                if ($number === 1) {
                    return 0;
                }
                if ($number === 0 || ($n100 > 0 && $n100 < 20)) {
                    return 1;
                }
                return 2;

            case 'ru':
            case 'sr':
            case 'uk':
                $n10 = $number % 10;
                $n100 = $number % 100;
                if ($n10 === 1 && $n100 !== 11) {
                    return 0;
                }
                if ($n10 >= 2 && $n10 <= 4 && ($n100 < 10 || $n100 >= 20)) {
                    return 1;
                }
                return 2;

            case 'sl':
                $n100 = $number % 100;
                if ($n100 === 1) {
                    return 0;
                }
                if ($n100 === 2) {
                    return 1;
                }
                if ($n100 === 3 || $n100 === 4) {
                    return 2;
                }
                return 3;

            default:
                return -1;
        }
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
     * If translation is missing message in English plural form is returned.
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
            // this means we have to fall back to English, so return the correct English plural form
            return $this->plural(language: 'en', number: $number);
        }

        $plural = $this->plural(language: $this->lang, number: $number);
        if ($plural > ($this->nPlurals - 1)) {
            // incorrectly defined plural function or wrong $nPlurals
            return $this->nPlurals - 1;
        }

        return $plural;
    }
}
