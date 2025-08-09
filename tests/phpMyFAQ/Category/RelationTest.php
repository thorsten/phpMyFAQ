<?php

/**
 * Tests for phpMyFAQ\Category\Relation
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
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Language;
use stdClass;

/**
 * Class RelationTest
 */
class RelationTest extends TestCase
{
    private Relation $relation;
    private Configuration|MockObject $configurationMock;
    private Category|MockObject $categoryMock;
    private DatabaseDriver|MockObject $databaseMock;
    private Language|MockObject $languageMock;

    protected function setUp(): void
    {
        $this->databaseMock = $this->createMock(DatabaseDriver::class);
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->categoryMock = $this->createMock(Category::class);
        $this->languageMock = $this->createMock(Language::class);

        $this->configurationMock->expects($this->any())
            ->method('getDb')
            ->willReturn($this->databaseMock);

        $this->configurationMock->expects($this->any())
            ->method('getLanguage')
            ->willReturn($this->languageMock);

        $this->relation = new Relation($this->configurationMock, $this->categoryMock);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Relation::class, $this->relation);
    }

    public function testSetGroups(): void
    {
        $groups = [1, 2, 3];
        $result = $this->relation->setGroups($groups);

        $this->assertInstanceOf(Relation::class, $result);
        $this->assertSame($this->relation, $result);
    }

    public function testSetGroupsChaining(): void
    {
        $groups1 = [1, 2];
        $groups2 = [3, 4];

        $result = $this->relation->setGroups($groups1)->setGroups($groups2);
        $this->assertInstanceOf(Relation::class, $result);
    }

    public function testGetCategoryFaqsMatrixEmpty(): void
    {
        Database::setTablePrefix('test_');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT'))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->with('query_result')
            ->willReturn(0);

        $result = $this->relation->getCategoryFaqsMatrix();
        $this->assertEquals([], $result);
    }

    public function testGetCategoryFaqsMatrixWithData(): void
    {
        Database::setTablePrefix('test_');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(2);

        $row1 = new stdClass();
        $row1->id_cat = 1;
        $row1->id = 10;

        $row2 = new stdClass();
        $row2->id_cat = 2;
        $row2->id = 20;

        $this->databaseMock->expects($this->exactly(3))
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls($row1, $row2, false);

        $result = $this->relation->getCategoryFaqsMatrix();

        $expected = [
            1 => [10 => true],
            2 => [20 => true]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetCategoryFaqsMatrixMultipleFaqsPerCategory(): void
    {
        Database::setTablePrefix('test_');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(3);

        $row1 = new stdClass();
        $row1->id_cat = 1;
        $row1->id = 10;

        $row2 = new stdClass();
        $row2->id_cat = 1;
        $row2->id = 11;

        $row3 = new stdClass();
        $row3->id_cat = 2;
        $row3->id = 20;

        $this->databaseMock->expects($this->exactly(4))
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls($row1, $row2, $row3, false);

        $result = $this->relation->getCategoryFaqsMatrix();

        $expected = [
            1 => [10 => true, 11 => true],
            2 => [20 => true]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetCategoryWithFaqsBasicPermissions(): void
    {
        Database::setTablePrefix('test_');

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.permLevel')
            ->willReturn('basic');

        $this->languageMock->expects($this->any())
            ->method('getLanguage')
            ->willReturn('en');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains("fd.lang = 'en'"))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(1);

        $categoryRow = new stdClass();
        $categoryRow->id = 1;
        $categoryRow->parent_id = 0;
        $categoryRow->category_name = 'Test Category';
        $categoryRow->description = 'Test Description';
        $categoryRow->number = 5;

        $this->databaseMock->expects($this->exactly(2))
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls($categoryRow, false);

        $result = $this->relation->getCategoryWithFaqs();

        $expected = [
            1 => [
                'category_id' => 1,
                'parent_id' => 0,
                'name' => 'Test Category',
                'description' => 'Test Description',
                'faqs' => 5
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetCategoryWithFaqsAdvancedPermissionsWithUser(): void
    {
        Database::setTablePrefix('test_');

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.permLevel')
            ->willReturn('medium');

        $this->categoryMock->expects($this->any())
            ->method('getUser')
            ->willReturn(42);

        $this->categoryMock->expects($this->any())
            ->method('getGroups')
            ->willReturn([1, 2, 3]);

        $this->languageMock->expects($this->any())
            ->method('getLanguage')
            ->willReturn('de');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('fdu.user_id = 42'))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(0);

        $result = $this->relation->getCategoryWithFaqs();
        $this->assertEquals([], $result);
    }

    public function testGetCategoryWithFaqsAdvancedPermissionsGuestUser(): void
    {
        Database::setTablePrefix('test_');

        $this->configurationMock->expects($this->once())
            ->method('get')
            ->with('security.permLevel')
            ->willReturn('medium');

        $this->categoryMock->expects($this->any())
            ->method('getUser')
            ->willReturn(-1);

        $this->categoryMock->expects($this->any())
            ->method('getGroups')
            ->willReturn([1, 2]);

        $this->languageMock->expects($this->any())
            ->method('getLanguage')
            ->willReturn('');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('fdg.group_id IN (1, 2)'))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(0);

        $result = $this->relation->getCategoryWithFaqs();
        $this->assertEquals([], $result);
    }

    public function testGetNumberOfFaqsPerCategoryWithoutRestriction(): void
    {
        Database::setTablePrefix('test_');

        $this->languageMock->expects($this->any())
            ->method('getLanguage')
            ->willReturn('en');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('COUNT(fcr.record_id) AS number'))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(1);

        $row = new stdClass();
        $row->category_id = 1;
        $row->number = 10;

        $this->databaseMock->expects($this->exactly(2))
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls($row, false);

        $result = $this->relation->getNumberOfFaqsPerCategory();

        $expected = [1 => 10];
        $this->assertEquals($expected, $result);
    }

    public function testGetNumberOfFaqsPerCategoryWithRestriction(): void
    {
        Database::setTablePrefix('test_');

        // Set groups first
        $this->relation->setGroups([5]);

        $this->languageMock->expects($this->any())
            ->method('getLanguage')
            ->willReturn('de');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('fdg.group_id = 5'))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(2);

        $row1 = new stdClass();
        $row1->category_id = 1;
        $row1->number = 5;

        $row2 = new stdClass();
        $row2->category_id = 2;
        $row2->number = 8;

        $this->databaseMock->expects($this->exactly(3))
            ->method('fetchObject')
            ->willReturnOnConsecutiveCalls($row1, $row2, false);

        $result = $this->relation->getNumberOfFaqsPerCategory(true);

        $expected = [1 => 5, 2 => 8];
        $this->assertEquals($expected, $result);
    }

    public function testGetNumberOfFaqsPerCategoryOnlyActive(): void
    {
        Database::setTablePrefix('test_');

        $this->languageMock->expects($this->once())
            ->method('getLanguage')
            ->willReturn('');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains("AND fd.active = 'yes'"))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(0);

        $result = $this->relation->getNumberOfFaqsPerCategory(false, true);
        $this->assertEquals([], $result);
    }

    public function testGetNumberOfFaqsPerCategoryWithRestrictionAndOnlyActive(): void
    {
        Database::setTablePrefix('test_');

        // Set groups first
        $this->relation->setGroups([3]);

        $this->languageMock->expects($this->any())
            ->method('getLanguage')
            ->willReturn('fr');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->logicalAnd(
                $this->stringContains('fdg.group_id = 3'),
                $this->stringContains("fd.active = 'yes'")
            ))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(0);

        $result = $this->relation->getNumberOfFaqsPerCategory(true, true);
        $this->assertEquals([], $result);
    }

    public function testGetNumberOfFaqsPerCategoryEmptyLanguage(): void
    {
        Database::setTablePrefix('test_');

        $this->languageMock->expects($this->any())
            ->method('getLanguage')
            ->willReturn('');

        $this->databaseMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('COUNT(fcr.record_id) AS number'))
            ->willReturn('query_result');

        $this->databaseMock->expects($this->once())
            ->method('numRows')
            ->willReturn(0);

        $result = $this->relation->getNumberOfFaqsPerCategory();
        $this->assertEquals([], $result);
    }

    protected function tearDown(): void
    {
        // Reset table prefix if needed
        Database::setTablePrefix('');
    }
}
