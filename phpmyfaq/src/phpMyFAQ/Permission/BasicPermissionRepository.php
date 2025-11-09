<?php

/**
 * BasicPermission Repository.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

readonly class BasicPermissionRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Grants a user right by inserting into the faquser_right table.
     *
     * @param int $userId  User ID
     * @param int $rightId Right ID
     */
    public function grantUserRight(int $userId, int $rightId): bool
    {
        $insert = sprintf(
            'INSERT INTO %sfaquser_right (user_id, right_id) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $userId,
            $rightId,
        );

        return (bool) $this->configuration->getDb()->query($insert);
    }

    /**
     * Returns an associative array with all data stored in the
     * database for the specified right.
     *
     * @param int $rightId Right ID
     * @return array<string, bool|int|string>
     */
    public function getRightData(int $rightId): array
    {
        $select = sprintf('
            SELECT
                right_id,
                name,
                description,
                for_users,
                for_groups,
                for_sections
            FROM
                %sfaqright
            WHERE
                right_id = %d', Database::getTablePrefix(), $rightId);

        $res = $this->configuration->getDb()->query($select);
        if ($this->configuration->getDb()->numRows($res) != 1) {
            return [];
        }

        $rightData = $this->configuration->getDb()->fetchArray($res);
        $rightData['for_users'] = (bool) $rightData['for_users'];
        $rightData['for_groups'] = (bool) $rightData['for_groups'];
        $rightData['for_sections'] = (bool) $rightData['for_sections'];

        return $rightData;
    }

    /**
     * Returns the right-ID of the right with the name $name.
     *
     * @param string $name Right name
     */
    public function getRightId(string $name): int
    {
        $select = sprintf(
            "
            SELECT
                right_id
            FROM
                %sfaqright
            WHERE
                name = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($name),
        );

        $res = $this->configuration->getDb()->query($select);
        if ($this->configuration->getDb()->numRows($res) != 1) {
            return 0;
        }

        $row = $this->configuration->getDb()->fetchArray($res);

        return (int) $row['right_id'];
    }

    /**
     * Returns true if the user given by user_id has the right
     * specified by right_id, otherwise false.
     *
     * @param int $userId  User ID
     * @param int $rightId Right ID
     */
    public function checkUserRight(int $userId, int $rightId): bool
    {
        if ($rightId <= 0) {
            return false;
        }

        $select = sprintf(
            '
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaquser_right fur,
                %sfaquser fu
            WHERE
                fr.right_id = %d AND
                fr.right_id = fur.right_id AND
                fu.user_id  = %d AND
                fu.user_id  = fur.user_id',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $rightId,
            $userId,
        );

        $res = $this->configuration->getDb()->query($select);

        return $this->configuration->getDb()->numRows($res) === 1;
    }

    /**
     * Returns an array with the IDs of all user-rights the user
     * specified by user_id owns. Group rights are not taken into
     * account.
     *
     * @param int $userId User ID
     * @return array<int>
     */
    public function getUserRights(int $userId): array
    {
        $select = sprintf(
            '
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaquser_right fur,
                %sfaquser fu
            WHERE
                fr.right_id = fur.right_id AND
                fu.user_id  = %d AND
                fu.user_id  = fur.user_id',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $userId,
        );

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = (int) $row['right_id'];
        }

        return $result;
    }

    /**
     * Adds a new right into the database. Returns the ID of the new right.
     *
     * @param array<string, string|int> $rightData Array of rights
     */
    public function addRight(array $rightData, int $nextId): bool
    {
        $insert = sprintf(
            "
            INSERT INTO
                %sfaqright
            (right_id, name, description, for_users, for_groups, for_sections)
                VALUES
            (%d, '%s', '%s', %d, %d, %d)",
            Database::getTablePrefix(),
            $nextId,
            $rightData['name'],
            $rightData['description'],
            $rightData['for_users'] ?? 1,
            $rightData['for_groups'] ?? 1,
            $rightData['for_sections'] ?? 1,
        );

        return (bool) $this->configuration->getDb()->query($insert);
    }

    /**
     * Renames a right, only used for updates.
     *
     * @param int    $rightId Right ID
     * @param string $newName New name
     */
    public function renameRight(int $rightId, string $newName): bool
    {
        $update = sprintf('
            UPDATE
                %sfaqright
            SET
                name = \'%s\'
            WHERE
                right_id = %d', Database::getTablePrefix(), $newName, $rightId);

        return (bool) $this->configuration->getDb()->query($update);
    }

    /**
     * Returns an array that contains all rights stored in the database.
     *
     * @param string $order Ordering (ASC or DESC)
     * @return array<int, array<string, mixed>>
     */
    public function getAllRightsData(string $order = 'ASC'): array
    {
        $select = sprintf('
            SELECT
                right_id,
                name,
                description,
                for_users,
                for_groups,
                for_sections
            FROM
                %sfaqright
            ORDER BY
                right_id %s', Database::getTablePrefix(), $order);

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        $i = 0;

        if ($res) {
            while ($row = $this->configuration->getDb()->fetchArray($res)) {
                $result[$i] = $row;
                ++$i;
            }
        }

        return $result;
    }

    /**
     * Refuses all user rights.
     *
     * @param int $userId User ID
     */
    public function refuseAllUserRights(int $userId): bool
    {
        $delete = sprintf('
            DELETE FROM
                %sfaquser_right
            WHERE
                user_id  = %d', Database::getTablePrefix(), $userId);

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Generates the next ID for the faqright table.
     */
    public function nextRightId(): int
    {
        return $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqright', 'right_id');
    }
}
