<?php

namespace phpMyFAQ\Category;

use phpMyFAQ\Category\Permission\CategoryPermissionService;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Tenant\QuotaExceededException;
use phpMyFAQ\Tenant\TenantContext;
use phpMyFAQ\Tenant\TenantContextResolver;
use phpMyFAQ\Tenant\TenantQuotaEnforcer;
use phpMyFAQ\Tenant\TenantQuotas;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CategoryRepository::class)]
#[UsesClass(Database::class)]
#[UsesClass(CategoryEntity::class)]
#[UsesClass(CategoryPermissionService::class)]
#[UsesClass(TenantQuotaEnforcer::class)]
#[UsesClass(TenantContext::class)]
#[UsesClass(TenantContextResolver::class)]
#[UsesClass(TenantQuotas::class)]
class CategoryRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('PMF_TENANT_QUOTA_MAX_CATEGORIES');
        Database::setTablePrefix('');
    }

    public function testCreateThrowsWhenCategoryQuotaIsExceeded(): void
    {
        putenv('PMF_TENANT_QUOTA_MAX_CATEGORIES=0');
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(1) AS amount FROM pmf_faqcategories')
            ->willReturn(true);
        $db->expects($this->once())->method('fetchArray')->with(true)->willReturn(['amount' => 0]);
        $db->expects($this->never())->method('nextId');

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $repository = new CategoryRepository($configuration);

        $categoryEntity = new CategoryEntity()
            ->setId(0)
            ->setLang('en')
            ->setParentId(0)
            ->setName('Category')
            ->setDescription('Description')
            ->setUserId(1)
            ->setGroupId(-1)
            ->setActive(true)
            ->setImage('')
            ->setShowHome(true);

        $this->expectException(QuotaExceededException::class);
        $repository->create($categoryEntity);
    }

    public function testFindCategoriesPaginatedAllowListsTheSortDirection(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with(self::callback(static function (string $query): bool {
                self::assertStringContainsString('ORDER BY fc.id ASC LIMIT 25 OFFSET 0', $query);
                self::assertStringNotContainsString('DROP', $query);
                return true;
            }))
            ->willReturn(true);
        $db->method('fetchArray')->willReturn(false);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $repository = new CategoryRepository($configuration);

        self::assertSame(
            [],
            $repository->findCategoriesPaginated(sortField: 'id', sortOrder: 'ASC; DROP TABLE pmf_faqcategories'),
        );
    }

    public function testFindCategoriesPaginatedAcceptsLowerCaseDescendingSortDirection(): void
    {
        Database::setTablePrefix('pmf_');

        $db = $this->createMock(DatabaseDriver::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with(self::stringContains('ORDER BY fc.name DESC'))
            ->willReturn(true);
        $db->method('fetchArray')->willReturn(false);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        $repository = new CategoryRepository($configuration);

        self::assertSame([], $repository->findCategoriesPaginated(sortField: 'name', sortOrder: 'desc'));
    }

    public function testFindCategoriesPaginatedFallsBackToTheIdForUnknownSortFields(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db
            ->expects($this->once())
            ->method('query')
            ->with(self::stringContains('ORDER BY fc.id ASC'))
            ->willReturn(true);
        $db->method('fetchArray')->willReturn(false);

        $repository = new CategoryRepository($this->createConfiguration($db));

        self::assertSame([], $repository->findCategoriesPaginated(sortField: 'description; DROP TABLE'));
    }

    public function testFindOrderedCategoriesIncludesInactiveCategoriesOnRequest(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $db
            ->expects($this->once())
            ->method('query')
            ->with(self::callback(static function (string $query): bool {
                self::assertStringNotContainsString('fc.active = 1', $query);
                self::assertStringContainsString("fc.lang = 'en'", $query);
                return true;
            }))
            ->willReturn(true);
        $db->method('fetchArray')->willReturnOnConsecutiveCalls([
            'id' => 3,
            'lang' => 'en',
            'parent_id' => 0,
            'name' => 'Inactive',
            'description' => '',
            'user_id' => 1,
            'group_id' => -1,
            'active' => 0,
            'image' => '',
            'show_home' => 0,
        ], false);

        $repository = new CategoryRepository($this->createConfiguration($db));

        $categories = $repository->findOrderedCategories([-1], -1, 'en', true, true);

        self::assertSame([3], array_keys($categories));
        self::assertSame(0, $categories[3]['active']);
    }

    public function testFindCategoryIdByNameReturnsNullForUnknownNames(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $db->method('query')->willReturn(true);
        $db->method('numRows')->willReturn(0);
        $db->expects($this->never())->method('fetchRow');

        $repository = new CategoryRepository($this->createConfiguration($db));

        self::assertNull($repository->findCategoryIdByName('Unknown'));
    }

    public function testCreateAssignsTheNextIdToNewCategories(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $db->expects($this->once())->method('nextId')->with('faqcategories', 'id')->willReturn(17);
        $db->method('fetchArray')->willReturn(['amount' => 0]);
        $db->method('query')->willReturnCallback(static function (string $query): bool {
            if (str_starts_with($query, 'INSERT INTO')) {
                self::assertStringContainsString('VALUES(17,', $query);
            }

            return true;
        });

        $repository = new CategoryRepository($this->createConfiguration($db));

        $categoryEntity = new CategoryEntity()
            ->setId(0)
            ->setLang('en')
            ->setParentId(0)
            ->setName('Category')
            ->setDescription(null)
            ->setUserId(1)
            ->setGroupId(-1)
            ->setActive(true)
            ->setImage(null)
            ->setShowHome(true);

        self::assertSame(17, $repository->create($categoryEntity));
        self::assertSame(17, $categoryEntity->getId());
    }

    public function testCountAndLinkLookupsReturnNothingWhenTheQueryFails(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $db->method('query')->willReturn(false);
        $db->expects($this->never())->method('fetchArray');

        $repository = new CategoryRepository($this->createConfiguration($db));

        self::assertSame(0, $repository->countByNameLangParent('Category', 'en', 0));
        self::assertFalse($repository->hasLinkToFaq(1, 2));
    }

    private function createConfiguration(DatabaseDriver $db): Configuration
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($db);

        return $configuration;
    }
}
