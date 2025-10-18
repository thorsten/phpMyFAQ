<?php

declare(strict_types=1);

/**
 * Tree builder for categories.
 *
 * @package phpMyFAQ\Category\Tree
 */

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
}
