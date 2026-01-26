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
    public function __construct(
        private Configuration $configuration,
        private string $permissionName,
        private string $permissionDescription,
        private int $userId = 1,
    ) {
    }

    public function getType(): string
    {
        return 'permission_grant';
    }

    public function getDescription(): string
    {
        return sprintf('Add permission: %s (%s)', $this->permissionName, $this->permissionDescription);
    }

    public function getPermissionName(): string
    {
        return $this->permissionName;
    }

    public function getPermissionDescription(): string
    {
        return $this->permissionDescription;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

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
