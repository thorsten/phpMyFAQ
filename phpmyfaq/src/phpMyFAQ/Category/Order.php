<?php

/**
 * The category order class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-09-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use stdClass;

/**
 * Class CategoryOrder
 *
 * @package phpMyFAQ\Category
 */
readonly class Order
{
    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Adds a given category ID to the last position.
     */
    public function add(int $categoryId, int $parentId): bool
    {
        $this->syncMissingCategories($categoryId);

        $query = sprintf(
            'INSERT INTO %sfaqcategory_order (category_id, parent_id, position) VALUES (%d, %d, %d)',
            Database::getTablePrefix(),
            $categoryId,
            $parentId,
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqcategory_order', 'position'),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes a given category ID.
     */
    public function remove(int $categoryId): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqcategory_order WHERE category_id = %d',
            Database::getTablePrefix(),
            $categoryId,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Stores the category tree in the database.
     *
     * @param stdClass[] $categoryTree
     */
    public function setCategoryTree(
        array $categoryTree,
        ?int $parentId = null,
        int $position = 1,
        array &$insertQueries = [],
    ): void {
        // Clear the existing category order table
        if ($parentId === null) {
            $this->configuration
                ->getDb()
                ->query(sprintf('DELETE FROM %sfaqcategory_order', Database::getTablePrefix()));
        }

        foreach ($categoryTree as $category) {
            $id = (int) $category->id;

            if ($id > 0) {
                $insertQueries[] = sprintf(
                    'INSERT INTO %sfaqcategory_order(category_id, parent_id, position) VALUES (%d, %d, %d)',
                    Database::getTablePrefix(),
                    $id,
                    $parentId,
                    $position,
                );

                if (!empty($category->children)) {
                    // Pass the same reference of $insertQueries to the recursive call
                    $this->setCategoryTree($category->children, $id, 1, $insertQueries);
                }

                ++$position;
            }
        }

        // Execute queries only on the top-level call
        if ($parentId === null) {
            foreach ($insertQueries as $insertQuery) {
                $this->configuration->getDb()->query($insertQuery);
            }
        }
    }

    /**
     * Returns the category tree.
     *
     * @param array<array{category_id: string, parent_id: string}> $categories
     * @param int $parentId
     * @param array<int, bool> $visited Array to track visited category IDs to prevent infinite recursion
     */
    public function getCategoryTree(array $categories, int $parentId = 0, array &$visited = []): array
    {
        $result = [];

        foreach ($categories as $category) {
            $categoryId = (int) $category['category_id'];
            $categoryParentId = (int) $category['parent_id'];

            // Skip if category is its own parent or creates a cycle
            if ($categoryId === $categoryParentId) {
                continue;
            }

            if ($categoryParentId === $parentId) {
                // Check if this category has already been visited to prevent cycles
                if (isset($visited[$categoryId])) {
                    continue;
                }

                // Add the current category to a visited list
                $visited[$categoryId] = true;

                $childCategories = $this->getCategoryTree($categories, $categoryId, $visited);
                $result[$categoryId] = $childCategories;
            }
        }

        return $result;
    }

    /**
     * Returns the parent ID of a given categoryTree.
     *
     * @param stdClass[] $categoryTree
     */
    public function getParentId(array $categoryTree, int $categoryId, ?int $parentId = null): ?int
    {
        foreach ($categoryTree as $category) {
            if ((int) $category->id === $categoryId) {
                return (int) $parentId;
            }

            if (!empty($category->children)) {
                $foundParentId = $this->getParentId($category->children, $categoryId, (int) $category->id);
                if ($foundParentId !== null) {
                    return $foundParentId;
                }
            }
        }

        return null;
    }

    /**
     * Returns all categories.
     *
     * @return array<int, array{category_id: int, parent_id: int, position: int}>
     */
    public function getAllCategories(): array
    {
        $this->syncMissingCategories();

        $query = sprintf(
            'SELECT category_id, parent_id, position FROM %sfaqcategory_order ORDER BY position',
            Database::getTablePrefix(),
        );
        $result = $this->configuration->getDb()->query($query);

        $categories = [];

        while ($row = $this->configuration->getDb()->fetchArray($result)) {
            $categories[] = $row;
        }

        return $categories;
    }

    /**
     * Adds all categories that are missing in the category order table, e.g. after an update from an older
     * version where the table was never populated. Returns the number of added categories.
     */
    public function syncMissingCategories(?int $excludeCategoryId = null): int
    {
        $query = sprintf(
            'SELECT c.id AS id, c.parent_id AS parent_id FROM %sfaqcategories c
                LEFT JOIN %sfaqcategory_order o ON o.category_id = c.id
                WHERE o.category_id IS NULL%s
                ORDER BY c.id',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $excludeCategoryId !== null ? sprintf(' AND c.id <> %d', $excludeCategoryId) : '',
        );
        $result = $this->configuration->getDb()->query($query);

        $missingCategories = [];
        while ($row = $this->configuration->getDb()->fetchArray($result)) {
            $categoryId = (int) $row['id'];
            // faqcategories contains one row per language, use the first one
            if (!array_key_exists($categoryId, $missingCategories)) {
                $missingCategories[$categoryId] = (int) $row['parent_id'];
            }
        }

        $added = 0;
        foreach ($missingCategories as $categoryId => $parentId) {
            $insert = sprintf(
                'INSERT INTO %sfaqcategory_order (category_id, parent_id, position) VALUES (%d, %d, %d)',
                Database::getTablePrefix(),
                $categoryId,
                $parentId,
                $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqcategory_order', 'position'),
            );
            if ($this->configuration->getDb()->query($insert)) {
                ++$added;
            }
        }

        return $added;
    }
}
