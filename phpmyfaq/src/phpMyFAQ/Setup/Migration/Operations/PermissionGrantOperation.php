<?php

/**
 * Permission grant operation for migrations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\User;

readonly class PermissionGrantOperation implements OperationInterface
{
    /**
     * Initialize the operation with configuration, the permission's name and description, and the target user ID.
     *
     * @param string $permissionName The machine name/key of the permission to create.
     * @param string $permissionDescription Human-readable description of the permission.
     * @param int $userId ID of the user who will receive the granted permission.
     */
    public function __construct(
        private Configuration $configuration,
        private string $permissionName,
        private string $permissionDescription,
        private int $userId = 1,
    ) {
    }

    /**
     * Get the operation type identifier for this migration.
     *
     * @return string The operation type identifier 'permission_grant'.
     */
    public function getType(): string
    {
        return 'permission_grant';
    }

    /**
     * Get a human-readable description of the permission being added.
     *
     * @return string The description formatted as "Add permission: {name} ({description})".
     */
    public function getDescription(): string
    {
        return sprintf('Add permission: %s (%s)', $this->permissionName, $this->permissionDescription);
    }

    /**
     * Get the permission name configured for this operation.
     *
     * @return string The configured permission name.
     */
    public function getPermissionName(): string
    {
        return $this->permissionName;
    }

    /**
     * Retrieve the human-readable description of the permission.
     *
     * @return string The permission description.
     */
    public function getPermissionDescription(): string
    {
        return $this->permissionDescription;
    }

    /**
     * Get the target user id for the permission grant.
     *
     * @return int The user id to which the new permission will be granted.
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Creates a new permission and assigns it to the configured user.
     *
     * @return bool `true` if the permission was added and granted to the user, `false` otherwise.
     */
    public function execute(): bool
    {
        try {
            $user = new User($this->configuration);
            $rightData = [
                'name' => $this->permissionName,
                'description' => $this->permissionDescription,
            ];
            $rightId = $user->perm->addRight($rightData);
            $user->perm->grantUserRight($this->userId, $rightId);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Serialize the operation into an associative array.
     *
     * @return array{
     *     type: string,
     *     description: string,
     *     permissionName: string,
     *     permissionDescription: string,
     *     userId: int
     * } An associative array with the operation's type, description, permission name and description, and target user ID.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'permissionName' => $this->permissionName,
            'permissionDescription' => $this->permissionDescription,
            'userId' => $this->userId,
        ];
    }
}