<?php
/**
 * Helper class for categories
 *
 * PHP Version 5.2.0
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
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */

/**
 * PMF_Category_Helper
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-04
 */
class PMF_Category_Helper extends PMF_Category_Abstract
{
    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Checks wether a language is already defined for a category id
     *
     * @param  integer $categoryId   Category id
     * @param  string  $categoryLang Category language
     * @return boolean
     */
    public function hasTranslation($categoryId, $categoryLang)
    {
        $query = sprintf("
            SELECT
                lang
            FROM
                %sfaqcategories
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            (int)$categoryId,
            $categoryLang);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return (bool)$this->db->numRows($result);
    }
    
    /**
     * Get number of nodes at the same parent_id level
     *
     * @param  integer $parent_id Parent id
     * @return integer
     */
    public function numParent($parent_id)
    {
        $query = sprintf("
            SELECT distinct
                id
            FROM
                %sfaqcategories
            WHERE
                parent_id = %d",
            SQLPREFIX,
            $parent_id);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $this->db->numRows($result);
    }
    
    /**
     * Move the categories ownership, if any.
     *
     * @param  integer $from Old user id
     * @param  integer $to   New user id
     * @return boolean
     */
    public function moveOwnership($from, $to)
    {
        if (!is_numeric($from) || !is_numeric($to)) {
            return false;
        }

        $query = sprintf("
            UPDATE
                %sfaqcategories
            SET
                user_id = %d
            WHERE
                user_id = %d",
            SQLPREFIX,
            $to,
            $from);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    
    /**
     * Swaps two categories
     *
     * @param  integer $firstCategoryId  First category
     * @param  integer $secondCategoryId Second category
     * @return boolean
     */
    public function swapCategories($firstCategoryId, $secondCategoryId)
    {
        $temp_cat = rand(200000, 400000);

        $tables = array(
            array('faqcategories'        => 'id'),
            array('faqcategories'        => 'parent_id'),
            array('faqcategoryrelations' => 'category_id'),
            array('faqquestions'         => 'ask_rubrik'),
            array('faqcategory_group'    => 'category_id'),
            array('faqcategory_user'     => 'category_id'));

        $result = true;
        foreach ($tables as $pair) {
            foreach ($pair as $_table => $_field) {
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = %d WHERE %s = %d",
                    SQLPREFIX.$_table,
                    $_field,
                    $temp_cat,
                    $_field,
                    $secondCategoryId));
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = %d WHERE %s = %d",
                    SQLPREFIX.$_table,
                    $_field,
                    $secondCategoryId,
                    $_field,
                    $firstCategoryId));
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = %d WHERE %s = %d",
                    SQLPREFIX.$_table,
                    $_field,
                    $firstCategoryId,
                    $_field,
                    $temp_cat));
            }
        }

        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    
    /**
     * Create all languagess which can be used for translation as <option>
     *
     * @param  integer $categoryId   Category id
     * @param  string  $selectedLanguage Selected language
     * @return string
     */
    public function renderLanguages($categoryId, $selectedLanguage)
    {
        $existingLanguages = PMF_Utils::languageAvailable($categoryId, 'faqcategories');
        
        $options = '';
        foreach (PMF_Language::getAvailableLanguages() as $lang => $langname) {
            if (!in_array(strtolower($lang), $existingLanguages)) {
                $options .= sprintf("\t<option value=\"%s\"%s>%s</option>\n", 
                    strtolower($lang),
                    ($lang == $selectedLanguage) ? ' selected="selected"' : '',
                    $langname);
           }
        }
        return $options;
    }
}