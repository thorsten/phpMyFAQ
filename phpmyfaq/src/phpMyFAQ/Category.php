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
    public array $categoryNames = [];

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
     * @var array<int, int>
     */
    private array $owner = [];

    /**
     * Entity moderators
     *
     * @var array<int, int>
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

    public function __construct(
        private readonly Configuration $configuration,
        array $groups = [],
        bool $withPerm = true,
    ) {
        $this->setGroups($groups);
        $this->setLanguage($this->configuration->getLanguage()->getLanguage());

        $this->getOrderedCategories($withPerm);
        foreach ($this->categoryNames as $row) {
            if (is_array($row) && isset($row['id'])) {
                $this->categoryNames[$row['id']]['level'] = $this->getLevelOf((int) $row['id']);
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
     */
    public function getOrderedCategories(bool $withPermission = true, bool $withInactive = false): array
    {
        $categories = [];

        $rows = $this->getCategoryRepository()->findOrderedCategories(
            $this->groups,
            $this->user,
            $this->language,
            $withPermission,
            $withInactive,
        );

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = (int) $row['parent_id'];

            $this->categoryNames[$id] = $row;
            $this->categories[$id] = $row;
            $this->children[$parentId][$id] = &$this->categoryNames[$id];
            $this->owner[$id] = (int) $row['user_id'];
            $this->moderators[$id] = (int) $row['group_id'];

            $categories[$id] = $row
            + [
                'level' => $this->getLevelOf($id),
            ];
        }

        return $categories;
    }

    /**
     * Get the level of the item id.
     */
    private function getLevelOf(int $categoryId): int
    {
        return $this->getTreeBuilder()->computeLevel($this->categoryNames, $categoryId);
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
     */
    public function buildCategoryTree(int $parentId = 0, int $indent = 0): void
    {
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
     */
    public function transform(int $categoryId): void
    {
        $presenter = $this->getAdminCategoryTreePresenter();
        $entries = $presenter->transform($this->getTreeBuilder(), $this->categoryNames, $this->children, $categoryId);
        foreach ($entries as $entry) {
            $this->treeTab[] = $entry;
        }
    }

    private function buildTree(int $categoryId): array
    {
        return $this->getTreeBuilder()->buildTree($this->categoryNames, $this->children, $categoryId);
    }

    private function getSymbol(int $categoryId, int $parentId): string
    {
        $siblings = $this->children[$parentId] ?? [];
        $array = array_keys($siblings);
        return $categoryId == end($array) ? 'angle' : 'medium';
    }

    /**
     * List in array the root, super-root, ... of the $id.
     */
    private function getNodes(int $categoryId): array
    {
        return $this->getTreeBuilder()->getNodes($this->categoryNames, $categoryId);
    }

    /**
     * Gets the list of the brothers of $id (include $id).
     */
    private function getBrothers(int $categoryId): array
    {
        return $this->getTreeBuilder()->getBrothers($this->categoryNames, $this->children, $categoryId);
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
     */
    public function expand(int $categoryId): void
    {
        $this->treeTab[$this->getLineCategory($categoryId)]['symbol'] = 'minus';
    }

    public function getCategoryData(int $categoryId): CategoryEntity
    {
        $entity = $this->language !== null
            ? $this->getCategoryRepository()->findByIdAndLanguage($categoryId, $this->language)
            : null;

        return $entity ?? new CategoryEntity();
    }

    public function getPath(
        int $catId,
        string $separator = ' / ',
        bool $renderAsHtml = false,
        string $useCssClass = 'breadcrumb',
    ): string {
        $ids = $this->getNodes($catId);
        $segments = $this->getBreadcrumbsBuilder()->buildFromIds($this->categoryNames, $ids);

        if ($segments === []) {
            return '';
        }

        if ($renderAsHtml) {
            return $this->getBreadcrumbsHtmlRenderer()->render($this->configuration, $segments, $useCssClass);
        }

        $names = array_map(static fn(array $s): string => (string) $s['name'], $segments);
        return implode($separator, $names);
    }

    public function getCategoryIdFromFaq(int $faqId): int
    {
        $cats = $this->getCategoryIdsFromFaq($faqId);
        return $cats[0] ?? 0;
    }

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

    public function getCategoriesFromFaq(int $faqId): array
    {
        $rows = $this->getCategoryRepository()->findCategoriesFromFaq($faqId, (string) $this->language);
        $this->categories = $rows;
        return $this->categories;
    }

    public function create(CategoryEntity $categoryEntity): ?int
    {
        return $this->getCategoryRepository()->create($categoryEntity);
    }

    public function checkIfCategoryExists(CategoryEntity $categoryEntity): int
    {
        return $this->getCategoryRepository()->countByNameLangParent(
            $categoryEntity->getName(),
            $categoryEntity->getLang(),
            $categoryEntity->getParentId(),
        );
    }

    public function update(CategoryEntity $categoryEntity): bool
    {
        return $this->getCategoryRepository()->update($categoryEntity);
    }

    public function moveOwnership(int $currentOwner, int $newOwner): bool
    {
        return $this->getCategoryRepository()->moveOwnership($currentOwner, $newOwner);
    }

    public function hasLanguage(int $categoryId, string $categoryLanguage): bool
    {
        return $this->getCategoryRepository()->hasLanguage($categoryId, $categoryLanguage);
    }

    public function updateParentCategory(int $categoryId, int $parentId): bool
    {
        if ($categoryId === $parentId) {
            return false;
        }

        return $this->getCategoryRepository()->updateParentCategory($categoryId, $parentId);
    }

    public function delete(int $categoryId, string $categoryLang): bool
    {
        return $this->getCategoryRepository()->delete($categoryId, $categoryLang);
    }

    public function getCategoryLanguagesTranslated(int $categoryId): array
    {
        return $this->getCategoryRepository()->getCategoryLanguagesTranslated($categoryId);
    }

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

    public function getMissingCategories(): void
    {
        $rows = $this->getCategoryRepository()->findMissingCategories($this->language);
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = (int) $row['parent_id'];
            if (!array_key_exists($id, $this->categoryNames)) {
                $this->categoryNames[$id] = $row;
                $this->categories[$id] = $row;
                $this->children[$parentId][$id] = &$this->categoryNames[$id];
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
     * Returns the moderator group id for a given category or 0 if none assigned.
     */
    public function getModeratorGroupId(int $categoryId): int
    {
        return $this->moderators[$categoryId] ?? 0;
    }

    /**
     * Returns the category tree as an array.
     */
    public function getCategoryTree(): array
    {
        return $this->catTree;
    }

    public function getCategoryName(int $categoryId): array
    {
        return $this->categoryNames[$categoryId]['name'] ?? [];
    }

    public function getCategoryDescription(int $categoryId): array
    {
        return $this->categoryNames[$categoryId]['description'] ?? [];
    }
}
