<?php

/**
 * The main category class. Yes, it's huge.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <larstiedemann@yahoo.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2004-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-02-16
 */

namespace phpMyFAQ;

use phpMyFAQ\Category\CategoryEntity;
use phpMyFAQ\Helper\LanguageHelper;

/**
 * Class Category
 *
 * @package phpMyFAQ
 */
class Category
{
    /**
     * The categories as an array.
     *
     * @var array
     */
    public $categories = [];

    /**
     * The category names as an array.
     *
     * @var array
     */
    public $categoryName = [];

    /**
     * The image as an array.
     *
     * @var array
     */
    public $image = [];
    /**
     * The tree with the tabs.
     *
     * @var array
     */
    public $treeTab = [];
    /**
     * The category tree.
     *
     * @var array
     */
    private $catTree = [];

    /**
     * @var Configuration
     */
    private $config = null;

    /**
     * User ID.
     *
     * @var int
     */
    private $user = -1;

    /**
     * Groupd.
     *
     * @var array
     */
    private $groups = [-1];

    /**
     * The children nodes.
     *
     * @var array
     */
    private $children = [];
    /**
     * The current language.
     *
     * @var string
     */
    private $language = null;

    /**
     * Entity owners
     *
     * @var array
     */
    private $owner = [];

    /**
     * Entity moderators
     *
     * @var array
     */
    private $moderators = [];

    /**
     * Symbol for each item
     * NOTE: We do not use this currently.
     *
     * @var array
     */
    private $symbols = [
        'vertical' => '|',
        'plus' => '+',
        'minus' => '-',
        'space' => '&nbsp;',
        'angle' => '-',
        'medium' => '|-'
    ];

    /**
     * Constructor.
     *
     * @param Configuration $config Configuration object
     * @param array $groups Array with group IDs
     * @param bool $withPerm With or without permission check
     */
    public function __construct(Configuration $config, array $groups = [], bool $withPerm = true)
    {
        $this->config = $config;

        $this->setGroups($groups);
        $this->setLanguage($this->config->getLanguage()->getLanguage());

        $this->getOrderedCategories($withPerm);
        foreach ($this->categoryName as $id) {
            $this->categoryName[$id['id']]['level'] = $this->getLevelOf($this->categoryName[$id['id']]['id']);
        }
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        if (0 === count($groups)) {
            $groups = array(-1);
        }
        $this->groups = $groups;
    }

