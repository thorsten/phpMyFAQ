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
        
        return (bool)$this->db->num_rows($result);
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
        
        return $this->db->num_rows($result);
    }
}