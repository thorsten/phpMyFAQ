<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Navigation;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class BreadcrumbsBuilderTest extends TestCase
{
    private BreadcrumbsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new BreadcrumbsBuilder();
    }

    public function testBuildFromIdsReturnsEmptyArrayWhenNoIds(): void
    {
        $categoryMap = [];
        $ids = [];

        $result = $this->builder->buildFromIds($categoryMap, $ids);

        $this->assertSame([], $result);
    }

    public function testBuildFromIdsReturnsSingleSegment(): void
    {
        $categoryMap = [
            1 => [
                'id' => 1,
                'name' => 'Category 1',
                'description' => 'First category',
            ],
        ];
        $ids = [1];

        $result = $this->builder->buildFromIds($categoryMap, $ids);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame('Category 1', $result[0]['name']);
        $this->assertSame('First category', $result[0]['description']);
    }

    public function testBuildFromIdsReturnsMultipleSegments(): void
    {
        $categoryMap = [
            1 => [
                'id' => 1,
                'name' => 'Root',
                'description' => 'Root category',
            ],
            2 => [
                'id' => 2,
                'name' => 'Child',
                'description' => 'Child category',
            ],
            3 => [
                'id' => 3,
                'name' => 'Grandchild',
                'description' => 'Grandchild category',
            ],
        ];
        $ids = [1, 2, 3];

        $result = $this->builder->buildFromIds($categoryMap, $ids);

        $this->assertCount(3, $result);
        $this->assertSame('Root', $result[0]['name']);
        $this->assertSame('Child', $result[1]['name']);
        $this->assertSame('Grandchild', $result[2]['name']);
    }

    public function testBuildFromIdsSkipsMissingCategories(): void
    {
        $categoryMap = [
            1 => [
                'id' => 1,
                'name' => 'Category 1',
                'description' => 'First',
            ],
            3 => [
                'id' => 3,
                'name' => 'Category 3',
                'description' => 'Third',
            ],
        ];
        $ids = [1, 2, 3]; // ID 2 is missing in map

        $result = $this->builder->buildFromIds($categoryMap, $ids);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame(3, $result[1]['id']);
    }

    public function testBuildFromIdsHandlesMissingNameAndDescription(): void
    {
        $categoryMap = [
            1 => [
                'id' => 1,
            ],
        ];
        $ids = [1];

        $result = $this->builder->buildFromIds($categoryMap, $ids);

        $this->assertCount(1, $result);
        $this->assertSame('', $result[0]['name']);
        $this->assertSame('', $result[0]['description']);
    }

    public function testBuildFromIdsWithStartpageAddsStartpageSegment(): void
    {
        $categoryMap = [];
        $ids = [];

        $result = $this->builder->buildFromIdsWithStartPage($categoryMap, $ids, 'Home', '', 'All categories');

        $this->assertCount(2, $result); // startpage + all categories
        $this->assertSame(-1, $result[0]['id']);
        $this->assertSame('Home', $result[0]['name']);
        $this->assertSame('', $result[0]['description']);
        $this->assertSame(0, $result[1]['id']);
        $this->assertSame('All categories', $result[1]['name']);
    }

    public function testBuildFromIdsWithStartpageIncludesCategorySegments(): void
    {
        $categoryMap = [
            1 => [
                'id' => 1,
                'name' => 'Category 1',
                'description' => 'First category',
            ],
            2 => [
                'id' => 2,
                'name' => 'Category 2',
                'description' => 'Second category',
            ],
        ];
        $ids = [1, 2];

        $result = $this->builder->buildFromIdsWithStartPage($categoryMap, $ids, 'Home', '', 'All categories');

        $this->assertCount(4, $result); // startpage + all categories + 2 categories
        $this->assertSame(-1, $result[0]['id']); // startpage
        $this->assertSame('Home', $result[0]['name']);
        $this->assertSame(0, $result[1]['id']); // all categories
        $this->assertSame('All categories', $result[1]['name']);
        $this->assertSame(1, $result[2]['id']);
        $this->assertSame(2, $result[3]['id']);
        $this->assertSame('Category 1', $result[2]['name']);
        $this->assertSame('Category 2', $result[3]['name']);
    }

    public function testBuildFromIdsWithStartpageSkipsMissingCategories(): void
    {
        $categoryMap = [
            1 => [
                'id' => 1,
                'name' => 'Category 1',
                'description' => 'First',
            ],
        ];
        $ids = [1, 999]; // ID 999 doesn't exist

        $result = $this->builder->buildFromIdsWithStartPage($categoryMap, $ids, 'Home', '', 'All categories');

        $this->assertCount(3, $result); // startpage + all categories + 1 category (999 is skipped)
        $this->assertSame(-1, $result[0]['id']);
        $this->assertSame('Home', $result[0]['name']);
        $this->assertSame(0, $result[1]['id']);
        $this->assertSame('All categories', $result[1]['name']);
        $this->assertSame(1, $result[2]['id']);
    }

    public function testBuildFromIdsWithStartpageHandlesMissingFields(): void
    {
        $categoryMap = [
            1 => [
                'id' => 1,
                // missing name and description
            ],
        ];
        $ids = [1];

        $result = $this->builder->buildFromIdsWithStartPage($categoryMap, $ids, 'Home', '', 'All categories');

        $this->assertCount(3, $result); // startpage + all categories + 1 category
        $this->assertSame('Home', $result[0]['name']);
        $this->assertSame('All categories', $result[1]['name']);
        $this->assertSame('', $result[2]['name']);
        $this->assertSame('', $result[2]['description']);
    }

    public function testBuildFromIdsWithStartpageWithCustomDescription(): void
    {
        $categoryMap = [];
        $ids = [];

        $result = $this->builder->buildFromIdsWithStartPage(
            $categoryMap,
            $ids,
            'Home',
            'Welcome to FAQ',
            'All categories',
        );

        $this->assertCount(2, $result); // startpage + all categories
        $this->assertSame(-1, $result[0]['id']);
        $this->assertSame('Home', $result[0]['name']);
        $this->assertSame('Welcome to FAQ', $result[0]['description']);
        $this->assertSame(0, $result[1]['id']);
        $this->assertSame('All categories', $result[1]['name']);
    }
}
