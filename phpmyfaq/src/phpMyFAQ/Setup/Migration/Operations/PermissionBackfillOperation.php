<?php

/**
 * Permission backfill operation for migrations.
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
 * @since     2026-08-12
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Permission\BasicPermissionRepository;
use phpMyFAQ\Permission\GroupCategoryPermissionRepository;
use phpMyFAQ\Permission\LanguagePermissionRepository;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Permission\MediumPermissionRepository;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\User;
use Throwable;

/**
 * Grants an existing or newly created right to many users and groups at once.
 *
 * PermissionGrantOperation cannot do this: it grants to exactly one user, and it reports
 * failure when the right already exists, because BasicPermission::addRight() returns 0 for a
 * duplicate name. A backfill has to succeed on a right that is already present and has to reach
 * every account, so that turning an existing-but-unenforced right into an enforced one does not
 * revoke access from anybody.
 *
 * With $mirrorFrom set, the right is granted to exactly those users and groups that hold the
 * source right, optionally carrying its category and language restrictions across, so a scoped
 * grant stays scoped instead of silently widening.
 */
readonly class PermissionBackfillOperation implements OperationInterface
{
    public function __construct(
        private Configuration $configuration,
        private string $permissionName,
        private string $permissionDescription,
        private bool $grantToAllUsers = false,
        private bool $grantToAllGroups = false,
        private ?string $mirrorFrom = null,
        private bool $mirrorRestrictions = false,
    ) {
    }

    public function getType(): string
    {
        return 'permission_backfill';
    }

    public function getDescription(): string
    {
        if ($this->mirrorFrom !== null) {
            return sprintf('Backfill permission: %s for every holder of %s', $this->permissionName, $this->mirrorFrom);
        }

        return sprintf('Backfill permission: %s for all users and groups', $this->permissionName);
    }

    public function getPermissionName(): string
    {
        return $this->permissionName;
    }

    public function getPermissionDescription(): string
    {
        return $this->permissionDescription;
    }

    public function getMirrorFrom(): ?string
    {
        return $this->mirrorFrom;
    }

    public function execute(): bool
    {
        try {
            $user = new User($this->configuration);
            $permission = $user->perm;

            $rightId = $this->resolveOrCreateRight($permission->getRightId($this->permissionName), $permission);
            if ($rightId <= 0) {
                return false;
            }

            $sourceRightId = $this->mirrorFrom === null ? 0 : $permission->getRightId($this->mirrorFrom);

            $this->backfillUsers($user, $rightId, $sourceRightId);
            $this->backfillGroups($rightId, $sourceRightId);

            return true;
        } catch (Throwable) {
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
            'grantToAllUsers' => $this->grantToAllUsers,
            'grantToAllGroups' => $this->grantToAllGroups,
            'mirrorFrom' => $this->mirrorFrom,
            'mirrorRestrictions' => $this->mirrorRestrictions,
        ];
    }

    /**
     * An already existing right is a success, not a failure — that is the whole point of a
     * backfill, which typically runs against a right that every installation already carries.
     */
    private function resolveOrCreateRight(int $rightId, PermissionInterface $permission): int
    {
        if ($rightId > 0) {
            return $rightId;
        }

        return $permission->addRight([
            'name' => $this->permissionName,
            'description' => $this->permissionDescription,
        ]);
    }

    private function backfillUsers(User $user, int $rightId, int $sourceRightId): void
    {
        $userIds = $this->resolveUserIds($user, $sourceRightId);

        foreach ($userIds as $userId) {
            // grantUserRight() is a plain INSERT, so a re-run after a partially failed update
            // would hit a primary key violation without this guard.
            if (!$user->perm->checkUserRight($userId, $rightId)) {
                $user->perm->grantUserRight($userId, $rightId);
            }

            if ($this->mirrorRestrictions && $sourceRightId > 0) {
                $this->mirrorUserLanguageRestrictions($userId, $rightId, $sourceRightId);
            }
        }
    }

    private function backfillGroups(int $rightId, int $sourceRightId): void
    {
        if (!$this->grantToAllGroups && $this->mirrorFrom === null) {
            return;
        }

        // The group tables exist in both permission levels, and an installation can be switched
        // from basic to medium later on, so back-fill groups regardless of security.permLevel.
        $groupRepository = new MediumPermissionRepository($this->configuration);
        $groupIds = $this->mirrorFrom === null
            ? $groupRepository->getAllGroups()
            : $groupRepository->getGroupIdsWithRight($sourceRightId);

        if ($groupIds === []) {
            return;
        }

        $permission = new MediumPermission($this->configuration);

        foreach ($groupIds as $groupId) {
            if (!in_array($rightId, $groupRepository->getGroupRights($groupId), strict: true)) {
                $permission->grantGroupRight($groupId, $rightId);
            }

            if ($this->mirrorRestrictions && $sourceRightId > 0) {
                $this->mirrorGroupRestrictions($groupId, $rightId, $sourceRightId);
            }
        }
    }

    /**
     * @return array<int>
     */
    private function resolveUserIds(User $user, int $sourceRightId): array
    {
        if ($this->mirrorFrom !== null) {
            return new BasicPermissionRepository($this->configuration)->getUserIdsWithRight($sourceRightId);
        }

        if (!$this->grantToAllUsers) {
            return [];
        }

        return $user->getAllUsers();
    }

    private function mirrorUserLanguageRestrictions(int $userId, int $rightId, int $sourceRightId): void
    {
        $languageRepository = new LanguagePermissionRepository($this->configuration);
        $languages = $languageRepository->getUserLanguageRestrictions($userId, $sourceRightId);

        // An empty restriction set means "unrestricted", so there is nothing to carry over.
        if ($languages === []) {
            return;
        }

        $languageRepository->setUserLanguageRestrictions($userId, $rightId, $languages);
    }

    private function mirrorGroupRestrictions(int $groupId, int $rightId, int $sourceRightId): void
    {
        $categoryRepository = new GroupCategoryPermissionRepository($this->configuration);
        $categoryIds = $categoryRepository->getCategoryRestrictions($groupId, $sourceRightId);
        if ($categoryIds !== []) {
            $categoryRepository->setCategoryRestrictions($groupId, $rightId, $categoryIds);
        }

        $languageRepository = new LanguagePermissionRepository($this->configuration);
        $languages = $languageRepository->getLanguageRestrictions($groupId, $sourceRightId);
        if ($languages !== []) {
            $languageRepository->setLanguageRestrictions($groupId, $rightId, $languages);
        }
    }
}
