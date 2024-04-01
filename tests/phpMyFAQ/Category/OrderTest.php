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
}
