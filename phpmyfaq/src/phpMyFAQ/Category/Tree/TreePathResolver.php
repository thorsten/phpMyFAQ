<?php

/**
 * Resolves paths and relationships in category trees.
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
 * @since     2025-10-19
 */

declare(strict_types=1);

namespace phpMyFAQ\Category\Tree;

final class TreePathResolver
{
    /**
     * Returns a node path from root to the given category id.
     *
     * @param array<int, array<string, mixed>> $categoryName Map id => row (needs parent_id)
     * @return array<int>
     */
    public function getNodes(array $categoryName, int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $nodes = [$categoryId];
        $currentCategoryId = $categoryId;

        while ($currentCategoryId > 0) {
            $parentId = $this->getValidParentId($categoryName, $currentCategoryId);
            if ($parentId === null) {
                break;
            }

            array_unshift($nodes, $parentId);
            $currentCategoryId = $parentId;
        }

        return $nodes;
    }

    /**
     * Returns direct children IDs for a category.
     *
     * @param array<int, array<int, array<string, mixed>>> $childrenMap parentId => [childId => row]
     * @return array<int>
     */
    public function getChildren(array $childrenMap, int $categoryId): array
    {
        return isset($childrenMap[$categoryId]) ? array_keys($childrenMap[$categoryId]) : [];
    }

    /**
     * Returns all descendant IDs of a category.
     *
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int>
     */
    public function getChildNodes(array $childrenMap, int $categoryId): array
    {
        $result = [];
        if (!isset($childrenMap[$categoryId])) {
            return $result;
        }

        foreach (array_keys($childrenMap[$categoryId]) as $childId) {
            $result[] = (int) $childId;
            $result = array_merge($result, $this->getChildNodes($childrenMap, (int) $childId));
        }

        return $result;
    }

    /**
     * Returns siblings (brothers) of a category including itself.
     *
     * @param array<int, array<string, mixed>> $categoryName
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int>
     */
    public function getBrothers(array $categoryName, array $childrenMap, int $categoryId): array
    {
        $parentId = (int) ($categoryName[$categoryId]['parent_id'] ?? 0);
        return $this->getChildren($childrenMap, $parentId);
    }

    /**
     * Computes the depth level of a category within the tree (root has 0).
     *
     * @param array<int, array<string, mixed>> $categoryName Map id => row (needs parent_id)
     */
    public function computeLevel(array $categoryName, int $categoryId): int
    {
        $alreadyListed = [$categoryId];
        $level = 0;
        while (isset($categoryName[$categoryId]['parent_id']) && (int) $categoryName[$categoryId]['parent_id'] !== 0) {
            ++$level;
            $categoryId = (int) $categoryName[$categoryId]['parent_id'];
            if (in_array($categoryId, $alreadyListed, strict: true)) {
                break;
            }

            $alreadyListed[] = $categoryId;
        }

        return $level;
    }

    /**
     * Gets valid parent ID for a category, or null if none exists.
     *
     * @param array<int, array<string, mixed>> $categoryName
     */
    private function getValidParentId(array $categoryName, int $currentCategoryId): ?int
    {
        if (!isset($categoryName[$currentCategoryId])) {
            return null;
        }

        $parentId = (int) ($categoryName[$currentCategoryId]['parent_id'] ?? 0);

        if ($parentId <= 0 || $parentId === $currentCategoryId) {
            return null;
        }

        if (!isset($categoryName[$parentId])) {
            return null;
        }

        return $parentId;
    }
}
