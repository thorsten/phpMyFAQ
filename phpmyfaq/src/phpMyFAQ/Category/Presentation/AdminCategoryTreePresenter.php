<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Presentation;

use phpMyFAQ\Category\Tree\TreeBuilder;

/**
 * Presenter for admin category tree data (non-HTML).
 * Produces entries equivalent to the legacy Category::transform additions.
 */
final class AdminCategoryTreePresenter
{
    /**
     * @param array<int, array<string, mixed>> $categoryName Map id => row
     * @param array<int, array<int, array<string, mixed>>> $childrenMap Parent => [childId => row]
     * @return array<int, array<string, mixed>> linear list of entries
     */
    public function transform(TreeBuilder $treeBuilder, array $categoryName, array $childrenMap, int $categoryId): array
    {
        $entries = [];

        $category = $categoryName[$categoryId] ?? [];
        $parentId = (int) ($category['parent_id'] ?? 0);
        $children = $childrenMap[$categoryId] ?? [];

        if ($categoryId > 0 && $category !== []) {
            $entry = $category;
            $entry['level'] = $treeBuilder->computeLevel($categoryName, $categoryId);
            $entry['children'] = array_keys($children);
            $entry['tree'] = $treeBuilder->buildTree($categoryName, $childrenMap, $categoryId);
            $entry['symbol'] = $this->getSymbol($childrenMap, $categoryId, $parentId);
            $entries[] = $entry;
        }

        foreach (array_keys($children) as $childId) {
            $entries = array_merge($entries, $this->transform(
                $treeBuilder,
                $categoryName,
                $childrenMap,
                (int) $childId,
            ));
        }

        return $entries;
    }

    /**
     * Returns the symbol for a category based on its position among its siblings.
     */
    private function getSymbol(array $childrenMap, int $categoryId, int $parentId): string
    {
        $siblings = $childrenMap[$parentId] ?? [];
        $keys = array_keys($siblings);
        $last = end($keys);
        return $categoryId === $last ? 'angle' : 'medium';
    }
}
