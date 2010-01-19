<?php
/**
 * Dataprovider of PMF_Category_Tree class
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
 * @author    Johannes Schlüter <johannes@schlueters.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-06
 */

/**
 * PMF_Category_Tree_DataProvider_MultiQuery
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Johannes Schlüter <johannes@schlueters.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-06
 */
class PMF_Category_Tree_DataProvider_MultiQuery 
    extends PMF_Category_Abstract 
    implements PMF_Category_Tree_DataProvider_Interface 
{
    /**
     * Constructor
     * 
     * @param string $language Language
     * 
     * @return void
     */
    public function __construct($language = null)
    {
        parent::__construct();
        $this->setLanguage($language);
    }
    
    /**
     * Fetches data for categories which are children from the given parent
     *
     * The Iterator to be returned should provide arrays holding the Category
     * data as needed by the PMF_Category constructor.
     *
     * @see   PMF_Category::__construct()
     * @param integer $parentId Parent ID
     * 
     * @return Traversable
     */
    public function getData($parentId = 0)
    {
        $query = sprintf("
            SELECT 
                fc.id AS id,
                fc.lang AS lang,
                fc.parent_id AS parent_id,
                fc.name AS name,
                fc.description AS description,
                fc.user_id AS user_id,
                (SELECT count(*) FROM %sfaqcategories b WHERE b.parent_id = fc.id) as children 
            FROM 
                %sfaqcategories fc
            WHERE 
                fc.parent_id = %d",
            SQLPREFIX,
            SQLPREFIX,
            (int)$parentId);
        
        if (!is_null($this->language)) {
            $query .= sprintf(" 
            AND 
                fc.lang = '%s'",
            $this->language);
        }
        
        $query .= "
            ORDER BY fc.id";
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return new PMF_DB_Resultset($result);
    }

    /**
     * Get the path to a Category.
     *
     * The array returned provides th ids of the Categories on the way to the
     * requested one, excluding the root element (0), but including the requested
     * id.
     *
     * @param integer $id Category ID
     * 
     * @return array
     */
    public function getPath($id)
    {
        $retval = array();
        
        while ($id) {
            array_unshift($retval, $id);
            $query = sprintf("
                SELECT
                    parent_id
                FROM
                    %sfaqcategories a
                WHERE
                    id = %d",
                SQLPREFIX,
                $id);
            
            if (!is_null($this->language)) {
                $query .= sprintf(" 
                AND 
                    lang = '%s'",
                $this->language);
            }
            
            $result = $this->db->query($query);
            
            if (!$result) {
                throw new PMF_Exception($this->db->error());
            }
            
            $row = $this->db->fetch_assoc($result);
            
            if (!$row) {
                throw new PMF_Exception("Category not found");
            }
            
            $id = $row['parent_id'];
        }
        return $retval;
    }
}
