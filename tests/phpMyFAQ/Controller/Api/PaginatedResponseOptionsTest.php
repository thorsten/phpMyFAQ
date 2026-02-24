<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Api\Filtering\FilterRequest;
use phpMyFAQ\Api\Sorting\SortRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaginatedResponseOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new PaginatedResponseOptions();

        $this->assertNull($options->sort);
        $this->assertNull($options->filters);
        $this->assertSame(Response::HTTP_OK, $options->status);
    }

    public function testCustomValues(): void
    {
        $request = new Request(query: ['sort' => 'id', 'order' => 'desc', 'active' => 'true']);
        $sort = SortRequest::fromRequest($request, allowedFields: ['id'], defaultField: 'id');
        $filters = FilterRequest::fromRequest($request, allowedFilters: ['active' => 'bool']);

        $options = new PaginatedResponseOptions(sort: $sort, filters: $filters, status: Response::HTTP_CREATED);

        $this->assertSame($sort, $options->sort);
        $this->assertSame($filters, $options->filters);
        $this->assertSame(Response::HTTP_CREATED, $options->status);
    }
}
