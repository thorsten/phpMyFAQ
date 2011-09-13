<?php
/**
 * Helper class for phpMyFAQ categories
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Helper
 * @license    MPL
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-07
 * @version    SVN: $Id$
 * @copyright  2009-2011 phpMyFAQ Team
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
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper
 * 
 * @package    phpMyFAQ
 * @subpackage PMF_Helper
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-07
 * @copyright  2009 phpMyFAQ Team
 */
class PMF_Helper_Category extends PMF_Helper 
{
    /**
     * Instance
     * 
     * @var PMF_Helper_Search
     */
    private static $instance = null;
    
    /**
     * Language
     * 
     * @var string
     */
    private $language = null;
    
    /**
     * Constructor
     * 
     * @return 
     */
    private function __construct()
    {
        
    }
    
    /**
     * Returns the single instance
     *
     * @access static
     * @return PMF_Helper_Category
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            $className = __CLASS__;
            self::$instance = new $className();
        }
        return self::$instance;
    }
   
    /**
     * __clone() Magic method to prevent cloning
     * 
     * @return void
     */
    private function __clone()
    {
        
    }

    /**
     * Renders the main navigation
     *
     * @param  integer $activeCategory Selected category
     * @return string
     */
    public function renderCategoryNavigation($activeCategory = 0)
    {
        global $sids, $PMF_LANG;

        $open          = 0;
        $output        = '';
        $numCategories = $this->Category->height();
        
        if ($numCategories > 0) {
            for ($y = 0 ;$y < $numCategories; $y = $this->Category->getNextLineTree($y)) {
                
                list($symbol, $name, $categoryId, $description) = $this->Category->getLineDisplay($y);

                if ($activeCategory == $categoryId) {
                    $isActive = true;
                } else {
                    $isActive = false;
                }

                $level     = $this->Category->treeTab[$y]['level'];
                $leveldiff = $open - $level;

                if ($leveldiff > 1) {
                    $output .= '</li>';
                    for ($i = $leveldiff; $i > 1; $i--) {
                        $output .= sprintf("\n%s</ul>\n%s</li>\n",
                            str_repeat("\t", $level + $i + 1),
                            str_repeat("\t", $level + $i));
                    }
                }

                if ($level < $open) {
                    if (($level - $open) == -1) {
                        $output .= '</li>';
                    }
                    $output .= "\n".str_repeat("\t", $level + 2)."</ul>\n".str_repeat("\t", $level + 1)."</li>\n";
                } elseif ($level == $open && $y != 0) {
                    $output .= "</li>\n";
                }

                if ($level > $open) {
                    $output .= sprintf("\n%s<ul class=\"subcat\">\n%s<li>",
                        str_repeat("\t", $level + 1),
                        str_repeat("\t", $level + 1));
                } else {
                    $output .= str_repeat("\t", $level + 1)."<li>";
                }

                if (isset($this->Category->treeTab[$y]['symbol']) && $this->Category->treeTab[$y]['symbol'] == 'plus') {
                    $output .= $this->Category->addCategoryLink($sids, $categoryId, $name, $description, true, $isActive);
                } else {
                    if ($this->Category->treeTab[$y]['symbol'] == 'minus') {
                        $name = ($this->Category->treeTab[$y]['parent_id'] == 0) 
                                ? 
                                $name 
                                : 
                                $this->Category->categoryName[$this->Category->treeTab[$y]['id']]['name'];
                        $output .= $this->Category->addCategoryLink($sids, $this->Category->treeTab[$y]['parent_id'], $name, $description, false, $isActive);
                    } else {
                        $output .= $this->Category->addCategoryLink($sids, $categoryId, $name, $description, false, $isActive);
                    }
                }
                $open = $level;
            }
            if ($open > 0) {
                $output .= str_repeat("</li>\n\t</ul>\n\t", $open);
            }
            $output .= "</li>";
            return $output;

        } else {
            $output = '<li><a href="#">'.$PMF_LANG['no_cats'].'</a></li>';
        }
        return $output;
    }

    /**
     * Get all categories in <option> tags
     *
     * @param  mixed $categoryId Category id or array of category ids
     * 
     * @return string
     */
    public function renderCategoryOptions($categoryId = '')
    {
        $categories = '';

        if (!is_array($categoryId)) {
            $categoryId = array(array('category_id'   => $categoryId, 
                                      'category_lang' => ''));
        }

        $i = 0;
        foreach ($this->Category->catTree as $cat) {
            $indent = '';
            for ($j = 0; $j < $cat['indent']; $j++) {
                $indent .= '....';
            }
            $categories .= "\t<option value=\"".$cat['id']."\"";

            if (0 == $i && count($categoryId) == 0) {
                $categories .= ' selected="selected"';
            } else {
                foreach ($categoryId as $categoryid) {
                    if ($cat['id'] == $categoryid['category_id']) {
                        $categories .= ' selected="selected"';
                    }
                }
            }

            $categories .= ">";
            $categories .= $indent.$cat['name'] . "</option>\n";
            $i++;
        }
        return $categories;
    }
       
}