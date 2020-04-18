<?php

/**
 * Helper class for phpMyFAQ categories.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Helper;
use phpMyFAQ\Link;

/**
 * Class CategoryHelper
 *
 * @package phpMyFAQ\Helper
 */
class CategoryHelper extends Helper
{
    /**
     * Renders the main navigation.
     *
     * @param int $activeCategory Selected category
     *
     * @return string
     */
    public function renderNavigation($activeCategory = 0)
    {
        global $sids, $PMF_LANG;

        $open = 0;
        $output = '';
        $numCategories = $this->Category->height();
        $numFaqs = $this->categoryRelation->getNumberOfFaqsPerCategory();

        if ($numCategories > 0) {
            for ($y = 0; $y < $numCategories; $y = $this->Category->getNextLineTree($y)) {
                list($hasChild, $name, $categoryId, $description, $active) = $this->Category->getLineDisplay($y);

                if (!$active) {
                    continue;
                }

                if ($activeCategory == $categoryId) {
                    $isActive = true;
                } else {
                    $isActive = false;
                }

                $level = $this->Category->treeTab[$y]['level'];
                $leveldiff = $open - $level;

                if (
                    $this->config->get('records.hideEmptyCategories') && !isset($numFaqs[$categoryId])
                    && '-' === $hasChild
                ) {
                    continue;
                }

                if ($leveldiff > 1) {
                    $output .= '</li>';
                    for ($i = $leveldiff; $i > 1; --$i) {
                        $output .= sprintf(
                            "\n%s</ul>\n%s</li>\n",
                            str_repeat("\t", $level + $i + 1),
                            str_repeat("\t", $level + $i)
                        );
                    }
                }

                if ($level < $open) {
                    if (($level - $open) == -1) {
                        $output .= '</li>';
                    }
                    $output .= "\n" . str_repeat("\t", $level + 2) . "</ul>\n" .
                        str_repeat("\t", $level + 1) . "</li>\n";
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
                        '%s<li%s>',
                        str_repeat("\t", $level + 1),
                        $isActive ? ' class="active"' : ''
                    );
                }

                if (isset($this->Category->treeTab[$y]['symbol']) && $this->Category->treeTab[$y]['symbol'] == 'plus') {
                    $output .= $this->Category->addCategoryLink(
                        $sids,
                        $categoryId,
                        $name,
                        $description,
                        true,
                        $isActive
                    );
                } else {
                    if ($this->Category->treeTab[$y]['symbol'] == 'minus') {
                        $name = ($this->Category->treeTab[$y]['parent_id'] === 0)
                            ?
                            $name
                            :
                            $this->Category->categoryName[$this->Category->treeTab[$y]['id']]['name'];
                        $output .= $this->Category->addCategoryLink(
                            $sids,
                            $categoryId,
                            $name,
                            $description,
                            false,
                            $isActive
                        );
                    } else {
                        $output .= $this->Category->addCategoryLink(
                            $sids,
                            $categoryId,
                            $name,
                            $description,
                            false,
                            $isActive
                        );
                    }
                }
                $open = $level;
            }
            if ($open > 0) {
                $output .= str_repeat("</li>\n\t</ul>\n\t", $open);
            }
            $output .= '</li>';

            return $output;
        } else {
            $output = '<li><a href="#">' . $PMF_LANG['no_cats'] . '</a></li>';
        }

