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
 * @copyright 2004-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-02-16
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Category\CategoryRepository;
use phpMyFAQ\Category\CategoryRepositoryInterface;
use phpMyFAQ\Category\Navigation\BreadcrumbsBuilder;
use phpMyFAQ\Category\Navigation\BreadcrumbsHtmlRenderer;
use phpMyFAQ\Category\Presentation\AdminCategoryTreePresenter;
use phpMyFAQ\Category\Tree\TreeBuilder;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Helper\LanguageHelper;

/**
 * Class Category
 *
 * @todo Refactor this class and split it into smaller classes.
 *
 * @package phpMyFAQ
 */
class Category
{
    /**
     * The categories as an array.
     * @deprecated Will be removed in a future version. Use query methods (e.g., getOrderedCategories) instead.
     * @var array<int, array<string, mixed>>
     */
    public array $categories = [];

    /**
     * The category names as an array indexed by category ID.
     * @deprecated Will be removed in a future version. Use query methods and dedicated builders instead.
     * @var array<int, array<string, mixed>>
     */
    public array $categoryName = [];

    /**
     * The image as an array.
     * @deprecated Will be removed in a future version.
     * @var array<int, string>
     */
    public array $image = [];

    /**
     * The tree with the tabs.
     * @deprecated Will be removed in a future version.
     * @var array<int, array<string, mixed>>
     */
    public array $treeTab = [];

    /**
     * The category tree.
     * @var array<int, array<string, mixed>>
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
     * The children nodes: parentId => [childId => &categoryRow]
     * @var array<int, array<int, array<string, mixed>>>
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
     * Internal repository for persistence access (Phase 1 extraction).
     */
    private ?CategoryRepositoryInterface $categoryRepository = null;

    /**
     * Internal tree builder (Phase 2 extraction).
     */
    private ?TreeBuilder $treeBuilder = null;

    /**
     * Internal breadcrumbs builder (Phase 3 extraction).
     */
    private ?BreadcrumbsBuilder $breadcrumbsBuilder = null;

    /**
     * Internal breadcrumbs HTML renderer (Phase 3 extraction).
     */
    private ?BreadcrumbsHtmlRenderer $breadcrumbsHtmlRenderer = null;

