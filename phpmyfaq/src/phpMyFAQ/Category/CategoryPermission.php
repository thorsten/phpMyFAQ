<?php

/**
 * Category permissions class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-04
 */

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

/**
 * Class CategoryPermission
 *
 * @package phpMyFAQ\Category
 */
class CategoryPermission
{
    /** @var string */
    final public const USER = 'user';

    /** @var string */
    final public const GROUP = 'group';

    /**
     * FaqPermission constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Adds the category permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param array  $categories ID of the current category
     * @param array  $ids Array of group or user IDs
     */
    public function add(string $mode, array $categories, array $ids): bool
    {
        if (self::USER !== $mode && self::GROUP !== $mode) {
            return false;
        }

        foreach ($categories as $categoryId) {
            foreach ($ids as $id) {
                $query = sprintf(
                    'SELECT * FROM %sfaqcategory_%s WHERE category_id = %d AND %s_id = %d',
                    Database::getTablePrefix(),
                    $mode,
                    $categoryId,
                    $mode,
                    $id
                );

                if ($this->config->getDb()->numRows($this->config->getDb()->query($query))) {
                    continue;
                }

                $query = sprintf(
                    'INSERT INTO %sfaqcategory_%s (category_id, %s_id) VALUES (%d, %d)',
                    Database::getTablePrefix(),
                    $mode,
                    $mode,
                    $categoryId,
                    $id
                );

                $this->config->getDb()->query($query);
            }
        }

        return true;
    }

    /**
     * Deletes the category permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param array  $categories ID of the current category
     */
    public function delete(string $mode, array $categories): bool
    {
        if (self::USER !== $mode && self::GROUP !== $mode) {
            return false;
        }

        foreach ($categories as $category_id) {
            $query = sprintf(
                '
                DELETE FROM
                    %sfaqcategory_%s
                WHERE
                    category_id = %d',
                Database::getTablePrefix(),
                $mode,
                $category_id
            );
            $this->config->getDb()->query($query);
        }

        return true;
    }

    /**
     * Returns true, if a given category has user or group permissions.
     * Otherwise, the methods returns false.
     */
    public function isRestricted(int $categoryId): bool
    {
        $hasUserPermissions = $this->get(self::USER, [$categoryId]);
        $hasGroupPermissions = $this->get(self::GROUP, [$categoryId]);

        if (
            (isset($hasUserPermissions[0]) && $hasUserPermissions[0] !== -1) ||
            (isset($hasGroupPermissions[0]) && $hasGroupPermissions[0] !== -1)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns the category permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param array  $categories Array of category ids
     */
    public function get(string $mode, array $categories): array
    {
        $permissions = [];

        if (self::USER !== $mode && self::GROUP !== $mode) {
            return $permissions;
        }

        $query = sprintf(
            'SELECT %s_id AS permission FROM %sfaqcategory_%s WHERE category_id IN (%s)',
            $mode,
            Database::getTablePrefix(),
            $mode,
            implode(', ', $categories)
        );

        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchObject($result)) {
            $permissions[] = (int)$row->permission;
        }

        return $permissions;
    }
}
