<?php

declare(strict_types=1);

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Language;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Runs the category counting and translation queries against a private copy of the
 * SQLite test database, which ships without categories.
 */
#[CoversClass(CategoryRepository::class)]
#[UsesNamespace('phpMyFAQ')]
final class CategoryRepositoryDatabaseTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private CategoryRepository $repository;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
        $this->configuration->setLanguage(
            new Language($this->configuration, new Session(new MockArraySessionStorage())),
        );
        $this->repository = new CategoryRepository($this->configuration);
    }

    private function insertCategory(
        int $id,
        string $lang,
        string $name,
        string $description = '',
        bool $active = true,
        int $groupId = -1,
    ): void {
        $db = $this->configuration->getDb();
        $db->query(sprintf(
            "INSERT INTO %sfaqcategories (id, lang, parent_id, name, description, user_id, group_id, active)"
            . " VALUES (%d, '%s', 0, '%s', '%s', 1, %d, %d)",
            Database::getTablePrefix(),
            $id,
            $lang,
            $db->escape($name),
            $db->escape($description),
            $groupId,
            $active ? 1 : 0,
        ));
        $db->query(sprintf(
            'INSERT OR IGNORE INTO %sfaqcategory_group (category_id, group_id) VALUES (%d, %d)',
            Database::getTablePrefix(),
            $id,
            $groupId,
        ));
    }

    public function testCountCategoriesHonoursLanguageActivityAndGroups(): void
    {
        $this->insertCategory(9001, 'en', 'Public');
        $this->insertCategory(9002, 'en', 'Hidden', active: false);
        $this->insertCategory(9003, 'de', 'Öffentlich');
        $this->insertCategory(9004, 'en', 'Restricted', groupId: 5);

        $this->assertSame(3, $this->repository->countCategories());
        $this->assertSame(2, $this->repository->countCategories('en'));
        $this->assertSame(1, $this->repository->countCategories('en', activeOnly: true));
        $this->assertSame(1, $this->repository->countCategories('en', groups: [5]));
        $this->assertSame(3, $this->repository->countCategories(activeOnly: true, groups: [-1, 5]));
        $this->assertSame(0, $this->repository->countCategories('fr'));
    }

    public function testCountCategoriesIgnoresAMalformedLanguageFilter(): void
    {
        $this->insertCategory(9005, 'en', 'Public');
        $this->insertCategory(9006, 'de', 'Öffentlich');

        $this->assertSame(2, $this->repository->countCategories("en' OR 1=1 --"));
    }

    public function testCategoryLanguagesTranslatedListsEveryTranslationWithItsDescription(): void
    {
        $this->insertCategory(9010, 'en', 'Hardware', 'Devices and drivers');
        $this->insertCategory(9010, 'de', 'Hardware');
        $this->insertCategory(9011, 'fr', 'Logiciel', 'Programmes');

        $this->assertSame([
            'de' => 'Hardware',
            'en' => 'Hardware  (Devices and drivers)',
        ], $this->repository->getCategoryLanguagesTranslated(9010));
    }

    public function testCategoryLanguagesTranslatedForAllCategoriesCoversEveryLanguage(): void
    {
        $this->insertCategory(9012, 'en', 'Hardware', 'Devices and drivers');
        $this->insertCategory(9013, 'fr', 'Logiciel', 'Programmes');

        $translated = $this->repository->getCategoryLanguagesTranslated(0);

        $this->assertSame(['en', 'fr'], array_keys($translated));
        $this->assertSame('Logiciel  (Programmes)', $translated['fr']);
    }

    public function testCategoryLanguagesTranslatedIsEmptyForAnUnknownCategory(): void
    {
        $this->assertSame([], $this->repository->getCategoryLanguagesTranslated(424242));
    }
}
