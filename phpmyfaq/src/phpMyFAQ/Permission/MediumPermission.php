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

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\User\CurrentUser;

/**
 * Class MediumPermission
 *
 * @package phpMyFAQ\Permission
 */
class MediumPermission extends BasicPermission implements PermissionInterface
{
    protected MediumPermissionRepository $mediumRepository;

    public function __construct(
        protected Configuration $configuration,
    ) {
        parent::__construct($configuration);
        $this->mediumRepository = new MediumPermissionRepository($configuration);
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
        return $this->mediumRepository->getGroupRights($groupId);
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
        return $this->mediumRepository->checkUserGroupRight($userId, $rightId);
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
        $right_data = $this->getRightData($rightId);
        if (count($right_data) === 0 || !($right_data['for_groups'] ?? false)) {
            return false;
        }

        return $this->mediumRepository->grantGroupRight($groupId, $rightId);
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
        if ($this->getGroupId($groupData['name']) > 0) {
            return 0;
        }

        $nextId = $this->mediumRepository->nextGroupId();
        $groupData = $this->checkGroupData($groupData);

        if (!$this->mediumRepository->addGroup($groupData, $nextId)) {
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
        return $this->mediumRepository->getGroupId($name);
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

        $groupData['auto_join'] = (int) $groupData['auto_join'];

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
        return $this->mediumRepository->changeGroup($groupId, $checkedData);
    }

    /**
     * Removes the group given by $groupId from the database.
     * Returns true if successful, otherwise false.
     *
     * @param int $groupId Group ID
     */
    public function deleteGroup(int $groupId): bool
    {
        if (!$this->mediumRepository->deleteGroup($groupId)) {
            return false;
        }

        if (!$this->mediumRepository->deleteGroupMemberships($groupId)) {
            return false;
        }

        return $this->mediumRepository->deleteGroupRights($groupId);
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
        return $this->mediumRepository->getGroupMembers($groupId);
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
        return $this->mediumRepository->getUserGroups($userId);
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
            if (-1 === $allGroup) {
                continue;
            }

            $options .= sprintf(
                '<option value="%d" %s>%s</option>',
                $allGroup,
                in_array($allGroup, $groups, strict: true) || isset($groups[0]) && $groups[0] === -1 ? 'selected' : '',
                $this->getGroupName($allGroup),
            );
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
        if (
            !$this->configuration->get(item: 'main.enableCategoryRestrictions')
            && $currentUser->getUserId() !== 1
            && !$currentUser->isSuperAdmin()
        ) {
            return $this->mediumRepository->getAllGroups($currentUser->getUserId());
        }

        return $this->mediumRepository->getAllGroups();
    }

    /**
     * Returns the name of the group $groupId.
     *
     * @param int $groupId Group ID
     */
    public function getGroupName(int $groupId): string
    {
        return $this->mediumRepository->getGroupName($groupId);
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
        return $this->mediumRepository->getUserGroupRights($userId);
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

        $autoJoinGroups = $this->mediumRepository->getAutoJoinGroups();

        // add to groups
        foreach ($autoJoinGroups as $groupId) {
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

        return $this->mediumRepository->addToGroup($userId, $groupId);
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
        return $this->mediumRepository->getGroupData($groupId);
    }

    /**
     * Removes the user $userId from all groups.
     * Returns true for success, otherwise false.
     *
     * @param int $userId User ID
     */
    public function removeFromAllGroups(int $userId): bool
    {
        return $this->mediumRepository->removeFromAllGroups($userId);
    }

    /**
     * Refuses all group rights.
     * Returns true for success, otherwise false.
     *
     * @param int $groupId Group ID
     */
    public function refuseAllGroupRights(int $groupId): bool
    {
        return $this->mediumRepository->refuseAllGroupRights($groupId);
    }

    /**
     * Removes all users from the group $groupId.
     * Returns true for success, otherwise false.
     *
     * @param int $groupId Group ID
     */
    public function removeAllUsersFromGroup(int $groupId): bool
    {
        return $this->mediumRepository->removeAllUsersFromGroup($groupId);
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
