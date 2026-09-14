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
}
