<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Tree;

use PHPUnit\Framework\TestCase;

final class TreeBuilderTest extends TestCase
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

        $this->assertSame([
            1 => [],
            2 => [],
            3 => [],
        ], $tree);
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
}

