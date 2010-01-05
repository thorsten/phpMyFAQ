<?php
/**
 * Category path implementation based on RecursiveFilterIterator
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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-05
 */

/**
 * PMF_Category_Path
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Johannes Schlüter <johannes@schlueters.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-05
 */
class PMF_Category_Path extends RecursiveFilterIterator
{
    /**
     * Array with path
     *
     * @var array
     */
    private $path = array();
    
    /**
     * Constructor
     *
     * @param PMF_Category_Tree $categoryTree PMF_Category_Tree object
     * @param array             $path         Array with path
     * 
     * @return void
     */
    public function __construct(PMF_Category_Tree $categoryTree, Array $path)
    {
        parent::__construct($categoryTree);
        $this->path = $path;
    }
    
    /**
     * Return the inner iterator's children contained in a RecursiveFilterIterator
     *
     * @return PMF_Category_Path
     */
    public function getChildren()
    {
        return new self($this->getInnerIterator()->getChildren(), $this->path);
    }
    
    /**
     * Check whether the current element of the iterator is acceptable
     *
     * @return boolean
     */
    public function accept()
    {
        $parent = $this->getInnerIterator()->current()->getParent();
        /* if the parent is NULL we're on root level */
        return (!$parent || in_array($parent->getId(), $this->path));
    }
}