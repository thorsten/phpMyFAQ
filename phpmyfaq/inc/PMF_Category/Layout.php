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
     * @param integer $selectedCategoryId Selected category ID
     * 
     * @return string
     */
    public function renderNavigation($selectedCategoryId = 0)
    {
        global $sids;
        
        $navigation   = '';
        $currentDepth = 0;
        foreach ($this->categoryTreeHelper as $categoryId => $categoryName) {
            
            $indent      = str_repeat(' ', $this->categoryTreeHelper->indent);
            $depth       = $currentDepth - $this->categoryTreeHelper->getDepth();
            $hasChildren = $this->categoryTreeHelper->callHasChildren();
            $isActive    = ($selectedCategoryId == $categoryId) ? true : false;
            
            if ($depth > 1) {
                for ($i = $depth; $i > 1; $i--) {
                   $navigation .= sprintf("\n%s</ul></li>\n", str_repeat(' ', $depth * 4), $indent);
                }
            }
            
            if ($currentDepth < $this->categoryTreeHelper->getDepth()) {
                $navigation .= sprintf("\n%s<ul>\n", $indent);
            } 
            if ($currentDepth > $this->categoryTreeHelper->getDepth()) {
                $navigation .= sprintf("%s</ul>\n", $indent);
            }
            $navigation .= sprintf ("%s<li>%s%s",
                $indent,
                $this->renderLink($sids, $categoryId, $categoryName, $hasChildren, $isActive),
                $hasChildren ? '' : "</li>\n");
                
            $currentDepth = $this->categoryTreeHelper->getDepth();
        }
        
        return $navigation;
    }
    
    /**
     * Renders the category tree
     *
     * @return string
     */
    public function renderTree()
    {
        global $sids;
        
        $tree         = "<ul>\n";
        $currentDepth = 0;
        foreach ($this->categoryTreeHelper as $categoryId => $categoryName) {
            
            $indent      = str_repeat(' ', $this->categoryTreeHelper->indent);
            $depth       = $currentDepth - $this->categoryTreeHelper->getDepth();
            $hasChildren = $this->categoryTreeHelper->callHasChildren();
            
            if ($depth > 1) {
                for ($i = $depth; $i > 1; $i--) {
                   $tree .= sprintf("\n%s</ul></li>\n", str_repeat(' ', $depth * 4), $indent);
                }
            }
            
            if ($currentDepth < $this->categoryTreeHelper->getDepth()) {
                $tree .= sprintf("\n%s<ul>\n", $indent);
            } 
            if ($currentDepth > $this->categoryTreeHelper->getDepth()) {
                $tree .= sprintf("%s</ul>\n", $indent);
            }
            
            $url              = sprintf('%saction=show&amp;cat=%d', $sids, $categoryId);
            $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri() . '?' . $url);
            $oLink->itemTitle = $oLink->text = $categoryName;
            
            $tree .= "<li>" . $oLink->toHtmlAnchor();
            $tree .= $hasChildren ? '' : "</li>\n";
            
            $currentDepth = $this->categoryTreeHelper->getDepth();
        }
        $tree .= "</ul>\n";
        
        return $tree;
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
            $options .= sprintf('<option value="%d"%s>%s%s</option>',
                $categoryId,
                (in_array($categoryId, $categoryIds) ? 'selected="selected"' : ''),
                str_repeat('.', $this->categoryTreeHelper->indent),
                $categoryName) . "\n";
        }
        
        return $options;
    }
    
    /**
     * Renders a category link
     *
     * @param  string  $sids         Session id
     * @param  integer $categoryId   Parent category
     * @param  string  $categoryName Category name
     * @param  boolean $hasChildren  Child categories available
     * @param  boolean $isActive     Sets a link active via CSS
     * @return  string
     */
    public function renderLink($sids, $categoryId, $categoryName, $hasChildren = false, $isActive = false)
    {
        $url              = sprintf('%saction=show&amp;cat=%d', $sids, $categoryId);
        $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
        $oLink->itemTitle = $oLink->text = $oLink->tooltip = $categoryName;
        
        if ($hasChildren) {
            $oLink->text .= sprintf(' <img src="images/more.gif" width="11" height="11" alt="%s" style="border: none; vertical-align: middle;" />',
                $categoryName);
        }
        
        if ($isActive) {
            $oLink->class = 'active';
        }
        
        return $oLink->toHtmlAnchor();
    }
    
    /**
     * Renders a breadcrumb along the given path
     *
     * @param array  $path Array of path
     * @param string $separator Separator
     * 
     * @return string
     */
    public function renderBreadcrumb(Array $path, $separator = ' &raquo; ')
    {
        $breadcrumb = '';
        
        foreach ($this->categoryTreeHelper as $categoryId => $categoryName) {
            if (in_array($categoryId, $path)) {
                $breadcrumb .= $categoryName . $separator;
            } 
        }
        return $breadcrumb;
    }
 }