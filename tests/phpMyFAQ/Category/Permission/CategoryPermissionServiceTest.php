<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Permission;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CategoryPermissionServiceTest extends TestCase
{
    public function testBuildWhereClauseWithInactiveFalseAddsActiveFilter(): void
    {
        $service = new CategoryPermissionService();
        $sql = $service->buildWhereClause([1, 2], 42);

        $this->assertStringStartsWith(
            'WHERE ( fg.group_id IN (1, 2) AND (fu.user_id IS NULL OR fu.user_id = -1 OR fu.user_id = 42) )',
            $sql,
        );
        $this->assertStringContainsString('AND fc.active = 1', $sql);
    }

    public function testBuildWhereClauseWithInactiveTrueOmitsActiveFilter(): void
    {
        $service = new CategoryPermissionService();
        $sql = $service->buildWhereClauseWithInactive([], 0);

        $this->assertSame(
            'WHERE ( fg.group_id IN (-1) AND (fu.user_id IS NULL OR fu.user_id = -1 OR fu.user_id = 0) ) ',
            $sql,
        );
    }

    /**
     * A user restriction must narrow the result on its own. The former clause ANDed the user
     * term with the group term it was ORed with, so "restricted users" never hid anything.
     */
    public function testUserRestrictionIsNotSwallowedByTheGroupTerm(): void
    {
        $service = new CategoryPermissionService();
        $sql = $service->buildWhereClause([-1], 5);

        $this->assertStringNotContainsString('OR (fu.user_id', $sql);
        $this->assertSame(
            'WHERE ( fg.group_id IN (-1) AND (fu.user_id IS NULL OR fu.user_id = -1 OR fu.user_id = 5) ) AND fc.active = 1',
            $sql,
        );
    }
}
