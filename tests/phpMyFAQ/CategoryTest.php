<?php

namespace phpMyFAQ;

use phpMyFAQ\Category\CategoryCache;
use phpMyFAQ\Category\CategoryPermissionContext;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Entity\CategoryEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class CategoryTest extends TestCase
{
    private Category $category;
    private Configuration $configuration;
    private PdoSqlite $dbHandle;
    private string $databaseFile;

    /**
     * @throws Exception|Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-category-test-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $this->dbHandle = new PdoSqlite();
        $this->dbHandle->connect($this->databaseFile, '', '');

        $this->configuration = new Configuration($this->dbHandle);
        $this->configuration->set('main.currentVersion', System::getVersion());
        $this->configuration->set('main.referenceURL', 'https://localhost/');
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->category = new Category($this->configuration);
    }

    protected function tearDown(): void
    {
        $this->dbHandle->close();
        @unlink($this->databaseFile);

        parent::tearDown();
    }

    public function testGetGroups(): void
    {
        $groups = [1, 2, 3];
        $this->category->setGroups($groups);

        $result = $this->category->getGroups();

        $this->assertSame($groups, $result);
    }

    public function testGetUser(): void
    {
        $user = 1;
        $this->category->setUser($user);

        $result = $this->category->getUser();

        $this->assertSame($user, $result);
    }

    public function testSetLanguage(): void
    {
        $language = 'en';
        $result = $this->category->setLanguage($language);

        $this->assertInstanceOf(Category::class, $result);
    }

    public function testSetGroups(): void
    {
        $groups = [1, 2, 3];

        $result = $this->category->setGroups($groups);

        $this->assertSame($groups, $this->category->getGroups());
        $this->assertInstanceOf(Category::class, $result);
    }

    public function testSetGroupsSetsDefaultGroupForEmptyArray(): void
    {
        $this->category->setGroups([]);
        $this->assertSame([-1], $this->category->getGroups());
    }

    public function testGetOrderedCategories(): void
    {
        $category = $this->createCategory();

        $this->category->create($category);

        $this->assertEquals(
            [
                1 => [
                    'id' => 1,
                    'lang' => 'en',
                    'parent_id' => 0,
                    'name' => 'Category 1',
                    'description' => 'Description 1',
                    'user_id' => 1,
                    'group_id' => 1,
                    'active' => 1,
                    'show_home' => 1,
                    'image' => 'image.png',
                    'level' => 0,
                ],
            ],
            $this->category->getOrderedCategories(false),
        );

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testSetUser(): void
    {
        $user = 1;

        $result = $this->category->setUser($user);

        $this->assertSame($user, $this->category->getUser());
        $this->assertInstanceOf(Category::class, $result);
    }

    public function testGetAllCategories(): void
    {
        $category = $this->createCategory();

        $this->category->create($category);

        $this->assertEquals(
            [
                1 => [
                    'id' => 1,
                    'lang' => 'en',
                    'parent_id' => 0,
                    'name' => 'Category 1',
                    'description' => 'Description 1',
                    'user_id' => 1,
                    'group_id' => 1,
                    'active' => 1,
                    'show_home' => 1,
                    'image' => 'image.png',
                    'level' => 0,
                ],
            ],
            $this->category->getAllCategories(),
        );

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testGetAllCategoryIds(): void
    {
        $category = $this->createCategory();

        $this->category->create($category);

        $this->assertEquals([1], $this->category->getAllCategoryIds());

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testAdminCategoryTree(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $category = $this->createCategory(2);
        $this->category->create($category);

        $category = $this->createCategory(3);
        $this->category->create($category);

        $categories = $this->category->getOrderedCategories(false);

        $this->assertEquals([1 => [], 2 => [], 3 => []], $this->category->buildAdminCategoryTree($categories));

        // Cleanup
        $this->category->delete(1, 'en');
        $this->category->delete(2, 'en');
        $this->category->delete(3, 'en');
    }

    private function createCategory(
        int $id = 1,
        int $parentId = 0,
        string $lang = 'en',
        ?string $name = null,
        ?string $description = null,
    ): CategoryEntity {
        $name ??= 'Category ' . $id;
        $description ??= 'Description ' . $id;
        $category = new CategoryEntity();
        $category
            ->setId($id)
            ->setLang($lang)
            ->setParentId($parentId)
            ->setName($name)
            ->setDescription($description)
            ->setUserId(1)
            ->setGroupId(1)
            ->setActive(true)
            ->setShowHome(true)
            ->setImage('image.png');

        return $category;
    }

    private function deleteCategories(int ...$categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            $this->category->delete($categoryId, 'en');
        }
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflectionProperty = new ReflectionProperty($object, $property);
        return $reflectionProperty->getValue($object);
    }

    public function testGetCategoryData(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $this->assertEquals($category, $this->category->getCategoryData(1));

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testGetCategoryIdFromName(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $this->assertEquals(1, $this->category->getCategoryIdFromName('Category 1'));

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testCheckIfCategoryExists(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $this->assertEquals(1, $this->category->checkIfCategoryExists($category));

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testUpdate(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $category->setName('Updated Category');
        $category->setDescription('Updated Description');
        $category->setUserId(2);
        $category->setGroupId(2);
        $category->setActive(false);
        $category->setShowHome(false);
        $category->setImage('updated.png');

        $this->category->update($category);

        $this->assertEquals($category, $this->category->getCategoryData(1));

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testMoveOwnership(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $this->category->moveOwnership(1, 2);

        $category->setUserId(2);

        $this->assertEquals($category, $this->category->getCategoryData(1));

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testHasLanguage(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $this->assertTrue($this->category->hasLanguage(1, 'en'));

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testUpdateParentCategory(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $this->category->updateParentCategory(1, 2);

        $category->setParentId(2);

        $this->assertEquals($category, $this->category->getCategoryData(1));

        // Cleanup
        $this->category->delete(1, 'en');
    }

    public function testDelete(): void
    {
        $category = $this->createCategory();
        $this->category->create($category);

        $this->assertTrue($this->category->delete(1, 'en'));
    }

    public function testGetCategoriesPaginatedReturnsSortedActiveCategories(): void
    {
        $this->category->create($this->createCategory(101));
        $this->category->create($this->createCategory(102, 101));
        $this->category->create($this->createCategory(103));

        $inactiveCategory = $this->createCategory(104);
        $inactiveCategory->setActive(false);
        $this->category->create($inactiveCategory);

        // Insert permission records for all categories
        $prefix = \phpMyFAQ\Database::getTablePrefix();
        foreach ([101, 102, 103, 104] as $catId) {
            $this->dbHandle->query(sprintf(
                'INSERT INTO %sfaqcategory_group (category_id, group_id) VALUES (%d, -1)',
                $prefix,
                $catId,
            ));
            $this->dbHandle->query(sprintf(
                'INSERT INTO %sfaqcategory_user (category_id, user_id) VALUES (%d, -1)',
                $prefix,
                $catId,
            ));
        }

        $this->category->setUser(-1);
        $this->category->setGroups([-1]);

        $this->category->getOrderedCategories(false, true);

        $paginatedCategories = $this->category->getCategoriesPaginated(2, 0, 'id', 'DESC', true);

        $this->assertSame([103, 102], array_keys($paginatedCategories));
        $this->assertSame(0, $paginatedCategories[103]['level']);
        $this->assertSame(1, $paginatedCategories[102]['level']);

        // Cleanup permission records
        foreach ([101, 102, 103, 104] as $catId) {
            $this->dbHandle->query(sprintf('DELETE FROM %sfaqcategory_group WHERE category_id = %d', $prefix, $catId));
            $this->dbHandle->query(sprintf('DELETE FROM %sfaqcategory_user WHERE category_id = %d', $prefix, $catId));
        }

        $this->deleteCategories(101, 102, 103, 104);
    }

    public function testExpandCollapseAndCategoryTreeOperations(): void
    {
        $this->category->create($this->createCategory(201));
        $this->category->create($this->createCategory(202, 201));
        $this->category->create($this->createCategory(203, 202));

        $this->category->getOrderedCategories(false);
        $this->category->buildCategoryTree();

        $this->assertCount(3, $this->category->getCategoryTree());

        $cache = $this->readProperty($this->category, 'categoryCache');
        assert($cache instanceof CategoryCache);
        $cache->setTreeTab([
            ['id' => 201, 'symbol' => 'minus', 'numChildren' => 1],
            ['id' => 202, 'symbol' => 'plus', 'numChildren' => 1],
            ['id' => 203, 'symbol' => 'angle', 'numChildren' => 0],
        ]);

        $this->category->collapseAll();
        $this->assertSame('plus', $cache->getTreeTabEntry(0)['symbol']);

        $this->category->expand(202);
        $this->assertSame('minus', $cache->getTreeTabEntry(1)['symbol']);

        $this->category->expandTo(203);
        $this->assertSame('minus', $cache->getTreeTabEntry(0)['symbol']);
        $this->assertSame('minus', $cache->getTreeTabEntry(1)['symbol']);
        $this->assertSame('angle', $cache->getTreeTabEntry(2)['symbol']);

        $this->deleteCategories(201, 202, 203);
    }

    public function testGetPathReturnsTextAndHtmlBreadcrumbs(): void
    {
        $this->category->create($this->createCategory(301, 0, 'en', 'Root & One', 'Parent <desc>'));
        $this->category->create($this->createCategory(302, 301, 'en', 'Child "Two"', 'Leaf > desc'));

        $this->category->getOrderedCategories(false);

        $textPath = $this->category->getPath(302, ' > ');
        $htmlPath = $this->category->getPath(302, ' > ', true, 'crumbs');

        $this->assertSame('Root & One > Child "Two"', $textPath);
        $this->assertStringContainsString('<ol class="crumbs">', $htmlPath);
        $this->assertStringContainsString('Root &amp; One', $htmlPath);
        $this->assertStringContainsString('Child "Two"', $htmlPath);

        $this->deleteCategories(301, 302);
    }

    public function testGetPathWithStartPageReturnsTextAndHtmlBreadcrumbs(): void
    {
        $this->category->create($this->createCategory(401, 0, 'en', 'Root', 'Root desc'));
        $this->category->create($this->createCategory(402, 401, 'en', 'Leaf', 'Leaf desc'));

        $this->category->getOrderedCategories(false);

        $textPath = $this->category->getPathWithStartPage(402, ' > ', false, 'breadcrumb', 'Home');
        $htmlPath = $this->category->getPathWithStartPage(402, ' > ', true, 'trail', 'Home', 'Start');

        $this->assertStringStartsWith('Home > ', $textPath);
        $this->assertStringContainsString(' > Root > Leaf', $textPath);
        $this->assertStringContainsString('<ol class="trail">', $htmlPath);
        $this->assertStringContainsString('title="Start"', $htmlPath);
        $this->assertStringContainsString('Root', $htmlPath);
        $this->assertStringContainsString('Leaf', $htmlPath);

        $this->deleteCategories(401, 402);
    }

    public function testFaqRelationLookupsAndCategoryLinkChecks(): void
    {
        $this->category->create($this->createCategory(501));
        $this->configuration
            ->getDb()
            ->query(
                "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (501, 'en', 9001, 'en')",
            );

        $categories = $this->category->getCategoriesFromFaq(9001);

        $this->assertSame([501], array_keys($categories));
        $this->assertSame([501], $this->category->getCategoryIdsFromFaq(9001));
        $this->assertSame(501, $this->category->getCategoryIdFromFaq(9001));
        $this->assertTrue($this->category->categoryHasLinkToFaq(9001, 501));
        $this->assertFalse($this->category->categoryHasLinkToFaq(9002, 501));

        $this->configuration
            ->getDb()
            ->query(
                "DELETE FROM faqcategoryrelations WHERE category_id = 501 AND record_id = 9001 AND category_lang = 'en'",
            );
        $this->category->delete(501, 'en');
    }

    public function testGetMissingCategoriesUpdatesCacheAndDescriptionAccessors(): void
    {
        $this->category->create($this->createCategory(601, 0, 'en', 'English Category', 'English description'));
        $this->category->create($this->createCategory(602, 0, 'de', 'German Category', 'German description'));

        $this->category->getOrderedCategories(false);
        $this->category->getMissingCategories();

        $this->assertArrayHasKey(602, $this->category->getCategoryNames());
        $this->assertSame('German Category', $this->category->getCategoryName(602));
        $this->assertSame('German description', $this->category->getCategoryDescription(602));
        $this->assertContains(602, $this->category->getChildren(0));
        $this->assertContains(602, $this->category->getChildNodes(0));

        $this->deleteCategories(601);
        $this->category->delete(602, 'de');
    }

    public function testGetOwnerAndModeratorGroupIdReadPermissionContext(): void
    {
        $permissionContext = $this->readProperty($this->category, 'categoryPermissionContext');
        assert($permissionContext instanceof CategoryPermissionContext);

        $permissionContext->setOwner(701, 99);
        $permissionContext->setModerator(701, 13);

        $this->assertSame(99, $this->category->getOwner(701));
        $this->assertSame(1, $this->category->getOwner());
        $this->assertSame(13, $this->category->getModeratorGroupId(701));
        $this->assertSame(0, $this->category->getModeratorGroupId(999));
    }

    public function testConstructorUsesFaqLanguageOverride(): void
    {
        $this->category->create($this->createCategory(801, 0, 'en', 'English Name', 'English description'));
        $this->category->create($this->createCategory(801, 0, 'de', 'German Name', 'German description'));

        $germanCategory = new Category($this->configuration, [], false, 'de');

        $this->assertSame('German Name', $germanCategory->getCategoryName(801));

        $this->category->delete(801, 'en');
        $this->category->delete(801, 'de');
    }
}
