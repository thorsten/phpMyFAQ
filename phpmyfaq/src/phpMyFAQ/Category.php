<?php

/**
 * The main category class. Yes, it's huge.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <larstiedemann@yahoo.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2004-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-02-16
 */

namespace phpMyFAQ;

use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Language\LanguageCodes;

/**
 * Class Category
 *
 * @package phpMyFAQ
 */
class Category
{
    /**
     * The categories as an array.
     * @var array<int>
     */
    public array $categories = [];

    /**
     * The category names as an array.
     * @var array<string>
     */
    public array $categoryName = [];

    /**
     * The image as an array.
     * @var array<string>
     */
    public array $image = [];

    /**
     * The tree with the tabs.
     */
    public array $treeTab = [];

    /**
     * The category tree.
     */
    private array $catTree = [];

    /**
     * User ID.
     */
    private int $user = -1;

    /**
     * Groups.
     * @var int[]
     */
    private array $groups = [-1];

    /**
     * The children nodes.
     */
    private array $children = [];

    /**
     * The current language.
     */
    private ?string $language = null;

    /**
     * Entity owners
     *
     * @var array<int, int>>
     */
    private array $owner = [];

    /**
     * Entity moderators
     *
     * @var array<int, int>>
     */
    private array $moderators = [];

    /**
     * Constructor.
     *
     * @param Configuration $configuration Configuration object
     * @param int[] $groups Array with group IDs
     * @param bool $withPerm With or without permission check
     */
    public function __construct(
        private readonly Configuration $configuration,
        array $groups = [],
        bool $withPerm = true
    ) {
        $this->setGroups($groups);
        $this->setLanguage($this->configuration->getLanguage()->getLanguage());

        $this->getOrderedCategories($withPerm);
        foreach ($this->categoryName as $id) {
            $this->categoryName[$id['id']]['level'] = $this->getLevelOf($this->categoryName[$id['id']]['id']);
        }
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): Category
    {
        if ([] === $groups) {
            $groups = [-1];
        }

        $this->groups = $groups;
        return $this;
    }

    public function setLanguage(string $language): Category
    {
        $this->language = $language;
        return $this;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Returns all categories with ordered category IDs according to the user
     * and group permissions.
     *
     * @param bool $withPermission With or without permission check
     */
    public function getOrderedCategories(bool $withPermission = true, bool $withInactive = false): array
    {
        $categories = [];
        $where = '';

        if ($withPermission) {
            $where = sprintf(
                '
                WHERE
                    ( fg.group_id IN (%s)
                OR
                    (fu.user_id = %d AND fg.group_id IN (%s)))
                %s',
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups),
                $withInactive ? '' : 'AND fc.active = 1'
            );
        }

        if ($this->language !== null && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $where .= $where === '' || $where === '0' ? '
            WHERE' : '
            AND';
            $where .= "
                fc.lang = '" . $this->configuration->getDb()->escape($this->language) . "'";
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
                fc.show_home, fco.position
            ORDER BY
                fco.position, fc.id ASC',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $where
        );

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchArray($result)) {
                $this->categoryName[(int)$row['id']] = $row;
                $this->categories[(int)$row['id']] = $row;
                $this->children[(int)$row['parent_id']][(int)$row['id']] = &$this->categoryName[(int)$row['id']];
                $this->owner[(int)$row['id']] = &$row['user_id'];
                $this->moderators[(int)$row['id']] = &$row['group_id'];

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
        }

