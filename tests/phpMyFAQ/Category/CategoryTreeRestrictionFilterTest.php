<?php

declare(strict_types=1);

namespace phpMyFAQ\Category;

use PHPUnit\Framework\TestCase;

final class CategoryTreeRestrictionFilterTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $tree = [
        ['id' => 1, 'name' => 'Allowed root'],
        ['id' => 2, 'name' => 'Forbidden root'],
        ['id' => 3, 'name' => 'Allowed child'],
    ];

    public function testNullMeansUnrestricted(): void
    {
        $this->assertSame($this->tree, CategoryTreeRestrictionFilter::filter($this->tree, null));
    }

    public function testKeepsOnlyAllowedCategories(): void
    {
        $filtered = CategoryTreeRestrictionFilter::filter($this->tree, [1, 3]);
        $this->assertSame([1, 3], array_column($filtered, 'id'));
    }

    public function testEmptyAllowListHidesEverything(): void
    {
        $this->assertSame([], CategoryTreeRestrictionFilter::filter($this->tree, []));
    }

    public function testNestedNullMeansUnrestricted(): void
    {
        $nested = [1 => [3 => []], 2 => []];
        $this->assertSame($nested, CategoryTreeRestrictionFilter::filterNested($nested, null));
    }

    public function testNestedKeepsOnlyAllowedBranches(): void
    {
        $nested = [1 => [3 => [], 4 => []], 2 => [5 => []]];
        $this->assertSame(
            [1 => [3 => []]],
            CategoryTreeRestrictionFilter::filterNested($nested, [1, 3, 5]),
        );
    }

    public function testNestedEmptyAllowListHidesEverything(): void
    {
        $this->assertSame([], CategoryTreeRestrictionFilter::filterNested([1 => [], 2 => []], []));
    }
}