    /**
     * Internal admin category tree presenter (Phase 3 extraction).
     */
    private ?AdminCategoryTreePresenter $adminCategoryTreePresenter = null;

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
        bool $withPerm = true,
    ) {
        $this->setGroups($groups);
        $this->setLanguage($this->configuration->getLanguage()->getLanguage());

        $this->getOrderedCategories($withPerm);
        foreach ($this->categoryName as $row) {
            if (is_array($row) && isset($row['id'])) {
                $this->categoryName[$row['id']]['level'] = $this->getLevelOf((int) $row['id']);
            }
        }
    }

    /**
     * Lazy repository factory.
     */
    private function getCategoryRepository(): CategoryRepositoryInterface
    {
        if ($this->categoryRepository === null) {
            $this->categoryRepository = new CategoryRepository($this->configuration);
        }
        return $this->categoryRepository;
    }

    /**
     * Lazy tree builder factory.
     */
    private function getTreeBuilder(): TreeBuilder
    {
        if ($this->treeBuilder === null) {
            $this->treeBuilder = new TreeBuilder();
        }
        return $this->treeBuilder;
    }

    /**
     * Lazy breadcrumbs builder factory.
     */
    private function getBreadcrumbsBuilder(): BreadcrumbsBuilder
    {
        if ($this->breadcrumbsBuilder === null) {
            $this->breadcrumbsBuilder = new BreadcrumbsBuilder();
        }
        return $this->breadcrumbsBuilder;
    }

    /**
     * Lazy breadcrumbs HTML renderer factory.
     */
    private function getBreadcrumbsHtmlRenderer(): BreadcrumbsHtmlRenderer
    {
        if ($this->breadcrumbsHtmlRenderer === null) {
            $this->breadcrumbsHtmlRenderer = new BreadcrumbsHtmlRenderer();
        }
        return $this->breadcrumbsHtmlRenderer;
    }

    /**
     * Lazy admin category tree presenter factory.
     */
    private function getAdminCategoryTreePresenter(): AdminCategoryTreePresenter
    {
        if ($this->adminCategoryTreePresenter === null) {
            $this->adminCategoryTreePresenter = new AdminCategoryTreePresenter();
        }
        return $this->adminCategoryTreePresenter;
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

        // Delegate to repository to fetch raw rows
        $rows = $this->getCategoryRepository()->findOrderedCategories(
            $this->groups,
            $this->user,
            $this->language,
            $withPermission,
            $withInactive,
        );

        // Rebuild internal indexes/state as before
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = (int) $row['parent_id'];

            $this->categoryName[$id] = $row;
            $this->categories[$id] = $row;
            $this->children[$parentId][$id] = &$this->categoryName[$id];
            $this->owner[$id] = (int) $row['user_id'];
            $this->moderators[$id] = (int) $row['group_id'];

            // Keep method behavior: include a level entry based on current map
            $categories[$id] = $row
            + [
                'level' => $this->getLevelOf($id),
            ];
        }

        return $categories;
    }

    /**
     * Get the level of the item id.
     *
     * @param int $categoryId Entity id
     */
    private function getLevelOf(int $categoryId): int
    {
        return $this->getTreeBuilder()->computeLevel($this->categoryName, $categoryId);
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
        $rows = $this->getCategoryRepository()->findAllCategories($this->language);
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $categories[$id] = $row
            + [
                'level' => $this->getLevelOf($id),
            ];
        }

        return $categories;
    }

    /**
     * Gets all category IDs
     */
    public function getAllCategoryIds(): array
    {
        return $this->getCategoryRepository()->findAllCategoryIds($this->language);
    }

    /**
     * Builds the category tree.
     *
     * @param int $parentId Parent id
     * @param int $indent Indention
     */
    public function buildCategoryTree(int $parentId = 0, int $indent = 0): void
    {
        // Delegate to TreeBuilder to compute a fresh linear tree
        $this->catTree = $this->getTreeBuilder()->buildLinearTree($this->categories, $parentId, $indent);
    }

    /**
     * Creates the category tree for the admin category overview.
     */
    public function buildAdminCategoryTree(array $categories, int $parentId = 0): array
    {
        return $this->getTreeBuilder()->buildAdminCategoryTree($categories, $parentId);
    }

    /**
     * Transforms the linear array in a 1D array in the order of the tree, with
     * the info.
     *
     * @param int $categoryId Category ID
     */
    public function transform(int $categoryId): void
    {
        $presenter = $this->getAdminCategoryTreePresenter();
        $entries = $presenter->transform($this->getTreeBuilder(), $this->categoryName, $this->children, $categoryId);
        // Append to legacy treeTab as before
        foreach ($entries as $entry) {
            $this->treeTab[] = $entry;
        }
    }

    private function buildTree(int $categoryId): array
    {
        return $this->getTreeBuilder()->buildTree($this->categoryName, $this->children, $categoryId);
    }

    private function getSymbol(int $categoryId, int $parentId): string
    {
        $siblings = $this->children[$parentId] ?? [];

        $array = array_keys($siblings);

        return $categoryId == end($array) ? 'angle' : 'medium';
    }

    /**
     * List in array the root, super-root, ... of the $id.
     *
     * @return array<int>
     */
    private function getNodes(int $categoryId): array
    {
        return $this->getTreeBuilder()->getNodes($this->categoryName, $categoryId);
    }

    /**
     * Gets the list of the brothers of $id (include $id).
     *
     * @param int $categoryId Brothers
     * @return array<int>
     */
    private function getBrothers(int $categoryId): array
    {
        return $this->getTreeBuilder()->getBrothers($this->categoryName, $this->children, $categoryId);
    }

    /**
     * List in an array of the $id of the child.
     */
    public function getChildren(int $categoryId): array
    {
        return $this->getTreeBuilder()->getChildren($this->children, $categoryId);
    }

    /**
     * List in an array of the $id of the child.
     *
     * @param int $categoryId Entity id
     * @return array<int>
     */
    public function getChildNodes(int $categoryId): array
    {
        return $this->getTreeBuilder()->getChildNodes($this->children, $categoryId);
    }

    /**
     * Try to expand from the parent_id to the node $id
     */
    public function expandTo(int $categoryId): void
    {
        $this->collapseAll();
        $ascendants = $this->getNodes($categoryId);
        $ascendants[] = $categoryId;
        $numAscendants = count($ascendants);
        for ($i = 0; $i < $numAscendants; ++$i) {
            if (isset($this->treeTab[$this->getLineCategory($ascendants[$i])]['numChildren'])) {
                $numChildren = $this->treeTab[$this->getLineCategory($ascendants[$i])]['numChildren'];
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
     * @param int $categoryId Entity id
     */
    private function getLineCategory(int $categoryId): int
    {
        $num = count($this->treeTab);
        for ($i = 0; $i < $num; ++$i) {
            if (isset($this->treeTab[$i]['id']) && $this->treeTab[$i]['id'] === $categoryId) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Expand the node $id.
     *
     * @param int $categoryId Entity id
     */
    public function expand(int $categoryId): void
    {
        $this->treeTab[$this->getLineCategory($categoryId)]['symbol'] = 'minus';
    }

    /**
     * Returns the data of the given category.
     */
    public function getCategoryData(int $categoryId): CategoryEntity
    {
        $entity = $this->language !== null
            ? $this->getCategoryRepository()->findByIdAndLanguage($categoryId, $this->language)
            : null;

        return $entity ?? new CategoryEntity();
    }

    /**
     * Gets the path from root to child as breadcrumbs.
     *
     * @param int    $catId Entity ID
     * @param string $separator Path separator
     * @param bool   $renderAsHtml Renders breadcrumbs as HTML
     * @param string $useCssClass Use CSS class "breadcrumb"
     */
    public function getPath(
        int $catId,
        string $separator = ' / ',
        bool $renderAsHtml = false,
        string $useCssClass = 'breadcrumb',
    ): string {
        $ids = $this->getNodes($catId);
        // Build breadcrumb segments from ids
        $segments = $this->getBreadcrumbsBuilder()->buildFromIds($this->categoryName, $ids);

        if ($segments === []) {
            return '';
        }

        if ($renderAsHtml) {
            return $this->getBreadcrumbsHtmlRenderer()->render($this->configuration, $segments, $useCssClass);
        }

        // plain text
        $names = array_map(static fn(array $s): string => (string) $s['name'], $segments);
        return implode($separator, $names);
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
            if (isset($category['id'])) {
                $result[] = (int) $category['id'];
            }
        }

        return $result;
    }

    public function getCategoryIdFromName(string $categoryName): int|bool
    {
        $id = $this->getCategoryRepository()->findCategoryIdByName($categoryName);
        return $id ?? false;
    }

    /**
     * Returns all categories that are related to the given article-id and
     * the current language $this→language in an unsorted array which consists
     * of associative arrays with the keys 'name', 'id', 'lang',
     * 'parent_id' and 'description'.
     *
     * @param int $faqId Record id
     */
    public function getCategoriesFromFaq(int $faqId): array
    {
        $rows = $this->getCategoryRepository()->findCategoriesFromFaq($faqId, (string) $this->language);
        $this->categories = $rows;
        return $this->categories;
    }

    /**
     * Creates a new category
     */
    public function create(CategoryEntity $categoryEntity): ?int
    {
        return $this->getCategoryRepository()->create($categoryEntity);
    }

    /**
     * Check if category already exists.
     *
     * @param CategoryEntity $categoryEntity Array of category data
     */
    public function checkIfCategoryExists(CategoryEntity $categoryEntity): int
    {
        return $this->getCategoryRepository()->countByNameLangParent(
            $categoryEntity->getName(),
            $categoryEntity->getLang(),
            $categoryEntity->getParentId(),
        );
    }

    /**
     * Updates an existent category entry.
     *
     * @param CategoryEntity $categoryEntity CategoryEntity object
     */
    public function update(CategoryEntity $categoryEntity): bool
    {
        return $this->getCategoryRepository()->update($categoryEntity);
    }

    /**
     * Move the categories' ownership for users.
     *
     * @param int $currentOwner Old user id
     * @param int $newOwner New user id
     */
    public function moveOwnership(int $currentOwner, int $newOwner): bool
    {
        return $this->getCategoryRepository()->moveOwnership($currentOwner, $newOwner);
    }

    /**
     * Checks if a language is already defined for a category id.
     *
     * @param int $categoryId Entity id
     * @param string $categoryLanguage Entity language
     */
    public function hasLanguage(int $categoryId, string $categoryLanguage): bool
    {
        return $this->getCategoryRepository()->hasLanguage($categoryId, $categoryLanguage);
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

        return $this->getCategoryRepository()->updateParentCategory($categoryId, $parentId);
    }

    /**
     * Deletes a category.
     */
    public function delete(int $categoryId, string $categoryLang): bool
    {
        return $this->getCategoryRepository()->delete($categoryId, $categoryLang);
    }

    /**
     * Create an array with translated categories.
     *
     * @return string[]
     */
    public function getCategoryLanguagesTranslated(int $categoryId): array
    {
        return $this->getCategoryRepository()->getCategoryLanguagesTranslated($categoryId);
    }

    /**
     * Create all languages that can be used for translation as <option>.
     *
     * @deprecated Will be removed in a future version. Use \phpMyFAQ\Category\Language\CategoryLanguageService
     *             to retrieve data and render options in the template layer.
     *
     * @param int $categoryId Entity id
     * @param string $selectedLanguage Selected language
     */
    public function getCategoryLanguagesToTranslate(int $categoryId, string $selectedLanguage): string
    {
        $output = '';
        $existingLanguage = $this->configuration->getLanguage()->isLanguageAvailable($categoryId, 'faqcategories');

        foreach (LanguageHelper::getAvailableLanguages() as $lang => $langname) {
            if (!in_array(strtolower((string) $lang), $existingLanguage)) {
                $output .= "\t<option value=\"" . strtolower((string) $lang) . '"';
                if ($lang === $selectedLanguage) {
                    $output .= ' selected="selected"';
                }

                $output .= '>' . $langname . "</option>\n";
            }
        }

        return $output;
    }

    /**
     * Gets all categories that are not translated in actual language
     * to add in this→categories (used in the admin section).
     */
    public function getMissingCategories(): void
    {
        $rows = $this->getCategoryRepository()->findMissingCategories($this->language);
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = (int) $row['parent_id'];
            if (!array_key_exists($id, $this->categoryName)) {
                $this->categoryName[$id] = $row;
                $this->categories[$id] = $row;
                $this->children[$parentId][$id] = &$this->categoryName[$id];
            }
        }
    }

    /**
     * Returns the user id of the category owner
     */
    public function getOwner(?int $categoryId = null): int
    {
        return $this->owner[$categoryId] ?? 1;
    }

    /**
     * Returns the category tree as an array.
     */
    public function getCategoryTree(): array
    {
        return $this->catTree;
    }
}
