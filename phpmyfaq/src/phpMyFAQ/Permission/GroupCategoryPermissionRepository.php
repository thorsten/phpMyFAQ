<?php

/**
 * Repository for group-level category permission restrictions.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-03-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

readonly class GroupCategoryPermissionRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Returns the category IDs that a group's right is restricted to.
     * An empty array means the right is unrestricted (applies globally).
     *
     * @param int $groupId Group ID
     * @param int $rightId Right ID
     * @return array<int>
     */
    public function getCategoryRestrictions(int $groupId, int $rightId): array
    {
        if ($groupId <= 0 || $rightId <= 0) {
            return [];
        }

        $select = sprintf(
            'SELECT category_id FROM %sfaqgroup_right_category WHERE group_id = %d AND right_id = %d',
            Database::getTablePrefix(),
            $groupId,
            $rightId,
        );

        $res = $this->configuration->getDb()->query($select);
        if (!$res) {
            return [];
        }

        $result = [];
        while (true) {
            $row = $this->configuration->getDb()->fetchArray($res);
            if ($row === false || $row === null || $row === []) {
                break;
            }

            $result[] = (int) $row['category_id'];
        }

        return $result;
    }

    /**
     * Returns all category restrictions for a group, keyed by right ID.
     *
     * @param int $groupId Group ID
     * @return array<int, array<int>> Map of right_id => [category_ids]
     */
    public function getAllCategoryRestrictions(int $groupId): array
    {
        if ($groupId <= 0) {
            return [];
        }

        $select = sprintf(
            'SELECT right_id, category_id FROM %sfaqgroup_right_category WHERE group_id = %d ORDER BY right_id',
            Database::getTablePrefix(),
            $groupId,
        );

        $res = $this->configuration->getDb()->query($select);
        if (!$res) {
            return [];
        }

        $result = [];
        while (true) {
            $row = $this->configuration->getDb()->fetchArray($res);
            if ($row === false || $row === null || $row === []) {
                break;
            }

            $rightId = (int) $row['right_id'];
            $result[$rightId][] = (int) $row['category_id'];
        }

        return $result;
    }

    /**
     * Sets category restrictions for a group's right.
     * Replaces any existing restrictions for this group-right pair.
     *
     * @param int $groupId Group ID
     * @param int $rightId Right ID
     * @param array<int> $categoryIds Category IDs to restrict to
     */
    public function setCategoryRestrictions(int $groupId, int $rightId, array $categoryIds): bool
    {
        if ($groupId <= 0 || $rightId <= 0) {
            return false;
        }

        // Remove existing restrictions
        if (!$this->deleteCategoryRestrictions($groupId, $rightId)) {
            return false;
        }

        // Insert new restrictions
        foreach ($categoryIds as $categoryId) {
            $categoryId = (int) $categoryId;
            if ($categoryId <= 0) {
                continue;
            }

            $insert = sprintf(
                'INSERT INTO %sfaqgroup_right_category (group_id, right_id, category_id) VALUES (%d, %d, %d)',
                Database::getTablePrefix(),
                $groupId,
                $rightId,
                $categoryId,
            );

            if (!$this->configuration->getDb()->query($insert)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes all category restrictions for a specific group-right pair.
     *
     * @param int $groupId Group ID
     * @param int $rightId Right ID
     */
    public function deleteCategoryRestrictions(int $groupId, int $rightId): bool
    {
        if ($groupId <= 0 || $rightId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaqgroup_right_category WHERE group_id = %d AND right_id = %d',
            Database::getTablePrefix(),
            $groupId,
            $rightId,
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Deletes all category restrictions for a group.
     *
     * @param int $groupId Group ID
     */
    public function deleteAllForGroup(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaqgroup_right_category WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId,
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Checks if a user has a specific right for a given category via group membership.
     * Returns true if:
     * - The user's group has the right with no category restrictions (global), OR
     * - The user's group has the right restricted to categories that include the given category.
     *
     * @param int $userId User ID
     * @param int $rightId Right ID
     * @param int $categoryId Category ID
     */
    public function checkUserGroupRightForCategory(int $userId, int $rightId, int $categoryId): bool
    {
        if ($userId <= 0 || $rightId <= 0 || $categoryId <= 0) {
            return false;
        }

        // Check if user has the right via any group that either:
        // 1. Has no category restrictions for this right (global), OR
        // 2. Has the specific category in its restrictions
        $select = sprintf(
            '
            SELECT
                fgr.group_id
            FROM
                %sfaqgroup_right fgr
            INNER JOIN
                %sfaquser_group fug ON fgr.group_id = fug.group_id
            WHERE
                fug.user_id = %d AND
                fgr.right_id = %d AND
                (
                    NOT EXISTS (
                        SELECT 1 FROM %sfaqgroup_right_category fgrc
                        WHERE fgrc.group_id = fgr.group_id AND fgrc.right_id = fgr.right_id
                    )
                    OR EXISTS (
                        SELECT 1 FROM %sfaqgroup_right_category fgrc
                        WHERE fgrc.group_id = fgr.group_id
                          AND fgrc.right_id = fgr.right_id
                          AND fgrc.category_id = %d
                    )
                )',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $userId,
            $rightId,
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $categoryId,
        );

        $res = $this->configuration->getDb()->query($select);

        return $this->configuration->getDb()->numRows($res) > 0;
    }
}
