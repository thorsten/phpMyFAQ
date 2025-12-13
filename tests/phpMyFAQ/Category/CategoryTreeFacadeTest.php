<?php

declare(strict_types=1);

namespace phpMyFAQ\Category;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use phpMyFAQ\Category\Tree\TreeBuilder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class CategoryTreeFacadeTest extends TestCase
{
    private TreeBuilder $treeBuilder;
    private CategoryTreeFacade $facade;

    protected function setUp(): void
    {
        $this->treeBuilder = $this->createMock(TreeBuilder::class);
        $this->facade = new CategoryTreeFacade($this->treeBuilder);
    }

    public function testBuildLinearTree(): void
    {
        $categories = [
            1 => ['id' => 1, 'parent_id' => 0],
            2 => ['id' => 2, 'parent_id' => 1],
        ];
        $expected = [
            ['id' => 1, 'indent' => 0],
            ['id' => 2, 'indent' => 1],
        ];

        $this->treeBuilder->expects($this->once())
            ->method('buildLinearTree')
            ->with($categories, 0, 0)
            ->willReturn($expected);

        $result = $this->facade->buildLinearTree($categories);
        $this->assertSame($expected, $result);
    }

    public function testBuildLinearTreeWithCustomParams(): void
    {
        $categories = [1 => ['id' => 1]];
        $expected = [['id' => 1, 'indent' => 2]];

        $this->treeBuilder->expects($this->once())
            ->method('buildLinearTree')
            ->with($categories, 5, 2)
            ->willReturn($expected);

        $result = $this->facade->buildLinearTree($categories, 5, 2);
        $this->assertSame($expected, $result);
    }

    public function testBuildAdminCategoryTree(): void
    {
        $categories = [
            1 => ['id' => 1, 'parent_id' => 0],
            2 => ['id' => 2, 'parent_id' => 1],
        ];
        $expected = [1 => [2 => []]];

        $this->treeBuilder->expects($this->once())
            ->method('buildAdminCategoryTree')
            ->with($categories, 0)
            ->willReturn($expected);

        $result = $this->facade->buildAdminCategoryTree($categories);
        $this->assertSame($expected, $result);
    }

    public function testBuildAdminCategoryTreeWithParentId(): void
    {
        $categories = [2 => ['id' => 2, 'parent_id' => 1]];
        $expected = [2 => []];

        $this->treeBuilder->expects($this->once())
            ->method('buildAdminCategoryTree')
            ->with($categories, 1)
            ->willReturn($expected);

        $result = $this->facade->buildAdminCategoryTree($categories, 1);
        $this->assertSame($expected, $result);
    }

    public function testGetChildren(): void
    {
        $childrenMap = [
            0 => [1 => ['id' => 1], 2 => ['id' => 2]],
        ];
        $expected = [1, 2];

        $this->treeBuilder->expects($this->once())
            ->method('getChildren')
            ->with($childrenMap, 0)
            ->willReturn($expected);

        $result = $this->facade->getChildren($childrenMap, 0);
        $this->assertSame($expected, $result);
    }

    public function testGetChildNodes(): void
    {
        $childrenMap = [
            0 => [1 => ['id' => 1]],
            1 => [2 => ['id' => 2], 3 => ['id' => 3]],
        ];
        $expected = [1, 2, 3];

        $this->treeBuilder->expects($this->once())
            ->method('getChildNodes')
            ->with($childrenMap, 0)
            ->willReturn($expected);

        $result = $this->facade->getChildNodes($childrenMap, 0);
        $this->assertSame($expected, $result);
    }

    public function testGetLevelOf(): void
    {
        $categoryNames = [
            1 => ['id' => 1, 'parent_id' => 0],
            2 => ['id' => 2, 'parent_id' => 1],
            3 => ['id' => 3, 'parent_id' => 2],
        ];

        $this->treeBuilder->expects($this->once())
            ->method('computeLevel')
            ->with($categoryNames, 3)
            ->willReturn(2);

        $result = $this->facade->getLevelOf($categoryNames, 3);
        $this->assertSame(2, $result);
    }

    public function testGetNodes(): void
    {
        $categoryNames = [
            1 => ['id' => 1, 'parent_id' => 0],
            2 => ['id' => 2, 'parent_id' => 1],
            3 => ['id' => 3, 'parent_id' => 2],
        ];
        $expected = [1, 2, 3];

        $this->treeBuilder->expects($this->once())
            ->method('getNodes')
            ->with($categoryNames, 3)
            ->willReturn($expected);

        $result = $this->facade->getNodes($categoryNames, 3);
        $this->assertSame($expected, $result);
    }

    public function testConstructorWithoutTreeBuilder(): void
    {
        $facade = new CategoryTreeFacade();

        // Verify it works by calling a method
        $categoryNames = [1 => ['id' => 1, 'parent_id' => 0]];
        $result = $facade->getLevelOf($categoryNames, 1);

        $this->assertIsInt($result);
    }
}

