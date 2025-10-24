<?php

/**
 * Tree builder interface for phpMyFAQ category trees.
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

namespace phpMyFAQ\Category\Tree;

interface TreeBuilderInterface
{
    /**
     * Builds a linear tree structure with indentation levels.
     *
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, array<string, mixed>>
     */
    public function buildLinearTree(array $categories, int $parentId = 0, int $indent = 0): array;

    /**
     * Builds the admin category tree structure.
     *
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, array<string, mixed>>
     */
    public function buildAdminCategoryTree(array $categories, int $parentId = 0): array;

    /**
     * Builds a hierarchical tree structure from flat category data.
     *
     * @param array<int, array<string, mixed>> $categoryNames
     * @param array<int, array<int, array<string, mixed>>> $children
     * @return array<string, mixed>
     */
    public function buildTree(array $categoryNames, array $children, int $categoryId): array;

    /**
     * Gets direct children IDs of a category.
     *
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int>
     */
    public function getChildren(array $childrenMap, int $categoryId): array;

    /**
     * Gets all descendant IDs of a category (recursively).
     *
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int>
     */
    public function getChildNodes(array $childrenMap, int $categoryId): array;

    /**
     * Gets the path from root to a category (list of ancestor IDs).
     *
     * @param array<int, array<string, mixed>> $categoryNames
     * @return array<int>
     */
    public function getNodes(array $categoryNames, int $categoryId): array;

    /**
     * Gets sibling category IDs (including the category itself).
     *
     * @param array<int, array<string, mixed>> $categoryNames
     * @param array<int, array<int, array<string, mixed>>> $children
     * @return array<int>
     */
    public function getBrothers(array $categoryNames, array $children, int $categoryId): array;

    /**
     * Computes the depth level of a category in the tree.
     *
     * @param array<int, array<string, mixed>> $categoryNames
     */
    public function computeLevel(array $categoryNames, int $categoryId): int;
}
