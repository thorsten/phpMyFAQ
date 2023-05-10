<?php

/**
 * The category order class.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2023 phpMyFAQ Team
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
class CategoryOrder
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Adds a given category ID to the last position.
     */
    public function add(int $categoryId): bool
    {
        $query = sprintf(
            'INSERT INTO %sfaqcategory_order (category_id, position) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $categoryId,
            $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqcategory_order', 'position')
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Returns the current position for the given category ID
     *
     * @return bool
     */
    public function getPositionById(int $categoryId)
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
}
