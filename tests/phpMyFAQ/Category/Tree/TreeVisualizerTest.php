<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Tree;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TreeVisualizer::class)]
#[UsesClass(TreePathResolver::class)]
final class TreeVisualizerTest extends TestCase
{
    private TreeVisualizer $visualizer;

    /**
     * 1 ─┬─ 2 ─── 4
     *    └─ 3
     * 5
     *
     * @var array<int, array<string, mixed>>
     */
    private const array CATEGORY_NAME = [
        1 => ['id' => 1, 'parent_id' => 0],
        2 => ['id' => 2, 'parent_id' => 1],
        3 => ['id' => 3, 'parent_id' => 1],
        4 => ['id' => 4, 'parent_id' => 2],
        5 => ['id' => 5, 'parent_id' => 0],
    ];

    /** @var array<int, array<int, array<string, mixed>>> */
    private const array CHILDREN_MAP = [
        0 => [1 => ['id' => 1], 5 => ['id' => 5]],
        1 => [2 => ['id' => 2], 3 => ['id' => 3]],
        2 => [4 => ['id' => 4]],
    ];

    protected function setUp(): void
    {
        $this->visualizer = new TreeVisualizer(new TreePathResolver());
    }

    public function testBuildTreeMarksAncestorsWithFollowingSiblingsAsVertical(): void
    {
        // Path 1 -> 2 -> 4: root 1 has sibling 5 after it, 2 has sibling 3 after it, 4 is the only child.
        $this->assertSame(
            [0 => 'vertical', 1 => 'vertical', 2 => 'space'],
            $this->visualizer->buildTree(self::CATEGORY_NAME, self::CHILDREN_MAP, 4),
        );
    }

    public function testBuildTreeMarksLastSiblingsAsSpace(): void
    {
        // Path 1 -> 3: 3 is the last child of 1.
        $this->assertSame(
            [0 => 'vertical', 1 => 'space'],
            $this->visualizer->buildTree(self::CATEGORY_NAME, self::CHILDREN_MAP, 3),
        );

        // 5 is the last root category.
        $this->assertSame([0 => 'space'], $this->visualizer->buildTree(self::CATEGORY_NAME, self::CHILDREN_MAP, 5));
    }

    public function testBuildTreeReturnsEmptyArrayForRootLevel(): void
    {
        $this->assertSame([], $this->visualizer->buildTree(self::CATEGORY_NAME, self::CHILDREN_MAP, 0));
    }

    public function testBuildTreeForUnknownCategoryTreatsItAsOrphanedRoot(): void
    {
        // An unknown id has no parent entry, so its "brothers" are the root categories
        // and it is not the last of them.
        $this->assertSame([0 => 'vertical'], $this->visualizer->buildTree(self::CATEGORY_NAME, self::CHILDREN_MAP, 99));
    }
}
