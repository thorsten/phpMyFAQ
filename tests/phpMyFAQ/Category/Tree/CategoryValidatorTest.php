<?php

declare(strict_types=1);

namespace phpMyFAQ\Category\Tree;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CategoryValidator::class)]
final class CategoryValidatorTest extends TestCase
{
    private CategoryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CategoryValidator();
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function categoryProvider(): iterable
    {
        yield 'valid' => [['id' => 1, 'parent_id' => 0], true];
        yield 'valid with extra keys' => [['id' => 1, 'parent_id' => 0, 'name' => 'x'], true];
        yield 'null parent still counts as present' => [['id' => 1, 'parent_id' => null], true];
        yield 'missing parent_id' => [['id' => 1], false];
        yield 'missing id' => [['parent_id' => 0], false];
        yield 'empty array' => [[], false];
        yield 'not an array' => ['category', false];
        yield 'null' => [null, false];
        yield 'object' => [(object) ['id' => 1, 'parent_id' => 0], false];
    }

    #[DataProvider('categoryProvider')]
    public function testIsValidCategory(mixed $category, bool $expected): void
    {
        $this->assertSame($expected, $this->validator->isValidCategory($category));
    }

    public function testIsDirectChildMatchesParentAndPositiveIntegerId(): void
    {
        $category = ['id' => 5, 'parent_id' => 2];

        $this->assertTrue($this->validator->isDirectChild($category, 5, 2));
    }

    public function testIsDirectChildAcceptsNumericStringParent(): void
    {
        $this->assertTrue($this->validator->isDirectChild(['parent_id' => '2'], 5, 2));
    }

    public function testIsDirectChildRejectsOtherParent(): void
    {
        $this->assertFalse($this->validator->isDirectChild(['parent_id' => 3], 5, 2));
    }

    public function testIsDirectChildRejectsMissingParentKey(): void
    {
        $this->assertFalse($this->validator->isDirectChild(['id' => 5], 5, 2));
    }

    public function testIsDirectChildRejectsNonPositiveOrNonIntegerIds(): void
    {
        $category = ['parent_id' => 2];

        $this->assertFalse($this->validator->isDirectChild($category, 0, 2));
        $this->assertFalse($this->validator->isDirectChild($category, -1, 2));
        $this->assertFalse($this->validator->isDirectChild($category, '5', 2));
        $this->assertFalse($this->validator->isDirectChild($category, null, 2));
    }

    public function testCollectDirectChildrenReturnsMatchingKeysInOrder(): void
    {
        $categories = [
            1 => ['id' => 1, 'parent_id' => 0],
            2 => ['id' => 2, 'parent_id' => 1],
            3 => ['id' => 3, 'parent_id' => 0],
            4 => ['id' => 4, 'parent_id' => 1],
            5 => ['id' => 5, 'parent_id' => 4],
        ];

        $this->assertSame([1, 3], $this->validator->collectDirectChildren($categories, 0));
        $this->assertSame([2, 4], $this->validator->collectDirectChildren($categories, 1));
        $this->assertSame([5], $this->validator->collectDirectChildren($categories, 4));
        $this->assertSame([], $this->validator->collectDirectChildren($categories, 5));
    }

    public function testCollectDirectChildrenSkipsRowsWithoutParentId(): void
    {
        $categories = [
            1 => ['id' => 1],
            2 => ['id' => 2, 'parent_id' => 0],
        ];

        $this->assertSame([2], $this->validator->collectDirectChildren($categories, 0));
    }

    public function testCollectDirectChildrenReturnsEmptyArrayForEmptyInput(): void
    {
        $this->assertSame([], $this->validator->collectDirectChildren([], 0));
    }
}
