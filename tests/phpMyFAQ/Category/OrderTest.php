<?php

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\System;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    private Order $categoryOrder;

    protected function setUp(): void
    {
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.currentVersion', System::getVersion());
        $this->categoryOrder = new Order($configuration);
    }

    protected function tearDown(): void
    {
        #echo 'teardown';
        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $dbHandle->query('DELETE FROM faqcategory_order');
    }

    public function testAdd(): void
    {
        $this->categoryOrder->add(1, 0);
        $this->categoryOrder->add(2, 0);

        $actual = $this->categoryOrder->getAllCategories();

        $expected = [
            [
                'category_id' => 1,
                'parent_id' => 0,
                'position' => 1,
            ],
            [
                'category_id' => 2,
                'parent_id' => 0,
                'position' => 2,
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testRemove(): void
    {
        $this->categoryOrder->add(1, 0);
        $this->categoryOrder->add(2, 0);
        $this->categoryOrder->remove(1);

        $actual = $this->categoryOrder->getAllCategories();

        $expected = [
            [
                'category_id' => 2,
                'parent_id' => 0,
                'position' => 2,
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testGetCategoryTree(): void
    {
        $this->categoryOrder->add(1, 0);
        $this->categoryOrder->add(2, 0);
        $this->categoryOrder->add(3, 1);

        $actual = $this->categoryOrder->getAllCategories();

        $expected = [
            [
                'category_id' => 1,
                'parent_id' => 0,
                'position' => 1,
            ],
            [
                'category_id' => 2,
                'parent_id' => 0,
                'position' => 2,
            ],
            [
                'category_id' => 3,
                'parent_id' => 1,
                'position' => 3,
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testGetParentId(): void
    {
        $categoryTree = [
            (object)[
                'id' => '1',
                'children' => [
                    (object)[
                        'id' => '6',
                        'children' => [
                            (object)['id' => '7', 'children' => []],
                            (object)['id' => '8', 'children' => []],
                        ],
                    ],
                    (object)['id' => '9', 'children' => []],
                    (object)['id' => '10', 'children' => []],
                    (object)['id' => '12', 'children' => []],
                ],
            ],
            (object)['id' => '3', 'children' => []],
            (object)['id' => '2', 'children' => []],
            (object)['id' => '4', 'children' => []],
            (object)['id' => '5', 'children' => []],
        ];

        $actual = $this->categoryOrder->getParentId($categoryTree, 7);

        $expected = 6;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that getCategoryTree handles self-referencing categories without infinite recursion
     */
    public function testGetCategoryTreeWithSelfReference(): void
    {
        // Simulate a category that references itself as parent
        $categories = [
            [
                'category_id' => 1,
                'parent_id' => 1, // Self-reference
                'position' => 1,
            ],
            [
                'category_id' => 2,
                'parent_id' => 0,
                'position' => 2,
            ],
        ];

        $result = $this->categoryOrder->getCategoryTree($categories);

        // Category 1 should be skipped due to self-reference
        // Only category 2 should be in the result
        $expected = [
            2 => [],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test that getCategoryTree handles circular references without infinite recursion
     */
    public function testGetCategoryTreeWithCircularReference(): void
    {
        // Simulate circular reference: 1 -> 2 -> 3 -> 2 (cycle)
        $categories = [
            [
                'category_id' => 1,
                'parent_id' => 0,
                'position' => 1,
            ],
            [
                'category_id' => 2,
                'parent_id' => 1,
                'position' => 2,
            ],
            [
                'category_id' => 3,
                'parent_id' => 2,
                'position' => 3,
            ],
            [
                'category_id' => 2, // Duplicate entry creating circular reference
                'parent_id' => 3,
                'position' => 4,
            ],
        ];

        $result = $this->categoryOrder->getCategoryTree($categories);

        // Should handle circular reference gracefully
        // Category 2 should only be visited once
        $expected = [
            1 => [
                2 => [
                    3 => [],
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
