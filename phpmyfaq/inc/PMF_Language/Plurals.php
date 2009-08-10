<?php
/**
 * The plurals class provides support for plural forms in PMF translations.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Language
 * @author     Aurimas Fišeras <aurimas@gmail.com>
 * @since      2009-07-30
 * @version    SVN: $Id$
 * @copyright  2009 Aurimas Fišeras and phpMyFAQ Team
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
 */

/**
 * PMF_Language_Plurals
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Language
 * @author     Aurimas Fišeras <aurimas@gmail.com>
 * @since      2009-07-30
 * @version    SVN: $Id$
 * @copyright  2009 Aurimas Fišeras and phpMyFAQ Team
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
        $this->nPlurals   = $this->PMF_TRANSL['nplurals'];
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
     * Returns the translation in the correct plural form
     * If translation is missing, message in English plural form is returned
     *
     * @param  string  $msgID Message identificator
     * @param  integer $n     The number used to determine the plural form
     * @return string
     */
    public function getMsg($msgID, $n)
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
            // @todo add expressions for all supported languages in PMF
            // Note: expressions in .po files are not strict C expressions, so extra braces might be
            // needed for that expression to work here (for example see 'lt')
            case 'en':
                return $n != 1;
            case 'lt':
                return ($n%10 == 1 && $n%100 != 11 ? 0 : ($n%10 >= 2 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2));
            default:
                //plural expressions can't return negative values, so use -1 to signal unsupported language
                return -1;
        }
    }

}
