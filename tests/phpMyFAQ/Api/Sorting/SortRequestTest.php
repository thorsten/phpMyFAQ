<?php

declare(strict_types=1);

namespace phpMyFAQ\Api\Sorting;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test case for SortRequest
 */
class SortRequestTest extends TestCase
{
    public function testFromRequestWithNoSortParameters(): void
    {
        $request = new Request();
        $allowedFields = ['id', 'name', 'created'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertNull($sort->getField());
        $this->assertEquals('asc', $sort->getOrder());
        $this->assertFalse($sort->hasSort());
    }

    public function testFromRequestWithValidSortField(): void
    {
        $request = new Request(['sort' => 'name', 'order' => 'desc']);
        $allowedFields = ['id', 'name', 'created'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertEquals('name', $sort->getField());
        $this->assertEquals('desc', $sort->getOrder());
        $this->assertEquals('DESC', $sort->getOrderSql());
        $this->assertTrue($sort->hasSort());
    }

    public function testFromRequestWithInvalidSortField(): void
    {
        $request = new Request(['sort' => 'invalid_field']);
        $allowedFields = ['id', 'name', 'created'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertNull($sort->getField());
        $this->assertFalse($sort->hasSort());
    }

    public function testFromRequestUsesDefaultField(): void
    {
        $request = new Request();
        $allowedFields = ['id', 'name', 'created'];
        $sort = SortRequest::fromRequest($request, $allowedFields, 'created');

        $this->assertEquals('created', $sort->getField());
        $this->assertTrue($sort->hasSort());
    }

    public function testFromRequestDefaultFieldNotInAllowedList(): void
    {
        $request = new Request();
        $allowedFields = ['id', 'name'];
        $sort = SortRequest::fromRequest($request, $allowedFields, 'invalid');

        $this->assertNull($sort->getField()); // Invalid default is ignored
        $this->assertFalse($sort->hasSort());
    }

    public function testFromRequestWithAscendingOrder(): void
    {
        $request = new Request(['sort' => 'name', 'order' => 'asc']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertEquals('asc', $sort->getOrder());
        $this->assertEquals('ASC', $sort->getOrderSql());
    }

    public function testFromRequestWithDescendingOrder(): void
    {
        $request = new Request(['sort' => 'name', 'order' => 'desc']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertEquals('desc', $sort->getOrder());
        $this->assertEquals('DESC', $sort->getOrderSql());
    }

    public function testFromRequestWithAscendingFullWord(): void
    {
        $request = new Request(['sort' => 'name', 'order' => 'ascending']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertEquals('asc', $sort->getOrder());
    }

    public function testFromRequestWithDescendingFullWord(): void
    {
        $request = new Request(['sort' => 'name', 'order' => 'descending']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertEquals('desc', $sort->getOrder());
    }

    public function testFromRequestWithInvalidOrder(): void
    {
        $request = new Request(['sort' => 'name', 'order' => 'invalid']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertEquals('asc', $sort->getOrder()); // Falls back to default
    }

    public function testFromRequestWithCustomDefaultOrder(): void
    {
        $request = new Request(['sort' => 'name']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields, null, 'desc');

        $this->assertEquals('desc', $sort->getOrder());
    }

    public function testFromRequestCaseInsensitiveOrder(): void
    {
        $testCases = ['ASC', 'Asc', 'DESC', 'Desc', 'ASCENDING', 'DESCENDING'];

        foreach ($testCases as $orderValue) {
            $request = new Request(['sort' => 'name', 'order' => $orderValue]);
            $allowedFields = ['name'];
            $sort = SortRequest::fromRequest($request, $allowedFields);

            $this->assertContains($sort->getOrder(), ['asc', 'desc']);
        }
    }

    public function testToSqlOrderByWithValidSort(): void
    {
        $request = new Request(['sort' => 'name', 'order' => 'desc']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $sql = $sort->toSqlOrderBy();

        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('DESC', $sql);
        $this->assertStringContainsString('`', $sql); // Should have backticks for escaping
    }

    public function testToSqlOrderByWithNoSort(): void
    {
        $request = new Request();
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $sql = $sort->toSqlOrderBy();

        $this->assertEquals('', $sql); // Empty string when no sort
    }

    public function testToSqlOrderByEscapesFieldName(): void
    {
        $request = new Request(['sort' => 'user_name', 'order' => 'asc']);
        $allowedFields = ['user_name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $sql = $sort->toSqlOrderBy();

        $this->assertStringStartsWith('`', $sql);
        $this->assertStringContainsString('`', $sql);
    }

    public function testToArrayWithValidSort(): void
    {
        $request = new Request(['sort' => 'created', 'order' => 'desc']);
        $allowedFields = ['created'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $array = $sort->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('field', $array);
        $this->assertArrayHasKey('order', $array);
        $this->assertEquals('created', $array['field']);
        $this->assertEquals('desc', $array['order']);
    }

    public function testToArrayWithNoSort(): void
    {
        $request = new Request();
        $allowedFields = ['created'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $array = $sort->toArray();

        $this->assertNull($array);
    }

    public function testSqlInjectionPrevention(): void
    {
        // Attempt SQL injection in the sort field
        $request = new Request(['sort' => 'name; DROP TABLE users--', 'order' => 'asc']);
        $allowedFields = ['name', 'id'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        // Field should be rejected because it's not in an allowed list
        $this->assertNull($sort->getField());
        $this->assertFalse($sort->hasSort());
    }

    public function testSqlInjectionInOrder(): void
    {
        // Attempt SQL injection in order
        $request = new Request(['sort' => 'name', 'order' => 'asc; DELETE FROM users']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        // Order should fall back to default because it's invalid
        $this->assertEquals('asc', $sort->getOrder());
    }

    public function testWhitelistEnforcementStrict(): void
    {
        $request = new Request(['sort' => 'password', 'order' => 'asc']);
        $allowedFields = ['id', 'name', 'email']; // 'password' not in list
        $sort = SortRequest::fromRequest($request, $allowedFields);

        // Should reject field not in the whitelist
        $this->assertNull($sort->getField());
        $this->assertFalse($sort->hasSort());
    }

    public function testMultipleFieldsInAllowedList(): void
    {
        $allowedFields = ['id', 'name', 'email', 'created', 'updated'];

        foreach ($allowedFields as $field) {
            $request = new Request(['sort' => $field]);
            $sort = SortRequest::fromRequest($request, $allowedFields);

            $this->assertEquals($field, $sort->getField());
            $this->assertTrue($sort->hasSort());
        }
    }

    public function testEmptyAllowedFieldsList(): void
    {
        $request = new Request(['sort' => 'name']);
        $allowedFields = []; // Empty whitelist
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertNull($sort->getField());
        $this->assertFalse($sort->hasSort());
    }

    public function testSpecialCharactersInFieldName(): void
    {
        // Fields with underscores, numbers should work if whitelisted
        $request = new Request(['sort' => 'user_name_123']);
        $allowedFields = ['user_name_123'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertEquals('user_name_123', $sort->getField());
    }

    public function testEmptySortParameter(): void
    {
        $request = new Request(['sort' => '']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertNull($sort->getField());
        $this->assertFalse($sort->hasSort());
    }

    public function testEmptyOrderParameter(): void
    {
        $request = new Request(['sort' => 'name', 'order' => '']);
        $allowedFields = ['name'];
        $sort = SortRequest::fromRequest($request, $allowedFields);

        $this->assertEquals('asc', $sort->getOrder()); // Falls back to default
    }
}
