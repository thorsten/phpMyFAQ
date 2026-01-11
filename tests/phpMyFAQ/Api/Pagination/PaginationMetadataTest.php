<?php

declare(strict_types=1);

namespace phpMyFAQ\Api\Pagination;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test case for PaginationMetadata
 */
class PaginationMetadataTest extends TestCase
{
    public function testConstructorWithBasicData(): void
    {
        $request = new Request(['page' => '1', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $this->assertInstanceOf(PaginationMetadata::class, $metadata);
    }

    public function testToArrayStructure(): void
    {
        $request = new Request(['page' => '1', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $array = $metadata->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('count', $array);
        $this->assertArrayHasKey('per_page', $array);
        $this->assertArrayHasKey('current_page', $array);
        $this->assertArrayHasKey('total_pages', $array);
        $this->assertArrayHasKey('offset', $array);
        $this->assertArrayHasKey('has_more', $array);
        $this->assertArrayHasKey('has_previous', $array);
        $this->assertArrayHasKey('links', $array);
    }

    public function testMetadataCalculations(): void
    {
        $request = new Request(['page' => '2', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $array = $metadata->toArray();

        $this->assertEquals(50, $array['total']);
        $this->assertEquals(10, $array['count']); // items in current page
        $this->assertEquals(10, $array['per_page']);
        $this->assertEquals(2, $array['current_page']);
        $this->assertEquals(5, $array['total_pages']); // ceil(50/10)
        $this->assertEquals(10, $array['offset']);
        $this->assertTrue($array['has_more']); // page 2 of 5
        $this->assertTrue($array['has_previous']); // page 2 has previous
    }

    public function testFirstPage(): void
    {
        $request = new Request(['page' => '1', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $array = $metadata->toArray();

        $this->assertFalse($array['has_previous']); // The first page has no previous
        $this->assertTrue($array['has_more']); // Has more pages
        $this->assertEquals(1, $array['current_page']);
    }

    public function testLastPage(): void
    {
        $request = new Request(['page' => '5', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $array = $metadata->toArray();

        $this->assertTrue($array['has_previous']); // The last page has previous
        $this->assertFalse($array['has_more']); // No more pages
        $this->assertEquals(5, $array['current_page']);
        $this->assertEquals(5, $array['total_pages']);
    }

    public function testSinglePage(): void
    {
        $request = new Request(['page' => '1', 'per_page' => '25']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(10, $paginationRequest, '/api/test');

        $array = $metadata->toArray();

        $this->assertFalse($array['has_previous']);
        $this->assertFalse($array['has_more']);
        $this->assertEquals(1, $array['current_page']);
        $this->assertEquals(1, $array['total_pages']);
    }

    public function testEmptyResults(): void
    {
        $request = new Request(['page' => '1', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(0, $paginationRequest, '/api/test');

        $array = $metadata->toArray();

        $this->assertEquals(0, $array['total']);
        $this->assertEquals(0, $array['count']);
        $this->assertEquals(1, $array['total_pages']);
        $this->assertFalse($array['has_more']);
        $this->assertFalse($array['has_previous']);
    }

    public function testLinksStructure(): void
    {
        $request = new Request(['page' => '2', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $array = $metadata->toArray();
        $links = $array['links'];

        $this->assertIsArray($links);
        $this->assertArrayHasKey('first', $links);
        $this->assertArrayHasKey('last', $links);
        $this->assertArrayHasKey('prev', $links);
        $this->assertArrayHasKey('next', $links);
    }

    public function testPageBasedLinks(): void
    {
        $request = new Request(['page' => '3', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(100, $paginationRequest, '/api/test');

        $array = $metadata->toArray();
        $links = $array['links'];

        $this->assertStringContainsString('page=1', $links['first']);
        $this->assertStringContainsString('page=10', $links['last']); // 100/10 = 10 pages
        $this->assertStringContainsString('page=2', $links['prev']);
        $this->assertStringContainsString('page=4', $links['next']);
        $this->assertStringContainsString('per_page=10', $links['first']);
    }

    public function testOffsetBasedLinks(): void
    {
        $request = new Request(['offset' => '20', 'limit' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(100, $paginationRequest, '/api/test');

        $array = $metadata->toArray();
        $links = $array['links'];

        $this->assertStringContainsString('offset=0', $links['first']);
        $this->assertStringContainsString('offset=90', $links['last']); // (10 pages - 1) * 10
        $this->assertStringContainsString('offset=10', $links['prev']);
        $this->assertStringContainsString('offset=30', $links['next']);
        $this->assertStringContainsString('limit=10', $links['first']);
    }

    public function testLinksWithExistingQueryParams(): void
    {
        $request = new Request(['page' => '2', 'per_page' => '10', 'sort' => 'name', 'filter' => 'active']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $baseUrl = '/api/test?sort=name&filter=active&page=2&per_page=10';
        $metadata = new PaginationMetadata(50, $paginationRequest, $baseUrl);

        $array = $metadata->toArray();
        $links = $array['links'];

        // Links should preserve other query parameters
        $this->assertStringContainsString('sort=name', $links['first']);
        $this->assertStringContainsString('filter=active', $links['first']);
    }

    public function testFirstPageLinksNoNull(): void
    {
        $request = new Request(['page' => '1', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $array = $metadata->toArray();
        $links = $array['links'];

        $this->assertNotNull($links['first']);
        $this->assertNotNull($links['last']);
        $this->assertNull($links['prev']); // No previous on the first page
        $this->assertNotNull($links['next']); // Has next
    }

    public function testLastPageLinksNull(): void
    {
        $request = new Request(['page' => '5', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $array = $metadata->toArray();
        $links = $array['links'];

        $this->assertNotNull($links['first']);
        $this->assertNotNull($links['last']);
        $this->assertNotNull($links['prev']); // Has previous
        $this->assertNull($links['next']); // No next on the last page
    }

    public function testActualCountOverride(): void
    {
        $request = new Request(['page' => '5', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        // The last page might have fewer items than per_page
        $metadata = new PaginationMetadata(47, $paginationRequest, '/api/test', 7);

        $array = $metadata->toArray();

        $this->assertEquals(47, $array['total']);
        $this->assertEquals(7, $array['count']); // Actual items returned
        $this->assertEquals(10, $array['per_page']); // Max per page
    }

    public function testTotalPagesCalculation(): void
    {
        $testCases = [
            ['total' => 100, 'per_page' => 10, 'expected_pages' => 10],
            ['total' => 95, 'per_page' => 10, 'expected_pages' => 10],
            ['total' => 91, 'per_page' => 10, 'expected_pages' => 10],
            ['total' => 90, 'per_page' => 10, 'expected_pages' => 9],
            ['total' => 1, 'per_page' => 10, 'expected_pages' => 1],
            ['total' => 0, 'per_page' => 10, 'expected_pages' => 1],
        ];

        foreach ($testCases as $testCase) {
            $request = new Request(['page' => '1', 'per_page' => (string) $testCase['per_page']]);
            $paginationRequest = PaginationRequest::fromRequest($request);
            $metadata = new PaginationMetadata($testCase['total'], $paginationRequest, '/api/test');

            $array = $metadata->toArray();

            $this->assertEquals(
                $testCase['expected_pages'],
                $array['total_pages'],
                "Failed for total={$testCase['total']}, per_page={$testCase['per_page']}",
            );
        }
    }

    public function testNegativeTotalHandled(): void
    {
        $request = new Request(['page' => '1', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $metadata = new PaginationMetadata(-10, $paginationRequest, '/api/test');

        $array = $metadata->toArray();

        $this->assertEquals(0, $array['total']); // Negative total converted to 0
        $this->assertEquals(0, $array['count']);
    }
}
