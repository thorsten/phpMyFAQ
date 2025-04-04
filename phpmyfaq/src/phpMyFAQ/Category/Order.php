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
 * @copyright 2020-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-09-06
 */

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
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Adds a given category ID to the last position.
     */
    public function add(int $categoryId, int $parentId): bool
    {
        $query = sprintf(
            'INSERT INTO %sfaqcategory_order (category_id, parent_id, position) VALUES (%d, %d, %d)',
            Database::getTablePrefix(),
            $categoryId,
            $parentId,
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqcategory_order', 'position')
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
            $categoryId
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Stores the category tree in the database.
     *
     * @param stdClass[] $categoryTree
     * @param int|null   $parentId
     * @param int        $position
     * @param array      $insertQueries
     */
    public function setCategoryTree(
        array $categoryTree,
        ?int $parentId = null,
        int $position = 1,
        array &$insertQueries = []
    ): void {
        // Clear existing category order table
        if ($parentId === null) {
            $this->configuration->getDb()->query(
                sprintf('DELETE FROM %sfaqcategory_order', Database::getTablePrefix())
            );
        }

        foreach ($categoryTree as $category) {
            $id = (int) $category->id;

            if ($id > 0) {
                $insertQueries[] = sprintf(
                    'INSERT INTO %sfaqcategory_order(category_id, parent_id, position) VALUES (%d, %d, %d)',
                    Database::getTablePrefix(),
                    $id,
                    $parentId,
                    $position
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
     * @param stdClass[] $categories
     */
    public function getCategoryTree(array $categories, int $parentId = 0): array
    {
        $result = [];

        foreach ($categories as $category) {
            if ((int) $category['parent_id'] === $parentId) {
                $children = $this->getCategoryTree($categories, $category['id']);
                $result[$category['id']] = $children;
            }
        }
        
        return $result;
    }

    /**
     * Returns the parent ID of a given categoryTree.
     *
     * @param stdClass[] $categoryTree
     * @param int        $categoryId
     * @param int|null   $parentId
     * @return int|null
     */
    public function getParentId(array $categoryTree, int $categoryId, ?int $parentId = null): ?int
    {
        foreach ($categoryTree as $category) {
            if ((int)$category->id === $categoryId) {
                return (int)$parentId;
            }

            if (!empty($category->children)) {
                $foundParentId = $this->getParentId($category->children, $categoryId, $category->id);
                if ($foundParentId !== null) {
                    return $foundParentId;
                }
            }
        }

        return null;
    }

    /**
     * Returns all categories.
     */
    public function getAllCategories(): array
    {
        $query = sprintf(
            'SELECT category_id, parent_id, position FROM %sfaqcategory_order ORDER BY position',
            Database::getTablePrefix()
        );
        $result = $this->configuration->getDb()->query($query);

        $categories = [];

        while ($row = $this->configuration->getDb()->fetchArray($result)) {
            $categories[] = $row;
        }

        return $categories;
    }
}
