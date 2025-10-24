<?php

/**
 * Cache for category data.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-10-20
 */

declare(strict_types=1);

namespace phpMyFAQ\Category;

/**
 * Manages cached category data structures.
 * Consolidates all internal cache arrays from the legacy Category class.
 */
final class CategoryCache
{
    /**
     * The categories as an array.
     * @var array<int, array<string, mixed>>
     */
    private array $categories = [];

    /**
     * The category names as an array indexed by category ID.
     * @var array<int, array<string, mixed>>
     */
    private array $categoryNames = [];

    /**
     * The tree with the tabs.
     * @var array<int, array<string, mixed>>
     */
    private array $treeTab = [];

    /**
     * The category tree.
     * @var array<int, array<string, mixed>>
     */
    private array $catTree = [];

    /**
     * The children nodes: parentId => [childId => &categoryRow]
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $children = [];

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    public function addCategory(int $id, array $category): void
    {
        $this->categories[$id] = $category;
    }

    public function getCategoryNames(): array
    {
        return $this->categoryNames;
    }

    public function setCategoryNames(array $categoryNames): void
    {
        $this->categoryNames = $categoryNames;
    }

    public function addCategoryName(int $id, array $categoryName): void
    {
        $this->categoryNames[$id] = $categoryName;
    }

    public function getCategoryName(int $id): array
    {
        return $this->categoryNames[$id] ?? [];
    }

    public function getTreeTab(): array
    {
        return $this->treeTab;
    }

    public function setTreeTab(array $treeTab): void
    {
        $this->treeTab = $treeTab;
    }

    public function addTreeTabEntry(array $entry): void
    {
        $this->treeTab[] = $entry;
    }

    public function updateTreeTabEntry(int $index, array $entry): void
    {
        if (isset($this->treeTab[$index])) {
            $this->treeTab[$index] = array_merge($this->treeTab[$index], $entry);
        }
    }

    public function getTreeTabEntry(int $index): ?array
    {
        return $this->treeTab[$index] ?? null;
    }

    public function countTreeTab(): int
    {
        return count($this->treeTab);
    }

    public function getCatTree(): array
    {
        return $this->catTree;
    }

    public function setCatTree(array $catTree): void
    {
        $this->catTree = $catTree;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function addChild(int $parentId, int $childId, array &$categoryRef): void
    {
        $this->children[$parentId][$childId] = &$categoryRef;
    }

    public function getChildrenOfParent(int $parentId): array
    {
        return $this->children[$parentId] ?? [];
    }

    /**
     * Clears all cached data.
     */
    public function clear(): void
    {
        $this->categories = [];
        $this->categoryNames = [];
        $this->treeTab = [];
        $this->catTree = [];
        $this->children = [];
    }
}
