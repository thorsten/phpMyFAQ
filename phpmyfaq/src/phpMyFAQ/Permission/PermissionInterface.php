<?php

/**
 * Interface for phpMyFAQ permission classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2022-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2022-12-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Permission;

use phpMyFAQ\User\CurrentUser;

interface PermissionInterface
{
    /**
     * Returns true, if the user given by $userId owns the right
     * specified by $right. It does not matter if the user owns this
     * right as a user-right or because of a group-membership.
     * The parameter $right may be a right-ID (recommended for
     * performance) or a right-name.
     *
     * @param int   $userId User ID
     * @param mixed $right  Rights
     */
    public function hasPermission(int $userId, mixed $right): bool;

    /**
     * Gives the user a new user-right.
     * Returns true on success, otherwise false.
     */
    public function grantUserRight(int $userId, int $rightId): bool;

    /**
     * Returns an associative array with all data stored in the database
     * for the specified right, keyed by field name.
     *
     * @return array<string, mixed>
     */
    public function getRightData(int $rightId): array;

    /**
     * Returns the right-ID for the given right-name, or 0 if unknown.
     */
    public function getRightId(string $name): int;

    /**
     * Returns true if the user owns the right given by ID as a direct
     * user-right (group rights are not taken into account).
     */
    public function checkUserRight(int $userId, int $rightId): bool;

    /**
     * Returns an array with the IDs of all rights the user owns.
     *
     * @return array<int>
     */
    public function getAllUserRights(int $userId): array;

    /**
     * Returns the number of user-rights the given user owns.
     */
    public function getUserRightsCount(CurrentUser $currentUser): int;

    /**
     * Returns an array with the IDs of all direct user-rights the user
     * owns. Group rights are not taken into account.
     *
     * @return array<int>
     */
    public function getUserRights(int $userId): array;

    /**
     * Adds a new right and returns its ID, or 0 when the right exists.
     *
     * @param array<string> $rightData
     */
    public function addRight(array $rightData): int;

    /**
     * Validates the given right data, replacing missing or invalid
     * fields with defaults.
     *
     * @param array<string, mixed> $rightData
     * @return array<string, mixed>
     */
    public function checkRightData(array $rightData): array;

    /**
     * Renames a right, only used for updates.
     */
    public function renameRight(string $oldName, string $newName): bool;

    /**
     * Returns all rights stored in the database as complete right-data
     * arrays.
     *
     * @return array<int, array>
     */
    public function getAllRightsData(string $order = 'ASC'): array;

    /**
     * Refuses all user rights.
     * Returns true on success, otherwise false.
     */
    public function refuseAllUserRights(int $userId): bool;

    /**
     * Returns an array with the IDs of all groups the user belongs to;
     * empty for permission levels without group support.
     *
     * @return array<int>
     */
    public function getUserGroups(int $userId): array;
}
