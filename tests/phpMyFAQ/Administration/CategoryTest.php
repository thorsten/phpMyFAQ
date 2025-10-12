<?php

/**
 * Administration Category Test
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-08-04
 */

namespace phpMyFAQ\Administration;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;

/**
 * Class CategoryTest
 */
class CategoryTest extends TestCase
{
    private Category $category;
    private Configuration|MockObject $configurationMock;
    private DatabaseDriver|MockObject $databaseMock;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseMock = $this->createMock(DatabaseDriver::class);
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->configurationMock
            ->method('getDb')
            ->willReturn($this->databaseMock);

        $this->category = new Category($this->configurationMock);
    }

    public function testConstructor(): void
    {
        $category = new Category($this->configurationMock);

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEmpty($category->categories);
        $this->assertEmpty($category->categoryName);
    }

    public function testSetAndGetLanguage(): void
    {
        $this->assertNull($this->category->getLanguage());

        $result = $this->category->setLanguage('de');
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('de', $this->category->getLanguage());

        $this->category->setLanguage(null);
        $this->assertNull($this->category->getLanguage());
    }

    public function testSetAndGetUser(): void
    {
        $this->assertEquals(-1, $this->category->getUser());

        $result = $this->category->setUser(42);
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals(42, $this->category->getUser());

        $this->category->setUser();
        $this->assertEquals(-1, $this->category->getUser());
    }

    public function testSetAndGetGroups(): void
    {
        $this->assertEquals([-1], $this->category->getGroups());

        $groups = [1, 2, 3];
        $result = $this->category->setGroups($groups);
        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals($groups, $this->category->getGroups());
    }

    public function testGetOwnerWithoutCategories(): void
    {
        $this->assertEquals(1, $this->category->getOwner(123));
    }

    public function testLoadCategoriesWithoutLanguage(): void
    {

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturn(null);

        $result = $this->category->loadCategories();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testLoadCategoriesWithLanguage(): void
    {
        $this->category->setLanguage('de');

        $this->databaseMock
            ->expects($this->once())
            ->method('escape')
            ->with('de')
            ->willReturn('de');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturn(null);

        $result = $this->category->loadCategories();
        $this->assertIsArray($result);
    }

    public function testLoadCategoriesWithSampleData(): void
    {
        $sampleData = [
            'id' => 1,
            'lang' => 'en',
            'parent_id' => 0,
            'name' => 'Test Category',
            'description' => 'Test Description',
            'user_id' => 1,
            'group_id' => 1,
            'active' => 1,
            'show_home' => 1,
            'image' => 'test.png'
        ];

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->willReturnOnConsecutiveCalls($sampleData, null);

        $result = $this->category->loadCategories();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals(1, $result[1]['id']);
        $this->assertEquals('Test Category', $result[1]['name']);
        $this->assertEquals(0, $result[1]['level']);
    }

    public function testLoadCategoriesWithInvalidLanguage(): void
    {
        $this->category->setLanguage('invalid_lang_123');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturn(null);

        $result = $this->category->loadCategories();
        $this->assertIsArray($result);
    }

    public function testBuildAdminCategoryTreeEmpty(): void
    {
        $result = $this->category->buildAdminCategoryTree([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testBuildAdminCategoryTreeWithData(): void
    {
        $categories = [
            ['id' => 1, 'parent_id' => 0, 'name' => 'Root Category'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Child Category'],
            ['id' => 3, 'parent_id' => 0, 'name' => 'Another Root']
        ];

        $result = $this->category->buildAdminCategoryTree($categories);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayNotHasKey(2, $result); // Child should not be at root level
    }

    public function testBuildAdminCategoryTreeWithSpecificParent(): void
    {
        $categories = [
            ['id' => 1, 'parent_id' => 0, 'name' => 'Root Category'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Child Category'],
            ['id' => 3, 'parent_id' => 1, 'name' => 'Another Child']
        ];

        $result = $this->category->buildAdminCategoryTree($categories, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(2, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayNotHasKey(1, $result);
    }

    public function testFluentInterface(): void
    {
        $result = $this->category
            ->setLanguage('de')
            ->setUser(42)
            ->setGroups([1, 2, 3]);

        $this->assertInstanceOf(Category::class, $result);
        $this->assertEquals('de', $this->category->getLanguage());
        $this->assertEquals(42, $this->category->getUser());
        $this->assertEquals([1, 2, 3], $this->category->getGroups());
    }

    public function testGetOwnerAfterLoadingCategories(): void
    {
        $sampleData = [
            'id' => 5,
            'lang' => 'en',
            'parent_id' => 0,
            'name' => 'Test Category',
            'description' => 'Test Description',
            'user_id' => 42,
            'group_id' => 1,
            'active' => 1,
            'show_home' => 1,
            'image' => 'test.png'
        ];

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->willReturnOnConsecutiveCalls($sampleData, null);

        $this->category->loadCategories();

        $this->assertEquals(42, $this->category->getOwner(5));
        $this->assertEquals(1, $this->category->getOwner(999)); // Non-existent category
    }
}
