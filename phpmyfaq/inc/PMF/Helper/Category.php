<?php
/**
 * Helper class for phpMyFAQ categories
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-07
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Helper_Category
 *
 * @category  phpMyFAQ
 * @package   Helper
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-09-07
 */
class PMF_Helper_Category extends PMF_Helper 
{
    /**
     * Constructor
     *
     * @return PMF_Helper_Category
     */
    public function __construct()
    {
    }

    /**
     * Renders the main navigation
     *
     * @param  integer $activeCategory Selected category
     *
     * @return string
     */
    public function renderNavigation($activeCategory = 0)
    {
        global $sids, $PMF_LANG;

        $open          = 0;
        $output        = '';
        $numCategories = $this->Category->height();
        $numFaqs       = $this->Category->getNumberOfRecordsOfCategory();
        
        if ($numCategories > 0) {
            for ($y = 0 ;$y < $numCategories; $y = $this->Category->getNextLineTree($y)) {
                
                list($hasChild, $name, $categoryId, $description) = $this->Category->getLineDisplay($y);

                if ($activeCategory == $categoryId) {
                    $isActive = true;
                } else {
                    $isActive = false;
                }

                $level     = $this->Category->treeTab[$y]['level'];
                $leveldiff = $open - $level;

                if ($this->_config->get('records.hideEmptyCategories') && !isset($numFaqs[$categoryId]) &&
                    '-' === $hasChild) {
                    continue;
                }

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
                    $output .= sprintf(
                        "\n%s<ul class=\"nav nav-list\">\n%s<li%s>",
                        str_repeat("\t", $level + 1),
                        str_repeat("\t", $level + 1),
                        $isActive ? ' class="active"' : ''
                    );
                } else {
                    $output .= sprintf(
                        "%s<li%s>",
                        str_repeat("\t", $level + 1),
                        $isActive ? ' class="active"' : ''
                    );
                }
                
                if (isset($this->Category->treeTab[$y]['symbol']) && $this->Category->treeTab[$y]['symbol'] == 'plus') {
                    $output .= $this->Category->addCategoryLink(
                        $sids, $categoryId, $name, $description, true, $isActive
                    );
                } else {
                    if ($this->Category->treeTab[$y]['symbol'] == 'minus') {
                        $name = ($this->Category->treeTab[$y]['parent_id'] == 0) 
                                ? 
                                $name 
                                : 
                                $this->Category->categoryName[$this->Category->treeTab[$y]['id']]['name'];
                        $output .= $this->Category->addCategoryLink(
                            $sids, $this->Category->treeTab[$y]['parent_id'], $name, $description, false, $isActive
                        );
                    } else {
                        $output .= $this->Category->addCategoryLink(
                            $sids, $categoryId, $name, $description, false, $isActive
                        );
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
     * Returns all top-level categories in <li> tags
     *
     * @return string
     */
    public function renderMainCategories()
    {
        $categories = '';
        foreach ($this->Category->categories as $cat) {
            if (0 === (int)$cat['parent_id']) {
                $categories .= sprintf(
                    '<li><a href="?action=show&cat=%d">%s</a></li>',
                    $cat['id'],
                    $cat['name']
                    );
            }
        }

        return $categories;
    }

    /**
     * Get all categories in <option> tags
     *
     * @param  array|integer $categoryId Category ID or array of category IDs
     *
     * @return string
     */
    public function renderOptions($categoryId = 0)
    {
        $categories = '';

        if (!is_array($categoryId)) {
            $categoryId = array(
                array(
                    'category_id'   => $categoryId,
                    'category_lang' => ''
                )
            );
        }

        $i = 0;
        foreach ($this->Category->catTree as $cat) {
            $indent = '';
            for ($j = 0; $j < $cat['indent']; $j++) {
                $indent .= '....';
            }
            $categories .= "\t<option value=\"".$cat['id']."\"";



            if (0 === $i && count($categoryId) === 0) {
                $categories .= ' selected';
            } else {
                foreach ($categoryId as $categoryid) {
                    if ($cat['id'] == $categoryid['category_id']) {
                        $categories .= ' selected';
                    }
                }
            }

            $categories .= ">";
            $categories .= $indent . $cat['name'] . "</option>\n";
            $i++;
        }

        return $categories;
    }
}