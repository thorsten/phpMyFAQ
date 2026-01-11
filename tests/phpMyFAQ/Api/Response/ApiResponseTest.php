<?php

declare(strict_types=1);

namespace phpMyFAQ\Api\Response;

use phpMyFAQ\Api\Filtering\FilterRequest;
use phpMyFAQ\Api\Pagination\PaginationMetadata;
use phpMyFAQ\Api\Pagination\PaginationRequest;
use phpMyFAQ\Api\Sorting\SortRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test case for ApiResponse
 */
class ApiResponseTest extends TestCase
{
    public function testSuccessWithDataOnly(): void
    {
        $data = ['item1', 'item2', 'item3'];
        $response = ApiResponse::success($data);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertTrue($response['success']);
        $this->assertEquals($data, $response['data']);
    }

    public function testSuccessWithEmptyData(): void
    {
        $response = ApiResponse::success([]);

        $this->assertTrue($response['success']);
        $this->assertIsArray($response['data']);
        $this->assertEmpty($response['data']);
    }

    public function testSuccessWithObjectData(): void
    {
        $data = (object) ['id' => 1, 'name' => 'Test'];
        $response = ApiResponse::success($data);

        $this->assertTrue($response['success']);
        $this->assertEquals($data, $response['data']);
    }

    public function testSuccessWithPagination(): void
    {
        $data = ['item1', 'item2'];
        $request = new Request(['page' => '2', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $pagination = new PaginationMetadata(50, $paginationRequest, '/api/test');

        $response = ApiResponse::success($data, $pagination);

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('pagination', $response['meta']);
        $this->assertEquals(50, $response['meta']['pagination']['total']);
        $this->assertEquals(10, $response['meta']['pagination']['per_page']);
        $this->assertEquals(2, $response['meta']['pagination']['current_page']);
    }

    public function testSuccessWithSorting(): void
    {
        $data = ['item1', 'item2'];
        $request = new Request(['sort' => 'name', 'order' => 'desc']);
        $sort = SortRequest::fromRequest($request, ['name']);

        $response = ApiResponse::success($data, null, $sort);

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('sorting', $response['meta']);
        $this->assertEquals('name', $response['meta']['sorting']['field']);
        $this->assertEquals('desc', $response['meta']['sorting']['order']);
    }

    public function testSuccessWithFilters(): void
    {
        $data = ['item1', 'item2'];
        $request = new Request(['active' => 'true', 'category_id' => '5']);
        $filters = FilterRequest::fromRequest($request, ['active' => 'bool', 'category_id' => 'int']);

        $response = ApiResponse::success($data, null, null, $filters);

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('filters', $response['meta']);
        $this->assertTrue($response['meta']['filters']['active']);
        $this->assertEquals(5, $response['meta']['filters']['category_id']);
    }

    public function testSuccessWithAllMetadata(): void
    {
        $data = ['item1', 'item2'];

        // Pagination
        $request = new Request(['page' => '1', 'per_page' => '25']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $pagination = new PaginationMetadata(50, $paginationRequest, '/api/test');

        // Sorting
        $sortRequest = new Request(['sort' => 'created', 'order' => 'desc']);
        $sort = SortRequest::fromRequest($sortRequest, ['created']);

        // Filtering
        $filterRequest = new Request(['active' => 'true']);
        $filters = FilterRequest::fromRequest($filterRequest, ['active' => 'bool']);

        $response = ApiResponse::success($data, $pagination, $sort, $filters);

        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('pagination', $response['meta']);
        $this->assertArrayHasKey('sorting', $response['meta']);
        $this->assertArrayHasKey('filters', $response['meta']);
    }

    public function testSuccessWithNoMetadata(): void
    {
        $data = ['item1', 'item2'];
        $response = ApiResponse::success($data);

        // Meta should not be present when no metadata is provided
        $this->assertArrayNotHasKey('meta', $response);
    }

    public function testSuccessWithNullSort(): void
    {
        $data = ['item1'];
        $request = new Request(); // No sort parameters
        $sort = SortRequest::fromRequest($request, ['name']);

        $response = ApiResponse::success($data, null, $sort);

        // Meta should not be present when sort has no field
        $this->assertArrayNotHasKey('meta', $response);
    }

    public function testSuccessWithNoFilters(): void
    {
        $data = ['item1'];
        $request = new Request(); // No filter parameters
        $filters = FilterRequest::fromRequest($request, ['active' => 'bool']);

        $response = ApiResponse::success($data, null, null, $filters);

        // Meta should not be present when no filters are set
        $this->assertArrayNotHasKey('meta', $response);
    }

    public function testErrorWithMessageAndCode(): void
    {
        $response = ApiResponse::error('Something went wrong', 'ERROR_CODE');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('error', $response);
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('code', $response['error']);
        $this->assertArrayHasKey('message', $response['error']);
        $this->assertEquals('ERROR_CODE', $response['error']['code']);
        $this->assertEquals('Something went wrong', $response['error']['message']);
    }

    public function testErrorWithDefaultCode(): void
    {
        $response = ApiResponse::error('Error occurred');

        $this->assertEquals('ERROR', $response['error']['code']);
        $this->assertEquals('Error occurred', $response['error']['message']);
    }

    public function testErrorWithDetails(): void
    {
        $details = [
            'field' => 'email',
            'reason' => 'Invalid format',
        ];
        $response = ApiResponse::error('Validation failed', 'VALIDATION_ERROR', $details);

        $this->assertArrayHasKey('details', $response['error']);
        $this->assertEquals($details, $response['error']['details']);
    }

    public function testErrorWithoutDetails(): void
    {
        $response = ApiResponse::error('Error', 'CODE');

        $this->assertArrayNotHasKey('details', $response['error']);
    }

    public function testErrorStructure(): void
    {
        $response = ApiResponse::error('Not found', 'NOT_FOUND', ['resource' => 'user']);

        $this->assertFalse($response['success']);
        $this->assertIsArray($response['error']);
        $this->assertArrayHasKey('code', $response['error']);
        $this->assertArrayHasKey('message', $response['error']);
        $this->assertArrayHasKey('details', $response['error']);
    }

    public function testSuccessResponseDoesNotIncludeError(): void
    {
        $response = ApiResponse::success(['data']);

        $this->assertArrayNotHasKey('error', $response);
    }

    public function testErrorResponseDoesNotIncludeData(): void
    {
        $response = ApiResponse::error('Error', 'CODE');

        $this->assertArrayNotHasKey('data', $response);
    }

    public function testSuccessWithLargeDataset(): void
    {
        $data = array_fill(0, 1000, 'item');
        $response = ApiResponse::success($data);

        $this->assertTrue($response['success']);
        $this->assertCount(1000, $response['data']);
    }

    public function testSuccessWithNestedData(): void
    {
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
            ],
            'meta' => ['total' => 2],
        ];
        $response = ApiResponse::success($data);

        $this->assertTrue($response['success']);
        $this->assertEquals($data, $response['data']);
    }

    public function testErrorWithEmptyMessage(): void
    {
        $response = ApiResponse::error('', 'CODE');

        $this->assertEquals('', $response['error']['message']);
        $this->assertEquals('CODE', $response['error']['code']);
    }

    public function testErrorWithLongMessage(): void
    {
        $longMessage = str_repeat('Error message. ', 100);
        $response = ApiResponse::error($longMessage, 'CODE');

        $this->assertEquals($longMessage, $response['error']['message']);
    }

    public function testErrorWithSpecialCharacters(): void
    {
        $message = "Error: <script>alert('xss')</script>";
        $response = ApiResponse::error($message, 'XSS_ATTEMPT');

        $this->assertEquals($message, $response['error']['message']);

        // Note: Sanitization should be done by caller if needed
    }

    public function testSuccessWithAssociativeArray(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test',
            'email' => 'test@example.com',
        ];
        $response = ApiResponse::success($data);

        $this->assertTrue($response['success']);
        $this->assertEquals($data, $response['data']);
    }

