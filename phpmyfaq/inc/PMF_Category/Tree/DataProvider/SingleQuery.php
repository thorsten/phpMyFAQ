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

/* TODO: Rename and move me
 * TODO: Should $allnodes be an iterastor already? (we would have to clone it ...)
 */
/* private */ class NodeList extends FilterIterator {
    private $parent_id;
    public function __construct(array $allnodes, $parent_id) {
        parent::__construct(new ArrayIterator($allnodes));
        $this->parent_id = $parent_id;
    }

    public function accept() {
        $tmp = $this->current();
        return $tmp['parent_id'] == $this->parent_id;
    }
}

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

        $childcount = array();
        while ($row = $this->db->fetch_assoc($result)) {
            $this->data[$row['id']] = $row;
            if (!isset($childcount[$row['parent_id']])) {
                $childcount[$row['parent_id']] = 1;
            } else {
                $childcount[$row['parent_id']]++;
            }
        }

        foreach ($this->data as $id => $count) {
            if (isset($childcount[$id])) {
                $this->data[$id]['children'] = $childcount[$id];
            } else {
                $this->data[$id]['children'] = 0;
            }
        }
    }
    
    /**
     * Fetches data for categories which are children fromthe given parent
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
        return new NodeList($this->data, $parentId);
    }

    /**
     * Get the path to a Category.
     *
     * The array returned provides th ids of the Categories on the way to the
     * requested one, excluding the root element (0), but including the requested
     * id.
     *
     * @todo Shouldn't the Parameter be an Node?
     * @todo Shouldn't we return a List of nodes, not ids?
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
            $id = $this->data[$id]['parent_id'];
        }
        return $retval;
    }
}