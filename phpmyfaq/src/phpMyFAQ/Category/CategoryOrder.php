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
    public function __construct(private Configuration $config)
    {
    }

    /**
     * Returns the current position for the given category ID
     *
     * @return mixed
     */
    public function getPositionById(int $categoryId)
    {
        $query = sprintf(
            'SELECT position FROM %sfaqcategory_order WHERE category_id = %d',
            Database::getTablePrefix(),
            $categoryId
        );
        $result = $this->config->getDb()->query($query);

        return $this->config->getDb()->fetchRow($result);
    }

    /**
     * Inserts the position for the given category ID
     *
     * @return mixed
     */
    public function setPositionById(int $categoryId, int $position)
    {
        $query = sprintf(
            'INSERT INTO %sfaqcategory_order (category_id, position) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $categoryId,
            $position
        );

        return $this->config->getDb()->query($query);
    }

    /**
     * Updates the position for the given category ID
     *
     * @return mixed
     */
    public function updatePositionById(int $categoryId, int $position)
    {
        $query = sprintf(
            'UPDATE %sfaqcategory_order SET position = %d WHERE category_id = %d',
            Database::getTablePrefix(),
            $position,
            $categoryId
        );

        return $this->config->getDb()->query($query);
    }
}
