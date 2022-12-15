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
 * @copyright 2009-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-07
 */

namespace phpMyFAQ\Helper;

use phpMyFAQ\Database;
use phpMyFAQ\Helper;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;
use phpMyFAQ\User;

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
     * @return string
     */
    public function renderNavigation(int $activeCategory = 0): string
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
                }
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
    public function renderCategoryDropDown(): string
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
                    '%sindex.php?%saction=show&amp;cat=%d',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $parent
                );
                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = Strings::htmlentities($categoryName);
                $oLink->text = Strings::htmlentities($categoryName);
                $oLink->tooltip = !is_null($description) ?? Strings::htmlentities($description);

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
    public function renderMainCategories(): string
    {
        $categories = '';
        foreach ($this->Category->categories as $cat) {
            if (0 === (int)$cat['parent_id']) {
                $categories .= sprintf(
                    '<li><a href="?action=show&cat=%d">%s</a></li>',
                    $cat['id'],
                    Strings::htmlentities($cat['name'])
                );
            }
        }

        return $categories;
    }

    /**
     * Get all categories in <option> tags.
     *
     * @param int[]|int $categoryId CategoryHelper ID or array of category IDs
     *
     * @return string
     */
    public function renderOptions($categoryId): string
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
            $categories .= $indent . Strings::htmlentities($cat['name']) . "</option>\n";
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
            $decks .= '<div class="card mb-4"><a href="' . $category['url'] . '">';
            if ('' !== $category['image']) {
                $decks .= '<img class="card-img-top embed-responsive-item" width="200" alt="' .
                $category['name'] . '" src="' . $category['image'] . '" />';
            }
            $decks .= '</a>' .
                '<div class="card-body">' .
                '<h4 class="card-title text-center">' .
                '<a href="' . Strings::htmlentities($category['url']) . '">' .
                Strings::htmlentities($category['name']) . '</a>' .
                '</h4>' .
                '<p class="card-text">' . Strings::htmlentities($category['description']) . '</p>' .
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

    /**
     * Renders the static tree with the number of records.
     *
     * @return string
     */
    public function renderCategoryTree(): string
    {
        global $sids, $plr;

        $number = [];

        $query = sprintf(
            '
            SELECT
                fcr.category_id AS category_id,
                count(fcr.category_id) AS number
            FROM
                %sfaqcategoryrelations fcr
                JOIN %sfaqdata fd ON fcr.record_id = fd.id AND fcr.record_lang = fd.lang
                LEFT JOIN %sfaqdata_group AS fdg ON fd.id = fdg.record_id
                LEFT JOIN %sfaqdata_user AS fdu ON fd.id = fdu.record_id
                LEFT JOIN %sfaqcategory_group AS fcg ON fcr.category_id = fcg.category_id
                LEFT JOIN %sfaqcategory_user AS fcu ON fcr.category_id = fcu.category_id
            WHERE 1=1 
            ',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix()
        );

        if ($this->config->get('security.permLevel') !== 'basic') {
            if (-1 === $this->Category->getUser()) {
                $query .= sprintf(
                    'AND fdg.group_id IN (%s) AND fcg.group_id IN (%s)',
                    implode(', ', $this->Category->getGroups()),
                    implode(', ', $this->Category->getGroups())
                );
            } else {
                $query .= sprintf(
                    'AND ( fdg.group_id IN (%s) OR (fdu.user_id = %d OR fdg.group_id IN (%s)) )
                    AND ( fcg.group_id IN (%s) OR (fcu.user_id = %d OR fcg.group_id IN (%s)) )',
                    implode(', ', $this->Category->getGroups()),
                    $this->Category->getUser(),
                    implode(', ', $this->Category->getGroups()),
                    implode(', ', $this->Category->getGroups()),
                    $this->Category->getUser(),
                    implode(', ', $this->Category->getGroups())
                );
            }
        }

        if (strlen($this->config->getLanguage()->getLanguage()) > 0) {
            $query .= sprintf(
                " AND fd.lang = '%s'",
                $this->config->getLanguage()->getLanguage()
            );
        }

        $query .= " AND fd.active = 'yes' GROUP BY fcr.category_id";

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $number[$row->category_id] = $row->number;
            }
        }
        $output = '<ul class="pmf-category-overview">';
        $open = 1;
        $this->Category->expandAll();

        for ($y = 0; $y < $this->Category->height(); $y = $this->Category->getNextLineTree($y)) {
            list($hasChild, $categoryName, $parent, $description) = $this->Category->getLineDisplay($y);

            $level = $this->Category->treeTab[$y]['level'];
            $levelDiff = $open - $level;
            if (!isset($number[$parent])) {
                $number[$parent] = 0;
            }

            if ($this->config->get('records.hideEmptyCategories') && 0 === $number[$parent] && '-' === $hasChild) {
                continue;
            }

            if ($levelDiff > 1) {
                $output .= '</li>';
                for ($i = $levelDiff; $i > 1; --$i) {
                    $output .= '</ul></li>';
                }
            }

            if ($level < $open) {
                if (($level - $open) == -1) {
                    $output .= '</li>';
                }
                $output .= '</ul></li>';
            } elseif ($level == $open && $y != 0) {
                $output .= '</li>';
            }

            if ($level > $open) {
                $output .= sprintf(
                    '<ul><li data-category-id="%d" data-category-level="%d">',
                    $parent,
                    $level
                );
            } else {
                $output .= sprintf(
                    '<li data-category-id="%d" data-category-level="%d">',
                    $parent,
                    $level
                );
            }

            if (0 === $number[$parent] && 0 === $level) {
                $numFaqs = '';
            } else {
                $numFaqs = ' <span class="badge badge-primary badge-pill">' .
                    $plr->getMsg('plmsgEntries', $number[$parent]) .
                    '</span>';
            }

            $url = sprintf(
                '%sindex.php?%saction=show&amp;cat=%d',
                $this->config->getDefaultUrl(),
                $sids,
                $parent
            );
            $oLink = new Link($url, $this->config);
            $oLink->itemTitle = Strings::htmlentities($categoryName);
            $oLink->text = Strings::htmlentities($categoryName);
            $oLink->tooltip = !is_null($description) ?? Strings::htmlentities($description);

            $output .= $oLink->toHtmlAnchor() . $numFaqs;
            $open = $level;
        }

        if (isset($level) && $level > 0) {
            $output .= str_repeat('</li></ul>', $level);
        }

        $output .= '</li></ul>';

        return $output;
    }

    /**
     * Returns an array with all moderators for the given categories.
     *
     * @param int[] $categories
     * @return string[]
     */
    public function getModerators(array $categories): array
    {
        $recipients = [];

        $user = new User($this->config);

        foreach ($categories as $_category) {
            $userId = $this->Category->getOwner($_category);
            $groupId = $this->Category->getModeratorGroupId($_category);

            $user->getUserById($userId);
            $catOwnerEmail = $user->getUserData('email');

            // Avoid to send multiple emails to the same owner
            if (!empty($catOwnerEmail) && !isset($send[$catOwnerEmail])) {
                $recipients[] = $catOwnerEmail;
            }

            if ($groupId > 0) {
                $moderators = $user->perm->getGroupMembers($groupId);
                foreach ($moderators as $moderator) {
                    $user->getUserById($moderator);
                    $moderatorEmail = $user->getUserData('email');

                    // Avoid to send multiple emails to the same moderator
                    if (!empty($moderatorEmail) && !isset($send[$moderatorEmail])) {
                        $recipients[] = $moderatorEmail;
                    }
                }
            }
        }

        return array_unique($recipients);
    }

    /**
     * Renders the <option> tags for the available translations for a given category.
     *
     * @param int $categoryId
     * @return string
     */
    public function renderAvailableTranslationsOptions(int $categoryId): string
    {
        $options = '';
        $availableTranslations = $this->config->getLanguage()->languageAvailable($categoryId, 'faqcategories');
        $availableLanguages = LanguageHelper::getAvailableLanguages();

        foreach ($availableTranslations as $language) {
            $options .= sprintf('<option value="%s">%s</option>', $language, $availableLanguages[$language]);
        }

        return $options;
    }
}
