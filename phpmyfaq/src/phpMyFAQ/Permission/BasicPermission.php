<?php

/**
 * The basic permission class provides user rights.
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
 * Class BasicPermission
 *
 * @package phpMyFAQ\Permission
 */
class BasicPermission implements PermissionInterface
{
    protected BasicPermissionRepository $repository;

    protected LanguagePermissionRepository $languageRepository;

    public function __construct(
        protected Configuration $configuration,
    ) {
        $this->repository = new BasicPermissionRepository($configuration);
        $this->languageRepository = new LanguagePermissionRepository($configuration);
    }

    /**
     * Default right data stored when a new right is created.
     *
     * @var array<string, string|bool>
     */
    public array $defaultRightData = [
        'name' => 'DEFAULT_RIGHT',
        'description' => 'Short description.',
        'for_users' => true,
        'for_groups' => true,
        'for_sections' => true,
    ];

    /**
     * Gives the user a new user-right.
     * Returns true on success, otherwise false.
     *
     * @param  int $userId  User ID
     * @param  int $rightId Right ID
     */
    public function grantUserRight(int $userId, int $rightId): bool
    {
        $rightData = $this->getRightData($rightId);

        if (!array_key_exists('for_users', $rightData)) {
            return false;
        }

        return $this->repository->grantUserRight($userId, $rightId);
    }

    /**
     * Returns an associative array with all data stored for in the
     * database for the specified right. The keys of the returned
     * array are the field names.
     *
     * @return array<string, mixed>
     */
    public function getRightData(int $rightId): array
    {
        return $this->repository->getRightData($rightId);
    }

    /**
     * Returns true if the user given by user_id has the right,
     * otherwise false. Unlike checkUserRight(), right may be a
     * right-ID or a right-name. Another difference is that also
     * group rights are taken into account.
     *
     * @param int   $userId User ID
     * @param mixed $right  Right ID or right name
     * @throws Exception
     */
    public function hasPermission(int $userId, mixed $right): bool
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->getUserById($userId);

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        if (!is_numeric($right) && is_string($right)) {
            $right = $this->getRightId($right);
        }

        if ($right instanceof PermissionType) {
            $right = $this->getRightId($right->value);
        }