        return $output;
    }

    /**
     * Renders the main navigation dropdown.
     *
     * @return string
     */
    public function renderCategoryDropDown()
    {
        global $sids, $PMF_LANG;

        $open = 0;
        $output = '';
        $numCategories = $this->Category->height();

        $this->Category->expandAll();

        if ($numCategories > 0) {
            for ($y = 0; $y < $this->Category->height(); $y = $this->Category->getNextLineTree($y)) {
                list($hasChild, $categoryName, $parent, $description, $active) = $this->Category->getLineDisplay($y);

                if (!$active) {
                    continue;
                }

                $level = $this->Category->treeTab[$y]['level'];
                $leveldiff = $open - $level;
                $numChildren = $this->Category->treeTab[$y]['numChilds'];

                if (!isset($number[$parent])) {
                    $number[$parent] = 0;
                }

                if ($this->config->get('records.hideEmptyCategories') && 0 === $number[$parent] && '-' === $hasChild) {
                    continue;
                }

                if ($leveldiff > 1) {
                    $output .= '</li>';
                    for ($i = $leveldiff; $i > 1; --$i) {
                        $output .= sprintf(
                            "\n%s</ul>\n%s</li>\n",
                            str_repeat("\t", $level + $i + 1),
                            str_repeat("\t", $level + $i)
                        );
                    }
                }

                if ($level < $open) {
                    if (($level - $open) == -1) {
                        $output .= '</li>';
                    }
                    $output .= sprintf(
                        "\n%s</ul>\n%s</li>\n",
                        str_repeat("\t", $level + 2),
                        str_repeat("\t", $level + 1)
                    );
                } elseif ($level == $open && $y != 0) {
                    $output .= "</li>\n";
                }

                if ($level > $open) {
                    $output .= sprintf(
                        "\n%s<ul class=\"dropdown-menu\">\n%s",
                        str_repeat("\t", $level + 1),
                        str_repeat("\t", $level + 1)
                    );
                    if ($numChildren > 0) {
                        $output .= '<li class="dropdown-submenu">';
                    } else {
                        $output .= '<li>';
                    }
                } else {
                    $output .= str_repeat("\t", $level + 1);
                    if ($numChildren > 0) {
                        $output .= '<li class="dropdown-submenu">';
                    } else {
                        $output .= '<li>';
                    }
                }

                $url = sprintf(
                    '%s?%saction=show&amp;cat=%d',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $parent
                );
                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $categoryName;
                $oLink->text = $categoryName;
                $oLink->tooltip = $description;

                $output .= $oLink->toHtmlAnchor();
                $open = $level;
            }

            if (isset($level) && $level > 0) {
                $output .= str_repeat("</li>\n\t</ul>\n\t", $level);
            }

            return $output;
        } else {
            $output = '<li><a href="#">' . $PMF_LANG['no_cats'] . '</a></li>';
        }

        return $output;
    }

    /**
     * Returns all top-level categories in <li> tags.
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
     * Get all categories in <option> tags.
     *
     * @param array|int $categoryId CategoryHelper ID or array of category IDs
     *
     * @return string
     */
    public function renderOptions($categoryId)
    {
        $categories = '';

        if (!is_array($categoryId)) {
            $categoryId = [
                [
                    'category_id' => $categoryId,
                    'category_lang' => '',
                ],
            ];
        } elseif (isset($categoryId['category_id'])) {
            $categoryId = [$categoryId];
        }

        $i = 0;
        foreach ($this->Category->getCategoryTree() as $cat) {
            $indent = '';
            for ($j = 0; $j < $cat['indent']; ++$j) {
                $indent .= '....';
            }
            $categories .= "\t<option value=\"" . $cat['id'] . '"';

            if (0 === $i && count($categoryId) === 0) {
                $categories .= ' selected';
            } else {
                foreach ($categoryId as $categorised) {
                    if ($cat['id'] == $categorised['category_id']) {
                        $categories .= ' selected';
                    }
                }
            }

            $categories .= '>';
            $categories .= $indent . $cat['name'] . "</option>\n";
            ++$i;
        }

        return $categories;
    }

    /**
     * Renders the start page category card decks
     * @param array $categories
     * @return string
     */
    public function renderStartPageCategories(array $categories): string
    {
        if (count($categories) === 0) {
            return '';
        }

        $decks = '';
        $key = 1;
        foreach ($categories as $category) {
            $decks .=
                '<div class="card mb-4">' .
                '<a href="' . $category['url'] . '">' .
                '<img class="card-img-top img-fluid" width="200" src="' . $category['image'] . '" alt="' .
                $category['name'] . '" />' .
                '</a>' .
                '<div class="card-body">' .
                '<h4 class="card-title text-center">' .
                '<a href="' . $category['url'] . '">' . $category['name'] . '</a>' .
                '</h4>' .
                '<p class="card-text">' . $category['description'] . '</p>' .
                '</div>' .
                '</div>';
            if ($key % 2 === 0) {
                $decks .= '<div class="w-100 d-none d-sm-block d-md-none"></div>';
            }
            if ($key % 3 === 0) {
                $decks .= '<div class="w-100 d-none d-md-block d-lg-none"></div>';
            }
            if ($key % 4 === 0) {
                $decks .= '<div class="w-100 d-none d-lg-block d-xl-block"></div>';
            }
            $key++;
        }

        return $decks;
    }
}
