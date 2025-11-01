<?php

/**
 * Permission context for categories.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-10-20
 */

declare(strict_types=1);

namespace phpMyFAQ\Category;

/**
 * Manages permission-related data for categories.
 * Consolidates user, group, owner, and moderator information.
 */
final class CategoryPermissionContext
{
    /**
     * User ID.
     */
    private int $user = -1;

    /**
     * Groups.
     * @var int[]
     */
    private array $groups = [-1];

    /**
     * Entity owners
     * @var array<int, int>
     */
    private array $owner = [];

    /**
     * Entity moderators
     * @var array<int, int>
     */
    private array $moderators = [];

    public function __construct(array $groups = [], int $user = -1)
    {
        $this->setGroups($groups);
        $this->setUser($user);
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    /**
     * @return int[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): void
    {
        if ($groups === []) {
            $groups = [-1];
        }
        $this->groups = $groups;
    }

    public function setOwner(int $categoryId, int $userId): void
    {
        $this->owner[$categoryId] = $userId;
    }

    public function getOwner(?int $categoryId = null): int
    {
        if ($categoryId === null) {
            return 1;
        }

        return $this->owner[$categoryId] ?? 1;
    }

    public function setModerator(int $categoryId, int $groupId): void
    {
        $this->moderators[$categoryId] = $groupId;
    }

    public function getModeratorGroupId(int $categoryId): int
    {
        return $this->moderators[$categoryId] ?? 0;
    }

    /**
     * Clears all permission data.
     */
    public function clear(): void
    {
        $this->user = -1;
        $this->groups = [-1];
        $this->owner = [];
        $this->moderators = [];
    }
}