    public function testSuccessWithNumericArray(): void
    {
        $data = [1, 2, 3, 4, 5];
        $response = ApiResponse::success($data);

        $this->assertTrue($response['success']);
        $this->assertEquals($data, $response['data']);
    }

    public function testMetadataOnlyIncludesProvidedComponents(): void
    {
        $data = ['item'];
        $request = new Request(['page' => '1', 'per_page' => '10']);
        $paginationRequest = PaginationRequest::fromRequest($request);
        $pagination = new PaginationMetadata(10, $paginationRequest, '/api/test');

        $response = ApiResponse::success($data, $pagination);

        $this->assertArrayHasKey('pagination', $response['meta']);
        $this->assertArrayNotHasKey('sorting', $response['meta']);
        $this->assertArrayNotHasKey('filters', $response['meta']);
    }

    public function testErrorWithComplexDetails(): void
    {
        $details = [
            'errors' => [
                ['field' => 'email', 'message' => 'Invalid format'],
                ['field' => 'password', 'message' => 'Too short'],
            ],
            'timestamp' => '2026-01-15T10:30:00Z',
        ];
        $response = ApiResponse::error('Validation failed', 'VALIDATION_ERROR', $details);

        $this->assertEquals($details, $response['error']['details']);
        $this->assertIsArray($response['error']['details']['errors']);
        $this->assertCount(2, $response['error']['details']['errors']);
    }
}
