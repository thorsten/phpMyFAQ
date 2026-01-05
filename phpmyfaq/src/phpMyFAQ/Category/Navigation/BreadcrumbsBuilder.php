<?php

/**
 * Category breadcrumb builder class
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
 * @since     2025-10-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Category\Navigation;

use phpMyFAQ\Translation;

/**
 * Builds breadcrumb segments from a category map and a list of ids.
 */
final class BreadcrumbsBuilder
{
    /**
     * @param array<int, array<string, mixed>> $categoryNameMap id => row (expects name, description)
     * @param array<int> $ids ordered list from root to leaf
     * @return array<int, array{id:int, name:string, description:string}>
     */
    public function buildFromIds(array $categoryNameMap, array $ids): array
    {
        return $this->buildCategorySegments($categoryNameMap, $ids);
    }

    /**
     * Builds breadcrumb segments with the start page as the first segment.
     *
     * @param array<int, array<string, mixed>> $categoryNameMap id => row (expects name, description)
     * @param array<int> $ids ordered list from root to leaf
     * @param string|null $startPageName Optional start page name (defaults to Translation msgHome)
     * @param string $startPageDescription Optional start page description
     * @param string|null $allCategoriesName Optional all categories name (defaults to Translation msgShowAllCategories)
     * @param string $allCategoriesDescription Optional all categories description
     * @return array<int, array{id:int, name:string, description:string}>
     */
    public function buildFromIdsWithStartPage(
        array $categoryNameMap,
        array $ids,
        ?string $startPageName = null,
        string $startPageDescription = '',
        ?string $allCategoriesName = null,
        string $allCategoriesDescription = '',
    ): array {
        // Add the start page as the first segment
        $segments = [
            [
                'id' => -1,
                'name' => $startPageName ?? Translation::get('msgHome'),
                'description' => $startPageDescription,
            ],
            [
                'id' => 0,
                'name' => $allCategoriesName ?? Translation::get('msgShowAllCategories'),
                'description' => $allCategoriesDescription,
            ],
        ];

        // Add category segments
        $categorySegments = $this->buildCategorySegments($categoryNameMap, $ids);
        return array_merge($segments, $categorySegments);
    }

    /**
     * Builds category segments from category map and IDs.
     *
     * @param array<int, array<string, mixed>> $categoryNameMap id => row (expects name, description)
     * @param array<int> $ids ordered list from root to leaf
     * @return array<int, array{id:int, name:string, description:string}>
     */
    private function buildCategorySegments(array $categoryNameMap, array $ids): array
    {
        $segments = [];
        foreach ($ids as $id) {
            if (!isset($categoryNameMap[$id])) {
                continue;
            }

            $row = $categoryNameMap[$id];
            $segments[] = [
                'id' => $id,
                'name' => (string) ($row['name'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
            ];
        }

        return $segments;
    }
}
