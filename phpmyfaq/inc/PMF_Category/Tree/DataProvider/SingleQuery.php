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
 * @since     2001-01-05
 */

/**
 * PMF_Category_Tree_DataProvider_SingleQuery
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Johannes Schlüter <johannes@schlueters.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-06
 */
class PMF_Category_Tree_DataProvider_SingleQuery
    extends PMF_Category_Abstract 
    implements PMF_Category_Tree_DataProvider_Interface 
{
    private $children = array();
    private $data = array();

    /**
     * Constructor
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $query = sprintf("
            SELECT
                a.id AS id,
                a.lang AS lang,
                a.parent_id AS parent_id,
                a.name AS name,
                a.description AS description,
                a.user_id AS user_id
            FROM
                %sfaqcategories a
                ",
            SQLPREFIX);

        $result = $this->db->query($query);

        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }

        while ($row = $this->db->fetch_assoc($result)) {
            $cat = new PMF_Category($row);
            $this->data[$row['id']] = $cat;
            if (!isset($this->children[$row['parent_id']])) {
                $this->children[$row['parent_id']] = array($cat);
            } else {
                $this->children[$row['parent_id']][] = $cat;
            }
        }

        foreach ($this->children as $parentid => $children) {
            if (!$parentid) {
                continue;
            }
            $parent = $this->data[$parentid];
            $parent->setChildcount(sizeof($children));
            foreach ($children as $child) {
                $child->setParent($parent);
            }
        }
    }
    
    /**
     * Fetches all category data from database
     *
     * @param integer $parentId Parent ID
     */
    public function getData($parentId = 0)
    {
        return new ArrayIterator($this->children[$parentId]);
    }

    /**
     *
     * @todo Shouldn't the Parameter be an Node?
     * @todo Shouldn't we return a List of nodes, not ids?
     * @param int $id
     */
    public function getPath($id)
    {
        $retval = array();
        while ($id) {
            array_unshift($retval, $id);
            $parent = $this->data[$id]->getParent();
            if (!$parent) {
                break;
            }
            $id = $parent->getId();
        }
        return $retval;
    }
}
