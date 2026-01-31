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
 * @copyright 2004-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-02-16
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Category\CategoryCache;
use phpMyFAQ\Category\CategoryPermissionContext;
use phpMyFAQ\Category\CategoryRepository;
use phpMyFAQ\Category\CategoryRepositoryInterface;
use phpMyFAQ\Category\CategoryService;
use phpMyFAQ\Category\CategoryTreeFacade;
use phpMyFAQ\Category\Navigation\BreadcrumbsBuilder;
use phpMyFAQ\Category\Navigation\BreadcrumbsHtmlRenderer;
use phpMyFAQ\Category\Presentation\AdminCategoryTreePresenter;
use phpMyFAQ\Category\Tree\TreeBuilder;
use phpMyFAQ\Entity\CategoryEntity;

class Category
{
    /**
     * The current language.
     */
    private ?string $language = null;

    /**
     * Internal cache for category data.
     */
    private CategoryCache $categoryCache;

    /**
     * Permission context for category access control.
     */
    private CategoryPermissionContext $categoryPermissionContext;

    /**
     * Internal repository for persistence access
     */
    private ?CategoryRepositoryInterface $categoryRepository = null;

    /**
     * Internal service for CRUD operations
     */
    private ?CategoryService $categoryService = null;

    /**
     * Internal tree facade for tree operations
     */
    private ?CategoryTreeFacade $categoryTreeFacade = null;

    /**
     * Internal tree builder
     */
    private ?TreeBuilder $treeBuilder = null;

    /**
     * Internal breadcrumbs builder
     */
    private ?BreadcrumbsBuilder $breadcrumbsBuilder = null;

    /**
     * Internal breadcrumbs HTML renderer
     */
    private ?BreadcrumbsHtmlRenderer $breadcrumbsHtmlRenderer = null;

    /**
     * Internal admin category tree presenter
     */
    private ?AdminCategoryTreePresenter $adminCategoryTreePresenter = null;

    public function __construct(
        private readonly Configuration $configuration,
        array $groups = [],
        bool $withPermission = true,
        ?string $faqLanguage = null,
    ) {
        $this->categoryCache = new CategoryCache();
        $this->categoryPermissionContext = new CategoryPermissionContext($groups);
        $languageToUse =
            $faqLanguage !== null && $faqLanguage !== ''
                ? $faqLanguage
                : $this->configuration->getLanguage()->getLanguage();
        $this->setLanguage($languageToUse);
        $this->getOrderedCategories($withPermission);

        foreach ($this->categoryCache->getCategoryNames() as $categoryName) {
            if (!(is_array($categoryName) && isset($categoryName['id']))) {
                continue;
            }

            $id = (int) $categoryName['id'];
            $level = $this->getLevelOf($id);
            $this->categoryCache->addCategoryName($id, array_merge($categoryName, ['level' => $level]));
        }
    }

    /**
     * Lazy repository factory.
     */
    private function getCategoryRepository(): CategoryRepositoryInterface
    {
        if (!$this->categoryRepository instanceof CategoryRepositoryInterface) {
            $this->categoryRepository = new CategoryRepository($this->configuration);
        }

        return $this->categoryRepository;
    }

    /**
     * Lazy tree builder factory.
     */
    private function getTreeBuilder(): TreeBuilder
    {
        if (!$this->treeBuilder instanceof TreeBuilder) {
            $this->treeBuilder = new TreeBuilder();
        }

        return $this->treeBuilder;
    }

    /**
     * Lazy breadcrumbs builder factory.
     */
    private function getBreadcrumbsBuilder(): BreadcrumbsBuilder
    {
        if (!$this->breadcrumbsBuilder instanceof BreadcrumbsBuilder) {
            $this->breadcrumbsBuilder = new BreadcrumbsBuilder();
        }

        return $this->breadcrumbsBuilder;
    }

    /**
     * Lazy breadcrumbs HTML renderer factory.
     */
    private function getBreadcrumbsHtmlRenderer(): BreadcrumbsHtmlRenderer
    {
        if (!$this->breadcrumbsHtmlRenderer instanceof BreadcrumbsHtmlRenderer) {
            $this->breadcrumbsHtmlRenderer = new BreadcrumbsHtmlRenderer();
        }

        return $this->breadcrumbsHtmlRenderer;
    }

    /**
     * Lazy admin category tree presenter factory.
     */
    private function getAdminCategoryTreePresenter(): AdminCategoryTreePresenter
    {
        if (!$this->adminCategoryTreePresenter instanceof AdminCategoryTreePresenter) {
            $this->adminCategoryTreePresenter = new AdminCategoryTreePresenter();
        }

        return $this->adminCategoryTreePresenter;
    }

    /**
     * Lazy category service factory.
     */
    private function getCategoryService(): CategoryService
    {
        if (!$this->categoryService instanceof CategoryService) {
            $this->categoryService = new CategoryService($this->getCategoryRepository());
        }

        return $this->categoryService;
    }

