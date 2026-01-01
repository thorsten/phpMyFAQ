<?php

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class StartpageTest
 */
#[AllowMockObjectsWithoutExpectations]
class StartpageTest extends TestCase
{
    private Startpage $startpage;
    private Configuration $configurationMock;
    private DatabaseDriver $databaseMock;

    protected function setUp(): void
    {
        $this->databaseMock = $this->createMock(DatabaseDriver::class);
        $this->configurationMock = $this->createMock(Configuration::class);

        $this->configurationMock
            ->expects($this->any())
            ->method('getDb')
            ->willReturn($this->databaseMock);

        $this->startpage = new Startpage($this->configurationMock);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Startpage::class, $this->startpage);
    }

    public function testSetUser(): void
    {
        $userId = 42;
        $result = $this->startpage->setUser($userId);

        $this->assertInstanceOf(Startpage::class, $result);
        $this->assertSame($this->startpage, $result);
    }

    public function testSetGroups(): void
    {
        $groups = [1, 2, 3];
        $result = $this->startpage->setGroups($groups);

        $this->assertInstanceOf(Startpage::class, $result);
        $this->assertSame($this->startpage, $result);
    }

    public function testSetLanguage(): void
    {
        $language = 'en';
        $result = $this->startpage->setLanguage($language);

        $this->assertInstanceOf(Startpage::class, $result);
        $this->assertSame($this->startpage, $result);
    }

    public function testMethodChaining(): void
    {
        $result = $this->startpage
            ->setUser(10)
            ->setGroups([1, 2])
            ->setLanguage('de');

        $this->assertInstanceOf(Startpage::class, $result);
        $this->assertSame($this->startpage, $result);
    }

    public function testGetCategoriesEmpty(): void
    {
        Database::setTablePrefix('test_');

        // Setup required properties
        $this->startpage
            ->setUser(1)
            ->setGroups([1])
            ->setLanguage('en');

        $this->databaseMock
            ->expects($this->once())
            ->method('escape')
            ->with('en')
            ->willReturn('en');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT'))
            ->willReturn('query_result');

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->with('query_result')
            ->willReturn(false);

        $result = $this->startpage->getCategories();
        $this->assertEquals([], $result);
    }

    public function testGetCategoriesWithValidLanguage(): void
    {
        Database::setTablePrefix('test_');

        $this->startpage
            ->setUser(42)
            ->setGroups([1, 2])
            ->setLanguage('de');

        $this->databaseMock
            ->expects($this->once())
            ->method('escape')
            ->with('de')
            ->willReturn('de');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->logicalAnd(
                $this->stringContains("fc.lang = 'de'"),
                $this->stringContains('fu.user_id = 42'),
                $this->stringContains('fg.group_id IN (1, 2)'),
            ))
            ->willReturn('query_result');

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturn(false);

        $result = $this->startpage->getCategories();
        $this->assertEquals([], $result);
    }

    public function testGetCategoriesWithInvalidLanguage(): void
    {
        Database::setTablePrefix('test_');

        $this->startpage
            ->setUser(1)
            ->setGroups([1])
            ->setLanguage('invalid123');

        // escape should not be called for invalid language
        $this->databaseMock->expects($this->never())->method('escape');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->logicalNot($this->stringContains('fc.lang =')))
            ->willReturn('query_result');

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturn(false);

        $result = $this->startpage->getCategories();
        $this->assertEquals([], $result);
    }

    public function testGetCategoriesWithData(): void
    {
        Database::setTablePrefix('test_');

        $this->startpage
            ->setUser(10)
            ->setGroups([5])
            ->setLanguage('en');

        $this->configurationMock
            ->expects($this->once())
            ->method('getDefaultUrl')
            ->willReturn('https://example.com/');

        $this->databaseMock
            ->expects($this->once())
            ->method('escape')
            ->with('en')
            ->willReturn('en');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn('query_result');

        $categoryData = [
            'id' => 1,
            'lang' => 'en',
            'parent_id' => 0,
            'name' => 'Test Category',
            'description' => 'Test Description',
            'user_id' => 1,
            'group_id' => 1,
            'active' => 1,
            'image' => 'test-image.jpg',
            'show_home' => 1,
            'position' => 1,
        ];

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->willReturnOnConsecutiveCalls($categoryData, false);

        $result = $this->startpage->getCategories();

        $this->assertCount(1, $result);
        $this->assertEquals('Test Category', $result[0]['name']);
        $this->assertEquals('Test Description', $result[0]['description']);
        $this->assertEquals('content/user/images/test-image.jpg', $result[0]['image']);
        $this->assertStringContainsString('action=show&cat=1', $result[0]['url']);
    }

    public function testGetCategoriesWithEmptyImage(): void
    {
        Database::setTablePrefix('test_');

        $this->startpage
            ->setUser(1)
            ->setGroups([1])
            ->setLanguage('fr');

        $this->configurationMock
            ->expects($this->once())
            ->method('getDefaultUrl')
            ->willReturn('https://test.com/');

        $this->databaseMock
            ->expects($this->once())
            ->method('escape')
            ->with('fr')
            ->willReturn('fr');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn('query_result');

        $categoryData = [
            'id' => 2,
            'lang' => 'fr',
            'parent_id' => 0,
            'name' => 'Catégorie Test',
            'description' => 'Description Test',
            'user_id' => 1,
            'group_id' => 1,
            'active' => 1,
            'image' => '', // Empty image
            'show_home' => 1,
            'position' => 2,
        ];

        $this->databaseMock
            ->expects($this->exactly(2))
            ->method('fetchArray')
            ->willReturnOnConsecutiveCalls($categoryData, false);

        $result = $this->startpage->getCategories();

        $this->assertCount(1, $result);
        $this->assertEquals('', $result[0]['image']);
        $this->assertEquals('Catégorie Test', $result[0]['name']);
    }

    public function testGetCategoriesMultipleCategories(): void
    {
        Database::setTablePrefix('test_');

        $this->startpage
            ->setUser(5)
            ->setGroups([2, 3])
            ->setLanguage('es');

        $this->configurationMock
            ->expects($this->any())
            ->method('getDefaultUrl')
            ->willReturn('https://demo.com/');

        $this->databaseMock
            ->expects($this->once())
            ->method('escape')
            ->willReturn('es');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->willReturn('query_result');

        $category1 = [
            'id' => 1,
            'lang' => 'es',
            'parent_id' => 0,
            'name' => 'Categoría 1',
            'description' => 'Descripción 1',
            'user_id' => 1,
            'group_id' => 2,
            'active' => 1,
            'image' => 'cat1.jpg',
            'show_home' => 1,
            'position' => 1,
        ];

        $category2 = [
            'id' => 2,
            'lang' => 'es',
            'parent_id' => 0,
            'name' => 'Categoría 2',
            'description' => 'Descripción 2',
            'user_id' => 1,
            'group_id' => 3,
            'active' => 1,
            'image' => '',
            'show_home' => 1,
            'position' => 2,
        ];

        $this->databaseMock
            ->expects($this->exactly(3))
            ->method('fetchArray')
            ->willReturnOnConsecutiveCalls($category1, $category2, false);

        $result = $this->startpage->getCategories();

        $this->assertCount(2, $result);
        $this->assertEquals('Categoría 1', $result[0]['name']);
        $this->assertEquals('content/user/images/cat1.jpg', $result[0]['image']);
        $this->assertEquals('Categoría 2', $result[1]['name']);
        $this->assertEquals('', $result[1]['image']);
    }

    public function testGetCategoriesLanguageEdgeCases(): void
    {
        Database::setTablePrefix('test_');

        // Test with language that has hyphen
        $this->startpage
            ->setUser(1)
            ->setGroups([1])
            ->setLanguage('zh-cn');

        $this->databaseMock
            ->expects($this->once())
            ->method('escape')
            ->with('zh-cn')
            ->willReturn('zh-cn');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains("fc.lang = 'zh-cn'"))
            ->willReturn('query_result');

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturn(false);

        $result = $this->startpage->getCategories();
        $this->assertEquals([], $result);
    }

    public function testGetCategoriesWithSingleCharacterLanguage(): void
    {
        Database::setTablePrefix('test_');

        // Single character language should not match regex
        $this->startpage
            ->setUser(1)
            ->setGroups([1])
            ->setLanguage('a');

        $this->databaseMock->expects($this->never())->method('escape');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->logicalNot($this->stringContains('fc.lang =')))
            ->willReturn('query_result');

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturn(false);

        $result = $this->startpage->getCategories();
        $this->assertEquals([], $result);
    }

    public function testGetCategoriesQueryStructure(): void
    {
        Database::setTablePrefix('test_');

        $this->startpage
            ->setUser(123)
            ->setGroups([10, 20])
            ->setLanguage('en');

        $this->databaseMock
            ->expects($this->once())
            ->method('escape')
            ->willReturn('en');

        $this->databaseMock
            ->expects($this->once())
            ->method('query')
            ->with($this->logicalAnd(
                $this->stringContains('fc.active = 1'),
                $this->stringContains('fc.show_home = 1'),
                $this->stringContains('ORDER BY'),
                $this->stringContains('fco.position'),
                $this->stringContains('GROUP BY'),
            ))
            ->willReturn('query_result');

        $this->databaseMock
            ->expects($this->once())
            ->method('fetchArray')
            ->willReturn(false);

        $result = $this->startpage->getCategories();
        $this->assertEquals([], $result);
    }

    protected function tearDown(): void
    {
        // Reset table prefix if needed
        Database::setTablePrefix('');
    }
}
