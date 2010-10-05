<?php
/**
 * The main category class
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
 * @package   PMF_Category
 * @author    Johannes Schlüter <johannes@schlueters.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2004-02-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Category
 *
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Johannes Schlüter <johannes@schlueters.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2004-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2004-02-16
 */
class PMF_Category
{
    /**
     * Category ID
     *
     * @var integer
     */
    private $id;
    
    /**
     * Category language
     *
     * @var string
     */
    private $language;
    
    /**
     * Category name
     *
     * @var string
     */
    private $name;
    
    /**
     * Number of children
     *
     * @var integer
     */
    private $children;
    
    /**
     * Parent ID
     *
     * @var integer
     */
    private $parent_id;
    
    /**
     * Parent PMF_Category object
     *
     * @var PMF_Category
     */
    private $parent;
    
    /**
     * Constructor
     *
     * @param array        $data   Array of category data
     * @param PMF_Category $parent Parent PMF_Category object
     * 
     * @return void
     */
    public function __construct(Array $data, PMF_Category $parent = null)
    {
        $this->id        = $data['id'];
        $this->language  = $data['lang'];
        $this->name      = $data['name'];
        $this->children  = isset($data['children']) ? $data['children'] : 0;
        $this->parent_id = $data['parent_id'];
        $this->parent    = $parent;
    }

    /**
     * Returns category name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
    
    /**
     * Returns category language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Returns current parent ID
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parent_id;
    }
    
    /**
     * Returns current category ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns parent PMF_Category object
     *
     * @return PMF_Category
     */
    public function getParent()
    {
        if (!$this->parent && $this->parent_id) {
            /* TODO: $this->parent = PMF_Category::createById() */
        }
        return $this->parent;
    }

    /**
     * Setter of parent PMF_Category object
     *
     * @param PMF_Category $parent Parent PMF_Category object
     * 
     * @return void
     */
    public function setParent(PMF_Category $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Setter of number of children
     * 
     * @param int $count Number of children
     * 
     * @return void
     */
    public function setChildcount($count)
    {
        $this->children = $count;
    }

    /**
     * Returns if category has children
     *
     * @return boolean
     */
    public function hasChildren()
    {
        return (bool)$this->children;
    }
}