        return $categories;
    }

    /**
     * Get the level of the item id.
     *
     * @param int $id Entity id
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
                $alreadyListed[] = $id;
            }
        }

        return $level;
    }

    public function setUser(int $userId = -1): Category
    {
        $this->user = $userId;
        return $this;
    }

    /**
     * Gets all categories and write them in an array.
     * @return array<int, array>
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
        if ($this->language !== null && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " WHERE lang = '" . $this->configuration->getDb()->escape($this->language) . "'";
        }

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchArray($result)) {
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
     * Gets all category IDs
     */
    public function getAllCategoryIds(): array
    {
        $categories = [];

        $query = sprintf('SELECT id FROM %sfaqcategories', Database::getTablePrefix());

        if ($this->language !== null && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= sprintf(" WHERE lang = '%s'", $this->language);
        }

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchArray($result)) {
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
    public function buildCategoryTree(int $parentId = 0, int $indent = 0): void
    {
        $tt = [];
        $x = 0;

        foreach ($this->categories as $categoryId => $n) {
            if (!isset($n['parent_id'])) {
                continue;
            }
            if ($n['parent_id'] != $parentId) {
                continue;
            }
            if ($categoryId <= 0) {
                continue;
            }
            $tt[$x++] = $categoryId;
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
     * Creates the category tree for the admin category overview.
     */
    public function buildAdminCategoryTree(array $categories, int $parentId = 0): array
    {
        $result = [];

        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $id = $category['id'];
                $children = $this->buildAdminCategoryTree($categories, $category['id']);
                $result[$id] = [];
            }
        }

        return $result;
    }


    /**
     * Transforms the linear array in a 1D array in the order of the tree, with
     * the info.
     *
     * @param int $id Entity id
     */
    public function transform(int $id): void
    {
        $parentId = 0;
        $showHome = 0;
        $tree = [];
        $tabs = isset($this->children[$id]) ? array_keys($this->children[$id]) : [];
        $num = count($tabs);
        $symbol = 'minus';
        $name = '';
        $description = '';
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

        foreach ($tabs as $tab) {
            $this->transform($tab);
        }
    }

    /**
     * List in array the root, super-root, ... of the $id.
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
     */
    private function getBrothers(int $id): array
    {
        return $this->getChildren($this->categoryName[$id]['parent_id']);
    }

    /**
     * List in a array of the $id of the child.
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
     */
    public function expandTo(int $id): void
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
     * Returns the data of the given category.
     */
    public function getCategoryData(int $categoryId): CategoryEntity
    {
        $categoryEntity = new CategoryEntity();

        $query = sprintf(
            "SELECT * FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getDb()->escape($this->language)
        );

        $result = $this->configuration->getDb()->query($query);

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
            $categoryEntity
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

        return $categoryEntity;
    }

    /**
     * Gets the path from root to child as breadcrumbs.
     *
     * @param int $id Entity ID
     * @param string $separator Path separator
     * @param bool $renderAsHtml Renders breadcrumbs as HTML
     * @param string $useCssClass Use CSS class "breadcrumb"
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
        $tempName = [];
        $categoryId = [];
        $description = [];
        $breadcrumb = [];

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
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $categoryId[$key]
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->text = Strings::htmlentities($category);
                $oLink->itemTitle = Strings::htmlentities($category);
                $oLink->tooltip = Strings::htmlentities($description[$key] ?? '');
                if (0 === $key) {
                    $oLink->setRelation('index');
                }

                $breadcrumb[] = sprintf(
                    '<li class="breadcrumb-item">%s</li>',
                    $oLink->toHtmlAnchor()
                );
            }

            $tempName = $breadcrumb;

            return sprintf(
                '<ul class="%s">%s</ul>',
                $useCssClass,
                implode('', $tempName)
            );
        }
        return implode($separator, $tempName);
    }

    /**
     * Returns the ID of a category that associated with the given article.
     *
     * @param int $faqId FAQ id
     */
    public function getCategoryIdFromFaq(int $faqId): int
    {
        $cats = $this->getCategoryIdsFromFaq($faqId);
        return $cats[0] ?? 0;
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

    public function getCategoryIdFromName(string $categoryName): int|bool
    {
        $query = sprintf(
            "SELECT id FROM %sfaqcategories WHERE name = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($categoryName)
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            return (int)$this->configuration->getDb()->fetchRow($result);
        }

        return false;
    }

    /**
     * Returns all categories that are related to the given article-id and
     * the current language $this->language in an unsorted array which consists
     * of associative arrays with the keys 'name', 'id', 'lang',
     * 'parent_id' and 'description'.
     *
     * @param int $faqId Record id
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
            $this->configuration->getDb()->escape($this->language),
            $this->configuration->getDb()->escape($this->language)
        );

        $result = $this->configuration->getDb()->query($query);
        $num = $this->configuration->getDb()->numRows($result);
        $this->categories = [];
        if ($num > 0) {
            while ($row = $this->configuration->getDb()->fetchArray($result)) {
                $this->categories[(int)$row['id']] = $row;
            }
        }

        return $this->categories;
    }

    /**
     * Given FAQ ID and category ID are connected or not.
     */
    public function categoryHasLinkToFaq(int $faqId, int $categoryId): bool
    {
        $categories = $this->getCategoriesFromFaq($faqId);
        foreach ($categories as $category) {
            if ((int)$category['id'] === $categoryId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the category tree as an array.
     */
    public function getCategoryTree(): array
    {
        return $this->catTree;
    }

    /**
     * Returns the moderator group ID of the given category.
     */
    public function getModeratorGroupId(int $categoryId): int
    {
        return $this->moderators[$categoryId] ?? -1;
    }

    /**
     * Creates a new category
     */
    public function create(CategoryEntity $category): ?int
    {
        if ($category->getId() === 0) {
            $category->setId($this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqcategories', 'id'));
        }

        $query = sprintf(
            "
            INSERT INTO
                %sfaqcategories
            (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
                VALUES
            (%d, '%s', %d, '%s', '%s', %d, %d, %d, '%s', %d)",
            Database::getTablePrefix(),
            $category->getId(),
            $this->configuration->getDb()->escape($category->getLang()),
            $category->getParentId(),
            $this->configuration->getDb()->escape($category->getName()),
            $this->configuration->getDb()->escape($category->getDescription()),
            $category->getUserId(),
            $category->getGroupId(),
            $category->getActive(),
            $this->configuration->getDb()->escape($category->getImage()),
            $category->getShowHome()
        );

        $this->configuration->getDb()->query($query);

        return $category->getId();
    }

    /**
     * Check if category already exists.
     *
     * @param CategoryEntity $category Array of category data
     */
    public function checkIfCategoryExists(CategoryEntity $category): int
    {
        $query = sprintf(
            "SELECT name from %sfaqcategories WHERE name = '%s' AND lang = '%s' AND parent_id = %d",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($category->getName()),
            $this->configuration->getDb()->escape($category->getLang()),
            $category->getParentId()
        );

        $result = $this->configuration->getDb()->query($query);

        return $this->configuration->getDb()->numRows($result);
    }

    /**
     * Updates an existent category entry.
     *
     * @param CategoryEntity $category CategoryEntity object
     */
    public function update(CategoryEntity $category): bool
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
            $this->configuration->getDb()->escape($category->getName()),
            $this->configuration->getDb()->escape($category->getDescription()),
            $category->getUserId(),
            $category->getGroupId(),
            $category->getActive(),
            $category->getShowHome(),
            $this->configuration->getDb()->escape($category->getImage()),
            $category->getId(),
            $this->configuration->getDb()->escape($category->getLang())
        );

        return (bool)$this->configuration->getDb()->query($query);
    }

    /**
     * Move the categories' ownership for users.
     *
     * @param int $currentOwner Old user id
     * @param int $newOwner New user id
     */
    public function moveOwnership(int $currentOwner, int $newOwner): bool
    {
        $query = sprintf(
            'UPDATE %sfaqcategories SET user_id = %d WHERE user_id = %d',
            Database::getTablePrefix(),
            $newOwner,
            $currentOwner
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Checks if a language is already defined for a category id.
     *
     * @param int $categoryId Entity id
     * @param string $categoryLanguage Entity language
     */
    public function hasLanguage(int $categoryId, string $categoryLanguage): bool
    {
        $query = sprintf(
            "SELECT lang FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getDb()->escape($categoryLanguage)
        );

        $result = $this->configuration->getDb()->query($query);

        return $this->configuration->getDb()->numRows($result) > 0;
    }

    /**
     * Updates the parent category.
     *
     * @param int $categoryId Entity id
     * @param int $parentId Parent category id
     */
    public function updateParentCategory(int $categoryId, int $parentId): bool
    {
        if ($categoryId === $parentId) {
            return false;
        }

        $query = sprintf(
            'UPDATE %sfaqcategories SET parent_id = %d WHERE id = %d',
            Database::getTablePrefix(),
            $parentId,
            $categoryId
        );

        return (bool)$this->configuration->getDb()->query($query);
    }

    /**
     * Deletes a category.
     */
    public function delete(int $categoryId, string $categoryLang): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getDb()->escape($categoryLang)
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Create an array with translated categories.
     *
     * @return string[]
     */
    public function getCategoryLanguagesTranslated(int $categoryId): array
    {
        $existingCategoryLanguages = $this->configuration->getLanguage()->isLanguageAvailable(
            $categoryId,
            'faqcategories'
        );
        $translated = [];

        foreach ($existingCategoryLanguages as $existingCategoryLanguage) {
            $query = sprintf(
                "SELECT name, description FROM %sfaqcategories WHERE %s lang = '%s'",
                Database::getTablePrefix(),
                $categoryId === 0 ? '' : 'id = ' . $categoryId . ' AND ',
                $this->configuration->getDb()->escape($existingCategoryLanguage)
            );

            $result = $this->configuration->getDb()->query($query);
            if ($row = $this->configuration->getDb()->fetchArray($result)) {
                $translated[$existingCategoryLanguage] =
                    $row['name'] . ('' == $row['description'] ? '' : '  (' . $row['description'] . ')');
            }
        }

        ksort($translated);

        return $translated;
    }

    /**
     * Create all languages which can be used for translation as <option>.
     *
     * @param int $categoryId Entity id
     * @param string $selectedLanguage Selected language
     */
    public function getCategoryLanguagesToTranslate(int $categoryId, string $selectedLanguage): string
    {
        $output = '';
        $existingCategoryLanguage = $this->configuration->getLanguage()->isLanguageAvailable(
            $categoryId,
            'faqcategories'
        );

        foreach (LanguageHelper::getAvailableLanguages() as $lang => $langname) {
            if (!in_array(strtolower((string)$lang), $existingCategoryLanguage)) {
                $output .= "\t<option value=\"" . strtolower((string)$lang) . '"';
                if ($lang == $selectedLanguage) {
                    $output .= ' selected="selected"';
                }

                $output .= '>' . $langname . "</option>\n";
            }
        }

        return $output;
    }

    /**
     * Gets all categories that are not translated in actual language
     * to add in this->categories (used in the admin section).
     */
    public function getMissingCategories(): void
    {
        $query = sprintf(
            'SELECT id, lang, parent_id, name, description, user_id, group_id, active FROM %sfaqcategories',
            Database::getTablePrefix()
        );
        if ($this->language !== null && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " WHERE lang != '" . $this->language . "'";
        }

        $query .= ' ORDER BY id';

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchArray($result)) {
            if (!array_key_exists($row['id'], $this->categoryName)) {
                $this->categoryName[$row['id']] = $row;
                $this->categories[$row['id']] = $row;
                $this->children[$row['parent_id']][$row['id']] = &$this->categoryName[$row['id']];
            }
        }
    }

    /**
     * Returns the user id of the category owner
     */
    public function getOwner(int $categoryId = null): int
    {
        return $this->owner[$categoryId] ?? 1;
    }
}
