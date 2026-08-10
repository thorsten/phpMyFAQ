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
 * @copyright 2005-2026 phpMyFAQ Team
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
/* @mago-expect lint:too-many-methods - implements the full group-permission contract */
class MediumPermission extends BasicPermission implements PermissionInterface
{
    protected MediumPermissionRepository $mediumRepository;

    protected GroupCategoryPermissionRepository $categoryPermissionRepository;

    public function __construct(
        protected Configuration $configuration,
    ) {
        parent::__construct($configuration);
        $this->mediumRepository = new MediumPermissionRepository($configuration);
        $this->categoryPermissionRepository = new GroupCategoryPermissionRepository($configuration);
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

        $rightId = $this->resolveRightId($right);

        // check user right and group right
        if ($this->checkUserGroupRight($userId, $rightId)) {
            return true;
        }

        return $this->checkUserRight($userId, $rightId);
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
        if ($right_data === [] || !($right_data['for_groups'] ?? false)) {
            return false;
        }

        return $this->mediumRepository->grantGroupRight($groupId, $rightId);
    }

    /**
     * Adds a new group to the database and returns the ID of the
     * new group. The associative array $groupData contains the
     * data for the new group.
     *
     * @param array<string, int|string|bool> $groupData Array of group data
     */
    public function addGroup(array $groupData): int
    {
        if ($this->getGroupId((string) ($groupData['name'] ?? '')) > 0) {
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
     * @param array<string, int|string|bool> $groupData Array of group data
     *
     * @return array<string, int|string>
     */
    public function checkGroupData(array $groupData): array
    {
        $name = $groupData['name'] ?? null;
        $description = $groupData['description'] ?? null;

        return [
            'name' => is_string($name) ? $name : (string) $this->defaultGroupData['name'],
            'description' => is_string($description) ? $description : (string) $this->defaultGroupData['description'],
            'auto_join' => (int) ($groupData['auto_join'] ?? $this->defaultGroupData['auto_join']),
        ];
    }

    /**
     * Changes the group data of the given group.
     *
     * @param int $groupId Group ID
     * @param array<string, int|string|bool> $groupData Array of group data
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

        $this->categoryPermissionRepository->deleteAllForGroup($groupId);
        $this->languageRepository->deleteAllForGroup($groupId);

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
                in_array($allGroup, $groups, strict: true) || ($groups[0] ?? null) === -1 ? 'selected' : '',
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
        foreach ($autoJoinGroups as $autoJoinGroup) {
            $this->addToGroup($userId, $autoJoinGroup);
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
     * @return array<array-key, mixed>
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
     * Removes a user from a single group.
     *
     * @param int $userId User ID
     * @param int $groupId Group ID
     */
    public function removeFromGroup(int $userId, int $groupId): bool
    {
        if ($userId <= 0 || $groupId <= 0) {
            return false;
        }

        return $this->mediumRepository->removeFromGroup($userId, $groupId);
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
            'description' => $description === '' ? 'Auto-created group for ' . $name : $description,
            'auto_join' => false,
        ];

        return $this->addGroup($groupData);
    }

    /**
     * Returns true if the user has the specified right for the given category,
     * taking into account group-level category restrictions.
     *
     * If the user's group has no category restrictions for a right, the right
     * applies globally. If restrictions exist, the right only applies to
     * the specified categories.
     *
     * @param int   $userId     User ID
     * @param mixed $right      Right ID, name, or PermissionType enum
     * @param int   $categoryId Category ID
     * @param CurrentUser|null $currentUser Optional pre-loaded user to avoid repeated instantiation
     * @throws Exception
     */
    public function hasPermissionForCategory(
        int $userId,
        mixed $right,
        int $categoryId,
        ?CurrentUser $currentUser = null,
    ): bool {
        if ($currentUser === null) {
            $currentUser = new CurrentUser($this->configuration);
            $currentUser->getUserById($userId);
        }

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        $rightId = $this->resolveRightId($right);

        // Check direct user right (always global, no category restriction)
        if ($this->checkUserRight($userId, $rightId)) {
            return true;
        }

        // Check group right with category restrictions
        return $this->categoryPermissionRepository->checkUserGroupRightForCategory($userId, $rightId, $categoryId);
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
        return $this->categoryPermissionRepository->getCategoryRestrictions($groupId, $rightId);
    }

    /**
     * Returns all category restrictions for a group, keyed by right ID.
     *
     * @param int $groupId Group ID
     * @return array<int, array<int>> Map of right_id => [category_ids]
     */
    public function getAllCategoryRestrictions(int $groupId): array
    {
        return $this->categoryPermissionRepository->getAllCategoryRestrictions($groupId);
    }

    /**
     * Sets category restrictions for a group's right.
     *
     * @param int $groupId Group ID
     * @param int $rightId Right ID
     * @param array<int> $categoryIds Category IDs to restrict to (empty = unrestricted)
     */
    public function setCategoryRestrictions(int $groupId, int $rightId, array $categoryIds): bool
    {
        return $this->categoryPermissionRepository->setCategoryRestrictions($groupId, $rightId, $categoryIds);
    }

    /**
     * Returns the category IDs in which the user may exercise the right.
     * Null means unrestricted: superadmins, direct user-rights, and group
     * grants without category restrictions always apply globally. An empty
     * array means the user cannot exercise the right in any category.
     *
     * @param int   $userId User ID
     * @param mixed $right  Right ID, right name, or PermissionType value
     * @return array<int>|null
     * @throws Exception
     */
    #[\Override]
    public function getAllowedCategoriesForRight(int $userId, mixed $right): ?array
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->getUserById($userId);

        if ($currentUser->isSuperAdmin()) {
            return null;
        }

        $rightId = $this->resolveRightId($right);

        if ($this->checkUserRight($userId, $rightId)) {
            return null;
        }

        $allowedCategories = [];
        foreach ($this->getUserGroups($userId) as $groupId) {
            if (!in_array($rightId, $this->getGroupRights($groupId), strict: true)) {
                continue;
            }

            $restrictions = $this->categoryPermissionRepository->getCategoryRestrictions($groupId, $rightId);
            if ($restrictions === []) {
                return null;
            }

            $allowedCategories = [...$allowedCategories, ...$restrictions];
        }

        return array_values(array_unique($allowedCategories));
    }

