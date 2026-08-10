<?php

/**
 * Filters linear category trees to the categories a user may act on.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-10
 */

declare(strict_types=1);

namespace phpMyFAQ\Category;

/**
 * Filters linear category trees down to the categories a user may act on,
 * based on group-level category restrictions. Null means unrestricted.
 */
final class CategoryTreeRestrictionFilter
{
    /**
     * @param array<int, array<string, mixed>> $categoryTree Linear tree from Category::getCategoryTree()
     * @param array<int>|null $allowedCategoryIds Null = unrestricted
     * @return array<int, array<string, mixed>>
     */
    public static function filter(array $categoryTree, ?array $allowedCategoryIds): array
    {
        if ($allowedCategoryIds === null) {
            return $categoryTree;
        }

        return array_values(array_filter($categoryTree, static fn(array $entry): bool => in_array(
            needle: (int) $entry['id'],
            haystack: $allowedCategoryIds,
            strict: true,
        )));
    }

    /**
     * Filters a nested tree (map of categoryId => children map) as produced by
     * Category\Order::getCategoryTree() and Category::buildAdminCategoryTree().
     * Removing a node removes its whole subtree.
     *
     * @param array<array-key, mixed> $categoryTree
     * @param array<int>|null $allowedCategoryIds Null = unrestricted
     * @return array<array-key, mixed>
     */
    public static function filterNested(array $categoryTree, ?array $allowedCategoryIds): array
    {
        if ($allowedCategoryIds === null) {
            return $categoryTree;
        }

        $filtered = [];
        foreach ($categoryTree as $categoryId => $children) {
            if (!in_array(needle: (int) $categoryId, haystack: $allowedCategoryIds, strict: true)) {
                continue;
            }

            $filtered[$categoryId] = is_array($children)
                ? self::filterNested($children, $allowedCategoryIds)
                : $children;
        }

        return $filtered;
    }
}
