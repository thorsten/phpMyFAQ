<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Tree;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TreeBuilderTest extends TestCase
{
    public function testBuildAdminCategoryTreeFlat(): void
    {
        $builder = new TreeBuilder();
        $categories = [
            1 => ['id' => 1, 'parent_id' => 0, 'name' => 'Root 1'],
            2 => ['id' => 2, 'parent_id' => 0, 'name' => 'Root 2'],
            3 => ['id' => 3, 'parent_id' => 0, 'name' => 'Root 3'],
        ];

        $tree = $builder->buildAdminCategoryTree($categories);

        $this->assertSame(
            [
                1 => [],
                2 => [],
                3 => [],
            ],
            $tree,
        );
    }

    public function testBuildLinearTreeNested(): void
    {
        $builder = new TreeBuilder();
        $categories = [
            1 => ['id' => 1, 'parent_id' => 0, 'name' => 'Root'],
            2 => ['id' => 2, 'parent_id' => 1, 'name' => 'Child A'],
            3 => ['id' => 3, 'parent_id' => 1, 'name' => 'Child B'],
        ];

        $result = $builder->buildLinearTree($categories);

        $this->assertSame(3, count($result));
        $this->assertSame(0, $result[0]['indent']);
        $this->assertSame(1, $result[1]['indent']);
        $this->assertSame(1, $result[2]['indent']);
        $this->assertSame(1, $result[1]['parent_id']);
        $this->assertSame(1, $result[2]['parent_id']);
    }

    public function testBuildAdminCategoryTreeSkipsEntriesWithoutIdOrParentId(): void
    {
        $builder = new TreeBuilder();
        $categories = [
            1 => ['id' => 1, 'parent_id' => 0, 'name' => 'Root'],
            2 => ['id' => 2, 'name' => 'No parent id'],
            3 => ['parent_id' => 0, 'name' => 'No id'],
            4 => 'not an array',
        ];

        $tree = $builder->buildAdminCategoryTree($categories);

        $this->assertSame([1 => []], $tree);
    }

    public function testGetBrothersReturnsTheSiblingsOfACategory(): void
    {
        $builder = new TreeBuilder();
        $categoryNames = [
            1 => ['id' => 1, 'parent_id' => 0],
            2 => ['id' => 2, 'parent_id' => 1],
            3 => ['id' => 3, 'parent_id' => 1],
        ];
        $children = [
            0 => [1 => $categoryNames[1]],
            1 => [2 => $categoryNames[2], 3 => $categoryNames[3]],
        ];

        $this->assertSame([2, 3], $builder->getBrothers($categoryNames, $children, 2));
        $this->assertSame([1], $builder->getBrothers($categoryNames, $children, 1));
    }

    public function testBuildTreeMarksTheLastSiblingWithSpaceAndOthersWithVertical(): void
    {
        $builder = new TreeBuilder();
        $categoryNames = [
            1 => ['id' => 1, 'parent_id' => 0],
            2 => ['id' => 2, 'parent_id' => 1],
            3 => ['id' => 3, 'parent_id' => 1],
            4 => ['id' => 4, 'parent_id' => 2],
        ];
        $children = [
            0 => [1 => $categoryNames[1]],
            1 => [2 => $categoryNames[2], 3 => $categoryNames[3]],
            2 => [4 => $categoryNames[4]],
        ];

        $tree = $builder->buildTree($categoryNames, $children, 4);

        $this->assertNotEmpty($tree);
        $this->assertContains('vertical', $tree);
        $this->assertContains('space', $tree);
    }
}
