<?php
/**
 * The plurals class provides support for plural forms in PMF translations.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Language
 * @author     Aurimas Fišeras <aurimas@gmail.com>
 * @since      2009-07-30
 * @copyright  2009 Aurimas Fišeras
 * @version    SVN: $Id$
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
 * @copyright  2009 Aurimas Fišeras
 * @version    SVN: $Id$
 */
class PMF_Language_Plurals
{
    /**
     * The currently loaded PMF translations
     *
     * @var array
     */
    private $PMF_TRANSL;

    /**
     * The number of plural forms for current language
     *
     * @var integer
     */
    private $nPlurals;

    /**
     * The name of plurals function for current language
     *
     * @var string
     */
    private $pluralFunction;

    /**
     * True when plurals function is missing for current language
     *
     * @var boolean
     */
    private $useDefaultPluralForm; //if true, real plurals function is missing, always return 0
 
    /**
    * Constructor
    *
    * @param  array $translation    PMF translation array for current language
    * @return void
    */
    public function __construct($translation) {
        $this->PMF_TRANSL = $translation;
        $this->nPlurals = $this->PMF_TRANSL['nplurals'];

        if (is_callable('plural_'.$this->PMF_TRANSL['metaLanguage'])) {
            $this->pluralFunction = 'plural_'.$this->PMF_TRANSL['metaLanguage'];
            $this->useDefaultPluralForm = false;
        } else {
            //TODO display warning!?
            //echo "function plural_".$this->PMF_TRANSL['metaLanguage']." was not found for language ".$this->PMF_TRANSL['language'])
            $this->useDefaultPluralForm = true;
        }
    }

    /**
     * Determines the correct plural form for integer $n
     * Returned integer is from interval [0, $nPlurals)
     *
     * @param   integer  $n The number used to determine the plural form
     * @return  integer
     * @access  private
     * @since   2009-07-30
     */
    private function _getPlural($n) {
        if ($this->useDefaultPluralForm) {
            //this means we have to fallback to English, so return correct English plural form
            return plural_en($n);
        }

        $plural = intval(call_user_func($this->pluralFunction, $n));
        if ($plural > $this->nPlurals-1) {
            //incorrectly defined plural function or wrong $nPlurals
            return $this->nPlurals-1;
        } else {
            return $plural;
        }
    }

    /**
     * Returns the translation in the correct plural form
     * If translation is missing, message in English plural form is returned
     *
     * @param   string   $msgID Message identificator
     * @param   integer  $n     The number used to determine the plural form
     * @return  string
     * @access  public
     * @since   2009-07-31
     */
    public function getMsg($msgID, $n) {
        $plural = $this->_getPlural($n);
        if (isset($this->PMF_TRANSL[$msgID][$plural])) {
            return $this->PMF_TRANSL[$msgID][$plural];
        } else {
            //translation for current plural form (>2, since we allways have 2 English plural forms)
            //in current language is missing, so as a last resort return default English plural form
            return $this->PMF_TRANSL[$msgID][1];
        }
    }
}
