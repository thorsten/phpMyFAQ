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

        $db = $this->configuration->getDb();

        $db->query($this->beginTransactionStatement());

        // Remove existing restrictions
        $delete = sprintf(
            'DELETE FROM %sfaqgroup_right_category WHERE group_id = %d AND right_id = %d',
            Database::getTablePrefix(),
            $groupId,
            $rightId,
        );

        if (!$db->query($delete)) {
            $db->query('ROLLBACK');
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

            if (!$db->query($insert)) {
                $db->query('ROLLBACK');
                return false;
            }
        }

        $db->query('COMMIT');

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
     * Returns the statement that opens a transaction on the current driver.
     * SQL Server treats a bare `BEGIN` as a statement block, not a transaction,
     * so the DELETE/INSERT pair would run unscoped there.
     */
    private function beginTransactionStatement(): string
    {
        return match (Database::getType()) {
            'sqlsrv', 'pdo_sqlsrv' => 'BEGIN TRANSACTION',
            default => 'BEGIN',
        };
    }
}
