<?php

declare(strict_types=1);

/**
 * The basic permission class provides user rights.
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

    public function __construct(
        protected Configuration $configuration,
    ) {
        $this->repository = new BasicPermissionRepository($configuration);
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

        if (!isset($rightData['for_users'])) {
            return false;
        }

        return $this->repository->grantUserRight($userId, $rightId);
    }

    /**
     * Returns an associative array with all data stored for in the
     * database for the specified right. The keys of the returned
     * array are the field names.
     *
     * @return array<string, bool>
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

        return $this->checkUserRight($currentUser->getUserId(), $right);
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
     * @param array<string> $rightData Array if rights
     */
    public function addRight(array $rightData): int
    {
        if ($this->getRightId($rightData['name']) > 0) {
            return 0;
        }

        $nextId = $this->repository->nextRightId();
        $rightData = $this->checkRightData($rightData);

        if (!$this->repository->addRight($rightData, $nextId)) {
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
     * @param array<string> $rightData Array of rights
     *
     * @return array<string, int>
     */
    public function checkRightData(array $rightData): array
    {
        if (!isset($rightData['name']) || !is_string($rightData['name'])) {
            $rightData['name'] = $this->defaultRightData['name'];
        }

        if (!isset($rightData['description']) || !is_string($rightData['description'])) {
            $rightData['description'] = $this->defaultRightData['description'];
        }

        if (!isset($rightData['for_users'])) {
            $rightData['for_users'] = $this->defaultRightData['for_users'];
        }

        if (!isset($rightData['for_groups'])) {
            $rightData['for_groups'] = $this->defaultRightData['for_groups'];
        }

        if (!isset($rightData['for_sections'])) {
            $rightData['for_sections'] = $this->defaultRightData['for_sections'];
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
        return $this->repository->refuseAllUserRights($userId);
    }
}
