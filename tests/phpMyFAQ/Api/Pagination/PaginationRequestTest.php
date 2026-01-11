<?php

declare(strict_types=1);

namespace phpMyFAQ\Api\Pagination;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test case for PaginationRequest
 */
class PaginationRequestTest extends TestCase
{
    public function testFromRequestWithDefaults(): void
    {
        $request = new Request();
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(25, $pagination->limit);
        $this->assertEquals(25, $pagination->perPage);
        $this->assertEquals(0, $pagination->offset);
        $this->assertEquals(1, $pagination->page);
        $this->assertTrue($pagination->isPageBased);
        $this->assertFalse($pagination->isOffsetBased);
    }

    public function testFromRequestWithPageBasedPagination(): void
    {
        $request = new Request(['page' => '3', 'per_page' => '10']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(10, $pagination->limit);
        $this->assertEquals(10, $pagination->perPage);
        $this->assertEquals(20, $pagination->offset); // (page - 1) * per_page = (3 - 1) * 10
        $this->assertEquals(3, $pagination->page);
        $this->assertTrue($pagination->isPageBased);
        $this->assertFalse($pagination->isOffsetBased);
    }

    public function testFromRequestWithOffsetBasedPagination(): void
    {
        $request = new Request(['limit' => '15', 'offset' => '30']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(15, $pagination->limit);
        $this->assertEquals(15, $pagination->perPage);
        $this->assertEquals(30, $pagination->offset);
        $this->assertEquals(3, $pagination->page); // floor(30 / 15) + 1
        $this->assertFalse($pagination->isPageBased);
        $this->assertTrue($pagination->isOffsetBased);
    }

    public function testFromRequestOffsetOverridesPage(): void
    {
        // When both offset and page are provided, offset takes priority
        $request = new Request(['page' => '5', 'per_page' => '10', 'offset' => '25']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(10, $pagination->limit);
        $this->assertEquals(25, $pagination->offset); // offset takes priority
        $this->assertEquals(3, $pagination->page); // Calculated from offset: floor(25/10) + 1
        $this->assertFalse($pagination->isPageBased);
        $this->assertTrue($pagination->isOffsetBased);
    }

    public function testFromRequestWithNegativePage(): void
    {
        $request = new Request(['page' => '-1']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(1, $pagination->page); // Minimum page is 1
        $this->assertEquals(0, $pagination->offset);
    }

    public function testFromRequestWithZeroPage(): void
    {
        $request = new Request(['page' => '0']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(1, $pagination->page); // Minimum page is 1
        $this->assertEquals(0, $pagination->offset);
    }

    public function testFromRequestWithNegativeOffset(): void
    {
        $request = new Request(['offset' => '-10', 'limit' => '25']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(0, $pagination->offset); // Minimum offset is 0
        $this->assertEquals(1, $pagination->page);
    }

    public function testFromRequestWithZeroPerPage(): void
    {
        $request = new Request(['per_page' => '0']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(1, $pagination->limit); // Minimum limit is 1
        $this->assertEquals(1, $pagination->perPage);
    }

    public function testFromRequestWithNegativePerPage(): void
    {
        $request = new Request(['per_page' => '-5']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(1, $pagination->limit); // Minimum limit is 1
        $this->assertEquals(1, $pagination->perPage);
    }

    public function testFromRequestWithExcessivePerPage(): void
    {
        $request = new Request(['per_page' => '500']);
        $pagination = PaginationRequest::fromRequest($request, 25, 100);

        $this->assertEquals(100, $pagination->limit); // Capped at maxPerPage
        $this->assertEquals(100, $pagination->perPage);
    }

    public function testFromRequestWithCustomDefaults(): void
    {
        $request = new Request();
        $pagination = PaginationRequest::fromRequest($request, 50, 200);

        $this->assertEquals(50, $pagination->limit);
        $this->assertEquals(50, $pagination->perPage);
        $this->assertEquals(0, $pagination->offset);
        $this->assertEquals(1, $pagination->page);
    }

    public function testFromRequestWithInvalidPageString(): void
    {
        $request = new Request(['page' => 'invalid']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(1, $pagination->page); // Falls back to default
        $this->assertEquals(0, $pagination->offset);
    }

    public function testFromRequestWithInvalidPerPageString(): void
    {
        $request = new Request(['per_page' => 'invalid']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(25, $pagination->perPage); // Falls back to default
        $this->assertEquals(25, $pagination->limit);
    }

    public function testFromRequestWithFloatValues(): void
    {
        $request = new Request(['page' => '2.7', 'per_page' => '15.3']);
        $pagination = PaginationRequest::fromRequest($request);

        // Filter should handle float to int conversion
        $this->assertIsInt($pagination->page);
        $this->assertIsInt($pagination->perPage);
    }

    public function testFromRequestWithLimitAliasForPerPage(): void
    {
        // Test that 'limit' can be used instead of 'per_page' for page-based
        $request = new Request(['page' => '2', 'limit' => '20']);
        $pagination = PaginationRequest::fromRequest($request);

        $this->assertEquals(20, $pagination->limit);
        $this->assertEquals(20, $pagination->perPage);
        $this->assertEquals(20, $pagination->offset); // (2-1) * 20
        $this->assertEquals(2, $pagination->page);
        $this->assertTrue($pagination->isPageBased);
    }

    public function testFromRequestWithBoundaryValues(): void
    {
        // Test with maximum allowed values
        $request = new Request(['page' => '999', 'per_page' => '100']);
        $pagination = PaginationRequest::fromRequest($request, 25, 100);

        $this->assertEquals(100, $pagination->perPage);
        $this->assertEquals(999, $pagination->page);
        $this->assertEquals(99800, $pagination->offset); // (999-1) * 100
    }

    public function testFromRequestCalculatesOffsetCorrectly(): void
    {
        // Various page numbers with per_page=10
        $testCases = [
            ['page' => 1, 'expected_offset' => 0],
            ['page' => 2, 'expected_offset' => 10],
            ['page' => 5, 'expected_offset' => 40],
            ['page' => 10, 'expected_offset' => 90],
        ];

        foreach ($testCases as $testCase) {
            $request = new Request(['page' => (string) $testCase['page'], 'per_page' => '10']);
            $pagination = PaginationRequest::fromRequest($request);

            $this->assertEquals(
                $testCase['expected_offset'],
                $pagination->offset,
                "Failed for page {$testCase['page']}",
            );
        }
    }

    public function testFromRequestCalculatesPageCorrectly(): void
    {
        // Various offsets with limit=20
        $testCases = [
            ['offset' => 0, 'expected_page' => 1],
            ['offset' => 20, 'expected_page' => 2],
            ['offset' => 40, 'expected_page' => 3],
            ['offset' => 50, 'expected_page' => 3], // 50/20 = 2.5, floor(2.5) + 1 = 3
            ['offset' => 100, 'expected_page' => 6],
        ];

        foreach ($testCases as $testCase) {
            $request = new Request(['offset' => (string) $testCase['offset'], 'limit' => '20']);
            $pagination = PaginationRequest::fromRequest($request);

            $this->assertEquals(
                $testCase['expected_page'],
                $pagination->page,
                "Failed for offset {$testCase['offset']}",
            );
        }
    }
}
