<?php

/**
 * Tests for phpMyFAQ\Category\Order
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    GitHub Copilot
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 */

namespace phpMyFAQ\Category;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use stdClass;

/**
 * Class OrderTest
 */
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

        $this->assertArrayHasKey('1', $result);
        $this->assertArrayHasKey('3', $result);
        $this->assertArrayHasKey('2', $result['1']);
        $this->assertArrayHasKey('4', $result['1']['2']);
    }

    public function testGetCategoryTreeWithSpecificParent(): void
    {
        $categories = [
            ['category_id' => '1', 'parent_id' => '0'],
            ['category_id' => '2', 'parent_id' => '1'],
            ['category_id' => '3', 'parent_id' => '1'],
        ];

        $result = $this->order->getCategoryTree($categories, 1);

        $this->assertArrayHasKey('2', $result);
        $this->assertArrayHasKey('3', $result);
        $this->assertArrayNotHasKey('1', $result);
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
        $this->assertArrayHasKey('1', $result);
        $this->assertArrayHasKey('3', $result);
        $this->assertArrayNotHasKey('2', $result);
    }

    public function testGetCategoryTreeWithCircularReference(): void
    {
        // Test case where categories have circular references: 1 -> 2 -> 1
        $categories = [
            ['category_id' => '1', 'parent_id' => '0'],
            ['category_id' => '2', 'parent_id' => '1'],
            ['category_id' => '1', 'parent_id' => '2'], // Circular reference
        ];

        $result = $this->order->getCategoryTree($categories, 0);

        // Should handle the circular reference gracefully
        $this->assertArrayHasKey('1', $result);
        // Category 1 should have category 2 as a child
        $this->assertArrayHasKey('2', $result['1']);
    }

    public function testGetCategoryTreeWithComplexCircularReference(): void
    {
        // Test case: A -> B -> C -> A (circular)
        $categories = [
            ['category_id' => '1', 'parent_id' => '0'],
            ['category_id' => '2', 'parent_id' => '1'],
            ['category_id' => '3', 'parent_id' => '2'],
            ['category_id' => '1', 'parent_id' => '3'], // Creates circle back to 1
        ];

        $result = $this->order->getCategoryTree($categories, 0);

        // Should handle gracefully and not cause infinite recursion
        $this->assertArrayHasKey('1', $result);
        $this->assertArrayHasKey('2', $result['1']);
        $this->assertArrayHasKey('3', $result['1']['2']);
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
