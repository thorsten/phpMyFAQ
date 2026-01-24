<?php

declare(strict_types=1);

namespace phpMyFAQ\Category;

use phpMyFAQ\Entity\CategoryEntity;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CategoryServiceTest extends TestCase
{
    private CategoryRepositoryInterface $repository;
    private CategoryService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CategoryRepositoryInterface::class);
        $this->service = new CategoryService($this->repository);
    }

    public function testGetAllCategories(): void
    {
        $expected = [
            1 => ['id' => 1, 'name' => 'Category 1'],
            2 => ['id' => 2, 'name' => 'Category 2'],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findAllCategories')
            ->with('en')
            ->willReturn($expected);

        $result = $this->service->getAllCategories('en');
        $this->assertSame($expected, $result);
    }

    public function testGetAllCategoryIds(): void
    {
        $expected = [1, 2, 3];

        $this->repository
            ->expects($this->once())
            ->method('findAllCategoryIds')
            ->with('en')
            ->willReturn($expected);

        $result = $this->service->getAllCategoryIds('en');
        $this->assertSame($expected, $result);
    }

    public function testGetCategoriesFromFaq(): void
    {
        $expected = [
            1 => ['id' => 1, 'name' => 'Category 1'],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findCategoriesFromFaq')
            ->with(42, 'en')
            ->willReturn($expected);

        $result = $this->service->getCategoriesFromFaq(42, 'en');
        $this->assertSame($expected, $result);
    }

    public function testGetCategoryIdFromFaq(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findCategoriesFromFaq')
            ->with(42, 'en')
            ->willReturn([
                5 => ['id' => 5, 'name' => 'Category 5'],
            ]);

        $result = $this->service->getCategoryIdFromFaq(42, 'en');
        $this->assertSame(5, $result);
    }

    public function testGetCategoryIdFromFaqReturnsZeroWhenEmpty(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findCategoriesFromFaq')
            ->with(42, 'en')
            ->willReturn([]);

        $result = $this->service->getCategoryIdFromFaq(42, 'en');
        $this->assertSame(0, $result);
    }

    public function testGetCategoryIdsFromFaq(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findCategoriesFromFaq')
            ->with(42, 'en')
            ->willReturn([
                1 => ['id' => 1],
                2 => ['id' => 2],
                3 => ['id' => 3],
            ]);

        $result = $this->service->getCategoryIdsFromFaq(42, 'en');
        $this->assertSame([1, 2, 3], $result);
    }

    public function testGetCategoryIdFromName(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findCategoryIdByName')
            ->with('Test Category')
            ->willReturn(42);

        $result = $this->service->getCategoryIdFromName('Test Category');
        $this->assertSame(42, $result);
    }

    public function testGetCategoryIdFromNameReturnsFalseWhenNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findCategoryIdByName')
            ->with('Non Existent')
            ->willReturn(null);

        $result = $this->service->getCategoryIdFromName('Non Existent');
        $this->assertFalse($result);
    }

    public function testGetCategoryData(): void
    {
        $entity = new CategoryEntity();
        $entity->setId(42);

        $this->repository
            ->expects($this->once())
            ->method('findByIdAndLanguage')
            ->with(42, 'en')
            ->willReturn($entity);

        $result = $this->service->getCategoryData(42, 'en');
        $this->assertSame($entity, $result);
    }

    public function testGetCategoryDataReturnsNewEntityWhenNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findByIdAndLanguage')
            ->with(42, 'en')
            ->willReturn(null);

        $result = $this->service->getCategoryData(42, 'en');
        $this->assertInstanceOf(CategoryEntity::class, $result);
        $this->assertSame(0, $result->getId());
    }

    public function testCreate(): void
    {
        $entity = new CategoryEntity();

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($entity)
            ->willReturn(42);

        $result = $this->service->create($entity);
        $this->assertSame(42, $result);
    }

    public function testUpdate(): void
    {
        $entity = new CategoryEntity();

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with($entity)
            ->willReturn(true);

        $result = $this->service->update($entity);
        $this->assertTrue($result);
    }

    public function testDelete(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with(42, 'en')
            ->willReturn(true);

        $result = $this->service->delete(42, 'en');
        $this->assertTrue($result);
    }

    public function testMoveOwnership(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('moveOwnership')
            ->with(10, 20)
            ->willReturn(true);

        $result = $this->service->moveOwnership(10, 20);
        $this->assertTrue($result);
    }

    public function testHasLanguage(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('hasLanguage')
            ->with(42, 'de')
            ->willReturn(true);

        $result = $this->service->hasLanguage(42, 'de');
        $this->assertTrue($result);
    }

    public function testUpdateParentCategory(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('updateParentCategory')
            ->with(42, 10)
            ->willReturn(true);

        $result = $this->service->updateParentCategory(42, 10);
        $this->assertTrue($result);
    }

    public function testUpdateParentCategoryReturnsFalseWhenSameId(): void
    {
        $this->repository->expects($this->never())->method('updateParentCategory');

        $result = $this->service->updateParentCategory(42, 42);
        $this->assertFalse($result);
    }

    public function testCheckIfCategoryExists(): void
    {
        $entity = new CategoryEntity();
        $entity->setName('Test');
        $entity->setLang('en');
        $entity->setParentId(0);

        $this->repository
            ->expects($this->once())
            ->method('countByNameLangParent')
            ->with('Test', 'en', 0)
            ->willReturn(1);

        $result = $this->service->checkIfCategoryExists($entity);
        $this->assertSame(1, $result);
    }

    public function testGetCategoryLanguagesTranslated(): void
    {
        $expected = ['en' => 'English Category', 'de' => 'Deutsche Kategorie'];

        $this->repository
            ->expects($this->once())
            ->method('getCategoryLanguagesTranslated')
            ->with(42)
            ->willReturn($expected);

        $result = $this->service->getCategoryLanguagesTranslated(42);
        $this->assertSame($expected, $result);
    }

    public function testGetMissingCategories(): void
    {
        $expected = [
            ['id' => 1, 'lang' => 'de'],
            ['id' => 2, 'lang' => 'fr'],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findMissingCategories')
            ->with('en')
            ->willReturn($expected);

        $result = $this->service->getMissingCategories('en');
        $this->assertSame($expected, $result);
    }
}
