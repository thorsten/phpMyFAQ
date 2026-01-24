<?php

declare(strict_types=1);

namespace phpMyFAQ\Category;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CategoryCacheTest extends TestCase
{
    private CategoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new CategoryCache();
    }

    public function testGetCategoriesReturnsEmptyArrayInitially(): void
    {
        $this->assertSame([], $this->cache->getCategories());
    }

    public function testSetCategories(): void
    {
        $categories = [
            1 => ['id' => 1, 'name' => 'Category 1'],
            2 => ['id' => 2, 'name' => 'Category 2'],
        ];

        $this->cache->setCategories($categories);
        $this->assertSame($categories, $this->cache->getCategories());
    }

    public function testAddCategory(): void
    {
        $category = ['id' => 1, 'name' => 'Test Category'];
        $this->cache->addCategory(1, $category);

        $categories = $this->cache->getCategories();
        $this->assertArrayHasKey(1, $categories);
        $this->assertSame($category, $categories[1]);
    }

    public function testGetCategoryNamesReturnsEmptyArrayInitially(): void
    {
        $this->assertSame([], $this->cache->getCategoryNames());
    }

    public function testSetCategoryNames(): void
    {
        $categoryNames = [
            1 => ['id' => 1, 'name' => 'Name 1'],
            2 => ['id' => 2, 'name' => 'Name 2'],
        ];

        $this->cache->setCategoryNames($categoryNames);
        $this->assertSame($categoryNames, $this->cache->getCategoryNames());
    }

    public function testAddCategoryName(): void
    {
        $categoryName = ['id' => 1, 'name' => 'Test Name'];
        $this->cache->addCategoryName(1, $categoryName);

        $this->assertSame($categoryName, $this->cache->getCategoryName(1));
    }

    public function testGetCategoryNameReturnsEmptyArrayForNonExistent(): void
    {
        $this->assertSame([], $this->cache->getCategoryName(999));
    }

    public function testGetTreeTabReturnsEmptyArrayInitially(): void
    {
        $this->assertSame([], $this->cache->getTreeTab());
    }

    public function testSetTreeTab(): void
    {
        $treeTab = [
            ['id' => 1, 'symbol' => 'plus'],
            ['id' => 2, 'symbol' => 'minus'],
        ];

        $this->cache->setTreeTab($treeTab);
        $this->assertSame($treeTab, $this->cache->getTreeTab());
    }

    public function testAddTreeTabEntry(): void
    {
        $entry = ['id' => 1, 'symbol' => 'plus'];
        $this->cache->addTreeTabEntry($entry);

        $this->assertSame(1, $this->cache->countTreeTab());
        $this->assertSame($entry, $this->cache->getTreeTabEntry(0));
    }

    public function testUpdateTreeTabEntry(): void
    {
        $entry = ['id' => 1, 'symbol' => 'plus'];
        $this->cache->addTreeTabEntry($entry);

        $this->cache->updateTreeTabEntry(0, ['symbol' => 'minus']);

        $updated = $this->cache->getTreeTabEntry(0);
        $this->assertSame('minus', $updated['symbol']);
        $this->assertSame(1, $updated['id']);
    }

    public function testGetTreeTabEntryReturnsNullForNonExistent(): void
    {
        $this->assertNull($this->cache->getTreeTabEntry(999));
    }

    public function testCountTreeTab(): void
    {
        $this->assertSame(0, $this->cache->countTreeTab());

        $this->cache->addTreeTabEntry(['id' => 1]);
        $this->assertSame(1, $this->cache->countTreeTab());

        $this->cache->addTreeTabEntry(['id' => 2]);
        $this->assertSame(2, $this->cache->countTreeTab());
    }

    public function testGetCatTreeReturnsEmptyArrayInitially(): void
    {
        $this->assertSame([], $this->cache->getCatTree());
    }

    public function testSetCatTree(): void
    {
        $catTree = [
            ['id' => 1, 'indent' => 0],
            ['id' => 2, 'indent' => 1],
        ];

        $this->cache->setCatTree($catTree);
        $this->assertSame($catTree, $this->cache->getCatTree());
    }

    public function testGetChildrenReturnsEmptyArrayInitially(): void
    {
        $this->assertSame([], $this->cache->getChildren());
    }

    public function testSetChildren(): void
    {
        $children = [
            0 => [
                1 => ['id' => 1, 'name' => 'Child 1'],
            ],
        ];

        $this->cache->setChildren($children);
        $this->assertSame($children, $this->cache->getChildren());
    }

    public function testAddChild(): void
    {
        $child = ['id' => 1, 'name' => 'Child'];
        $this->cache->addChild(0, 1, $child);

        $children = $this->cache->getChildren();
        $this->assertArrayHasKey(0, $children);
        $this->assertArrayHasKey(1, $children[0]);
    }

    public function testGetChildrenOfParent(): void
    {
        $child1 = ['id' => 1, 'name' => 'Child 1'];
        $child2 = ['id' => 2, 'name' => 'Child 2'];

        $this->cache->addChild(0, 1, $child1);
        $this->cache->addChild(0, 2, $child2);

        $children = $this->cache->getChildrenOfParent(0);
        $this->assertCount(2, $children);
        $this->assertArrayHasKey(1, $children);
        $this->assertArrayHasKey(2, $children);
    }

    public function testGetChildrenOfParentReturnsEmptyArrayForNonExistent(): void
    {
        $this->assertSame([], $this->cache->getChildrenOfParent(999));
    }

    public function testClear(): void
    {
        // Populate cache
        $this->cache->addCategory(1, ['id' => 1]);
        $this->cache->addCategoryName(1, ['id' => 1]);
        $this->cache->addTreeTabEntry(['id' => 1]);
        $this->cache->setCatTree([['id' => 1]]);
        $child = ['id' => 1];
        $this->cache->addChild(0, 1, $child);

        // Clear
        $this->cache->clear();

        // Verify all empty
        $this->assertSame([], $this->cache->getCategories());
        $this->assertSame([], $this->cache->getCategoryNames());
        $this->assertSame([], $this->cache->getTreeTab());
        $this->assertSame([], $this->cache->getCatTree());
        $this->assertSame([], $this->cache->getChildren());
    }

    public function testAddChildWithReference(): void
    {
        $child = ['id' => 1, 'name' => 'Original'];
        $this->cache->addChild(0, 1, $child);

        // Modify the original
        $child['name'] = 'Modified';

        // The cached reference should be updated
        $children = $this->cache->getChildrenOfParent(0);
        $this->assertSame('Modified', $children[1]['name']);
    }
}
