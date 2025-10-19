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
 * @since     2026-10-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Category\Navigation;

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
