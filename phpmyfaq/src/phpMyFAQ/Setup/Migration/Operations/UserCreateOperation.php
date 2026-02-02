<?php

/**
 * Operation for creating a user during installation.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\User;
use SensitiveParameter;

readonly class UserCreateOperation implements OperationInterface
{
    public function __construct(
        private Configuration $configuration,
        private string $loginName,
        #[SensitiveParameter]
        private string $password,
        private string $displayName,
        private string $email,
        private int $userId,
        private bool $isSuperAdmin = false,
        private string $status = 'protected',
    ) {
    }

    public function getType(): string
    {
        return 'user_create';
    }

    public function getDescription(): string
    {
        return sprintf('Create user "%s" (ID: %d)', $this->loginName, $this->userId);
    }

    public function execute(): bool
    {
        try {
            $user = new User($this->configuration);
            if (!$user->createUser($this->loginName, $this->password, '', $this->userId)) {
                return false;
            }

            $user->setStatus($this->status);

            $userData = [
                'display_name' => $this->displayName,
                'email' => $this->email,
            ];
            $user->setUserData($userData);

            if ($this->isSuperAdmin) {
                $user->setSuperAdmin(true);
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'login_name' => $this->loginName,
            'user_id' => $this->userId,
            'is_super_admin' => $this->isSuperAdmin,
        ];
    }
}
