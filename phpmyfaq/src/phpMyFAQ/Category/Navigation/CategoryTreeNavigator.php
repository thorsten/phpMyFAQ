<?php

/**
 * Category tree navigation service.
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

namespace phpMyFAQ\Category\Navigation;

use phpMyFAQ\Category\CategoryCache;
use phpMyFAQ\Category\Tree\TreeBuilder;

/**
 * Service for category tree navigation operations like expand, collapse, transform.
 */
class CategoryTreeNavigator
{
    public function __construct(
        private ?TreeBuilder $treeBuilder = new TreeBuilder(),
    ) {
    }

    /**
     * Transforms the linear array into a 1D array in tree order with info.
     *
     * @return array<array<string, mixed>>
     */
    public function transform(CategoryCache $categoryCache, int $categoryId): array
    {
        $entries = [];
        $tree = $this->buildTree($categoryCache, $categoryId);
        $this->transformRecursive($categoryCache, $tree, indent: 0, entries: $entries);
        return $entries;
    }

    /**
     * Builds tree structure for a category.
     *
     * @return array<string, mixed>
     */
    private function buildTree(CategoryCache $categoryCache, int $categoryId): array
    {
        return $this->treeBuilder->buildTree(
            $categoryCache->getCategoryNames(),
            $categoryCache->getChildren(),
            $categoryId,
        );
    }

    /**
     * Recursively transforms tree structure into a flat list.
     *
     * @param array<string, mixed> $tree
     * @param array<array<string, mixed>> $entries
     */
    private function transformRecursive(CategoryCache $categoryCache, array $tree, int $indent, array &$entries): void
    {
        // Skip invalid or empty trees
        if ($tree === [] || !isset($tree['id'])) {
            return;
        }

        $categoryId = (int) $tree['id'];
        $parentId = (int) ($tree['parent_id'] ?? 0);
        $children = $tree['children'] ?? [];
        $numChildren = count($children);

        $symbol = $this->getSymbol($categoryCache, $categoryId, $parentId, $numChildren);

        $entry = [
            'id' => $categoryId,
            'lang' => $tree['lang'] ?? '',
            'parent_id' => $parentId,
            'name' => $tree['name'] ?? '',
            'description' => $tree['description'] ?? '',
            'user_id' => $tree['user_id'] ?? 0,
            'group_id' => $tree['group_id'] ?? -1,
            'active' => $tree['active'] ?? 0,
            'show_home' => $tree['show_home'] ?? 0,
            'image' => $tree['image'] ?? '',
            'level' => $indent,
            'symbol' => $symbol,
            'numChildren' => $numChildren,
        ];

        $entries[] = $entry;

        foreach ($children as $child) {
            $this->transformRecursive($categoryCache, $child, $indent + 1, $entries);
        }
    }

    /**
     * Gets the symbol for tree rendering.
     */
    private function getSymbol(CategoryCache $categoryCache, int $categoryId, int $parentId, int $numChildren): string
    {
        if ($numChildren > 0) {
            return 'plus';
        }

        $siblings = $categoryCache->getChildren()[$parentId] ?? [];
        $array = array_keys($siblings);
        return $categoryId === end($array) ? 'angle' : 'medium';
    }

    /**
     * Expands a category node in the tree tab.
     */
    public function expand(CategoryCache $categoryCache, int $categoryId): void
    {
        $lineIndex = $this->getLineCategory($categoryCache, $categoryId);
        if ($lineIndex >= 0) {
            $categoryCache->updateTreeTabEntry($lineIndex, ['symbol' => 'minus']);
        }
    }

    /**
     * Collapses all nodes in the tree tab.
     */
    public function collapseAll(CategoryCache $categoryCache): void
    {
        $numTreeTab = $categoryCache->countTreeTab();
        for ($i = 0; $i < $numTreeTab; ++$i) {
            $entry = $categoryCache->getTreeTabEntry($i);
            if ($entry !== null && isset($entry['symbol']) && $entry['symbol'] === 'minus') {
                $categoryCache->updateTreeTabEntry($i, ['symbol' => 'plus']);
            }
        }
    }

    /**
     * Expands a tree from root to the given category.
     */
    public function expandTo(CategoryCache $categoryCache, int $categoryId): void
    {
        $this->collapseAll($categoryCache);
        $ascendants = $this->treeBuilder->getNodes($categoryCache->getCategoryNames(), $categoryId);
        $ascendants[] = $categoryId;
        $numAscendants = count($ascendants);

        for ($i = 0; $i < $numAscendants; ++$i) {
            $lineIndex = $this->getLineCategory($categoryCache, $ascendants[$i]);
            if ($lineIndex < 0) {
                continue;
            }

            $entry = $categoryCache->getTreeTabEntry($lineIndex);
            if ($entry !== null && isset($entry['numChildren'])) {
                $numChildren = $entry['numChildren'];
                if ($numChildren > 0) {
                    $this->expand($categoryCache, $ascendants[$i]);
                    continue;
                }

                break;
            }
        }
    }

    /**
     * Gets the line number where to find the node in the tree tab.
     */
    private function getLineCategory(CategoryCache $categoryCache, int $categoryId): int
    {
        $num = $categoryCache->countTreeTab();
        for ($i = 0; $i < $num; ++$i) {
            $entry = $categoryCache->getTreeTabEntry($i);
            if ($entry !== null && isset($entry['id']) && $entry['id'] === $categoryId) {
                return $i;
            }
        }

        return -1;
    }
}
