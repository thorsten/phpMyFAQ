<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Tree;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TreePathResolver::class)]
final class TreePathResolverTest extends TestCase
{
    private TreePathResolver $resolver;

    /**
     * 1 ─┬─ 2 ─── 4
     *    └─ 3
     * 5 (second root)
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
        $this->resolver = new TreePathResolver();
    }

    public function testGetNodesReturnsPathFromRootToCategory(): void
    {
        $this->assertSame([1, 2, 4], $this->resolver->getNodes(self::CATEGORY_NAME, 4));
        $this->assertSame([1, 3], $this->resolver->getNodes(self::CATEGORY_NAME, 3));
        $this->assertSame([1], $this->resolver->getNodes(self::CATEGORY_NAME, 1));
    }

    public function testGetNodesReturnsEmptyArrayForNonPositiveId(): void
    {
        $this->assertSame([], $this->resolver->getNodes(self::CATEGORY_NAME, 0));
        $this->assertSame([], $this->resolver->getNodes(self::CATEGORY_NAME, -3));
    }

    public function testGetNodesReturnsOnlyTheCategoryWhenItIsUnknown(): void
    {
        $this->assertSame([99], $this->resolver->getNodes(self::CATEGORY_NAME, 99));
    }

    public function testGetNodesStopsAtMissingOrSelfReferencingParent(): void
    {
        $categories = [
            7 => ['id' => 7, 'parent_id' => 42], // parent not in the map
            8 => ['id' => 8, 'parent_id' => 8], // points at itself
            9 => ['id' => 9], // no parent_id key at all
        ];

        $this->assertSame([7], $this->resolver->getNodes($categories, 7));
        $this->assertSame([8], $this->resolver->getNodes($categories, 8));
        $this->assertSame([9], $this->resolver->getNodes($categories, 9));
    }

    public function testGetNodesTerminatesOnParentCycle(): void
    {
        $categories = [
            1 => ['id' => 1, 'parent_id' => 2],
            2 => ['id' => 2, 'parent_id' => 1],
        ];

        // Walking up from 1 reaches 2, whose parent 1 is already on the path: stop there.
        $this->assertSame([2, 1], $this->resolver->getNodes($categories, 1));
    }

    public function testGetChildrenReturnsDirectChildIds(): void
    {
        $this->assertSame([2, 3], $this->resolver->getChildren(self::CHILDREN_MAP, 1));
        $this->assertSame([4], $this->resolver->getChildren(self::CHILDREN_MAP, 2));
        $this->assertSame([1, 5], $this->resolver->getChildren(self::CHILDREN_MAP, 0));
    }

    public function testGetChildrenReturnsEmptyArrayForLeafOrUnknownCategory(): void
    {
        $this->assertSame([], $this->resolver->getChildren(self::CHILDREN_MAP, 4));
        $this->assertSame([], $this->resolver->getChildren(self::CHILDREN_MAP, 99));
    }

    public function testGetChildNodesReturnsAllDescendantsDepthFirst(): void
    {
        $this->assertSame([2, 4, 3], $this->resolver->getChildNodes(self::CHILDREN_MAP, 1));
        $this->assertSame([1, 2, 4, 3, 5], $this->resolver->getChildNodes(self::CHILDREN_MAP, 0));
        $this->assertSame([], $this->resolver->getChildNodes(self::CHILDREN_MAP, 4));
    }

    public function testGetBrothersIncludesTheCategoryItself(): void
    {
        $this->assertSame([2, 3], $this->resolver->getBrothers(self::CATEGORY_NAME, self::CHILDREN_MAP, 2));
        $this->assertSame([2, 3], $this->resolver->getBrothers(self::CATEGORY_NAME, self::CHILDREN_MAP, 3));
        $this->assertSame([1, 5], $this->resolver->getBrothers(self::CATEGORY_NAME, self::CHILDREN_MAP, 1));
    }

    public function testGetBrothersOfUnknownCategoryFallsBackToRootLevel(): void
    {
        $this->assertSame([1, 5], $this->resolver->getBrothers(self::CATEGORY_NAME, self::CHILDREN_MAP, 99));
    }

    public function testComputeLevelCountsAncestors(): void
    {
        $this->assertSame(0, $this->resolver->computeLevel(self::CATEGORY_NAME, 1));
        $this->assertSame(1, $this->resolver->computeLevel(self::CATEGORY_NAME, 2));
        $this->assertSame(2, $this->resolver->computeLevel(self::CATEGORY_NAME, 4));
        $this->assertSame(0, $this->resolver->computeLevel(self::CATEGORY_NAME, 99));
    }

    public function testComputeLevelTerminatesOnParentCycle(): void
    {
        $categories = [
            1 => ['id' => 1, 'parent_id' => 2],
            2 => ['id' => 2, 'parent_id' => 1],
        ];

        $this->assertSame(2, $this->resolver->computeLevel($categories, 1));
    }
}
