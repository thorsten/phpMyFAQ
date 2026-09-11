<?php

/**
 * Renames a right that still carries a legacy name.
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
 * @since     2026-09-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\User;
use Throwable;

/**
 * Renames the right $oldName to $newName, but only when $newName does not exist yet.
 *
 * A rename is a repair for installations that were upgraded across a code-level rename of a
 * PermissionType value without a matching database change. Fresh installations and already
 * repaired ones carry the new name, so the operation succeeds without touching anything there,
 * which keeps a re-run after a partially failed update safe.
 */
readonly class PermissionRenameOperation implements OperationInterface
{
    public function __construct(
        private Configuration $configuration,
        private string $oldName,
        private string $newName,
    ) {
    }

    public function getType(): string
    {
        return 'permission_rename';
    }

    public function getDescription(): string
    {
        return sprintf('Rename permission: %s to %s', $this->oldName, $this->newName);
    }

    public function getOldName(): string
    {
        return $this->oldName;
    }

    public function getNewName(): string
    {
        return $this->newName;
    }

    public function execute(): bool
    {
        try {
            $permission = new User($this->configuration)->perm;

            if ($permission->getRightId($this->newName) > 0) {
                return true;
            }

            if ($permission->getRightId($this->oldName) === 0) {
                return true;
            }

            return $permission->renameRight($this->oldName, $this->newName);
        } catch (Throwable) {
            return false;
        }
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'oldName' => $this->oldName,
            'newName' => $this->newName,
        ];
    }
}
