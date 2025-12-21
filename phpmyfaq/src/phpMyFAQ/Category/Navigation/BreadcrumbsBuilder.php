<?php declare(strict_types=1);

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
     * Builds breadcrumb segments with the startpage as the first segment.
     *
     * @param array<int, array<string, mixed>> $categoryNameMap id => row (expects name, description)
     * @param array<int> $ids ordered list from root to leaf
     * @param string|null $startpageName Optional startpage name (defaults to Translation msgHome)
     * @param string $startpageDescription Optional startpage description
     * @return array<int, array{id:int, name:string, description:string}>
     */
    public function buildFromIdsWithStartpage(
        array $categoryNameMap,
        array $ids,
        ?string $startpageName = null,
        string $startpageDescription = '',
    ): array {
        // Add the startpage as the first segment
        $segments = [
            [
                'id' => -1,
                'name' => $startpageName ?? Translation::get('msgHome'),
                'description' => $startpageDescription,
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
