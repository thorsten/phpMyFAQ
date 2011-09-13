<?php
/**
 * The plurals class provides support for plural forms in PMF translations.
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Language
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2011 Aurimas Fišeras and phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-07-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Language_Plurals
 *
 * @category  phpMyFAQ
 * @package   PMF_Language
 * @author    Aurimas Fišeras <aurimas@gmail.com>
 * @copyright 2009-2010 Aurimas Fišeras and phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-07-30
 */
class PMF_Language_Plurals
{
    /**
     * The currently loaded PMF translations
     *
     * @var array
     */
    private $PMF_TRANSL = array();

    /**
     * The number of plural forms for current language $lang
     *
     * @var integer
     */
    private $nPlurals;

    /**
     * The language code of current language
     *
     * @var string
     */
    private $lang;

    /**
     * True when there is no support for plural forms in current language $lang
     *
     * @var boolean
     */
    private $useDefaultPluralForm;

    /**
    * Constructor
    *
    * @param  array $translation PMF translation array for current language
    * @return void
    */
    public function __construct($translation)
    {
        $this->PMF_TRANSL = $translation;
        $this->nPlurals   = (int)$this->PMF_TRANSL['nplurals'];
        $this->lang       = $this->PMF_TRANSL['metaLanguage'];

        if ($this->plural($this->lang, 0) != -1) {
            $this->useDefaultPluralForm = false;
        } else {
            //  @todo update $this->PMF_TRANSL with English plural messages for fall-back
            //  @todo display warning!?
            //echo "function plural_".$this->PMF_TRANSL['metaLanguage']." was not found for language ".$this->PMF_TRANSL['language'])
            $this->useDefaultPluralForm = true;
        }
    }

    /**
     * Determines the correct plural form for integer $n
     * Returned integer is from interval [0, $nPlurals)
     *
     * @param  integer $n The number used to determine the plural form
     * @return integer
     * @access private
     */
    private function _getPlural($n)
    {
        if ($this->useDefaultPluralForm) {
            // this means we have to fallback to English, so return correct English plural form
            return $this->plural('en', $n);
        }

        $plural = $this->plural($this->lang, $n);
        if ($plural > $this->nPlurals-1) {
            // incorrectly defined plural function or wrong $nPlurals
            return $this->nPlurals-1;
        } else {
            return $plural;
        }
    }

    /**
     * Helper function that returns the translation template in the correct plural form
     * If translation is missing, message in English plural form is returned
     *
     * @param  string  $msgID Message identificator
     * @param  integer $n     The number used to determine the plural form
     * @return string
     */
    public function getMsgTemplate($msgID, $n)
    {
        $plural = $this->_getPlural($n);
        if (isset($this->PMF_TRANSL[$msgID][$plural])) {
            return $this->PMF_TRANSL[$msgID][$plural];
        } else {
            // translation for current plural form (>2, since we allways have 2 English plural forms)
            // in current language is missing, so as a last resort return default English plural form
            return $this->PMF_TRANSL[$msgID][1];
        }
    }

    /**
     * Returns a translated string in the correct plural form,
     * produced according to the formatting of the message.
     *
     * @param  string  $msgID Message identificator
     * @param  integer $n     The number used to determine the plural form
     * @return string
     */
    public function getMsg($msgID, $n)
    {
        return sprintf($this->getMsgTemplate($msgID, $n), $n);
    }

    /**
     * Returns the plural form for language $lang or -1 if language $lang is not supported
     *
     * @param  string  $lang The language code
     * @param  integer $n    The number used to determine the plural form
     * @return integer
     * @link   http://www.gnu.org/software/gettext/manual/gettext.html#Plural-forms
     */
    private function plural($lang, $n)
    {
        switch ($lang) {
            // Note: expressions in .po files are not strict C expressions, so extra braces might be
            // needed for that expression to work here (for example see 'lt')
            case 'ar':
                return ($n == 0) ? 0 : ($n == 1 ? 1 : ($n == 2 ? 2 : (($n%100 >= 3 && $n%100 <= 10) ? 3 : (($n%100 >= 11 && $n%100 <= 99) || ($n%100 == 1) || ($n%100 == 2) ? 4 : 5))));
            case 'bn':
                return 0;
            case 'cy':
                return ($n == 1) ? 0 : ($n == 2 ? 1 : (($n != 8 && $n != 11) ? 2 : 3));
            case 'cs':
                return ($n == 1) ? 0 : (($n >= 2 && $n <= 4) ? 1 : 2);
            case 'da':
                return $n != 1;
            case 'de':
                return $n != 1;
            case 'el':
                return $n != 1;
            case 'en':
                return $n != 1;
            case 'es':
                return $n != 1;
            case 'eu':
                return $n != 1;
            case 'fa':
                return $n != 1;
            case 'fi':
                return $n != 1;
            case 'fr':
                return $n > 1;
            case 'he':
                return $n != 1;
            case 'hi':
                return $n != 1;
            case 'hu':
                return $n != 1;
            case 'id':
                return 0;
            case 'it':
                return $n != 1;
            case 'ja':
                return 0;
            case 'ko':
                return 0;
            case 'lt':
                return ($n%10 == 1 && $n%100 != 11) ? 0 : ($n%10 >= 2 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2);
            case 'lv':
                return ($n%10 == 1 && $n%100 != 11) ? 0 : ($n != 0 ? 1 : 2);
            case 'nb':
                return $n != 1;
            case 'nl':
                return $n != 1;
            case 'pl':
                return ($n == 1) ? 0 : ($n%10 >= 2 && $n%10 <= 4 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2);
            case 'pt':
                return $n != 1;
            case 'pt-br':
                return $n > 1;
            case 'ro':
                return ($n == 1) ? 0 : (($n == 0 || ($n%100 > 0 && $n%100 < 20)) ? 1 : 2);
            case 'ru':
                return ($n%10 == 1 && $n%100 != 11) ? 0 : ($n%10 >= 2 && $n%10 <= 4 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2);
            case 'sl':
                return ($n%100 == 1) ? 0 : ($n%100 == 2 ? 1 : ($n%100 == 3 || n%100 == 4 ? 2 : 3));
            case 'sr':
                return ($n%10 == 1 && $n%100 != 11) ? 0 : ($n%10 >= 2 && $n%10 <= 4 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2);
            case 'sv':
                return $n != 1;
            case 'th':
                return 0;
            case 'tr':
                return 0;
            case 'tw':
                return 0;
            case 'uk':
                return ($n%10 == 1 && $n%100 != 11) ? 0 : ($n%10 >= 2 && $n%10 <= 4 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2);
            case 'vi':
                return 0;
            case 'zh':
                return 0;
            default:
                //plural expressions can't return negative values, so use -1 to signal unsupported language
                return -1;
        }
    }

}
