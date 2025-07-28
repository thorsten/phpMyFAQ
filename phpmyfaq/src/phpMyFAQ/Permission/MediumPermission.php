<?php

/**
 * The medium permission class provides group rights.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\User\CurrentUser;

/**
 * Class MediumPermission
 *
 * @package phpMyFAQ\Permission
 */
class MediumPermission extends BasicPermission implements PermissionInterface
{
    public function __construct(protected Configuration $configuration)
    {
        parent::__construct($configuration);
    }

    /**
     * Default data for new groups.
     *
     * @var array<string, string|bool>
     */
    public array $defaultGroupData = [
        'name' => 'DEFAULT_GROUP',
        'description' => 'Short group description.',
        'auto_join' => false,
    ];

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

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
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
     * @throws Exception
     */
    #[\Override]
    public function hasPermission(int $userId, mixed $right): bool
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->getUserById($userId);

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        // get right id
        if (!is_numeric($right) && is_string($right)) {
            $right = $this->getRightId($right);
        }

        if ($right instanceof PermissionType) {
            $right = $this->getRightId($right->value);
        }

        // check user right and group right
        if ($this->checkUserGroupRight($userId, $right)) {
            return true;
        }

        return $this->checkUserRight($userId, $right);
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
        // check input
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
            $userId
        );

        $res = $this->configuration->getDb()->query($select);
        return $this->configuration->getDb()->numRows($res) !== 0;
    }

    /**
     * Grants the group given by $groupId the right specified by
     * $rightId.
     *
     * @param int $groupId Group ID
     * @param int $rightId Right ID
     */
    public function grantGroupRight(int $groupId, int $rightId): bool
    {
        // check input
        if ($rightId <= 0 || $groupId <= 0) {
            return false;
        }

        // is right for users?
        $right_data = $this->getRightData($rightId);
        if (!$right_data['for_groups']) {
            return false;
        }

        // grant right
        $insert = sprintf(
            'INSERT INTO %sfaqgroup_right (group_id, right_id) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $groupId,
            $rightId
        );

        return (bool) $this->configuration->getDb()->query($insert);
    }

    /**
     * Adds a new group to the database and returns the ID of the
     * new group. The associative array $groupData contains the
     * data for the new group.
     *
     * @param array<string> $groupData Array of group data
     */
    public function addGroup(array $groupData): int
    {
        // check if a group already exists
        if ($this->getGroupId($groupData['name']) > 0) {
            return 0;
        }

        $nextId = $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqgroup', 'group_id');
        $groupData = $this->checkGroupData($groupData);
        $insert = sprintf(
            "INSERT INTO %sfaqgroup (group_id, name, description, auto_join) VALUES (%d, '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $nextId,
            $this->configuration->getDb()->escape($groupData['name']),
            $this->configuration->getDb()->escape($groupData['description']),
            (int)$groupData['auto_join']
        );

        $res = $this->configuration->getDb()->query($insert);
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
     */
    public function getGroupId(string $name): int
    {
        $select = sprintf(
            "SELECT group_id FROM %sfaqgroup WHERE name = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($name)
        );

        $res = $this->configuration->getDb()->query($select);
        if ($this->configuration->getDb()->numRows($res) !== 1) {
            return 0;
        }

        $row = $this->configuration->getDb()->fetchArray($res);

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
     */
    public function changeGroup(int $groupId, array $groupData): bool
    {
        $checkedData = $this->checkGroupData($groupData);
        $set = '';
        $comma = '';

        foreach (array_keys($groupData) as $key) {
            $set .= $comma . $key . " = '" . $this->configuration->getDb()->escape($checkedData[$key]) . "'";
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

        return (bool) $this->configuration->getDb()->query($update);
    }

    /**
     * Removes the group given by $groupId from the database.
     * Returns true if successful, otherwise false.
     *
     * @param int $groupId Group ID
     */
    public function deleteGroup(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaqgroup WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->configuration->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaquser_group WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->configuration->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaqgroup_right WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        return (bool) $this->configuration->getDb()->query($delete);
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
            $groupId
        );

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
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
            $userId
        );

        $res = $this->configuration->getDb()->query($select);
        $result = [-1];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = $row['group_id'];
        }

        return $result;
    }

    /**
     * Get all groups in <option> tags.
     *
     * @param array<int> $groups Selected groups
     * @todo   Move into the Helper class
     */
    public function getAllGroupsOptions(array $groups, CurrentUser $currentUser): string
    {
        $options = '';
        $allGroups = $this->getAllGroups($currentUser);

        foreach ($allGroups as $allGroup) {
            if (-1 !== $allGroup) {
                $options .= sprintf(
                    '<option value="%d" %s>%s</option>',
                    $allGroup,
                    ((in_array($allGroup, $groups) || (isset($groups[0]) && $groups[0] === -1)) ? 'selected' : ''),
                    $this->getGroupName($allGroup)
                );
            }
        }

        return $options;
    }

    /**
     * Returns an array with the IDs of all groups stored in the
     * database if no user is passed.
     *
     * @return array<int>
     */
    public function getAllGroups(CurrentUser $currentUser): array
    {
        $select = sprintf('SELECT group_id FROM %sfaqgroup', Database::getTablePrefix());

        if (
            !$this->configuration->get('main.enableCategoryRestrictions') &&
            $currentUser->getUserId() !== 1 &&
            !$currentUser->isSuperAdmin()
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
                $currentUser->getUserId()
            );
        }

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = $row['group_id'];
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

        $res = $this->configuration->getDb()->query($select);
        if ($this->configuration->getDb()->numRows($res) != 1) {
            return '-';
        }

        $row = $this->configuration->getDb()->fetchArray($res);

        return $row['name'];
    }

    /**
     * Returns an array that contains the right-IDs of all rights
     * the user $userId owns. User-rights and the rights the user
     * owns because of a group-membership is taken into account.
     *
     * @param int $userId User ID
     *
     * @return array<int>
     */
    #[\Override]
    public function getAllUserRights(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $userRights = $this->getUserRights($userId);
        $groupRights = $this->getUserGroupRights($userId);

        return array_unique(array_merge($userRights, $groupRights));
    }

    /**
     * Returns the number of user- and group-rights the user specified by
     * user_id owns.
     *
     * @param CurrentUser $currentUser User object
     */
    #[\Override]
    public function getUserRightsCount(CurrentUser $currentUser): int
    {
        $userRights = $this->getAllUserRights($currentUser->getUserId());

        return is_countable($userRights) ? count($userRights) : 0;
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
            $userId
        );

        $res = $this->configuration->getDb()->query($select);
        $result = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
            $result[] = $row['right_id'];
        }

        return $result;
    }

    /**
     * Adds the user $userId to all groups with the auto_join
     * option. By using the auto_join option, user administration
     * can be much easier. For example, by setting this option only
     * for a single group called 'All Users'. The autoJoin() method
     * then has to be called every time a new user registers.
     * Returns true if successful, otherwise false.
     *
     * @param int $userId User ID
     */
    public function autoJoin(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $select = sprintf(
            'SELECT group_id FROM %sfaqgroup WHERE auto_join = 1',
            Database::getTablePrefix()
        );

        $res = $this->configuration->getDb()->query($select);
        if (!$res) {
            return false;
        }

        $auto_join = [];
        while ($row = $this->configuration->getDb()->fetchArray($res)) {
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
     * Returns true if successful, otherwise false.
     *
     * @param int $userId  User ID
     * @param int $groupId Group ID
     */
    public function addToGroup(int $userId, int $groupId): bool
    {
        if ($userId <= 0 || $groupId <= 0) {
            return false;
        }

        if (!$this->getGroupData($groupId)) {
            return false;
        }

        // add user to group
        $insert = sprintf(
            'INSERT INTO %sfaquser_group (user_id, group_id) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $userId,
            $groupId
        );

        $res = $this->configuration->getDb()->query($insert);
        return (bool) $res;
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
        if ($groupId <= 0) {
            return [];
        }

        $select = sprintf(
            'SELECT group_id, name, description, auto_join FROM %sfaqgroup WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        $res = $this->configuration->getDb()->query($select);
        if ($this->configuration->getDb()->numRows($res) != 1) {
            return [];
        }

        return $this->configuration->getDb()->fetchArray($res);
    }

    /**
     * Removes the user $userId from all groups.
     * Returns true for success, otherwise false.
     *
     * @param int $userId User ID
     */
    public function removeFromAllGroups(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaquser_group WHERE user_id  = %d',
            Database::getTablePrefix(),
            $userId
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Refuses all group rights.
     * Returns true for success, otherwise false.
     *
     * @param int $groupId Group ID
     */
    public function refuseAllGroupRights(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaqgroup_right WHERE group_id  = %d',
            Database::getTablePrefix(),
            $groupId
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Removes all users from the group $groupId.
     * Returns true for success, otherwise false.
     *
     * @param int $groupId Group ID
     */
    public function removeAllUsersFromGroup(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $delete = sprintf(
            'DELETE FROM %sfaquser_group WHERE group_id = %d',
            Database::getTablePrefix(),
            $groupId
        );

        return (bool) $this->configuration->getDb()->query($delete);
    }

    /**
     * Finds or creates a group by name.
     * Returns the group ID on success, 0 on failure.
     *
     * @param string $name Group name
     * @param string $description Optional group description
     */
    public function findOrCreateGroupByName(string $name, string $description = ''): int
    {
        $groupId = $this->getGroupId($name);

        if ($groupId > 0) {
            return $groupId;
        }

        // Create a new group if it doesn't exist
        $groupData = [
            'name' => $name,
            'description' => $description ?: 'Auto-created group for ' . $name,
            'auto_join' => false,
        ];

        return $this->addGroup($groupData);
    }
}
