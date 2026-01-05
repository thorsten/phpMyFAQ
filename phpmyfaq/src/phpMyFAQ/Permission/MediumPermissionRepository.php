<?php

/**
 * MediumPermission Repository.
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
 * @since     2025-01-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

readonly class MediumPermissionRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Returns an array that contains the right-IDs of all
     * group-rights the group $groupId owns.
     *
     * @param int $groupId Group ID
     * @return array<int>
     */
    public function getGroupRights(int $groupId): array
    {
        if ($groupId <= 0 || !is_numeric($groupId)) {
            return [];
        }

        $select = sprintf(
            '
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaqgroup_right fgr,
                %sfaqgroup fg
            WHERE
                fg.group_id = %d AND
                fg.group_id = fgr.group_id AND
                fr.right_id = fgr.right_id',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $groupId,
        );

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = (int) $row['right_id'];
        }

        return $result;
    }

    /**
     * Returns true if the user $userId owns the right $rightId
     * because of a group-membership, otherwise false.
     *
     * @param int $userId  User ID
     * @param int $rightId Right ID
     */
    public function checkUserGroupRight(int $userId, int $rightId): bool
    {
        if ($rightId <= 0 || $userId <= 0) {
            return false;
        }

        $select = sprintf(
            '
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaqgroup_right fgr,
                %sfaqgroup fg,
                %sfaquser_group fug,
                %sfaquser fu
            WHERE
                fr.right_id = %d AND
                fr.right_id = fgr.right_id AND
                fg.group_id = fgr.group_id AND
                fg.group_id = fug.group_id AND
                fu.user_id  = fug.user_id AND
                fu.user_id  = %d',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $rightId,
            $userId,
        );

        $res = $this->configuration->getDb()->query($select);
        return $this->configuration->getDb()->numRows($res) !== 0;
    }

    /**
     * Grants the group given by $groupId the right specified by $rightId.
     *
     * @param int $groupId Group ID
     * @param int $rightId Right ID
     */
    public function grantGroupRight(int $groupId, int $rightId): bool
    {
        if ($rightId <= 0 || $groupId <= 0) {
            return false;
        }

        $insert = sprintf(
            'INSERT INTO %sfaqgroup_right (group_id, right_id) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $groupId,
            $rightId,
        );

        return (bool) $this->configuration->getDb()->query($insert);
    }

    /**
     * Adds a new group to the database and returns the ID of the new group.
     *
     * @param array<string, string|int> $groupData Array of group data
     * @param int $nextId Next group ID
     */
    public function addGroup(array $groupData, int $nextId): bool
    {
        $insert = sprintf(
            "INSERT INTO %sfaqgroup (group_id, name, description, auto_join) VALUES (%d, '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $nextId,
            $this->configuration->getDb()->escape((string) $groupData['name']),
            $this->configuration->getDb()->escape((string) $groupData['description']),
            (int) $groupData['auto_join'],
        );

        return (bool) $this->configuration->getDb()->query($insert);
    }

    /**
     * Returns the ID of the group that has the name $name.
     *
     * @param string $name Group name
     */
    public function getGroupId(string $name): int
    {
        $select = sprintf(
            "SELECT group_id FROM %sfaqgroup WHERE name = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($name),
        );

        $res = $this->configuration->getDb()->query($select);
        if ($this->configuration->getDb()->numRows($res) !== 1) {
            return 0;
        }

        $row = $this->configuration->getDb()->fetchArray($res);

        return (int) $row['group_id'];
    }

    /**
     * Changes the group data of the given group.
     *
     * @param int $groupId Group ID
     * @param array<string, string|int> $groupData Array of group data
     */
    public function changeGroup(int $groupId, array $groupData): bool
    {
        $set = '';
        $comma = '';

        foreach (array_keys($groupData) as $key) {
            $set .= $comma . $key . " = '" . $this->configuration->getDb()->escape((string) $groupData[$key]) . "'";
            $comma = ",\n                ";
        }

        $update = sprintf('
            UPDATE
                %sfaqgroup
            SET
                %s
            WHERE
                group_id = %d', Database::getTablePrefix(), $set, $groupId);

        return (bool) $this->configuration->getDb()->query($update);
    }

    /**
     * Removes the group given by $groupId from the database.
     *
     * @param int $groupId Group ID
     */
    public function deleteGroup(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $delete = sprintf('DELETE FROM %sfaqgroup WHERE group_id = %d', Database::getTablePrefix(), $groupId);

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Deletes all group memberships for the given group.
     *
     * @param int $groupId Group ID
     */
    public function deleteGroupMemberships(int $groupId): bool
    {
        $delete = sprintf('DELETE FROM %sfaquser_group WHERE group_id = %d', Database::getTablePrefix(), $groupId);

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Deletes all group rights for the given group.
     *
     * @param int $groupId Group ID
     */
    public function deleteGroupRights(int $groupId): bool
    {
        $delete = sprintf('DELETE FROM %sfaqgroup_right WHERE group_id = %d', Database::getTablePrefix(), $groupId);

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Returns an array that contains the user-IDs of all members of the group $groupId.
     *
     * @param int $groupId Group ID
     * @return array<int>
     */
    public function getGroupMembers(int $groupId): array
    {
        if ($groupId <= 0) {
            return [];
        }

        $select = sprintf(
            '
            SELECT
                fu.user_id AS user_id
            FROM
                %sfaquser fu,
                %sfaquser_group fug,
                %sfaqgroup fg
            WHERE
                fg.group_id = %d AND
                fg.group_id = fug.group_id AND
                fu.user_id  = fug.user_id',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $groupId,
        );

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = (int) $row['user_id'];
        }

        return $result;
    }

    /**
     * Returns an array that contains the IDs of all groups in which
     * the user $userId is a member.
     *
     * @param int $userId User ID
     * @return array<int>
     */
    public function getUserGroups(int $userId): array
    {
        if ($userId <= 0) {
            return [-1];
        }

        $select = sprintf(
            '
            SELECT
                fg.group_id AS group_id
            FROM
                %sfaquser fu,
                %sfaquser_group fug,
                %sfaqgroup fg
            WHERE
                fu.user_id  = %d AND
                fu.user_id  = fug.user_id AND
                fg.group_id = fug.group_id',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $userId,
        );

        $res = $this->configuration->getDb()->query($select);
        $result = [-1];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = (int) $row['group_id'];
        }

        return $result;
    }

    /**
     * Returns an array with the IDs of all groups stored in the database.
     *
     * @param int|null $userId Optional user ID to filter groups by user membership
     * @return array<int>
     */
    public function getAllGroups(?int $userId = null): array
    {
        if ($userId !== null) {
            $select = sprintf(
                '
                SELECT
                    fg.group_id
                FROM
                    %sfaqgroup fg
                LEFT JOIN
                    %sfaquser_group fug ON fg.group_id = fug.group_id
                WHERE
                    fug.user_id = %d',
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                $userId,
            );
        } else {
            $select = sprintf('SELECT group_id FROM %sfaqgroup', Database::getTablePrefix());
        }

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = (int) $row['group_id'];
        }

        return $result;
    }

    /**
     * Returns the name of the group $groupId.
     *
     * @param int $groupId Group ID
     */
    public function getGroupName(int $groupId): string
    {
        if ($groupId <= 0 || !is_numeric($groupId)) {
            return '-';
        }

        $select = sprintf('
            SELECT
                name
            FROM
                %sfaqgroup
            WHERE
                group_id = %d', Database::getTablePrefix(), $groupId);

        $res = $this->configuration->getDb()->query($select);
        if ($this->configuration->getDb()->numRows($res) !== 1) {
            return '-';
        }

        $row = $this->configuration->getDb()->fetchArray($res);

        return (string) $row['name'];
    }

    /**
     * Returns an array that contains the IDs of all rights the user
     * $userId owns because of a group-membership.
     *
     * @param int $userId User ID
     * @return array<int>
     */
    public function getUserGroupRights(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $select = sprintf(
            '
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaqgroup_right fgr,
                %sfaqgroup fg,
                %sfaquser_group fug,
                %sfaquser fu
            WHERE
                fu.user_id  = %d AND
                fu.user_id  = fug.user_id AND
                fg.group_id = fug.group_id AND
                fg.group_id = fgr.group_id AND
                fr.right_id = fgr.right_id',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
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
     * Returns groups with auto_join option enabled.
     *
     * @return array<int>
     */
    public function getAutoJoinGroups(): array
    {
        $select = sprintf('SELECT group_id FROM %sfaqgroup WHERE auto_join = 1', Database::getTablePrefix());

        $res = $this->configuration->getDb()->query($select);
        if (!$res) {
            return [];
        }

        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = (int) $row['group_id'];
        }

        return $result;
    }

    /**
     * Adds a new member $userId to the group $groupId.
     *
     * @param int $userId  User ID
     * @param int $groupId Group ID
     */
    public function addToGroup(int $userId, int $groupId): bool
    {
        if ($userId <= 0 || $groupId <= 0) {
            return false;
        }

        $insert = sprintf(
            'INSERT INTO %sfaquser_group (user_id, group_id) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $userId,
            $groupId,
        );

        return (bool) $this->configuration->getDb()->query($insert);
    }

    /**
     * Returns an associative array with the group-data of the group $groupId.
     *
     * @param int $groupId Group ID
     * @return array<string, mixed>
     */
    public function getGroupData(int $groupId): array
    {
        if ($groupId <= 0) {
            return [];
        }

        $select = sprintf(
            'SELECT group_id, name, description, auto_join FROM %sfaqgroup WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId,
        );

        $res = $this->configuration->getDb()->query($select);
        if ($this->configuration->getDb()->numRows($res) !== 1) {
            return [];
        }

        return $this->configuration->getDb()->fetchArray($res);
    }

    /**
     * Removes the user $userId from all groups.
     *
     * @param int $userId User ID
     */
    public function removeFromAllGroups(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $delete = sprintf('DELETE FROM %sfaquser_group WHERE user_id  = %d', Database::getTablePrefix(), $userId);

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Refuses all group rights.
     *
     * @param int $groupId Group ID
     */
    public function refuseAllGroupRights(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $delete = sprintf('DELETE FROM %sfaqgroup_right WHERE group_id  = %d', Database::getTablePrefix(), $groupId);

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Removes all users from the group $groupId.
     *
     * @param int $groupId Group ID
     */
    public function removeAllUsersFromGroup(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $delete = sprintf('DELETE FROM %sfaquser_group WHERE group_id = %d', Database::getTablePrefix(), $groupId);

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Generates the next ID for the faqgroup table.
     */
    public function nextGroupId(): int
    {
        return $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqgroup', 'group_id');
    }
}
