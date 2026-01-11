<?php

/**
 * Abstract API Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use phpMyFAQ\Api\Filtering\FilterRequest;
use phpMyFAQ\Api\Pagination\PaginationMetadata;
use phpMyFAQ\Api\Pagination\PaginationRequest;
use phpMyFAQ\Api\Response\ApiResponse;
use phpMyFAQ\Api\Sorting\SortRequest;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class AbstractApiController
 *
 * Base controller for all API endpoints providing standardized pagination,
 * sorting, filtering, and response formatting.
 */
abstract class AbstractApiController extends AbstractController
{
    protected const int DEFAULT_PER_PAGE = 25;
    protected const int MAX_PER_PAGE = 100;

    /**
     * Constructor
     *
     * Verifies that API access is enabled before allowing any API operations.
     *
     * @throws UnauthorizedHttpException If API is not enabled
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        // Verify API is enabled
        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException(challenge: 'API is not enabled');
        }
    }

    /**
     * Parses pagination parameters from the request
     *
     * Supports both page-based (page + per_page) and offset-based (limit + offset) pagination.
     *
     * @param int $defaultPerPage Default items per page
     * @param int|null $maxPerPage Maximum items per page (uses class constant if null)
     * @return PaginationRequest
     */
    protected function getPaginationRequest(
        int $defaultPerPage = self::DEFAULT_PER_PAGE,
        ?int $maxPerPage = null,
    ): PaginationRequest {
        $maxPerPage ??= self::MAX_PER_PAGE;
        $request = Request::createFromGlobals();

        return PaginationRequest::fromRequest($request, $defaultPerPage, $maxPerPage);
    }

    /**
     * Parses sorting parameters from the request
     *
     * Validates sort field against whitelist to prevent SQL injection.
     *
     * @param array $allowedFields Whitelist of allowed sort fields
     * @param string|null $defaultField Default sort field if none specified
     * @param string $defaultOrder Default sort order (asc or desc)
     * @return SortRequest
     */
    protected function getSortRequest(
        array $allowedFields,
        ?string $defaultField = null,
        string $defaultOrder = 'asc',
    ): SortRequest {
        $request = Request::createFromGlobals();

        return SortRequest::fromRequest($request, $allowedFields, $defaultField, $defaultOrder);
    }

    /**
     * Parses filter parameters from the request
     *
     * Validates filters against allowed filters configuration.
     *
     * @param array $allowedFilters Configuration of allowed filters with their types
     * @return FilterRequest
     *
     * Example $allowedFilters:
     * [
     *     'active' => 'bool',
     *     'language' => 'string',
     *     'category_id' => 'int',
     *     'created_from' => 'date',
     * ]
     */
    protected function getFilterRequest(array $allowedFilters): FilterRequest
    {
        $request = Request::createFromGlobals();

        return FilterRequest::fromRequest($request, $allowedFilters);
    }

    /**
     * Creates a paginated API response with standardized envelope format
     *
     * @param array $data The response data
     * @param int $total Total number of items across all pages
     * @param PaginationRequest $pagination Pagination parameters
     * @param SortRequest|null $sort Optional sorting information
     * @param FilterRequest|null $filters Optional filtering information
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    protected function paginatedResponse(
        array $data,
        int $total,
        PaginationRequest $pagination,
        ?SortRequest $sort = null,
        ?FilterRequest $filters = null,
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        $request = Request::createFromGlobals();

        // Build base URL for pagination links
        $baseUrl = $request->getPathInfo();
        if ($request->getQueryString()) {
            $baseUrl .= '?' . $request->getQueryString();
        }

        // Generate pagination metadata
        $paginationMetadata = new PaginationMetadata(
            total: $total,
            request: $pagination,
            baseUrl: $baseUrl,
            actualCount: count($data),
        );

        // Build response with envelope
        $responseData = ApiResponse::success(
            data: $data,
            pagination: $paginationMetadata,
            sort: $sort,
            filters: $filters,
        );

        return new JsonResponse($responseData, $status);
    }

    /**
     * Creates a simple API response with standardized envelope format (no pagination)
     *
     * Use this for non-paginated endpoints or single-item responses.
     *
     * @param array|object $data The response data
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    protected function apiResponse(array|object $data, int $status = Response::HTTP_OK): JsonResponse
    {
        $responseData = ApiResponse::success(data: $data);

        return new JsonResponse($responseData, $status);
    }

    /**
     * Creates an error response with standardized format
     *
     * @param string $message Error message
     * @param string $code Error code (e.g., 'INVALID_PARAMETER', 'NOT_FOUND')
     * @param int $status HTTP status code
     * @param array|null $details Optional error details
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        string $code = 'ERROR',
        int $status = Response::HTTP_BAD_REQUEST,
        ?array $details = null,
    ): JsonResponse {
        $responseData = ApiResponse::error(message: $message, code: $code, details: $details);

        return new JsonResponse($responseData, $status);
    }
}