    /**
     * Sets language.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Returns all categories with ordered category IDs according to the user
     * and group permissions.
     *
     * @param bool $withPermission With or without permission check
     *
     * @return array
     */
    private function getOrderedCategories(bool $withPermission = true): array
    {
        $where = '';

        if ($withPermission) {
            $where = sprintf(
                '
                WHERE
                    ( fg.group_id IN (%s)
                OR
                    (fu.user_id = %d AND fg.group_id IN (%s)))
                AND
                    fc.active = 1',
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups)
            );
        }

        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $where .= empty($where) ? '
            WHERE' : '
            AND';
            $where .= "
                fc.lang = '" . $this->language . "'";
        }

        $query = sprintf(
            '
            SELECT
                fc.id AS id,
                fc.lang AS lang,
                fc.parent_id AS parent_id,
                fc.name AS name,
                fc.description AS description,
                fc.user_id AS user_id,
                fc.group_id AS group_id,
                fc.active AS active,
                fc.image AS image,
                fc.show_home AS show_home
            FROM
                %sfaqcategories fc
            LEFT JOIN
                %sfaqcategory_group fg
            ON
                fc.id = fg.category_id
            LEFT JOIN
                %sfaqcategory_user fu
            ON
                fc.id = fu.category_id
            %s
            GROUP BY
                fc.id, fc.lang, fc.parent_id, fc.name, fc.description, fc.user_id, fc.group_id, fc.active, fc.image, 
                fc.show_home
            ORDER BY
                fc.parent_id, fc.id',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $where
        );

        $result = $this->config->getDb()->query($query);

        if ($result) {
            while ($row = $this->config->getDb()->fetchArray($result)) {
                $this->categoryName[(int)$row['id']] = $row;
                $this->categories[(int)$row['id']] = $row;
                $this->children[(int)$row['parent_id']][(int)$row['id']] = &$this->categoryName[(int)$row['id']];
                $this->owner[(int)$row['id']] = &$row['user_id'];
                $this->moderators[(int)$row['id']] = &$row['group_id'];
            }
        }

        return $this->categories;
    }

    /**
     * Get the level of the item id.
     *
     * @param int $id Entity id
     *
     * @return int
     */
    private function getLevelOf(int $id): int
    {
        $alreadyListed = [$id];
        $level = 0;
        while ((isset($this->categoryName[$id]['parent_id'])) && ($this->categoryName[$id]['parent_id'] !== 0)) {
            ++$level;
            $id = (int)$this->categoryName[$id]['parent_id'];
            if (in_array($id, $alreadyListed)) {
                break;
            } else {
                array_push($alreadyListed, $id);
            }
        }

        return $level;
    }

    /**
     * @param int $userId
     */
    public function setUser($userId = -1)
    {
        $this->user = $userId;
    }

    /**
     * Gets the main categories and write them in an array.
     *
     * @param string $categories String of parent category ids
     * @param bool $parentId Only top level categories?
     *
     * @return array
     */
    public function getCategories(string $categories, bool $parentId = true): array
    {
        $_query = '';
        $query = sprintf(
            '
            SELECT
                id, lang, parent_id, name, description, user_id, group_id, active, image, show_home
            FROM
                %sfaqcategories
            WHERE ',
            Database::getTablePrefix()
        );

        if (true === $parentId) {
            $query .= 'parent_id = 0';
        }
        foreach (explode(',', $categories) as $cats) {
            $_query .= ' OR parent_id = ' . $cats;
        }
        if (false === $parentId && 0 < Strings::strlen($_query)) {
            $query .= Strings::substr($_query, 4);
        }
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " AND lang = '" . $this->language . "'";
        }
        $query .= ' ORDER BY id';
        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchArray($result)) {
            $this->categories[$row['id']] = $row;
        }

        return $this->categories;
    }

    /**
     * Gets all categories and write them in an array.
     *
     * @return array
     */
    public function getAllCategories()
    {
        $categories = [];
        $query = sprintf(
            'SELECT
                id, lang, parent_id, name, description, user_id, group_id, active, show_home, image
            FROM
                %sfaqcategories',
            Database::getTablePrefix()
        );
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " WHERE lang = '" . $this->language . "'";
        }
        $result = $this->config->getDb()->query($query);

        while ($row = $this->config->getDb()->fetchArray($result)) {
            $categories[(int)$row['id']] = [
                'id' => (int)$row['id'],
                'lang' => $row['lang'],
                'parent_id' => (int)$row['parent_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'user_id' => (int)$row['user_id'],
                'group_id' => (int)$row['group_id'],
                'active' => (int)$row['active'],
                'show_home' => (int)$row['show_home'],
                'image' => $row['image'],
                'level' => (int)$this->getLevelOf($row['id'])
            ];
        }

        return $categories;
    }

    /**
     * Gets all categories and write them in an array.
     *
     * @return array
     */
    public function getHomeCategories()
    {
        $categories = [];
        $query = sprintf(
            '
            SELECT
                id, lang, parent_id, name, description, user_id, group_id, active, show_home, image
            FROM
                %sfaqcategories
            WHERE 
                show_home = 1',
            Database::getTablePrefix()
        );
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " AND lang = '" . $this->language . "'";
        }
        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchArray($result)) {
            $url = sprintf('%s?action=show&amp;cat=%d', $this->config->getDefaultUrl(), $row['id']);
            $link = new Link($url, $this->config);
            $link->itemTitle = $row['name'];
            if ('' === $row['image']) {
                $image = 'data:image/png;base64,' .
                    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgDTD2qgAAAAASUVORK5CYII=';
            } else {
                $image = 'images/' . $row['image'];
            }

            $category = [
                'url' => $link->toString(),
                'name' => $row['name'],
                'description' => $row['description'],
                'image' => $image
            ];

            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * Gets all category IDs
     *
     * @return array
     */
    public function getAllCategoryIds()
    {
        $categories = [];

        $query = sprintf(
            '
            SELECT
                id
            FROM
                %sfaqcategories',
            Database::getTablePrefix()
        );

        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= sprintf(" WHERE lang = '%s'", $this->language);
        }

        $result = $this->config->getDb()->query($query);

        while ($row = $this->config->getDb()->fetchArray($result)) {
            $categories[] = (int)$row['id'];
        }

        return $categories;
    }

    /**
     * Builds the category tree.
     *
     * @param int $parentId Parent id
     * @param int $indent Indention
     */
    public function buildTree(int $parentId = 0, int $indent = 0)
    {
        $tt = [];
        $x = 0;

        foreach ($this->categories as $categoryId => $n) {
            if (isset($n['parent_id']) && $n['parent_id'] == $parentId && $categoryId > 0) {
                $tt[$x++] = $categoryId;
            }
        }

        if ($x != 0) {
            foreach ($tt as $d) {
                $tmp = [];
                if (isset($this->categories[$d])) {
                    foreach ($this->categories[$d] as $key => $value) {
                        $tmp[$key] = $value;
                    }
                    $tmp['indent'] = $indent;
                    $this->catTree[] = $tmp;
                    $this->buildTree($tmp['id'], $indent + 1);
                }
            }
        }
    }

    /**
     * Transforms the linear array in a 1D array in the order of the tree, with
     * the info.
     *
     * @param int $id Entity id
     */
    public function transform(int $id)
    {
        $parentId = $level = $showHome = 0;
        $tree = [];
        $tabs = isset($this->children[$id]) ? array_keys($this->children[$id]) : [];
        $num = count($tabs);
        $symbol = 'minus';
        $name = $description = '';
        $active = true;
        $image = '';

        if ($id > 0) {
            $active = $this->categoryName[$id]['active'];
            $description = $this->categoryName[$id]['description'];
            $name = $this->categoryName[$id]['name'];
            $parentId = $this->categoryName[$id]['parent_id'];
            $image = $this->categoryName[$id]['image'];
            $showHome = $this->categoryName[$id]['show_home'];
        }

        if ($num < 0) {
            $temp = isset($this->children[$parentId]) ? array_keys($this->children[$parentId]) : [];
            if (isset($temp[count($temp) - 1])) {
                $symbol = ($id == $temp[count($temp) - 1]) ? 'angle' : 'medium';
            }
        }

        $ascendants = $this->getNodes($id);
        $numAscendants = count($ascendants);

        if ($id > 0) {
            for ($i = 0; $i < $numAscendants; ++$i) {
                if (0 === (int)$ascendants[$i]) {
                    break;
                }
                $brothers = $this->getBrothers($ascendants[$i]);
                $tree[$i] = ($ascendants[$i] == $brothers[count($brothers) - 1]) ? 'space' : 'vertical';
            }
        }

        if ($id > 0) {
            $this->treeTab[] = array(
                'id' => $id,
                'symbol' => $symbol,
                'name' => $name,
                'numChilds' => count($tabs),
                'level' => $this->getLevelOf($id),
                'parent_id' => $parentId,
                'childs' => $tabs,
                'tree' => $tree,
                'description' => $description,
                'active' => $active,
                'image' => $image,
                'show_home' => $showHome
            );
        }

        foreach ($tabs as $i) {
            $this->transform($i);
        }
    }

    //

    /**
     * List in array the root, super-root, ... of the $id.
     *
     * @param  $id
     * @return array
     */
    private function getNodes(int $id): array
    {
        if (($id > 0) && (isset($this->categoryName[$id]['level']))) {
            $thisLevel = $this->categoryName[$id]['level'];
            $temp = [];
            for ($i = $thisLevel; $i > 0; --$i) {
                $id = $this->categoryName[$id]['parent_id'];
                array_unshift($temp, $id);
            }

            return $temp;
        }

        return [];
    }

    /**
     * Gets the list of the brothers of $id (include $id).
     *
     * @param int $id Brothers
     *
     * @return array
     */
    private function getBrothers(int $id): array
    {
        return $this->getChildren($this->categoryName[$id]['parent_id']);
    }

    /**
     * List in a array of the $id of the child.
     *
     * @param int $categoryId
     *
     * @return array
     */
    public function getChildren(int $categoryId): array
    {
        return isset($this->children[$categoryId]) ? array_keys($this->children[$categoryId]) : [];
    }

    /**
     * list in a array of the $id of the child.
     *
     * @param int $id Entity id
     *
     * @return array
     */
    public function getChildNodes($id)
    {
        $children = [];

        if (isset($this->children[$id])) {
            foreach (array_keys($this->children[$id]) as $childId) {
                $children = array_merge($children, array($childId));
                $children = array_merge($children, $this->getChildNodes($childId));
            }
        }

        return $children;
    }

    /**
     * Try to expand from the parent_id to the node $id
     *
     * @param int $id
     *
     * @return void
     */
    public function expandTo($id)
    {
        $this->collapseAll();
        $ascendants = $this->getNodes($id);
        $ascendants[] = $id;
        $numAscendants = count($ascendants);
        for ($i = 0; $i < $numAscendants; ++$i) {
            if (isset($this->treeTab[$this->getLineCategory($ascendants[$i])]['numChilds'])) {
                $numChildren = $this->treeTab[$this->getLineCategory($ascendants[$i])]['numChilds'];
                if ($numChildren > 0) {
                    $this->expand($ascendants[$i]);
                } else {
                    $i = count($ascendants);
                }
            }
        }
    }

    /**
     * Collapse the complete category tree.
     */
    public function collapseAll()
    {
        $numTreeTab = count($this->treeTab);
        for ($i = 0; $i < $numTreeTab; ++$i) {
            if ($this->treeTab[$i]['symbol'] == 'minus') {
                $this->treeTab[$i]['symbol'] = 'plus';
            }
        }
    }

    /**
     * Get the line number where to find the node $id in the category tree.
     *
     * @param int $id Entity id
     *
     * @return int
     */
    private function getLineCategory($id)
    {
        $num = count($this->treeTab);
        for ($i = 0; $i < $num; ++$i) {
            if (isset($this->treeTab[$i]['id']) && $this->treeTab[$i]['id'] == $id) {
                return $i;
            }
        }
    }

    /**
     * expand the node $id.
     *
     * @param int $id Entity id
     */
    public function expand($id)
    {
        $this->treeTab[$this->getLineCategory($id)]['symbol'] = 'minus';
    }

    /**
     * print the static tree with the number of records.
     *
     * @return string
     */
    public function viewTree()
    {
        global $sids, $plr;

        $number = [];

        $query = sprintf(
            '
            SELECT
                fcr.category_id AS category_id,
                count(fcr.category_id) AS number
            FROM
                %sfaqcategoryrelations fcr,
                %sfaqdata fd
            WHERE
                fcr.record_id = fd.id
            AND
                fcr.record_lang = fd.lang',
            Database::getTablePrefix(),
            Database::getTablePrefix()
        );

        if (strlen($this->language) > 0) {
            $query .= sprintf(
                " AND fd.lang = '%s'",
                $this->language
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
        $open = 0;
        $this->expandAll();

        for ($y = 0; $y < $this->height(); $y = $this->getNextLineTree($y)) {
            list($hasChild, $categoryName, $parent, $description) = $this->getLineDisplay($y);
            $level = $this->treeTab[$y]['level'];
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
                $numFaqs = ' <span class="badge badge-primary">' .
                    $plr->GetMsg('plmsgEntries', $number[$parent]) .
                    '</span>';
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
     * Expand the entire tree
     *
     * @return void
     */
    public function expandAll()
    {
        $numTreeTab = count($this->treeTab);
        for ($i = 0; $i < $numTreeTab; ++$i) {
            if ($this->treeTab[$i]['symbol'] == 'plus') {
                $this->treeTab[$i]['symbol'] = 'minus';
            }
        }
    }

    /**
     * Total height of the expanded tree.
     *
     * @return int
     */
    public function height()
    {
        return count($this->treeTab);
    }

    /**
     * Gets the next line in the array treeTab, depending of the
     * collapse/expand node.
     *
     * @param int $line Current line
     *
     * @return int
     */
    public function getNextLineTree($line)
    {
        if ($this->treeTab[$line]['symbol'] !== 'plus') {
            return $line + 1;
        } else {
            for ($i = $line + 1; $i < $this->height(); ++$i) {
                if ($this->treeTab[$i]['level'] <= $this->treeTab[$line]['level']) {
                    return $i;
                }
            }
        }

        return $this->height();
    }

    /**
     * Returns the four parts of a line to display: category name, the ID of
     * the root node, the description and if the category is active
     *
     * @param integer $node
     *
     * @return array
     */
    public function getLineDisplay($node)
    {
        return [
            $this->symbols[$this->treeTab[$node]['symbol']],
            $this->treeTab[$node]['name'],
            $this->treeTab[$node]['id'],
            $this->treeTab[$node]['description'],
            $this->treeTab[$node]['active'],
            $this->treeTab[$node]['image']
        ];
    }

    /**
     * Creates a category link.
     *
     * @param string $sids Session id
     * @param int $categoryId Parent category
     * @param string $categoryName Entity name
     * @param string $description Description
     * @param bool $hasChildren Child categories available
     * @param bool $isActive Sets a link active via CSS
     *
     * @return string
     */
    public function addCategoryLink(
        $sids,
        $categoryId,
        $categoryName,
        $description,
        $hasChildren = false,
        $isActive = false
    ) {
        $url = sprintf(
            '%s?%saction=show&amp;cat=%d',
            $this->config->getDefaultUrl(),
            $sids,
            $categoryId
        );

        $oLink = new Link($url, $this->config);
        $oLink->id = 'category_' . $categoryId;
        $oLink->itemTitle = $categoryName;
        $oLink->text = $categoryName;

        if ($hasChildren) {
            $oLink->text .= sprintf(
                '<i aria-hidden="true" class="fa fa-caret-right" title="%s"></i>',
                $categoryName
            );
        }

        if ($isActive) {
            $oLink->class = 'active';
        }

        $oLink->tooltip = $description;

        return $oLink->toHtmlAnchor();
    }

    /**
     * Returns the data of the given category.
     *
     * @param int $categoryId
     *
     * @return CategoryEntity
     */
    public function getCategoryData($categoryId)
    {
        $entity = new CategoryEntity();

        $query = sprintf(
            "SELECT * FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->language
        );

        $result = $this->config->getDb()->query($query);

        if ($row = $this->config->getDb()->fetchObject($result)) {
            $entity
                ->setId($row->id)
                ->setLang($row->lang)
                ->setParentId($row->parent_id)
                ->setName($row->name)
                ->setDescription($row->description)
                ->setUserId($row->user_id)
                ->setGroupId($row->group_id)
                ->setActive($row->active)
                ->setShowHome($row->show_home)
                ->setImage($row->image);
        }

        return $entity;
    }

    /**
     * Gets the path from root to child as breadcrumbs.
     *
     * @param int $id Entity ID
     * @param string $separator Path separator
     * @param bool $renderAsMicroData Renders breadcrumbs as HTML5 microdata
     * @param string $useCssClass Use CSS class "breadcrumb"
     *
     * @return string
     */
    public function getPath($id, $separator = ' / ', $renderAsMicroData = false, $useCssClass = 'breadcrumb')
    {
        global $sids;

        $ids = $this->getNodes($id);

        $num = count($ids);

        $temp = $catid = $desc = $breadcrumb = [];

        for ($i = 0; $i < $num; ++$i) {
            $t = $this->getLineCategory($ids[$i]);
            if (array_key_exists($t, $this->treeTab)) {
                $temp[] = $this->treeTab[$this->getLineCategory($ids[$i])]['name'];
                $catid[] = $this->treeTab[$this->getLineCategory($ids[$i])]['id'];
                $desc[] = $this->treeTab[$this->getLineCategory($ids[$i])]['description'];
            }
        }
        if (isset($this->treeTab[$this->getLineCategory($id)]['name'])) {
            $temp[] = $this->treeTab[$this->getLineCategory($id)]['name'];
            $catid[] = $this->treeTab[$this->getLineCategory($id)]['id'];
            $desc[] = $this->treeTab[$this->getLineCategory($id)]['description'];
        }

        // @todo Maybe this should be done somewhere else ...
        if ($renderAsMicroData) {
            foreach ($temp as $k => $category) {
                $url = sprintf(
                    '%s?%saction=show&amp;cat=%d',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $catid[$k]
                );
                $oLink = new Link($url, $this->config);
                $oLink->text = sprintf('<span itemprop="title">%s</span>', $category);
                $oLink->itemTitle = $category;
                $oLink->tooltip = $desc[$k];
                $oLink->setItemProperty('url');
                if (0 == $k) {
                    $oLink->setRelation('index');
                }

                $breadcrumb[] = sprintf(
                    '<li itemscope itemtype="http://data-vocabulary.org/Breadcrumb">%s</li>',
                    $oLink->toHtmlAnchor()
                );
            }

            $temp = $breadcrumb;

            return sprintf(
                '<ul class="%s">%s</ul>',
                $useCssClass,
                implode('', $temp)
            );
        } else {
            return implode($separator, $temp);
        }
    }

    /**
     * Returns the ID of a category that associated with the given article.
     *
     * @param int $faqId FAQ id
     *
     * @return int
     */
    public function getCategoryIdFromFaq($faqId)
    {
        $cats = $this->getCategoryIdsFromFaq($faqId);
        if (isset($cats[0])) {
            return $cats[0];
        } else {
            return 0;
        }
    }

    /**
     * Returns an array with the IDs of all categories that are associated with
     * the given article.
     *
     * @param int $faqId Record id
     *
     * @return array
     */
    public function getCategoryIdsFromFaq($faqId)
    {
        $categories = $this->getCategoriesFromFaq($faqId);
        $result = [];
        foreach ($categories as $category) {
            $result[] = $category['id'];
        }

        return $result;
    }

    /**
     * Returns all categories that are related to the given article-id and
     * the current language $this->language in an unsorted array which consists
     * of associative arrays with the keys 'name', 'id', 'lang',
     * 'parent_id' and 'description'.
     *
     * @param int $faqId Record id
     *
     * @return array
     */
    public function getCategoriesFromFaq($faqId)
    {
        $query = sprintf(
            "
            SELECT
                fc.id AS id,
                fc.lang AS lang,
                fc.parent_id AS parent_id,
                fc.name AS name,
                fc.description AS description
            FROM
                %sfaqcategoryrelations fcr,
                %sfaqcategories fc
            WHERE
                fc.id = fcr.category_id
            AND
                fcr.record_id = %d
            AND
                fcr.category_lang = '%s'
            AND
                fc.lang = '%s'",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $faqId,
            $this->language,
            $this->language
        );

        $result = $this->config->getDb()->query($query);
        $num = $this->config->getDb()->numRows($result);
        $this->categories = [];
        if ($num > 0) {
            while ($row = $this->config->getDb()->fetchArray($result)) {
                $this->categories[intval($row['id'])] = $row;
            }
        }

        return $this->categories;
    }

    /**
     * Given FAQ ID and category ID are connected or not.
     *
     * @param  $faqId
     * @param  $categoryId
     * @return bool
     */
    public function categoryHasLinkToFaq($faqId, $categoryId)
    {
        $categories = $this->getCategoriesFromFaq($faqId);
        foreach ($categories as $category) {
            if ((int)$category['id'] === (int)$categoryId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the category tree as array.
     *
     * @return array
     */
    public function getCategoryTree(): array
    {
        return $this->catTree;
    }

    /**
     * Returns the moderator group ID of the given category.
     *
     * @param int $categoryId
     *
     * @return int
     */
    public function getModeratorGroupId($categoryId)
    {
        return $this->moderators[$categoryId];
    }

    /**
     * Adds a new category entry.
     *
     * @param array $categoryData Array of category data
     * @param int $parentId Parent id
     * @param int $id Entity id
     *
     * @return int
     */
    public function addCategory(array $categoryData, $parentId = 0, $id = null)
    {
        // If we only need a new language, we don't need a new category id
        if (is_null($id)) {
            $id = $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqcategories', 'id');
        }

        $query = sprintf(
            "
            INSERT INTO
                %sfaqcategories
            (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
                VALUES
            (%d, '%s', %d, '%s', '%s', %d, %d, %d, '%s', %d)",
            Database::getTablePrefix(),
            $id,
            $categoryData['lang'],
            $parentId,
            $categoryData['name'],
            $categoryData['description'],
            $categoryData['user_id'],
            $categoryData['group_id'],
            $categoryData['active'],
            $categoryData['image'],
            $categoryData['show_home']
        );
        $this->config->getDb()->query($query);

        return $id;
    }

    /**
     * Updates an existent category entry.
     *
     * @param array $categoryData Array of category data
     *
     * @return bool
     */
    public function updateCategory(array $categoryData)
    {
        $query = sprintf(
            "
            UPDATE
                %sfaqcategories
            SET
                name = '%s',
                description = '%s',
                user_id = %d,
                group_id = %d,
                active = %d,
                show_home = %d,
                image = '%s'
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $categoryData['name'],
            $categoryData['description'],
            $categoryData['user_id'],
            $categoryData['group_id'],
            $categoryData['active'],
            $categoryData['show_home'],
            $categoryData['image'],
            $categoryData['id'],
            $categoryData['lang']
        );

        return $this->config->getDb()->query($query);
    }

    /**
     * Move the categories ownership for users.
     *
     * @param int $from Old user id
     * @param int $to New user id
     *
     * @return bool
     */
    public function moveOwnership($from, $to)
    {
        if (!is_numeric($from) || !is_numeric($to)) {
            return false;
        }

        $query = sprintf(
            '
            UPDATE
                %sfaqcategories
            SET
                user_id = %d
            WHERE
                user_id = %d',
            Database::getTablePrefix(),
            $to,
            $from
        );
        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Checks if a language is already defined for a category id.
     *
     * @param int $category_id Entity id
     * @param string $category_lang Entity language
     *
     * @return bool
     */
    public function checkLanguage($category_id, $category_lang)
    {
        $query = sprintf(
            "
            SELECT
                lang
            FROM
                %sfaqcategories
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $category_id,
            $category_lang
        );

        $result = $this->config->getDb()->query($query);

        return $this->config->getDb()->numRows($result);
    }

    /**
     * Swaps two categories.
     *
     * @param int $category_id_1 First category
     * @param int $category_id_2 Second category
     *
     * @return bool
     */
    public function swapCategories($category_id_1, $category_id_2)
    {
        $temp_cat = rand(200000, 400000);

        $tables = array(
            array('faqcategories' => 'id'),
            array('faqcategories' => 'parent_id'),
            array('faqcategoryrelations' => 'category_id'),
            array('faqcategory_group' => 'category_id'),
            array('faqcategory_user' => 'category_id'),
        );

        $result = true;
        foreach ($tables as $pair) {
            foreach ($pair as $_table => $_field) {
                $result = $result && $this->config->getDb()->query(
                    sprintf(
                        'UPDATE %s SET %s = %d WHERE %s = %d',
                        Database::getTablePrefix() . $_table,
                        $_field,
                        $temp_cat,
                        $_field,
                        $category_id_2
                    )
                );
                $result = $result && $this->config->getDb()->query(
                    sprintf(
                        'UPDATE %s SET %s = %d WHERE %s = %d',
                        Database::getTablePrefix() . $_table,
                        $_field,
                        $category_id_2,
                        $_field,
                        $category_id_1
                    )
                );
                $result = $result && $this->config->getDb()->query(
                    sprintf(
                        'UPDATE %s SET %s = %d WHERE %s = %d',
                        Database::getTablePrefix() . $_table,
                        $_field,
                        $category_id_1,
                        $_field,
                        $temp_cat
                    )
                );
            }
        }

        $tables2 = array(array('faqquestions' => 'category_id'));

        foreach ($tables2 as $pair) {
            foreach ($pair as $_table => $_field) {
                $result = $result && $this->config->getDb()->query(
                    sprintf(
                        "UPDATE %s SET %s = '%d' WHERE %s = '%d'",
                        Database::getTablePrefix() . $_table,
                        $_field,
                        $temp_cat,
                        $_field,
                        $category_id_2
                    )
                );
                $result = $result && $this->config->getDb()->query(
                    sprintf(
                        "UPDATE %s SET %s = '%d' WHERE %s = '%d'",
                        Database::getTablePrefix() . $_table,
                        $_field,
                        $category_id_2,
                        $_field,
                        $category_id_1
                    )
                );
                $result = $result && $this->config->getDb()->query(
                    sprintf(
                        "UPDATE %s SET %s = '%d' WHERE %s = '%d'",
                        Database::getTablePrefix() . $_table,
                        $_field,
                        $category_id_1,
                        $_field,
                        $temp_cat
                    )
                );
            }
        }

        return $result;
    }

    /**
     * Updates the parent category.
     *
     * @param int $category_id Entity id
     * @param int $parent_id Parent category id
     *
     * @return bool
     */
    public function updateParentCategory($category_id, $parent_id)
    {
        if ((!is_numeric($category_id) || !is_numeric($parent_id)) && $category_id != $parent_id) {
            return false;
        }

        $query = sprintf(
            '
            UPDATE
                %sfaqcategories
            SET
                parent_id = %d
            WHERE
                id = %d',
            Database::getTablePrefix(),
            $parent_id,
            $category_id
        );
        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Deletes a category.
     *
     * @param int $category_id Entity id
     * @param string $category_lang Categiry language
     * @param bool $delete_all Delete all languages?
     *
     * @return bool
     */
    public function deleteCategory($category_id, $category_lang, $delete_all = false)
    {
        $query = sprintf(
            '
            DELETE FROM
                %sfaqcategories
            WHERE
                id = %d',
            Database::getTablePrefix(),
            $category_id
        );
        if (!$delete_all) {
            $query .= " AND lang = '" . $category_lang . "'";
        }
        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Create array with translated categories.
     *
     * @param int $category_id
     *
     * @return array
     */
    public function getCategoryLanguagesTranslated($category_id)
    {
        global $languageCodes;

        $existcatlang = $this->config->getLanguage()->languageAvailable($category_id, 'faqcategories');
        $translated = [];

        foreach ($existcatlang as $language) {
            $query = sprintf(
                "
               SELECT
                  name, description
               FROM
                   %sfaqcategories
               WHERE
                   id = %d
               AND
                   lang = '%s'",
                Database::getTablePrefix(),
                $category_id,
                $language
            );
            $result = $this->config->getDb()->query($query);
            if ($row = $this->config->getDb()->fetchArray($result)) {
                $translated[$languageCodes[strtoupper($language)]] =
                    $row['name'] . ('' == $row['description'] ? '' : '  (' . $row['description'] . ')');
            }
        }
        ksort($translated);

        return $translated;
    }

    /**
     * Create all languages which can be used for translation as <option>.
     *
     * @param int $category_id Entity id
     * @param string $selected_lang Selected language
     *
     * @return string
     */
    public function getCategoryLanguagesToTranslate($category_id, $selected_lang)
    {
        $output = '';
        $existcatlang = $this->config->getLanguage()->languageAvailable($category_id, 'faqcategories');

        foreach (LanguageHelper::getAvailableLanguages() as $lang => $langname) {
            if (!in_array(strtolower($lang), $existcatlang)) {
                $output .= "\t<option value=\"" . strtolower($lang) . '"';
                if ($lang == $selected_lang) {
                    $output .= ' selected="selected"';
                }
                $output .= '>' . $langname . "</option>\n";
            }
        }

        return $output;
    }

    /**
     * Gets all categories which are not translated in actual language
     * to add in this->categories (used in admin section).
     */
    public function getMissingCategories()
    {
        $query = sprintf(
            '
            SELECT
                id, lang, parent_id, name, description, user_id
            FROM
                %sfaqcategories',
            Database::getTablePrefix()
        );
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " WHERE lang != '" . $this->language . "'";
        }
        $query .= ' ORDER BY id';
        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchArray($result)) {
            if (!array_key_exists($row['id'], $this->categoryName)) {
                $this->categoryName[$row['id']] = $row;
                $this->categories[$row['id']] = $row;
                $this->children[$row['parent_id']][$row['id']] = &$this->categoryName[$row['id']];
            }
        }
    }

    /**
     * Get number of nodes at the same parent_id level.
     *
     * @param int $parentId Parent id
     *
     * @return int
     */
    public function numParent(int $parentId): int
    {
        $query = sprintf(
            '
            SELECT DISTINCT 
                id
            FROM
                %sfaqcategories
            WHERE
                parent_id = %d',
            Database::getTablePrefix(),
            $parentId
        );
        $result = $this->config->getDb()->query($query);

        return $this->config->getDb()->numRows($result);
    }

    /**
     * Adds the category permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param array $categories ID of the current category
     * @param array $ids Array of group or user IDs
     *
     * @return bool
     */
    public function addPermission($mode, array $categories, array $ids)
    {
        if ('user' !== $mode && 'group' !== $mode) {
            return false;
        }

        foreach ($categories as $categoryId) {
            foreach ($ids as $id) {
                $query = sprintf(
                    'SELECT * FROM %sfaqcategory_%s WHERE category_id = %d AND %s_id = %d',
                    Database::getTablePrefix(),
                    $mode,
                    $categoryId,
                    $mode,
                    $id
                );

                if ($this->config->getDb()->numRows($this->config->getDb()->query($query))) {
                    continue;
                }

                $query = sprintf(
                    'INSERT INTO %sfaqcategory_%s (category_id, %s_id) VALUES (%d, %d)',
                    Database::getTablePrefix(),
                    $mode,
                    $mode,
                    $categoryId,
                    $id
                );

                $this->config->getDb()->query($query);
            }
        }

        return true;
    }

    /**
     * Deletes the category permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param array $categories ID of the current category
     *
     * @return bool
     */
    public function deletePermission($mode, $categories)
    {
        if (!($mode == 'user' || $mode == 'group')) {
            return false;
        }
        if (!is_array($categories)) {
            return false;
        }

        foreach ($categories as $category_id) {
            $query = sprintf(
                '
                DELETE FROM
                    %sfaqcategory_%s
                WHERE
                    category_id = %d',
                Database::getTablePrefix(),
                $mode,
                $category_id
            );
            $this->config->getDb()->query($query);
        }

        return true;
    }

    /**
     * Returns true, if a given category has user or group permissions.
     * Otherwise, the methods returns false.
     *
     * @param int $categoryId
     * @return bool
     */
    public function hasPermissions(int $categoryId): bool
    {
        $hasUserPermissions = $this->getPermissions('user', [$categoryId]);
        $hasGroupPermissions = $this->getPermissions('group', [$categoryId]);

        if ($hasUserPermissions[0] !== -1 || $hasGroupPermissions[0] !== -1) {
            return true;
        }

        return false;
    }

    /**
     * Returns the category permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param array $categories Array of category ids
     *
     * @return array
     */
    public function getPermissions(string $mode, array $categories): array
    {
        $permissions = [];
        if (!($mode === 'user' || $mode === 'group')) {
            return $permissions;
        }
        if (!is_array($categories)) {
            return $permissions;
        }

        $query = sprintf(
            '
            SELECT
                %s_id AS permission
            FROM
                %sfaqcategory_%s
            WHERE
                category_id IN (%s)',
            $mode,
            Database::getTablePrefix(),
            $mode,
            implode(', ', $categories)
        );

        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchObject($result)) {
            $permissions[] = (int)$row->permission;
        }

        return $permissions;
    }

    /**
     * Returns the user id of the category owner
     *
     * @param integer $categoryId
     * @return int
     */
    public function getOwner(int $categoryId): int
    {
        return isset($this->owner[$categoryId]) ? (int)$this->owner[$categoryId] : 1;
    }
}
