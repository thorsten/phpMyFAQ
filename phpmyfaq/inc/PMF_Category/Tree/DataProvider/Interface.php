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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-05
 */

/**
 * PMF_Category_Tree_DataProvider
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-05
 */
interface PMF_Category_Tree_DataProvider_Interface
{
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
    public function getData($parentId = 0);

    /**
     * Get the path to a Category.
     *
     * The array returned provides th ids of the Categories on the way to the
     * requested one, excluding the root element (0), but including the requested
     * id.
     *
     * @param  integer $id Category ID
     * 
     * @return array
     */
    public function getPath($id);
}