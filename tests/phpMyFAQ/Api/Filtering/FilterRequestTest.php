<?php

declare(strict_types=1);

namespace phpMyFAQ\Api\Filtering;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test case for FilterRequest
 */
class FilterRequestTest extends TestCase
{
    public function testFromRequestWithNoFilters(): void
    {
        $request = new Request();
        $allowedFilters = ['active' => 'bool', 'name' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertFalse($filter->hasFilters());
        $this->assertEmpty($filter->getFilters());
        $this->assertNull($filter->toArray());
    }

    public function testFromRequestWithStringFilter(): void
    {
        $request = new Request(['name' => 'John Doe']);
        $allowedFilters = ['name' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->hasFilters());
        $this->assertTrue($filter->has('name'));
        $this->assertEquals('John Doe', $filter->get('name'));
    }

    public function testFromRequestWithIntegerFilter(): void
    {
        $request = new Request(['category_id' => '42']);
        $allowedFilters = ['category_id' => 'int'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('category_id'));
        $this->assertEquals(42, $filter->get('category_id'));
        $this->assertIsInt($filter->get('category_id'));
    }

    public function testFromRequestWithBooleanFilterTrue(): void
    {
        $testCases = ['true', '1', 'yes', 'on', 'TRUE', 'Yes', 'ON'];

        foreach ($testCases as $value) {
            $request = new Request(['active' => $value]);
            $allowedFilters = ['active' => 'bool'];
            $filter = FilterRequest::fromRequest($request, $allowedFilters);

            $this->assertTrue($filter->get('active'), "Failed for value: $value");
            $this->assertIsBool($filter->get('active'));
        }
    }

    public function testFromRequestWithBooleanFilterFalse(): void
    {
        $testCases = ['false', '0', 'no', 'off', 'FALSE', 'No', 'OFF', ''];

        foreach ($testCases as $value) {
            $request = new Request(['active' => $value]);
            $allowedFilters = ['active' => 'bool'];
            $filter = FilterRequest::fromRequest($request, $allowedFilters);

            $this->assertFalse($filter->get('active'), "Failed for value: $value");
            $this->assertIsBool($filter->get('active'));
        }
    }

    public function testFromRequestWithDateFilter(): void
    {
        $request = new Request(['created' => '2026-01-15']);
        $allowedFilters = ['created' => 'date'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('created'));
        $this->assertEquals('2026-01-15', $filter->get('created'));
    }

    public function testFromRequestWithInvalidDateFilter(): void
    {
        $request = new Request(['created' => '2026-13-45']); // Invalid date
        $allowedFilters = ['created' => 'date'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertFalse($filter->has('created')); // Invalid date rejected
    }

    public function testFromRequestWithDateTimeFilter(): void
    {
        $request = new Request(['created_at' => '2026-01-15T10:30:00']);
        $allowedFilters = ['created_at' => 'datetime'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('created_at'));
        // DateTime should be normalized to Y-m-d H:i:s format
        $this->assertStringContainsString('2026-01-15', $filter->get('created_at'));
    }

    public function testFromRequestWithEmailFilter(): void
    {
        $request = new Request(['email' => 'test@example.com']);
        $allowedFilters = ['email' => 'email'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('email'));
        $this->assertEquals('test@example.com', $filter->get('email'));
    }

    public function testFromRequestWithInvalidEmailFilter(): void
    {
        $request = new Request(['email' => 'not-an-email']);
        $allowedFilters = ['email' => 'email'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertFalse($filter->has('email')); // Invalid email rejected
    }

    public function testFromRequestWithFloatFilter(): void
    {
        $request = new Request(['price' => '19.99']);
        $allowedFilters = ['price' => 'float'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('price'));
        $this->assertEquals(19.99, $filter->get('price'));
        $this->assertIsFloat($filter->get('price'));
    }

    public function testFromRequestWithFilterArraySyntax(): void
    {
        $request = new Request(['filter' => ['category_id' => '5', 'active' => 'true']]);
        $allowedFilters = ['category_id' => 'int', 'active' => 'bool'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('category_id'));
        $this->assertTrue($filter->has('active'));
        $this->assertEquals(5, $filter->get('category_id'));
        $this->assertTrue($filter->get('active'));
    }

    public function testFromRequestDirectParameterTakesPrecedence(): void
    {
        // Direct parameter should be checked before a filter array
        $request = new Request(['active' => 'true', 'filter' => ['active' => 'false']]);
        $allowedFilters = ['active' => 'bool'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->get('active')); // Direct parameter wins
    }

    public function testFromRequestMultipleFilters(): void
    {
        $request = new Request([
            'active' => 'true',
            'category_id' => '10',
            'name' => 'Test',
            'created' => '2026-01-15',
        ]);
        $allowedFilters = [
            'active' => 'bool',
            'category_id' => 'int',
            'name' => 'string',
            'created' => 'date',
        ];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->hasFilters());
        $this->assertCount(4, $filter->getFilters());
        $this->assertTrue($filter->get('active'));
        $this->assertEquals(10, $filter->get('category_id'));
        $this->assertEquals('Test', $filter->get('name'));
        $this->assertEquals('2026-01-15', $filter->get('created'));
    }

    public function testFromRequestIgnoresDisallowedFilters(): void
    {
        $request = new Request(['allowed' => 'value', 'disallowed' => 'value']);
        $allowedFilters = ['allowed' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('allowed'));
        $this->assertFalse($filter->has('disallowed'));
    }

    public function testGetWithDefault(): void
    {
        $request = new Request();
        $allowedFilters = ['name' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertEquals('default', $filter->get('name', 'default'));
        $this->assertNull($filter->get('name'));
    }

    public function testToArrayReturnsFilters(): void
    {
        $request = new Request(['active' => 'true', 'name' => 'Test']);
        $allowedFilters = ['active' => 'bool', 'name' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $array = $filter->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('active', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertTrue($array['active']);
        $this->assertEquals('Test', $array['name']);
    }

    public function testToArrayReturnsNullWhenNoFilters(): void
    {
        $request = new Request();
        $allowedFilters = ['name' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertNull($filter->toArray());
    }

    public function testGetFiltersReturnsAllFilters(): void
    {
        $request = new Request(['active' => '1', 'category_id' => '5']);
        $allowedFilters = ['active' => 'bool', 'category_id' => 'int'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $filters = $filter->getFilters();

        $this->assertIsArray($filters);
        $this->assertCount(2, $filters);
        $this->assertArrayHasKey('active', $filters);
        $this->assertArrayHasKey('category_id', $filters);
    }

    public function testSpecialCharactersSanitized(): void
    {
        $request = new Request(['name' => '<script>alert("xss")</script>']);
        $allowedFilters = ['name' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $name = $filter->get('name');
        $this->assertStringNotContainsString('<script>', $name);
        $this->assertStringNotContainsString('</script>', $name);
    }

    public function testInvalidIntegerReturnsNull(): void
    {
        $request = new Request(['category_id' => 'not-a-number']);
        $allowedFilters = ['category_id' => 'int'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertFalse($filter->has('category_id')); // Invalid int rejected
    }

    public function testInvalidFloatReturnsNull(): void
    {
        $request = new Request(['price' => 'not-a-float']);
        $allowedFilters = ['price' => 'float'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertFalse($filter->has('price')); // Invalid float rejected
    }

    public function testZeroValuesHandled(): void
    {
        $request = new Request(['count' => '0', 'price' => '0.00']);
        $allowedFilters = ['count' => 'int', 'price' => 'float'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('count'));
        $this->assertTrue($filter->has('price'));
        $this->assertEquals(0, $filter->get('count'));
        $this->assertEquals(0.0, $filter->get('price'));
    }

    public function testNegativeValuesHandled(): void
    {
        $request = new Request(['offset' => '-10', 'price' => '-5.99']);
        $allowedFilters = ['offset' => 'int', 'price' => 'float'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertEquals(-10, $filter->get('offset'));
        $this->assertEquals(-5.99, $filter->get('price'));
    }

    public function testEmptyStringFilter(): void
    {
        $request = new Request(['name' => '']);
        $allowedFilters = ['name' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        // Empty string is a valid filter value
        $this->assertTrue($filter->has('name'));
        $this->assertEquals('', $filter->get('name'));
    }

    public function testWhitespaceInStringFilter(): void
    {
        $request = new Request(['name' => '  test  ']);
        $allowedFilters = ['name' => 'string'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        // Whitespace should be preserved
        $this->assertTrue($filter->has('name'));
        $this->assertStringContainsString('test', $filter->get('name'));
    }

    public function testUnknownFilterTypeDefaultsToString(): void
    {
        $request = new Request(['field' => 'value']);
        $allowedFilters = ['field' => 'unknown_type'];
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('field'));
        $this->assertEquals('value', $filter->get('field'));
    }

    public function testBooleanAliases(): void
    {
        $request = new Request(['active' => 'true']);
        $allowedFilters = ['active' => 'boolean']; // 'boolean' alias for 'bool'
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('active'));
        $this->assertTrue($filter->get('active'));
    }

    public function testIntegerAliases(): void
    {
        $request = new Request(['count' => '42']);
        $allowedFilters = ['count' => 'integer']; // 'integer' alias for 'int'
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('count'));
        $this->assertEquals(42, $filter->get('count'));
    }

    public function testDoubleAlias(): void
    {
        $request = new Request(['price' => '19.99']);
        $allowedFilters = ['price' => 'double']; // 'double' alias for 'float'
        $filter = FilterRequest::fromRequest($request, $allowedFilters);

        $this->assertTrue($filter->has('price'));
        $this->assertEquals(19.99, $filter->get('price'));
    }
}