        return $this->checkUserRight($currentUser->getUserId(), (int) $right);
    }

    /**
     * Returns the right-ID of the right with the name $name.
     *
     * @param string $name Name
     */
    public function getRightId(string $name): int
    {
        return $this->repository->getRightId($name);
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
        return $this->repository->checkUserRight($userId, $rightId);
    }

    /**
     * Returns an array that contains the IDs of all user-rights
     * the user owns.
     *
     * @param int $userId User ID
     *
     * @return array<int>
     */
    public function getAllUserRights(int $userId): array
    {
        return $this->getUserRights($userId);
    }

    /**
     * Returns the number of user-rights the user specified by
     * user_id owns.
     *
     * @param CurrentUser $currentUser User object
     */
    public function getUserRightsCount(CurrentUser $currentUser): int
    {
        $userRights = $this->getUserRights($currentUser->getUserId());

        return is_countable($userRights) ? count($userRights) : 0;
    }

    /**
     * Returns an array with the IDs of all user-rights the user
     * specified by user_id owns. Group rights are not taken into
     * account.
     *
     * @param int $userId User ID
     *
     * @return array<int>
     */
    public function getUserRights(int $userId): array
    {
        return $this->repository->getUserRights($userId);
    }

    /**
     * Adds a new right into the database. Returns the ID of the
     * new right. The associative array right_data contains the right
     * data stored in the rights table.
     *
     * @param array<string, mixed> $rightData Array if rights
     */
    public function addRight(array $rightData): int
    {
        if ($this->getRightId((string) ($rightData['name'] ?? '')) > 0) {
            return 0;
        }

        $nextId = $this->repository->nextRightId();
        $checkedRightData = $this->checkRightData($rightData);
        $rightRow = [];
        foreach ($checkedRightData as $fieldName => $fieldValue) {
            $rightRow[$fieldName] = is_int($fieldValue) ? $fieldValue : (string) $fieldValue;
        }

        if (!$this->repository->addRight($rightRow, $nextId)) {
            return 0;
        }

        return $nextId;
    }

    /**
     * Checks the given associative array $right_data. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->default_right_data.
     * Returns the corrected $right_data associative array.
     *
     * @param array<string, mixed> $rightData Array of rights
     *
     * @return array<string, mixed>
     */
    public function checkRightData(array $rightData): array
    {
        $stringFields = ['name', 'description'];
        foreach ($stringFields as $field) {
            if (array_key_exists($field, $rightData) && is_string($rightData[$field])) {
                continue;
            }

            $rightData[$field] = $this->defaultRightData[$field];
        }

        $booleanLikeFields = ['for_users', 'for_groups', 'for_sections'];
        foreach ($booleanLikeFields as $field) {
            if (array_key_exists($field, $rightData)) {
                continue;
            }

            $rightData[$field] = $this->defaultRightData[$field];
        }

        $rightData['for_users'] = (int) $rightData['for_users'];
        $rightData['for_groups'] = (int) $rightData['for_groups'];
        $rightData['for_sections'] = (int) $rightData['for_sections'];

        return $rightData;
    }

    /**
     * Renames rights, only used for updates.
     */
    public function renameRight(string $oldName, string $newName): bool
    {
        $rightId = $this->getRightId($oldName);
        if ($rightId === 0) {
            return false;
        }

        return $this->repository->renameRight($rightId, $newName);
    }

    /**
     * Returns an array that contains all rights stored in the
     * database. Each array element is an associative array with
     * the complete right-data. By passing the optional parameter
     * $order, the order of the array may be specified. Default is
     * $order = 'right_id ASC'.
     *
     * @param string $order Ordering
     *
     * @return array<int, array>
     */
    public function getAllRightsData(string $order = 'ASC'): array
    {
        return $this->repository->getAllRightsData($order);
    }

    /**
     * Refuses all user rights.
     * Returns true on success, otherwise false.
     *
     * @param int $userId User ID
     */
    public function refuseAllUserRights(int $userId): bool
    {
        // Revoke the rights first. There is no transaction API on the database layer,
        // so order is what keeps this fail-safe: if revoking fails, the restriction rows
        // are still in place and the retained right stays scoped. Dropping the
        // restrictions first would leave a surviving right unrestricted.
        if (!$this->repository->refuseAllUserRights($userId)) {
            return false;
        }

        $this->languageRepository->deleteAllForUser($userId);

        return true;
    }

    /**
     * Returns an array with the IDs of all groups the user belongs to.
     * Since this is BasicPermission, always return an empty array.
     *
     * @param int $userId User ID
     * @return array<int>
     */
    public function getUserGroups(int $userId): array
    {
        return [];
    }

    /**
     * Basic mode has no groups and therefore no category restrictions:
     * the check is identical to the global permission check.
     */
    public function hasPermissionForCategory(int $userId, mixed $right, int $categoryId): bool
    {
        return $this->hasPermission($userId, $right);
    }

    /**
     * Basic mode has no groups and therefore no category restrictions.
     */
    public function getAllowedCategoriesForRight(int $userId, mixed $right): ?array
    {
        return null;
    }

    /**
     * Basic mode has no groups, but a user's direct right grant can still be
     * restricted to specific language(s).
     */
    public function hasPermissionForLanguage(int $userId, mixed $right, string $language): bool
    {
        if (!$this->hasPermission($userId, $right)) {
            return false;
        }

        // Superadmins hold every right implicitly, without a faquser_right row.
        // Querying the direct-grant restrictions would find no row and deny them,
        // so bypass here exactly as hasPermission() and MediumPermission do.
        if ($this->isSuperAdmin($userId)) {
            return true;
        }

        $rightId = $this->resolveRightId($right);

        return $this->languageRepository->checkUserRightForLanguage($userId, $rightId, $language);
    }

    /**
     * Returns the language codes the user's direct right grant is restricted
     * to, null if unrestricted, or an empty array if the user does not own
     * the right at all.
     */
    public function getAllowedLanguagesForRight(int $userId, mixed $right): ?array
    {
        if (!$this->hasPermission($userId, $right)) {
            return [];
        }

        // Superadmins are never language-restricted, see hasPermissionForLanguage().
        if ($this->isSuperAdmin($userId)) {
            return null;
        }

        $rightId = $this->resolveRightId($right);
        $restrictions = $this->languageRepository->getUserLanguageRestrictions($userId, $rightId);

        return $restrictions === [] ? null : $restrictions;
    }

    /**
     * Returns true if the given user is a superadmin.
     *
     * @throws Exception
     */
    private function isSuperAdmin(int $userId): bool
    {
        $currentUser = new CurrentUser($this->configuration);
        $currentUser->getUserById($userId);

        return $currentUser->isSuperAdmin();
    }

    /**
     * Returns the language codes that a user's direct right is restricted to.
     * An empty array means the right is unrestricted (applies globally).
     *
     * @return array<string>
     */
    public function getUserLanguageRestrictions(int $userId, int $rightId): array
    {
        return $this->languageRepository->getUserLanguageRestrictions($userId, $rightId);
    }

    /**
     * Returns all language restrictions for a user, keyed by right ID.
     *
     * @return array<int, array<string>>
     */
    public function getAllUserLanguageRestrictions(int $userId): array
    {
        return $this->languageRepository->getAllUserLanguageRestrictions($userId);
    }

    /**
     * Sets language restrictions for a user's direct right.
     *
     * @param array<string> $languages Language codes to restrict to (empty = unrestricted)
     */
    public function setUserLanguageRestrictions(int $userId, int $rightId, array $languages): bool
    {
        return $this->languageRepository->setUserLanguageRestrictions($userId, $rightId, $languages);
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
