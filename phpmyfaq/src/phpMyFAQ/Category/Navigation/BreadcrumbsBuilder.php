<?php

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
