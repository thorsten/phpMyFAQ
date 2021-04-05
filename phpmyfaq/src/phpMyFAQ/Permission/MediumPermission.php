<?php

/**
 * The medium permission class provides group rights.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\User\CurrentUser;

/**
 * Class MediumPermission
 *
 * @package phpMyFAQ\Permission
 */
class MediumPermission extends BasicPermission
{
    /**
     * Default data for new groups.
     *
     * @var array<string, string|bool>
     */
    public $defaultGroupData = [
        'name' => 'DEFAULT_GROUP',
        'description' => 'Short group description.',
        'auto_join' => false,
    ];

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * Returns an array that contains the right-IDs of all
     * group-rights the group $groupId owns.
     *
     * @param int $groupId Group ID
     *
     * @return array<int>
     */
    public function getGroupRights(int $groupId): array
    {
        if ($groupId <= 0 || !is_numeric($groupId)) {
            return [];
        }
        // check right
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
            $groupId
        );

        $res = $this->config->getDb()->query($select);
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['right_id'];
        }

        return $result;
    }

    /**
     * Returns true, if the user given by $userId owns the right
     * specified by $right. It does not matter if the user owns this
     * right as a user-right or because of a group-membership.
     * The parameter $right may be a right-ID (recommended for
     * performance) or a right-name.
     *
     * @param int   $userId Group ID
     * @param mixed $right  Rights
     *
     * @return bool
     */
    public function hasPermission(int $userId, $right): bool
    {
        $user = new CurrentUser($this->config);
        $user->getUserById($userId);

        if ($user->isSuperAdmin()) {
            return true;
        }

        // get right id
        if (!is_numeric($right) && is_string($right)) {
            $right = $this->getRightId($right);
        }

        // check user right and group right
        if (
            $this->checkUserGroupRight($userId, $right)
            || $this->checkUserRight($userId, $right)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the user $userId owns the right $rightId
     * because of a group-membership, otherwise false.
     *
     * @param int $userId  User ID
     * @param int $rightId Right ID
     *
     * @return bool
     */
    public function checkUserGroupRight(int $userId, int $rightId): bool
    {
        // check input
        if ($rightId <= 0 || $userId <= 0 || !is_numeric($rightId) || !is_numeric($userId)) {
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
            $userId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) == 1) {
            return true;
        }

        return false;
    }

    /**
     * Grants the group given by $groupId the right specified by
     * $rightId.
     *
     * @param int $groupId Group ID
     * @param int $rightId Right ID
     *
     * @return bool
     */
    public function grantGroupRight(int $groupId, int $rightId): bool
    {
        // check input
        if ($rightId <= 0 || $groupId <= 0 || !is_numeric($rightId) || !is_numeric($groupId)) {
            return false;
        }

        // is right for users?
        $right_data = $this->getRightData($rightId);
        if (!$right_data['for_groups']) {
            return false;
        }

        // grant right
        $insert = sprintf(
            '
            INSERT INTO
                %sfaqgroup_right
            (group_id, right_id)
                VALUES
            (%d, %d)',
            Database::getTablePrefix(),
            $groupId,
            $rightId
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Adds a new group to the database and returns the ID of the
     * new group. The associative array $groupData contains the
     * data for the new group.
     *
     * @param array<string> $groupData Array of group data
     *
     * @return int
     */
    public function addGroup(array $groupData): int
    {
        // check if group already exists
        if ($this->getGroupId($groupData['name']) > 0) {
            return 0;
        }

        $nextId = $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqgroup', 'group_id');
        $groupData = $this->checkGroupData($groupData);
        $insert = sprintf(
            "
            INSERT INTO
                %sfaqgroup
            (group_id, name, description, auto_join)
                VALUES
            (%d, '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $nextId,
            $groupData['name'],
            $groupData['description'],
            (int)$groupData['auto_join']
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return 0;
        }

        return $nextId;
    }

    /**
     * Returns the ID of the group that has the name $name. Returns
     * 0 if the group-name cannot be found.
     *
     * @param string $name Group name
     *
     * @return int
     */
    public function getGroupId(string $name): int
    {
        $select = sprintf(
            "
            SELECT
                group_id
            FROM
                %sfaqgroup
            WHERE
                name = '%s'",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($name)
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return 0;
        }
        $row = $this->config->getDb()->fetchArray($res);

        return $row['group_id'];
    }

    /**
     * Checks the given associative array $groupData. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->defaultGroupData.
     * Returns the corrected $groupData associative array.
     *
     * @param array<string> $groupData Array of group data
     *
     * @return array<string|int>
     */
    public function checkGroupData(array $groupData): array
    {
        if (!isset($groupData['name']) || !is_string($groupData['name'])) {
            $groupData['name'] = $this->defaultGroupData['name'];
        }
        if (!isset($groupData['description']) || !is_string($groupData['description'])) {
            $groupData['description'] = $this->defaultGroupData['description'];
        }
        if (!isset($groupData['auto_join'])) {
            $groupData['auto_join'] = $this->defaultGroupData['auto_join'];
        }
        $groupData['auto_join'] = (int)$groupData['auto_join'];

        return $groupData;
    }

    /**
     * Changes the group data of the given group.
     *
     * @param int $groupId Group ID
     * @param array<string> $groupData Array of group data
     *
     * @return bool
     */
    public function changeGroup(int $groupId, array $groupData): bool
    {
        $checkedData = $this->checkGroupData($groupData);
        $set = '';
        $comma = '';

        foreach ($groupData as $key => $val) {
            $set .= $comma . $key . " = '" . $this->config->getDb()->escape($checkedData[$key]) . "'";
            $comma = ",\n                ";
        }

        $update = sprintf(
            '
            UPDATE
                %sfaqgroup
            SET
                %s
            WHERE
                group_id = %d',
            Database::getTablePrefix(),
            $set,
            $groupId
        );

        $res = $this->config->getDb()->query($update);

        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Removes the group given by $groupId from the database.
     * Returns true on success, otherwise false.
     *
     * @param int $groupId Group ID
     *
     * @return bool
     */
    public function deleteGroup(int $groupId): bool
    {
        if ($groupId <= 0 || !is_numeric($groupId)) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaqgroup
            WHERE
                group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaquser_group
            WHERE
                group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaqgroup_right
            WHERE
                group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array that contains the user-IDs of all members
     * of the group $groupId.
     *
     * @param int $groupId Group ID
     *
     * @return array<int>
     */
    public function getGroupMembers(int $groupId): array
    {
        if ($groupId <= 0 || !is_numeric($groupId)) {
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
            $groupId
        );

        $res = $this->config->getDb()->query($select);
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['user_id'];
        }

        return $result;
    }

    /**
     * Returns an array that contains the IDs of all groups in which
     * the user $userId is a member.
     *
     * @param int $userId User ID
     *
     * @return array<int>
     */
    public function getUserGroups(int $userId): array
    {
        if ($userId <= 0 || !is_numeric($userId)) {
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
            $userId
        );

        $res = $this->config->getDb()->query($select);
        $result = [-1];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['group_id'];
        }

        return $result;
    }

    /**
     * Get all groups in <option> tags.
     *
     * @param array<int> $groups Selected groups
     * @param CurrentUser $user
     * @return string
     * @todo   Move into the Helper class
     */
    public function getAllGroupsOptions(array $groups, CurrentUser $user): string
    {
        $options = '';
        $allGroups = $this->getAllGroups($user);

        foreach ($allGroups as $groupId) {
            if (-1 != $groupId) {
                $options .= sprintf(
                    '<option value="%d" %s>%s</option>',
                    $groupId,
                    (in_array($groupId, $groups) ? 'selected' : ''),
                    $this->getGroupName($groupId)
                );
            }
        }

        return $options;
    }

    /**
     * Returns an array with the IDs of all groups stored in the
     * database if no user is passed.
     *
     * @param CurrentUser $user
     * @return array<int>
     */
    public function getAllGroups(CurrentUser $user): array
    {
        $select = sprintf('SELECT group_id FROM %sfaqgroup', Database::getTablePrefix());

        if (
            !$this->config->get('main.enableCategoryRestrictions') &&
            $user->getUserId() !== 1 &&
            !$user->isSuperAdmin()
        ) {
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
                $user->getUserId()
            );
        }

        $res = $this->config->getDb()->query($select);
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['group_id'];
        }

        return $result;
    }

    /**
     * Returns the name of the group $groupId.
     *
     * @param int $groupId Group ID
     *
     * @return string
     */
    public function getGroupName(int $groupId): string
    {
        if ($groupId <= 0 || !is_numeric($groupId)) {
            return '-';
        }

        $select = sprintf(
            '
            SELECT
                name
            FROM
                %sfaqgroup
            WHERE
                group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return '-';
        }
        $row = $this->config->getDb()->fetchArray($res);

        return $row['name'];
    }

    /**
     * Returns an array that contains the right-IDs of all rights
     * the user $userId owns. User-rights and the rights the user
     * owns because of a group-membership are taken into account.
     *
     * @param int $userId User ID
     *
     * @return array<int>
     */
    public function getAllUserRights(int $userId): array
    {
        if ($userId <= 0 || !is_numeric($userId)) {
            return [];
        }
        $userRights = $this->getUserRights($userId);
        $groupRights = $this->getUserGroupRights($userId);

        return array_unique(array_merge($userRights, $groupRights));
    }

    /**
     * Returns an array that contains the IDs of all rights the user
     * $userId owns because of a group-membership.
     *
     * @param int $userId User ID
     *
     * @return array<int>
     */
    public function getUserGroupRights(int $userId): array
    {
        if ($userId <= 0 || !is_numeric($userId)) {
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
            $userId
        );

        $res = $this->config->getDb()->query($select);
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['right_id'];
        }

        return $result;
    }

    /**
     * Adds the user $userId to all groups with the auto_join
     * option. By using the auto_join option, user administration
     * can be much easier. For example by setting this option only
     * for a single group called 'All Users'. The autoJoin() method
     * then has to be called every time a new user registers.
     * Returns true on success, otherwise false.
     *
     * @param int $userId User ID
     *
     * @return bool
     */
    public function autoJoin(int $userId): bool
    {
        if ($userId <= 0 || !is_numeric($userId)) {
            return false;
        }

        $select = sprintf(
            '
            SELECT
                group_id
            FROM
                %sfaqgroup
            WHERE
                auto_join = 1',
            Database::getTablePrefix()
        );

        $res = $this->config->getDb()->query($select);
        if (!$res) {
            return false;
        }

        $auto_join = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $auto_join[] = $row['group_id'];
        }

        // add to groups
        foreach ($auto_join as $groupId) {
            $this->addToGroup($userId, $groupId);
        }

        return true;
    }

    /**
     * Adds a new member $userId to the group $groupId.
     * Returns true on success, otherwise false.
     *
     * @param int $userId  User ID
     * @param int $groupId Group ID
     *
     * @return bool
     */
    public function addToGroup(int $userId, int $groupId): bool
    {
        if ($userId <= 0 || $groupId <= 0 || !is_numeric($userId) || !is_numeric($groupId)) {
            return false;
        }

        if (!$this->getGroupData($groupId)) {
            return false;
        }

        // add user to group
        $insert = sprintf(
            '
            INSERT INTO
                %sfaquser_group
            (user_id, group_id)
               VALUES
            (%d, %d)',
            Database::getTablePrefix(),
            $userId,
            $groupId
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Returns an associative array with the group-data of the group
     * $groupId.
     *
     * @param int $groupId Group ID
     *
     * @return array<int, string>
     */
    public function getGroupData(int $groupId): array
    {
        if ($groupId <= 0 || !is_numeric($groupId)) {
            return [];
        }

        $select = sprintf(
            '
            SELECT
                group_id,
                name,
                description,
                auto_join
            FROM
                %sfaqgroup
            WHERE
                group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return [];
        }

        return $this->config->getDb()->fetchArray($res);
    }

    /**
     * Removes the user $userId from all groups.
     * Returns true on success, otherwise false.
     *
     * @param int $userId User ID
     *
     * @return bool
     */
    public function removeFromAllGroups(int $userId): bool
    {
        if ($userId <= 0 || !is_numeric($userId)) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaquser_group
            WHERE
                user_id  = %d',
            Database::getTablePrefix(),
            $userId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Refuses all group rights.
     * Returns true on success, otherwise false.
     *
     * @param int $groupId Group ID
     *
     * @return bool
     */
    public function refuseAllGroupRights(int $groupId): bool
    {
        if ($groupId <= 0 || !is_numeric($groupId)) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaqgroup_right
            WHERE
                group_id  = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Removes all users from the group $groupId.
     * Returns true on success, otherwise false.
     *
     * @param int $groupId Group ID
     *
     * @return bool
     */
    public function removeAllUsersFromGroup(int $groupId): bool
    {
        if ($groupId <= 0 or !is_numeric($groupId)) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaquser_group WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }
}
