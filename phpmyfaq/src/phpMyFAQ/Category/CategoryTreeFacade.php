<?php

/**
 * Facade for category tree building operations.
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

use phpMyFAQ\Category\Tree\TreeBuilder;

/**
 * Facade for category tree building operations.
 * Consolidates tree-building methods from the legacy Category class.
 */
final class CategoryTreeFacade
{
    public function __construct(
        private ?TreeBuilder $treeBuilder = new TreeBuilder(),
    ) {
    }

    /**
     * Builds a linear category tree with indentation.
     *
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, array<string, mixed>>
     */
    public function buildLinearTree(array $categories, int $parentId = 0, int $indent = 0): array
    {
        return $this->treeBuilder->buildLinearTree($categories, $parentId, $indent);
    }

    /**
     * Builds the admin category tree.
     *
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, array>
     */
    public function buildAdminCategoryTree(array $categories, int $parentId = 0): array
    {
        return $this->treeBuilder->buildAdminCategoryTree($categories, $parentId);
    }

    /**
     * Gets direct children of a category.
     *
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int>
     */
    public function getChildren(array $childrenMap, int $categoryId): array
    {
        return $this->treeBuilder->getChildren($childrenMap, $categoryId);
    }

    /**
     * Gets all descendant nodes of a category.
     *
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int>
     */
    public function getChildNodes(array $childrenMap, int $categoryId): array
    {
        return $this->treeBuilder->getChildNodes($childrenMap, $categoryId);
    }

    /**
     * Computes the depth level of a category.
     *
     * @param array<int, array<string, mixed>> $categoryNames
     */
    public function getLevelOf(array $categoryNames, int $categoryId): int
    {
        return $this->treeBuilder->computeLevel($categoryNames, $categoryId);
    }

    /**
     * Gets the path from root to a category.
     *
     * @param array<int, array<string, mixed>> $categoryNames
     * @return array<int>
     */
    public function getNodes(array $categoryNames, int $categoryId): array
    {
        return $this->treeBuilder->getNodes($categoryNames, $categoryId);
    }
}
