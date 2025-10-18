<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Permission;

final class CategoryPermissionService
{
    /**
     * Builds the permission-related WHERE clause for category listing.
     *
     * @param int[] $groups
     */
    public function buildWhereClause(array $groups, int $userId, bool $withInactive): string
    {
        $groupsList = $groups === [] ? '-1' : implode(', ', array_map('intval', $groups));
        $activeClause = $withInactive ? '' : 'AND fc.active = 1';
        return sprintf(
            'WHERE ( fg.group_id IN (%s) OR (fu.user_id = %d AND fg.group_id IN (%s))) %s',
            $groupsList,
            $userId,
            $groupsList,
            $activeClause,
        );
    }
}
