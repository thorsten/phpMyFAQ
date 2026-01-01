<?php

namespace phpMyFAQ\Category;

use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use stdClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Class OrderTest
 */
#[AllowMockObjectsWithoutExpectations]
class OrderTest extends TestCase
{
    private Order $order;
    private Configuration $configurationMock;
    private DatabaseDriver $databaseMock;

    protected function setUp(): void
    {
        $this->databaseMock = $this->createMock(DatabaseDriver::class);
        $this->configurationMock = $this->createMock(Configuration::class);

        $this->configurationMock->expects($this->any())
            ->method('getDb')
            ->willReturn($this->databaseMock);

        $this->order = new Order($this->configurationMock);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Order::class, $this->order);
    }

    public function testAddSuccess(): void
    {
        $categoryId = 123;
        $parentId = 456;
        $nextId = 10;

        $this->databaseMock->expects($this->once())
            ->method('nextId')
            ->with('faqcategory_order', 'position')
            ->willReturn($nextId);

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('INSERT INTO faqcategory_order'))
            ->willReturn(true);

        $result = $this->order->add($categoryId, $parentId);
        $this->assertTrue($result);
    }

    public function testAddFailure(): void
    {
        $categoryId = 123;
        $parentId = 456;
        $nextId = 10;

        $this->databaseMock->expects($this->once())
            ->method('nextId')
            ->with('faqcategory_order', 'position')
            ->willReturn($nextId);

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->willReturn(false);

        $result = $this->order->add($categoryId, $parentId);
        $this->assertFalse($result);
    }

    public function testRemoveSuccess(): void
    {
        $categoryId = 123;

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM faqcategory_order WHERE category_id = 123'))
            ->willReturn(true);

        $result = $this->order->remove($categoryId);
        $this->assertTrue($result);
    }

    public function testRemoveFailure(): void
    {
        $categoryId = 123;

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->willReturn(false);

        $result = $this->order->remove($categoryId);
        $this->assertFalse($result);
    }

    public function testSetCategoryTreeWithEmptyTree(): void
    {
        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM faqcategory_order'));

        $this->order->setCategoryTree([]);
    }

    public function testSetCategoryTreeWithSimpleTree(): void
    {
        $category1 = new stdClass();
        $category1->id = 1;
        $category1->children = [];

        $category2 = new stdClass();
        $category2->id = 2;
        $category2->children = [];

        $categoryTree = [$category1, $category2];

        // Expect DELETE query first, then INSERT queries
        $this->databaseMock->expects($this->exactly(3))
            ->method('query');

        $this->order->setCategoryTree($categoryTree);
    }

    public function testSetCategoryTreeWithNestedTree(): void
    {
        $childCategory = new stdClass();
        $childCategory->id = 2;
        $childCategory->children = [];

        $parentCategory = new stdClass();
        $parentCategory->id = 1;
        $parentCategory->children = [$childCategory];

        $categoryTree = [$parentCategory];

        // Expect DELETE query first, then INSERT queries for parent and child
        $this->databaseMock->expects($this->exactly(3))
            ->method('query');

        $this->order->setCategoryTree($categoryTree);
    }

    public function testSetCategoryTreeWithInvalidId(): void
    {
        $category = new stdClass();
        $category->id = 0; // Invalid ID
        $category->children = [];

        $categoryTree = [$category];

        // Only expect DELETE query, no INSERT because ID is invalid
        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DELETE FROM faqcategory_order'));

        $this->order->setCategoryTree($categoryTree);
    }

    public function testGetCategoryTreeEmpty(): void
    {
        $result = $this->order->getCategoryTree([], 0);
        $this->assertEquals([], $result);
    }

    public function testGetCategoryTreeWithCategories(): void
    {
        $categories = [
            ['category_id' => '1', 'parent_id' => '0'],
            ['category_id' => '2', 'parent_id' => '1'],
            ['category_id' => '3', 'parent_id' => '0'],
            ['category_id' => '4', 'parent_id' => '2'],
        ];

        $result = $this->order->getCategoryTree($categories, 0);

        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayHasKey(2, $result[1]);
        $this->assertArrayHasKey(4, $result[1][2]);
    }

    public function testGetCategoryTreeWithSpecificParent(): void
    {
        $categories = [
            ['category_id' => '1', 'parent_id' => '0'],
            ['category_id' => '2', 'parent_id' => '1'],
            ['category_id' => '3', 'parent_id' => '1'],
        ];

        $result = $this->order->getCategoryTree($categories, 1);

        $this->assertArrayHasKey(2, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayNotHasKey(1, $result);
    }

    public function testGetCategoryTreeWithSelfReference(): void
    {
        // Test case where a category references itself as parent
        $categories = [
            ['category_id' => '1', 'parent_id' => '0'],
            ['category_id' => '2', 'parent_id' => '2'], // Self-reference
            ['category_id' => '3', 'parent_id' => '0'],
        ];

        $result = $this->order->getCategoryTree($categories, 0);

        // Should contain categories 1 and 3, but not 2 (self-referencing)
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayNotHasKey(2, $result);
    }

    public function testGetCategoryTreeWithCircularReference(): void
    {
        // Test case where categories have circular references: 2 -> 1 and 1 -> 2
        // This simulates corrupt data where two categories reference each other as parent
        $categories = [
            ['category_id' => '1', 'parent_id' => '2'], // Category 1's parent is 2
            ['category_id' => '2', 'parent_id' => '1'], // Category 2's parent is 1 (circular!)
            ['category_id' => '3', 'parent_id' => '0'],
        ];

        $result = $this->order->getCategoryTree($categories, 0);

        // Should handle the circular reference gracefully
        // Only category 3 should appear at root level since 1 and 2 are in a circular loop
        $this->assertArrayHasKey(3, $result);
        $this->assertCount(1, $result);
    }

    public function testGetCategoryTreeWithComplexCircularReference(): void
    {
        // Test case: Category chain where the last one references an earlier one in the chain
        // 1 (root) -> 2 -> 3 -> 4, but then 4 -> 2 (creates a loop: 2 -> 3 -> 4 -> 2)
        $categories = [
            ['category_id' => '1', 'parent_id' => '0'],
            ['category_id' => '2', 'parent_id' => '1'],
            ['category_id' => '3', 'parent_id' => '2'],
            ['category_id' => '4', 'parent_id' => '3'],
            ['category_id' => '5', 'parent_id' => '2'], // Another child of 2 (valid)
            // Simulate someone incorrectly updating category 3 to make it a child of 4
            // In real DB this would be an update, but we simulate by having it appear again
            ['category_id' => '3', 'parent_id' => '4'], // This creates: 3 -> 4 -> (back to 3 via next entry)
        ];

        $result = $this->order->getCategoryTree($categories, 0);

        // Should handle gracefully and not cause infinite recursion
        // The tree should build up to the point where the circular reference is detected
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result[1]);
        // Category 3 should be under 2, and when we try to add it again under 4, it's skipped
        $this->assertArrayHasKey(3, $result[1][2]);
    }

    public function testGetParentIdFound(): void
    {
        $childCategory = new stdClass();
        $childCategory->id = 2;
        $childCategory->children = [];

        $parentCategory = new stdClass();
        $parentCategory->id = 1;
        $parentCategory->children = [$childCategory];

        $categoryTree = [$parentCategory];

        $result = $this->order->getParentId($categoryTree, 2);
        $this->assertEquals(1, $result);
    }

    public function testGetParentIdNotFound(): void
    {
        $category = new stdClass();
        $category->id = 1;
        $category->children = [];

        $categoryTree = [$category];

        $result = $this->order->getParentId($categoryTree, 999);
        $this->assertNull($result);
    }

    public function testGetParentIdTopLevel(): void
    {
        $category = new stdClass();
        $category->id = 1;
        $category->children = [];

        $categoryTree = [$category];

        $result = $this->order->getParentId($categoryTree, 1);
        $this->assertEquals(0, $result);
    }

    public function testGetParentIdDeepNesting(): void
    {
        $deepChild = new stdClass();
        $deepChild->id = 3;
        $deepChild->children = [];

        $child = new stdClass();
        $child->id = 2;
        $child->children = [$deepChild];

        $parent = new stdClass();
        $parent->id = 1;
        $parent->children = [$child];

        $categoryTree = [$parent];

        $result = $this->order->getParentId($categoryTree, 3);
        $this->assertEquals(2, $result);
    }

    public function testGetAllCategoriesEmpty(): void
    {
        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT category_id, parent_id, position FROM faqcategory_order ORDER BY position'))
            ->willReturn('mock_result');

        $this->databaseMock->expects($this->exactly(1))
            ->method('fetchArray')
            ->with('mock_result')
            ->willReturn(false);

        $result = $this->order->getAllCategories();
        $this->assertEquals([], $result);
    }

    public function testGetAllCategoriesWithData(): void
    {
        $mockData = [
            ['category_id' => '1', 'parent_id' => '0', 'position' => '1'],
            ['category_id' => '2', 'parent_id' => '1', 'position' => '2'],
        ];

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->willReturn('mock_result');

        $this->databaseMock->expects($this->exactly(3))
            ->method('fetchArray')
            ->with('mock_result')
            ->willReturnOnConsecutiveCalls(
                $mockData[0],
                $mockData[1],
                false
            );

        $result = $this->order->getAllCategories();
        $this->assertEquals($mockData, $result);
    }
}
