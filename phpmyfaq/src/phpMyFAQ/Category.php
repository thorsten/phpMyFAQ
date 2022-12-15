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
 * @copyright 2004-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-02-16
 */

namespace phpMyFAQ;

use phpMyFAQ\Entity\CategoryEntity;
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
     * @var array<int>
     */
    public $categories = [];

    /**
     * The category names as an array.
     *
     * @var array<string>
     */
    public $categoryName = [];

    /**
     * The image as an array.
     *
     * @var array
     */
    public array $image = [];

    /**
     * The tree with the tabs.
     *
     * @var array
     */
    public array $treeTab = [];
    /**
     * The category tree.
     *
     * @var array
     */
    private array $catTree = [];

    /**
     * @var Configuration
     */
    private Configuration $config;

    /**
     * User ID.
     *
     * @var int
     */
    private int $user = -1;

    /**
     * Groups.
     *
     * @var int[]
     */
    private array $groups = [-1];

    /**
     * The children nodes.
     *
     * @var array
     */
    private array $children = [];

    /**
     * The current language.
     *
     * @var string|null
     */
    private ?string $language = null;

    /**
     * Entity owners
     *
     * @var array<int, int>>
     */
    private $owner = [];

    /**
     * Entity moderators
     *
     * @var array<int, int>>
     */
    private $moderators = [];

    /**
     * Symbol for each item
     * NOTE: We do not use this currently.
     *
     * @var string[]
     */
    private array $symbols = [
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
     * @param int[] $groups Array with group IDs
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
     * @param int[] $groups
     * @return Category
     */
    public function setGroups(array $groups): Category
    {
        if (0 === count($groups)) {
            $groups = [-1];
        }
        $this->groups = $groups;
        return $this;
    }

    /**
     * @param string $language
     * @return Category
     */
    public function setLanguage(string $language): Category
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return int
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @return int[]
     */
    public function getGroups(): array
    {
        return $this->groups;
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
                fc.lang = '" . $this->config->getDb()->escape($this->language) . "'";
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
                %sfaqcategory_order fco
            ON
                fc.id = fco.category_id
            LEFT JOIN
                %sfaqcategory_user fu
            ON
                fc.id = fu.category_id
            %s
            GROUP BY
                fc.id, fc.lang, fc.parent_id, fc.name, fc.description, fc.user_id, fc.group_id, fc.active, fc.image, 
                fc.show_home
            ORDER BY
                fco.position, fc.id ASC',
            Database::getTablePrefix(),
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
     * @return Category
     */
    public function setUser(int $userId = -1): Category
    {
        $this->user = $userId;
        return $this;
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
            $query .= " AND lang = '" . $this->config->getDb()->escape($this->language) . "'";
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
    public function getAllCategories(): array
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
            $query .= " WHERE lang = '" . $this->config->getDb()->escape($this->language) . "'";
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
                'level' => $this->getLevelOf($row['id'])
            ];
        }

        return $categories;
    }

    /**
     * Gets all categories and write them in an array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHomeCategories(): array
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
            $url = sprintf('%sindex.php?action=show&cat=%d', $this->config->getDefaultUrl(), $row['id']);
            $link = new Link($url, $this->config);
            $link->itemTitle = $row['name'];
            if ('' === $row['image']) {
                $image = '';
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
    public function getAllCategoryIds(): array
    {
        $categories = [];

        $query = sprintf('SELECT id FROM %sfaqcategories', Database::getTablePrefix());

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
    public function buildCategoryTree(int $parentId = 0, int $indent = 0)
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
                    $this->buildCategoryTree($tmp['id'], $indent + 1);
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
    public function transform(int $id): void
    {
        $parentId = $showHome = 0;
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

        if ($num > 0) {
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
            $this->treeTab[] = [
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
            ];
        }

        foreach ($tabs as $i) {
            $this->transform($i);
        }
    }

    //

    /**
     * List in array the root, super-root, ... of the $id.
     *
     * @param int $id
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
     * @return array<int>
     */
    public function getChildNodes(int $id): array
    {
        $children = [];

        if (isset($this->children[$id])) {
            foreach (array_keys($this->children[$id]) as $childId) {
                $children = array_merge($children, [$childId]);
                $children = array_merge($children, $this->getChildNodes($childId));
            }
        }

        return $children;
    }

    /**
     * Try to expand from the parent_id to the node $id
     *
     * @param int $id
     * @return void
     */
    public function expandTo(int $id)
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
    public function collapseAll(): void
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
     * @return int
     */
    private function getLineCategory(int $id): int
    {
        $num = count($this->treeTab);
        for ($i = 0; $i < $num; ++$i) {
            if (isset($this->treeTab[$i]['id']) && $this->treeTab[$i]['id'] === $id) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Expand the node $id.
     *
     * @param int $id Entity id
     */
    public function expand(int $id): void
    {
        $this->treeTab[$this->getLineCategory($id)]['symbol'] = 'minus';
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
    public function height(): int
    {
        return count($this->treeTab);
    }

    /**
     * Gets the next line in the array treeTab, depending on the
     * collapse/expand node.
     *
     * @param int $line Current line
     * @return int
     */
    public function getNextLineTree(int $line): int
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
     * @param int $node
     * @return array
     */
    public function getLineDisplay(int $node): array
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
     * @param string      $sids Session id
     * @param int         $categoryId Parent category
     * @param string      $categoryName Entity name
     * @param string|null $description Description
     * @param bool        $hasChildren Child categories available
     * @param bool        $isActive Sets a link active via CSS
     * @return string
     */
    public function addCategoryLink(
        string $sids,
        int $categoryId,
        string $categoryName,
        string $description = null,
        bool $hasChildren = false,
        bool $isActive = false
    ): string {
        $url = sprintf(
            '%sindex.php?%saction=show&amp;cat=%d',
            $this->config->getDefaultUrl(),
            $sids,
            $categoryId
        );

        $oLink = new Link($url, $this->config);
        $oLink->id = 'category_' . $categoryId;
        $oLink->itemTitle = Strings::htmlentities($categoryName);
        $oLink->text = Strings::htmlentities($categoryName);

        if ($hasChildren) {
            $oLink->text .= sprintf(
                '<i aria-hidden="true" class="fa fa-caret-right" title="%s"></i>',
                Strings::htmlentities($categoryName)
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
     * @return CategoryEntity
     */
    public function getCategoryData(int $categoryId): CategoryEntity
    {
        $entity = new CategoryEntity();

        $query = sprintf(
            "SELECT * FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->config->getDb()->escape($this->language)
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
     * @param int    $id Entity ID
     * @param string $separator Path separator
     * @param bool   $renderAsHtml Renders breadcrumbs as HTML
     * @param string $useCssClass Use CSS class "breadcrumb"
     * @return string
     */
    public function getPath(
        int $id,
        string $separator = ' / ',
        bool $renderAsHtml = false,
        string $useCssClass = 'breadcrumb'
    ): string {
        global $sids;

        $ids = $this->getNodes($id);

        $num = count($ids);

        $tempName = $categoryId = $description = $breadcrumb = [];

        for ($i = 0; $i < $num; ++$i) {
            $t = $this->getLineCategory($ids[$i]);
            if (array_key_exists($t, $this->treeTab)) {
                $tempName[] = $this->treeTab[$this->getLineCategory($ids[$i])]['name'];
                $categoryId[] = $this->treeTab[$this->getLineCategory($ids[$i])]['id'];
                $description[] = $this->treeTab[$this->getLineCategory($ids[$i])]['description'];
            }
        }
        if (isset($this->treeTab[$this->getLineCategory($id)]['name'])) {
            $tempName[] = $this->treeTab[$this->getLineCategory($id)]['name'];
            $categoryId[] = $this->treeTab[$this->getLineCategory($id)]['id'];
            $description[] = $this->treeTab[$this->getLineCategory($id)]['description'];
        }

        // @todo Maybe this should be done somewhere else ...
        if ($renderAsHtml) {
            foreach ($tempName as $key => $category) {
                $url = sprintf(
                    '%sindex.php?%saction=show&amp;cat=%d',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $categoryId[$key]
                );
                $oLink = new Link($url, $this->config);
                $oLink->text = sprintf('<span>%s</span>', $category);
                $oLink->itemTitle = Strings::htmlentities($category);
                $oLink->tooltip = Strings::htmlentities($description[$key]);
                if (0 === $key) {
                    $oLink->setRelation('index');
                }

                $breadcrumb[] = sprintf(
                    '<li>%s</li>',
                    $oLink->toHtmlAnchor()
                );
            }

            $tempName = $breadcrumb;

            return sprintf(
                '<ul class="%s">%s</ul>',
                $useCssClass,
                implode('', $tempName)
            );
        } else {
            return implode($separator, $tempName);
        }
    }

    /**
     * Returns the ID of a category that associated with the given article.
     *
     * @param int $faqId FAQ id
     * @return int
     */
    public function getCategoryIdFromFaq(int $faqId): int
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
     * @return int[]
     */
    public function getCategoryIdsFromFaq(int $faqId): array
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
     * @return array
     */
    public function getCategoriesFromFaq(int $faqId): array
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
            $this->config->getDb()->escape($this->language),
            $this->config->getDb()->escape($this->language)
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
     * @param int $faqId
     * @param int $categoryId
     * @return bool
     */
    public function categoryHasLinkToFaq(int $faqId, int $categoryId): bool
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
     * @return int
     */
    public function getModeratorGroupId(int $categoryId): int
    {
        return $this->moderators[$categoryId];
    }

    /**
     * Adds a new category entry.
     *
     * @param array $categoryData Array of category data
     * @param int   $parentId Parent id
     * @param null  $id Entity id
     * @return int
     */
    public function addCategory(array $categoryData, int $parentId = 0, $id = null): ?int
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
            $this->config->getDb()->escape($categoryData['lang']),
            $parentId,
            $this->config->getDb()->escape($categoryData['name']),
            $this->config->getDb()->escape($categoryData['description']),
            $categoryData['user_id'],
            $categoryData['group_id'],
            $categoryData['active'],
            $this->config->getDb()->escape($categoryData['image']),
            $categoryData['show_home']
        );
        $this->config->getDb()->query($query);

        return $id;
    }

    /**
     * Check if category already exists.
     *
     * @param string[] $categoryData Array of category data
     *
     * @return int
     */
    public function checkIfCategoryExists(array $categoryData): int
    {
        $query = sprintf(
            "SELECT name from %sfaqcategories WHERE name = '%s' AND lang = '%s'",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($categoryData['name']),
            $this->config->getDb()->escape($categoryData['lang'])
        );

        $result = $this->config->getDb()->query($query);

        return $this->config->getDb()->numRows($result);
    }

    /**
     * Updates an existent category entry.
     *
     * @param string[] $categoryData Array of category data
     *
     * @return bool
     */
    public function updateCategory(array $categoryData): bool
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
            $this->config->getDb()->escape($categoryData['name']),
            $this->config->getDb()->escape($categoryData['description']),
            $categoryData['user_id'],
            $categoryData['group_id'],
            $categoryData['active'],
            $categoryData['show_home'],
            $this->config->getDb()->escape($categoryData['image']),
            $categoryData['id'],
            $this->config->getDb()->escape($categoryData['lang'])
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Move the categories' ownership for users.
     *
     * @param int $from Old user id
     * @param int $to New user id
     * @return bool
     */
    public function moveOwnership(int $from, int $to): bool
    {
        $query = sprintf(
            'UPDATE %sfaqcategories SET user_id = %d WHERE user_id = %d',
            Database::getTablePrefix(),
            $to,
            $from
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Checks if a language is already defined for a category id.
     *
     * @param int    $categoryId Entity id
     * @param string $categoryLanguage Entity language
     * @return bool
     */
    public function checkLanguage(int $categoryId, string $categoryLanguage): bool
    {
        $query = sprintf(
            "SELECT lang FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->config->getDb()->escape($categoryLanguage)
        );

        $result = $this->config->getDb()->query($query);

        return $this->config->getDb()->numRows($result) > 0;
    }

    /**
     * Updates the parent category.
     *
     * @param int $categoryId Entity id
     * @param int $parentId Parent category id
     * @return bool
     */
    public function updateParentCategory(int $categoryId, int $parentId): bool
    {
        if ($categoryId !== $parentId) {
            return false;
        }

        $query = sprintf(
            'UPDATE %sfaqcategories SET parent_id = %d WHERE id = %d',
            Database::getTablePrefix(),
            $parentId,
            $categoryId
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Deletes a category.
     *
     * @param int $categoryId
     * @param string $categoryLang
     * @return bool
     */
    public function deleteCategory(int $categoryId, string $categoryLang): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->config->getDb()->escape($categoryLang)
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Create array with translated categories.
     *
     * @param int $categoryId
     * @return string[]
     */
    public function getCategoryLanguagesTranslated(int $categoryId): array
    {
        global $languageCodes;

        $existcatlang = $this->config->getLanguage()->languageAvailable($categoryId, 'faqcategories');
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
                $categoryId,
                $this->config->getDb()->escape($language)
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
     * @param int    $categoryId Entity id
     * @param string $selectedLanguage Selected language
     * @return string
     */
    public function getCategoryLanguagesToTranslate(int $categoryId, string $selectedLanguage): string
    {
        $output = '';
        $existcatlang = $this->config->getLanguage()->languageAvailable($categoryId, 'faqcategories');

        foreach (LanguageHelper::getAvailableLanguages() as $lang => $langname) {
            if (!in_array(strtolower($lang), $existcatlang)) {
                $output .= "\t<option value=\"" . strtolower($lang) . '"';
                if ($lang == $selectedLanguage) {
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
    public function getMissingCategories(): void
    {
        $query = sprintf(
            'SELECT id, lang, parent_id, name, description, user_id FROM %sfaqcategories',
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
            'SELECT DISTINCT id FROM %sfaqcategories WHERE parent_id = %d',
            Database::getTablePrefix(),
            $parentId
        );
        $result = $this->config->getDb()->query($query);

        return $this->config->getDb()->numRows($result);
    }

    /**
     * Returns the user id of the category owner
     *
     * @param int $categoryId
     * @return int
     */
    public function getOwner(int $categoryId): int
    {
        return isset($this->owner[$categoryId]) ? (int)$this->owner[$categoryId] : 1;
    }
}