    /**
     * Lazy tree facade factory.
     */
    private function getTreeFacade(): CategoryTreeFacade
    {
        if ($this->categoryTreeFacade === null) {
            $this->categoryTreeFacade = new CategoryTreeFacade();
        }

        return $this->categoryTreeFacade;
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): Category
    {
        if ([] === $groups) {
            $groups = [-1];
        }

        $this->categoryPermissionContext->setGroups($groups);
        return $this;
    }

    public function setLanguage(string $language): Category
    {
        $this->language = $language;
        return $this;
    }

    public function getUser(): int
    {
        return $this->categoryPermissionContext->getUser();
    }

    public function getGroups(): array
    {
        return $this->categoryPermissionContext->getGroups();
    }

    /**
     * Returns all categories with ordered category IDs according to the user
     * and group permissions.
     */
    public function getOrderedCategories(bool $withPermission = true, bool $withInactive = false): array
    {
        $categories = [];

        $rows = $this->getCategoryRepository()->findOrderedCategories(
            $this->categoryPermissionContext->getGroups(),
            $this->categoryPermissionContext->getUser(),
            $this->language,
            $withPermission,
            $withInactive,
        );

        foreach ($rows as $row) {
            $id = (int) $row['id'];

            $this->categoryCache->addCategory($id, $row);
            $this->categoryCache->addCategoryName($id, $row);
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
    public function getLevelOf(int $categoryId): int
    {
        return $this->getTreeFacade()->getLevelOf($this->categoryCache->getCategoryNames(), $categoryId);
    }

    public function setUser(int $userId = -1): Category
    {
        $this->categoryPermissionContext->setUser($userId);
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
     * Gets paginated categories with sorting support for API.
     *
     * @param int $limit Number of items per page
     * @param int $offset Starting offset
     * @param string $sortField Field to sort by
     * @param string $sortOrder Sort direction (ASC, DESC)
     * @return array
     */
    public function getCategoriesPaginated(
        int $limit = 25,
        int $offset = 0,
        string $sortField = 'id',
        string $sortOrder = 'ASC',
        bool $activeOnly = false,
    ): array {
        $categories = [];
        $rows = $this->getCategoryRepository()->findCategoriesPaginated(
            $this->language,
            $limit,
            $offset,
            $sortField,
            $sortOrder,
            $activeOnly,
        );
        foreach ($rows as $id => $row) {
            $categories[$id] = $row
            + [
                'level' => $this->getLevelOf($id),
            ];
        }

        return $categories;
    }

    /**
     * Counts total categories for current language.
     *
     * @param bool $activeOnly Only count active categories
     * @return int Total count
     */
    public function countCategories(bool $activeOnly = false): int
    {
        return $this->getCategoryRepository()->countCategories($this->language, $activeOnly);
    }

    /**
     * Builds the category tree.
     */
    public function buildCategoryTree(int $parentId = 0, int $indent = 0): void
    {
        $this->categoryCache->setCatTree($this->getTreeFacade()->buildLinearTree(
            $this->categoryCache->getCategories(),
            $parentId,
            $indent,
        ));
    }

    /**
     * Creates the category tree for the admin category overview.
     */
    public function buildAdminCategoryTree(array $categories, int $parentId = 0): array
    {
        return $this->getTreeFacade()->buildAdminCategoryTree($categories, $parentId);
    }

    /**
     * Transforms the linear array in a 1D array in the order of the tree, with
     * the info.
     */
    public function transform(int $categoryId): void
    {
        $adminCategoryTreePresenter = $this->getAdminCategoryTreePresenter();
        $entries = $adminCategoryTreePresenter->transform(
            $this->getTreeBuilder(),
            $this->categoryCache->getCategoryNames(),
            $this->categoryCache->getChildren(),
            $categoryId,
        );
        foreach ($entries as $entry) {
            $this->categoryCache->addTreeTabEntry($entry);
        }
    }

    /**
     * List in an array the root, super-root, ... of the $id.
     */
    private function getNodes(int $categoryId): array
    {
        return $this->getTreeBuilder()->getNodes($this->categoryCache->getCategoryNames(), $categoryId);
    }

    /**
     * List in an array of the $id of the child.
     */
    public function getChildren(int $categoryId): array
    {
        return $this->getTreeBuilder()->getChildren($this->categoryCache->getChildren(), $categoryId);
    }

    /**
     * List in an array of the $id of the child.
     */
    public function getChildNodes(int $categoryId): array
    {
        return $this->getTreeBuilder()->getChildNodes($this->categoryCache->getChildren(), $categoryId);
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
            $lineIndex = $this->getLineCategory($ascendants[$i]);
            $entry = $this->categoryCache->getTreeTabEntry($lineIndex);
            if ($entry !== null && isset($entry['numChildren'])) {
                $numChildren = $entry['numChildren'];
                if ($numChildren > 0) {
                    $this->expand($ascendants[$i]);
                    continue;
                }

                break;
            }
        }
    }

    /**
     * Collapse the complete category tree.
     */
    public function collapseAll(): void
    {
        $numTreeTab = $this->categoryCache->countTreeTab();
        for ($i = 0; $i < $numTreeTab; ++$i) {
            $entry = $this->categoryCache->getTreeTabEntry($i);
            if ($entry !== null && isset($entry['symbol']) && $entry['symbol'] === 'minus') {
                $this->categoryCache->updateTreeTabEntry($i, ['symbol' => 'plus']);
            }
        }
    }

    /**
     * Get the line number where to find the node $id in the category tree.
     */
    private function getLineCategory(int $categoryId): int
    {
        $num = $this->categoryCache->countTreeTab();
        for ($i = 0; $i < $num; ++$i) {
            $entry = $this->categoryCache->getTreeTabEntry($i);
            if ($entry !== null && isset($entry['id']) && $entry['id'] === $categoryId) {
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
        $lineIndex = $this->getLineCategory($categoryId);
        $this->categoryCache->updateTreeTabEntry($lineIndex, ['symbol' => 'minus']);
    }

    public function getCategoryData(int $categoryId): CategoryEntity
    {
        return $this->language !== null
            ? $this->getCategoryService()->getCategoryData($categoryId, $this->language)
            : new CategoryEntity();
    }

    public function getPath(
        int $catId,
        string $separator = ' / ',
        bool $renderAsHtml = false,
        string $useCssClass = 'breadcrumb',
    ): string {
        $ids = $this->getNodes($catId);
        $segments = $this->getBreadcrumbsBuilder()->buildFromIds($this->categoryCache->getCategoryNames(), $ids);

        if ($segments === []) {
            return '';
        }

        if ($renderAsHtml) {
            return $this->getBreadcrumbsHtmlRenderer()->render($this->configuration, $segments, $useCssClass);
        }

        $names = array_map(static fn(array $s): string => $s['name'], $segments);
        return implode($separator, $names);
    }

    /**
     * Returns the breadcrumb path with the start page as the first segment.
     *
     * @param int $catId Category ID
     * @param string $separator Separator for text mode
     * @param bool $renderAsHtml Render as HTML or plain text
     * @param string $useCssClass CSS class for HTML mode
     * @param string|null $startPageName Optional start page name (defaults to Translation msgHome)
     * @param string $startPageDescription Optional start page description
     */
    public function getPathWithStartPage(
        int $catId,
        string $separator = ' / ',
        bool $renderAsHtml = false,
        string $useCssClass = 'breadcrumb',
        ?string $startPageName = null,
        string $startPageDescription = '',
    ): string {
        $ids = $this->getNodes($catId);
        $segments = $this->getBreadcrumbsBuilder()->buildFromIdsWithStartPage(
            $this->categoryCache->getCategoryNames(),
            $ids,
            $startPageName,
            $startPageDescription,
        );

        if ($segments === []) {
            return '';
        }

        if ($renderAsHtml) {
            return $this->getBreadcrumbsHtmlRenderer()->render($this->configuration, $segments, $useCssClass);
        }

        $names = array_map(static fn(array $s): string => $s['name'], $segments);
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
            if (!isset($category['id'])) {
                continue;
            }

            $result[] = (int) $category['id'];
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
        foreach ($rows as $id => $row) {
            $this->categoryCache->addCategory($id, $row);
        }

        return $rows;
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

    public function getMissingCategories(): void
    {
        $rows = $this->getCategoryService()->getMissingCategories($this->language);
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = (int) $row['parent_id'];

            $currentNames = $this->categoryCache->getCategoryNames();
            if (!array_key_exists($id, $currentNames)) {
                $this->categoryCache->addCategoryName($id, $row);
                $this->categoryCache->addCategory($id, $row);

                $categoryNameRef = $this->categoryCache->getCategoryName($id);
                $this->categoryCache->addChild($parentId, $id, $categoryNameRef);
            }
        }
    }

    /**
     * Returns the user id of the category owner
     */
    public function getOwner(?int $categoryId = null): int
    {
        return $this->categoryPermissionContext->getOwner($categoryId);
    }

    /**
     * Returns the moderator group id for a given category or 0 if none assigned.
     */
    public function getModeratorGroupId(int $categoryId): int
    {
        return $this->categoryPermissionContext->getModeratorGroupId($categoryId);
    }

    /**
     * Returns the category tree as an array.
     */
    public function getCategoryTree(): array
    {
        return $this->categoryCache->getCatTree();
    }

    public function getCategoryName(int $categoryId): string
    {
        $categoryName = $this->categoryCache->getCategoryName($categoryId);
        return $categoryName['name'] ?? '';
    }

    public function getCategoryDescription(int $categoryId): string
    {
        $categoryName = $this->categoryCache->getCategoryName($categoryId);
        return $categoryName['description'] ?? '';
    }

    /**
     * Checks if a category has a link to a specific FAQ.
     */
    public function categoryHasLinkToFaq(int $faqId, int $categoryId): bool
    {
        return $this->getCategoryRepository()->hasLinkToFaq($faqId, $categoryId);
    }

    /**
     * Returns all category names from the internal cache.
     * This method replaces the deprecated public property $categoryNames.
     *
     * @return array<int, array<string, mixed>>
     * @since 4.2.0
     */
    public function getCategoryNames(): array
    {
        return $this->categoryCache->getCategoryNames();
    }
}
