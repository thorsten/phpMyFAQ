<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Permission;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use SQLite3;

#[AllowMockObjectsWithoutExpectations]
class CategoryPermissionServiceTest extends TestCase
{
    public function testBuildWhereClauseWithInactiveFalseAddsActiveFilter(): void
    {
        $service = new CategoryPermissionService();
        $sql = $service->buildWhereClause([1, 2], 42);

        $this->assertStringStartsWith(
            'WHERE ( fg.group_id IN (1, 2) AND ( fu.user_id = -1 OR fu.user_id = 42 ) )',
            $sql,
        );
        $this->assertStringContainsString('AND fc.active = 1', $sql);
    }

    public function testBuildWhereClauseWithInactiveTrueOmitsActiveFilter(): void
    {
        $service = new CategoryPermissionService();
        $sql = $service->buildWhereClauseWithInactive([], 0);

        $this->assertSame('WHERE ( fg.group_id IN (-1) AND ( fu.user_id = -1 OR fu.user_id = 0 ) ) ', $sql);
    }

    public function testBuildWhereClauseCastsGroupIdsToIntegers(): void
    {
        $service = new CategoryPermissionService();
        $sql = $service->buildWhereClauseWithInactive(['1) OR 1=1 -- ', '2'], 5);

        $this->assertStringContainsString('fg.group_id IN (1, 2)', $sql);
    }

    /**
     * Regression test: a category restricted to a single user must not be listed for anybody else.
     * Runs the generated clause against the real table layout (basic permission level: every
     * category stores group -1, requesters hold group -1).
     */
    public function testUserRestrictionNarrowsVisibility(): void
    {
        $db = new SQLite3(':memory:');
        $db->exec('CREATE TABLE faqcategories (id INT, lang TEXT, active INT)');
        $db->exec('CREATE TABLE faqcategory_group (category_id INT, group_id INT)');
        $db->exec('CREATE TABLE faqcategory_user (category_id INT, user_id INT)');
        $db->exec("INSERT INTO faqcategories VALUES (1, 'en', 1), (2, 'en', 1), (3, 'en', 1), (4, 'en', 0)");
        // 1: public, 2: user 7 only, 3: group 9 only, 4: public but inactive
        $db->exec('INSERT INTO faqcategory_group VALUES (1, -1), (2, -1), (3, 9), (4, -1)');
        $db->exec('INSERT INTO faqcategory_user VALUES (1, -1), (2, 7), (3, -1), (4, -1)');

        $service = new CategoryPermissionService();
        $visible = function (array $groups, int $userId) use ($db, $service): array {
            $where = $service->buildWhereClause($groups, $userId);
            $result = $db->query(
                'SELECT DISTINCT fc.id FROM faqcategories fc '
                . 'LEFT JOIN faqcategory_group fg ON fc.id = fg.category_id '
                . 'LEFT JOIN faqcategory_user fu ON fc.id = fu.category_id '
                . $where
                . ' ORDER BY fc.id',
            );
            $ids = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $ids[] = (int) $row['id'];
            }

            return $ids;
        };

        $this->assertSame([1], $visible([-1], -1), 'anonymous');
        $this->assertSame([1], $visible([-1], 5), 'other user');
        $this->assertSame([1, 2], $visible([-1], 7), 'restricted user');
        $this->assertSame([1, 3], $visible([-1, 9], 5), 'group member');
        $this->assertSame([1, 2, 3], $visible([-1, 9], 7), 'restricted user in group');
    }
}
