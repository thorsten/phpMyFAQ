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
            'WHERE ( fg.group_id IN (1, 2) OR (fu.user_id = 42 AND fg.group_id IN (1, 2)))',
            $sql,
        );
        $this->assertStringContainsString('AND fc.active = 1', $sql);
    }

    public function testBuildWhereClauseWithInactiveTrueOmitsActiveFilter(): void
    {
        $service = new CategoryPermissionService();
        $sql = $service->buildWhereClauseWithInactive([], 0);

        $this->assertSame('WHERE ( fg.group_id IN (-1) OR (fu.user_id = 0 AND fg.group_id IN (-1))) ', $sql);
    }
}
