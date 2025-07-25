<?php

/**
 * Category permissions class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2025 phpMyFAQ Team
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
class Permission
{
    final public const string USER = 'user';

    final public const string GROUP = 'group';

    /**
     * FaqPermission constructor.
     */
    public function __construct(private readonly Configuration $configuration)
    {
    }

    /**
     * Adds the category permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param int[]  $categories ID of the current category
     * @param int[]  $ids Array of group or user IDs
     */
    public function add(string $mode, array $categories, array $ids): bool
    {
        if (self::USER !== $mode && self::GROUP !== $mode) {
            return false;
        }

        foreach ($categories as $category) {
            foreach ($ids as $id) {
                $query = sprintf(
                    'SELECT * FROM %sfaqcategory_%s WHERE category_id = %d AND %s_id = %d',
                    Database::getTablePrefix(),
                    $mode,
                    $category,
                    $mode,
                    $id
                );

                if ($this->configuration->getDb()->numRows($this->configuration->getDb()->query($query)) !== 0) {
                    continue;
                }

                $query = sprintf(
                    'INSERT INTO %sfaqcategory_%s (category_id, %s_id) VALUES (%d, %d)',
                    Database::getTablePrefix(),
                    $mode,
                    $mode,
                    $category,
                    $id
                );

                $this->configuration->getDb()->query($query);
            }
        }

        return true;
    }

    /**
     * Deletes the category permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param int[]  $categories ID of the current category
     */
    public function delete(string $mode, array $categories): bool
    {
        if (self::USER !== $mode && self::GROUP !== $mode) {
            return false;
        }

        foreach ($categories as $category) {
            $query = sprintf(
                '
                DELETE FROM
                    %sfaqcategory_%s
                WHERE
                    category_id = %d',
                Database::getTablePrefix(),
                $mode,
                $category
            );
            $this->configuration->getDb()->query($query);
        }

        return true;
    }

    /**
     * Returns true, if a given category has user or group permissions.
     * Otherwise, the methods return false.
     */
    public function isRestricted(int $categoryId): bool
    {
        $hasUserPermissions = $this->get(self::USER, [$categoryId]);
        $hasGroupPermissions = $this->get(self::GROUP, [$categoryId]);
        return (isset($hasUserPermissions[0]) && $hasUserPermissions[0] !== -1) ||
        (isset($hasGroupPermissions[0]) && $hasGroupPermissions[0] !== -1);
    }

    /**
     * Returns the category permissions for users or groups.
     *
     * @param string $mode 'group' or 'user'
     * @param int[]  $categories Array of category ids
     * @return int[] Array of user or group IDs that have permissions for the given categories
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

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $permissions[] = (int)$row->permission;
        }

        return $permissions;
    }

    /**
     * Returns all category permissions for users and groups.
     *
     * @param int[] $categories Array of category ids
     * @return array Array of permissions with user and group IDs for each category
     */
    public function getAll(array $categories): array
    {
        $permissions = [];

        foreach ($categories as $category) {
            $permissions[$category] = [
                self::USER => [],
                self::GROUP => [],
            ];
        }

        $query = sprintf(
            'SELECT category_id, user_id AS permission FROM %sfaqcategory_user WHERE category_id IN (%s)',
            Database::getTablePrefix(),
            implode(', ', $categories)
        );

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $permissions[$row->category_id][self::USER][] = (int)$row->permission;
        }

        $query = sprintf(
            'SELECT category_id, group_id AS permission FROM %sfaqcategory_group WHERE category_id IN (%s)',
            Database::getTablePrefix(),
            implode(', ', $categories)
        );

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $permissions[$row->category_id][self::GROUP][] = (int)$row->permission;
        }

        return $permissions;
    }
}
