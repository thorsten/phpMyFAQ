<?php
/**
 * Layout rendering class for categories
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
 * @since     2001-01-08
 */

/**
 * PMF_Category_Layout
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-08
 */
 class PMF_Category_Layout
 {
    /**
     * PMF_Category_Tree_Helper object
     * 
     * @var PMF_Category_Tree_Helper
     */
    private $categoryTreeHelper = null;
    
    public function __construct(PMF_Category_Tree_Helper $categoryTreeHelper)
    {
        $this->categoryTreeHelper = $categoryTreeHelper;
    }
    
    /**
     * Renders the main navigation
     * 
     * @param integer $categoryId Selected category ID
     * 
     * @return string
     */
    public function renderNavigation($categoryId = 0)
    {
        $navigation = '';
        
        foreach ($this->categoryTreeHelper as $categoryId => $categoryName) {
            $navigation .= sprintf ("<li><a href=\"?action=show&amp;cat=%d\">%s</a></li>\n",
                $categoryId,
                $categoryName);
        }
        
        return $navigation;
    }
    
    /**
     * Get all categories in <option> tags
     *
     * @param array $categoryIds Array of selected category IDs
     */
    public function renderOptions(Array $categoryIds = null)
    {
        $options = '';
        
        foreach ($this->categoryTreeHelper as $categoryId => $categoryName) {
            $options .= sprintf('%s<option value="%d"%s>%s</option>',
                $this->categoryTreeHelper->indent,
                $categoryId,
                (in_array($categoryId) ? 'selected="selected"' : ''),
                $categoryName);
        }
        
        return $options;
    }
 }