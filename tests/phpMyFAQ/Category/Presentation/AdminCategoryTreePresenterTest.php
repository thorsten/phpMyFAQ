<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Presentation;

use phpMyFAQ\Category\Tree\CategoryValidator;
use phpMyFAQ\Category\Tree\TreeBuilder;
use phpMyFAQ\Category\Tree\TreePathResolver;
use phpMyFAQ\Category\Tree\TreeVisualizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminCategoryTreePresenter::class)]
#[UsesClass(TreeBuilder::class)]
#[UsesClass(TreePathResolver::class)]
#[UsesClass(TreeVisualizer::class)]
#[UsesClass(CategoryValidator::class)]
final class AdminCategoryTreePresenterTest extends TestCase
{
    private AdminCategoryTreePresenter $presenter;

    private TreeBuilder $treeBuilder;

    /**
     * 1 ─┬─ 2 ─── 4
     *    └─ 3
     * 5
     *
     * @var array<int, array<string, mixed>>
     */
    private const array CATEGORY_NAME = [
        1 => ['id' => 1, 'parent_id' => 0, 'name' => 'Root'],
        2 => ['id' => 2, 'parent_id' => 1, 'name' => 'Child A'],
        3 => ['id' => 3, 'parent_id' => 1, 'name' => 'Child B'],
        4 => ['id' => 4, 'parent_id' => 2, 'name' => 'Grandchild'],
        5 => ['id' => 5, 'parent_id' => 0, 'name' => 'Second root'],
    ];

    /** @var array<int, array<int, array<string, mixed>>> */
    private const array CHILDREN_MAP = [
        0 => [1 => ['id' => 1], 5 => ['id' => 5]],
        1 => [2 => ['id' => 2], 3 => ['id' => 3]],
        2 => [4 => ['id' => 4]],
    ];

    protected function setUp(): void
    {
        $this->presenter = new AdminCategoryTreePresenter();
        $this->treeBuilder = new TreeBuilder();
    }

    public function testTransformFromRootProducesDepthFirstLinearList(): void
    {
        $entries = $this->presenter->transform($this->treeBuilder, self::CATEGORY_NAME, self::CHILDREN_MAP, 0);

        $this->assertSame([1, 2, 4, 3, 5], array_column($entries, 'id'));
        $this->assertSame([0, 1, 2, 1, 0], array_column($entries, 'level'));
    }

    public function testTransformKeepsOriginalRowDataAndAddsPresentationFields(): void
    {
        $entries = $this->presenter->transform($this->treeBuilder, self::CATEGORY_NAME, self::CHILDREN_MAP, 2);

        $this->assertCount(2, $entries);

        $childA = $entries[0];
        $this->assertSame('Child A', $childA['name']);
        $this->assertSame(1, $childA['parent_id']);
        $this->assertSame(1, $childA['level']);
        $this->assertSame([4], $childA['children']);
        $this->assertSame([0 => 'vertical', 1 => 'vertical'], $childA['tree']);
        $this->assertSame('medium', $childA['symbol']);

        $grandchild = $entries[1];
        $this->assertSame(4, $grandchild['id']);
        $this->assertSame(2, $grandchild['level']);
        $this->assertSame([], $grandchild['children']);
        $this->assertSame('angle', $grandchild['symbol']);
    }

    public function testSymbolIsAngleForLastSiblingAndMediumOtherwise(): void
    {
        $entries = $this->presenter->transform($this->treeBuilder, self::CATEGORY_NAME, self::CHILDREN_MAP, 0);
        $symbols = array_combine(array_column($entries, 'id'), array_column($entries, 'symbol'));

        $this->assertSame(
            [1 => 'medium', 2 => 'medium', 4 => 'angle', 3 => 'angle', 5 => 'angle'],
            $symbols,
        );
    }

    public function testTransformOfLeafReturnsSingleEntry(): void
    {
        $entries = $this->presenter->transform($this->treeBuilder, self::CATEGORY_NAME, self::CHILDREN_MAP, 5);

        $this->assertCount(1, $entries);
        $this->assertSame(5, $entries[0]['id']);
        $this->assertSame(0, $entries[0]['level']);
        $this->assertSame([0 => 'space'], $entries[0]['tree']);
    }

    public function testTransformOfUnknownCategoryReturnsEmptyList(): void
    {
        $this->assertSame([], $this->presenter->transform($this->treeBuilder, self::CATEGORY_NAME, self::CHILDREN_MAP, 99));
    }

    public function testTransformWithEmptyTreeReturnsEmptyList(): void
    {
        $this->assertSame([], $this->presenter->transform($this->treeBuilder, [], [], 0));
    }
}
