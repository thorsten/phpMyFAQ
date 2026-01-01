<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CategoryEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class CategoryTest extends TestCase
{
    private Category $category;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.currentVersion', System::getVersion());
        $language = new Language($configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->category = new Category($configuration);
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

    private function createCategory(int $id = 1): CategoryEntity
    {
        $category = new CategoryEntity();
        $category
            ->setId($id)
            ->setLang('en')
            ->setParentId(0)
            ->setName('Category ' . $id)
            ->setDescription('Description ' . $id)
            ->setUserId(1)
            ->setGroupId(1)
            ->setActive(true)
            ->setShowHome(true)
            ->setImage('image.png');

        return $category;
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
}
