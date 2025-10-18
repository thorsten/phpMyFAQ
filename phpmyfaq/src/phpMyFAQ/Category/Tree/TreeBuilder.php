<?php

/**
 * Tree builder for categories.
 *
 *  This Source Code Form is subject to the terms of the Mozilla Public License,
 *  v. 2.0. If a copy of the MPL was not distributed with this file, You can
 *  obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-10-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Category\Tree;

final class TreeBuilder
{
    /**
     * Builds the category tree for the admin category overview.
     * Pure function: does not mutate input.
     *
     * @param array<int, array<string, mixed>> $categories Flat list keyed or not by id, each with id,parent_id
     * @param int $parentId Parent id to expand from
     * @return array<int, array> Map of categoryId => subtree
     */
    public function buildAdminCategoryTree(array $categories, int $parentId = 0): array
    {
        $result = [];

        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }
            if (!array_key_exists('parent_id', $category) || !array_key_exists('id', $category)) {
                continue;
            }
            if ((int) $category['parent_id'] === $parentId) {
                $categoryId = (int) $category['id'];
                $result[$categoryId] = $this->buildAdminCategoryTree($categories, $categoryId);
            }
        }

        return $result;
    }

    /**
     * Builds a linear tree (array) with indentation information.
     * Matches legacy Category::buildCategoryTree output shape.
     *
     * @param array<int, array<string, mixed>> $categories
     * @param int $parentId
     * @param int $indent
     * @return array<int, array<string, mixed>>
     */
    public function buildLinearTree(array $categories, int $parentId = 0, int $indent = 0): array
    {
        $catTree = [];

        // collect direct children ids in insertion order
        $temporaryTree = [];
        foreach ($categories as $categoryId => $n) {
            if (!isset($n['parent_id'])) {
                continue;
            }
            if ((int) $n['parent_id'] !== $parentId) {
                continue;
            }
            if ($categoryId <= 0) {
                continue;
            }
            $temporaryTree[] = $categoryId;
        }

        if ($temporaryTree === []) {
            return $catTree;
        }

        foreach ($temporaryTree as $childId) {
            if (!isset($categories[$childId])) {
                continue;
            }
            $row = $categories[$childId];
            $row['indent'] = $indent;
            $catTree[] = $row;
            $catTree = array_merge($catTree, $this->buildLinearTree($categories, $row['id'], $indent + 1));
        }

        return $catTree;
    }

    /**
     * Returns a node path from root to the given category id.
     *
     * @param array<int, array<string, mixed>> $categoryName Map id => row (needs parent_id)
     * @return array<int>
     */
    public function getNodes(array $categoryName, int $categoryId): array
    {
        $nodes = [];
        if ($categoryId <= 0) {
            return $nodes;
        }
        $nodes[] = $categoryId;
        $currentCategoryId = $categoryId;
        while ($currentCategoryId > 0) {
            if (!isset($categoryName[$currentCategoryId])) {
                break;
            }
            $parentId = (int) ($categoryName[$currentCategoryId]['parent_id'] ?? 0);
            if ($parentId <= 0 || $parentId === $currentCategoryId) {
                break;
            }
            if (!isset($categoryName[$parentId])) {
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
     * Builds branch visuals (vertical/space) for a category path.
     *
     * @param array<int, array<string, mixed>> $categoryName
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int, string>
     */
    public function buildTree(array $categoryName, array $childrenMap, int $categoryId): array
    {
        $ascendants = $this->getNodes($categoryName, $categoryId);
        $tree = [];
        foreach ($ascendants as $i => $ascendantId) {
            if ($ascendantId === 0) {
                break;
            }
            $brothers = $this->getBrothers($categoryName, $childrenMap, (int) $ascendantId);
            $last = end($brothers);
            $tree[$i] = $ascendantId === $last ? 'space' : 'vertical';
        }
        return $tree;
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
            if (in_array($categoryId, $alreadyListed, true)) {
                break;
            }
            $alreadyListed[] = $categoryId;
        }
        return $level;
    }
}
