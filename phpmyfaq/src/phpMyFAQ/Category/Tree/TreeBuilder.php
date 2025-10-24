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

class TreeBuilder implements TreeBuilderInterface
{
    private TreePathResolver $pathResolver;
    private TreeVisualizer $visualizer;
    private CategoryValidator $validator;

    public function __construct()
    {
        $this->pathResolver = new TreePathResolver();
        $this->visualizer = new TreeVisualizer($this->pathResolver);
        $this->validator = new CategoryValidator();
    }

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
            if (!$this->validator->isValidCategory($category)) {
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
        $childrenIds = $this->validator->collectDirectChildren($categories, $parentId);

        if ($childrenIds === []) {
            return [];
        }

        $catTree = [];
        foreach ($childrenIds as $childId) {
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
     * Delegates to TreePathResolver::getNodes()
     *
     * @param array<int, array<string, mixed>> $categoryName Map id => row (needs parent_id)
     * @return array<int>
     */
    public function getNodes(array $categoryName, int $categoryId): array
    {
        return $this->pathResolver->getNodes($categoryName, $categoryId);
    }

    /**
     * Delegates to TreePathResolver::getChildren()
     *
     * @param array<int, array<int, array<string, mixed>>> $childrenMap parentId => [childId => row]
     * @return array<int>
     */
    public function getChildren(array $childrenMap, int $categoryId): array
    {
        return $this->pathResolver->getChildren($childrenMap, $categoryId);
    }

    /**
     * Delegates to TreePathResolver::getChildNodes()
     *
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int>
     */
    public function getChildNodes(array $childrenMap, int $categoryId): array
    {
        return $this->pathResolver->getChildNodes($childrenMap, $categoryId);
    }

    /**
     * Delegates to TreePathResolver::getBrothers()
     *
     * @param array<int, array<string, mixed>> $categoryName
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int>
     */
    public function getBrothers(array $categoryName, array $childrenMap, int $categoryId): array
    {
        return $this->pathResolver->getBrothers($categoryName, $childrenMap, $categoryId);
    }

    /**
     * Delegates to TreeVisualizer::buildTree()
     *
     * @param array<int, array<string, mixed>> $categoryName
     * @param array<int, array<int, array<string, mixed>>> $childrenMap
     * @return array<int, string>
     */
    public function buildTree(array $categoryName, array $childrenMap, int $categoryId): array
    {
        return $this->visualizer->buildTree($categoryName, $childrenMap, $categoryId);
    }

    /**
     * Delegates to TreePathResolver::computeLevel()
     *
     * @param array<int, array<string, mixed>> $categoryName Map id => row (needs parent_id)
     */
    public function computeLevel(array $categoryName, int $categoryId): int
    {
        return $this->pathResolver->computeLevel($categoryName, $categoryId);
    }
}
