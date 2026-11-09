<?php

/**
 * Category permission service class
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
 * @since     2025-10-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Category\Permission;

final class CategoryPermissionService
{
    /**
     * Builds the permission-related WHERE clause for category listing (active categories only).
     *
     * @param int[] $groups
     */
    public function buildWhereClause(array $groups, int $userId): string
    {
        return $this->buildPermissionWhereClause($groups, $userId, activeClause: 'AND fc.active = 1');
    }

    /**
     * Builds the permission-related WHERE clause for category listing (including inactive categories).
     *
     * @param int[] $groups
     */
    public function buildWhereClauseWithInactive(array $groups, int $userId): string
    {
        return $this->buildPermissionWhereClause($groups, $userId, activeClause: '');
    }

    /**
     * Internal helper to build the WHERE clause with permission logic.
     *
     * A category is visible when one of the requester's groups is granted AND the user grant
     * covers the requester: -1 (or no row at all) means "all users". Both restrictions narrow
     * the result on their own; the former "group OR (user AND group)" clause was satisfied by
     * the group term alone, so a user restriction never hid anything.
     *
     * @param int[] $groups
     */
    private function buildPermissionWhereClause(array $groups, int $userId, string $activeClause): string
    {
        $groupsList = $groups === []
            ? '-1'
            : implode(separator: ', ', array: array_map(callback: intval(...), array: $groups));
        return strtr('WHERE ( fg.group_id IN ({groups}) AND (fu.user_id IS NULL OR fu.user_id = -1 OR fu.user_id = {userId}) ) {active}', [
            '{groups}' => $groupsList,
            '{userId}' => (string) $userId,
            '{active}' => $activeClause,
        ]);
    }
}
