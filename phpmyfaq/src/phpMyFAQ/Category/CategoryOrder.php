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
 * @copyright 2020-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-09-06
 */

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

/**
 * Class CategoryOrder
 *
 * @package phpMyFAQ\Category
 */
readonly class CategoryOrder
{
    /**
     * Constructor.
     */
    public function __construct(private Configuration $config)
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
            $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqcategory_order', 'position')
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Returns the current position for the given category ID
     *
     * @param int $categoryId
     * @return bool
     */
    public function getPositionById(int $categoryId): bool
    {
        $query = sprintf(
            'SELECT position FROM %sfaqcategory_order WHERE category_id = %d',
            Database::getTablePrefix(),
            $categoryId
        );
        $result = $this->config->getDb()->query($query);

        return (bool) $this->config->getDb()->fetchRow($result);
    }

    /**
     * Inserts the position for the given category ID
     */
    public function setPositionById(int $categoryId, int $position): bool
    {
        $query = sprintf(
            'INSERT INTO %sfaqcategory_order (category_id, position) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $categoryId,
            $position
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Updates the position for the given category ID
     */
    public function updatePositionById(int $categoryId, int $position): bool
    {
        $query = sprintf(
            'UPDATE %sfaqcategory_order SET position = %d WHERE category_id = %d',
            Database::getTablePrefix(),
            $position,
            $categoryId
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Returns the category tree from the database.
     */
    public function getCategoryTree(): array
    {
        $query = sprintf(
            'SELECT category_id, parent_id, position FROM %sfaqcategory_order ORDER BY parent_id, position',
            Database::getTablePrefix()
        );

        $result = $this->config->getDb()->query($query);

        $data = [];
        while ($row = $this->config->getDb()->fetchArray($result)) {
            $parentId = $row['parent_id'];
            $id = $row['id'];

            if (!isset($data[$parentId])) {
                $data[$parentId] = [];
            }

            $data[$parentId][$id] = $row;
        }

        return $data;
    }

    /**
     * Stores the category tree in the database.
     *
     * @param array $categoryTree
     * @param int|null $parentId
     * @param int $position
     * @return void
     */
    public function setCategoryTree(array $categoryTree, int $parentId = null, int $position = 1): void
    {
        foreach ($categoryTree as $category) {
            $id = $category['id'];

            $query = sprintf(
                'INSERT INTO %sfaqcategory_order(id, parent_id, ordering) VALUES (%d, %d, $%d)',
                Database::getTablePrefix(),
                $id,
                $parentId,
                $position
            );
            $this->config->getDb()->query($query);

            if (!empty($category['children'])) {
                $this->setCategoryTree($category['children'], $id);
            }

            $position++;
        }
    }
}
