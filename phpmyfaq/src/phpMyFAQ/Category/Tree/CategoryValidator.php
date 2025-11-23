<?php

/**
 * Validates category data structures.
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

final class CategoryValidator
{
    /**
     * Validates if a category array has required fields.
     *
     * @param mixed $category
     * @return bool
     */
    public function isValidCategory(mixed $category): bool
    {
        if (!is_array($category)) {
            return false;
        }
        return (
            array_key_exists(
                key: 'parent_id',
                array: $category,
            )
            && array_key_exists(
                key: 'id',
                array: $category,
            )
        );
    }

    /**
     * Checks if a category is a direct child of a parent.
     *
     * @param array<string, mixed> $category
     */
    public function isDirectChild(array $category, mixed $categoryId, int $parentId): bool
    {
        if (!isset($category['parent_id'])) {
            return false;
        }
        if ((int) $category['parent_id'] !== $parentId) {
            return false;
        }
        return is_int($categoryId) && $categoryId > 0;
    }

    /**
     * Collects direct children IDs for a parent.
     *
     * @param array<int, array<string, mixed>> $categories
     * @return array<int>
     */
    public function collectDirectChildren(array $categories, int $parentId): array
    {
        $children = [];
        foreach ($categories as $categoryId => $category) {
            if (!$this->isDirectChild($category, $categoryId, $parentId)) {
                continue;
            }

            $children[] = $categoryId;
        }
        return $children;
    }
}
