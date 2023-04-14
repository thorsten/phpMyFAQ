<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $configuration->set('main.currentVersion', System::getVersion());
        $language = new Language($configuration);
        $language->setLanguage(false, 'en');
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
}