    /**
     * Returns true if the user has the specified right for the given language,
     * taking into account both the user's own direct language restriction and
     * group-level language restrictions. Unlike category restrictions, a
     * direct user-right grant CAN be language-restricted.
     *
     * The right is usable for a language if either the user's own grant is
     * unrestricted or matches the language, OR any qualifying group's grant
     * is unrestricted or matches the language (union of all grants).
     *
     * @param int    $userId      User ID
     * @param mixed  $right       Right ID, name, or PermissionType enum
     * @param string $language    Language code (e.g. 'en', 'de')
     * @param CurrentUser|null $currentUser Optional pre-loaded user to avoid repeated instantiation
     * @throws Exception
     */
    public function hasPermissionForLanguage(
        int $userId,
        mixed $right,
        string $language,
        ?CurrentUser $currentUser = null,
    ): bool {
        if ($currentUser === null) {
            $currentUser = new CurrentUser($this->configuration);
            $currentUser->getUserById($userId);
        }

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        $rightId = $this->resolveRightId($right);

        if (
            $this->checkUserRight($userId, $rightId)
            && $this->languageRepository->checkUserRightForLanguage($userId, $rightId, $language)
        ) {
            return true;
        }

        return $this->languageRepository->checkUserGroupRightForLanguage($userId, $rightId, $language);
    }

    /**
     * Returns the language codes that a group's right is restricted to.
     * An empty array means the right is unrestricted (applies globally).
     *
     * @return array<string>
     */
    public function getLanguageRestrictions(int $groupId, int $rightId): array
    {
        return $this->languageRepository->getLanguageRestrictions($groupId, $rightId);
    }

    /**
     * Returns all language restrictions for a group, keyed by right ID.
     *
     * @return array<int, array<string>>
     */
    public function getAllLanguageRestrictions(int $groupId): array
    {
        return $this->languageRepository->getAllLanguageRestrictions($groupId);
    }

    /**
     * Sets language restrictions for a group's right.
     *
     * @param array<string> $languages Language codes to restrict to (empty = unrestricted)
     */
    public function setLanguageRestrictions(int $groupId, int $rightId, array $languages): bool
    {
        return $this->languageRepository->setLanguageRestrictions($groupId, $rightId, $languages);
    }

    /**
     * Returns the language codes in which the user may exercise the right:
     * the union of the user's own direct grant (if any) and every group
     * grant that also holds the right. Null means unrestricted (superadmin,
     * or any single grant is itself unrestricted). An empty array means the
     * user does not hold the right through any grant at all.
     *
     * @param int   $userId User ID
     * @param mixed $right  Right ID, right name, or PermissionType value
     * @return array<string>|null
     * @throws Exception
     */
    #[\Override]
    public function getAllowedLanguagesForRight(int $userId, mixed $right): ?array
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->getUserById($userId);

        if ($currentUser->isSuperAdmin()) {
            return null;
        }

        $rightId = $this->resolveRightId($right);
        $allowedLanguages = [];
        $hasAnyGrant = false;

        if ($this->checkUserRight($userId, $rightId)) {
            $hasAnyGrant = true;
            $userRestrictions = $this->languageRepository->getUserLanguageRestrictions($userId, $rightId);
            if ($userRestrictions === []) {
                return null;
            }

            $allowedLanguages = [...$allowedLanguages, ...$userRestrictions];
        }

        foreach ($this->getUserGroups($userId) as $groupId) {
            if (!in_array($rightId, $this->getGroupRights($groupId), strict: true)) {
                continue;
            }

            $hasAnyGrant = true;
            $groupRestrictions = $this->languageRepository->getLanguageRestrictions($groupId, $rightId);
            if ($groupRestrictions === []) {
                return null;
            }

            $allowedLanguages = [...$allowedLanguages, ...$groupRestrictions];
        }

        return $hasAnyGrant ? array_values(array_unique($allowedLanguages)) : [];
    }

    /**
     * Resolves a right given as ID, name, or PermissionType to its right ID.
     */
    private function resolveRightId(mixed $right): int
    {
        if (!is_numeric($right) && is_string($right)) {
            $right = $this->getRightId($right);
        }

        if ($right instanceof PermissionType) {
            $right = $this->getRightId($right->value);
        }

        return (int) $right;
    }
}
