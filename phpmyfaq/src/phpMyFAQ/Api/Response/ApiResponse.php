<?php

/**
 * API Response Envelope
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

namespace phpMyFAQ\Api\Response;

use phpMyFAQ\Api\Filtering\FilterRequest;
use phpMyFAQ\Api\Pagination\PaginationMetadata;
use phpMyFAQ\Api\Sorting\SortRequest;

/**
 * Class ApiResponse
 *
 * Provides a standardized response envelope for API responses including
 * success/error status, data payload, and metadata (pagination, sorting, filtering).
 */
class ApiResponse
{
    /**
     * Creates a successful API response with data and optional metadata
     *
     * @param array|object $data The response data
     * @param PaginationMetadata|null $pagination Optional pagination metadata
     * @param SortRequest|null $sort Optional sorting information
     * @param FilterRequest|null $filters Optional filtering information
     * @return array Standardized response array
     */
    public static function success(
        array|object $data,
        ?PaginationMetadata $pagination = null,
        ?SortRequest $sort = null,
        ?FilterRequest $filters = null,
    ): array {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        // Build metadata if any is present
        $meta = self::buildMetadata($pagination, $sort, $filters);
        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return $response;
    }

    /**
     * Creates an error API response
     *
     * @param string $message Error message
     * @param string $code Error code
     * @param array|null $details Optional error details
     * @return array Standardized error response array
     */
    public static function error(string $message, string $code = 'ERROR', ?array $details = null): array
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        return $response;
    }

    /**
     * Builds the metadata section of the response
     *
     * @param PaginationMetadata|null $pagination Pagination metadata
     * @param SortRequest|null $sort Sort information
     * @param FilterRequest|null $filters Filter information
     * @return array|null Metadata array or null if no metadata
     */
    private static function buildMetadata(
        ?PaginationMetadata $pagination,
        ?SortRequest $sort,
        ?FilterRequest $filters,
    ): ?array {
        $meta = [];

        if ($pagination !== null) {
            $meta['pagination'] = $pagination->toArray();
        }

        if ($sort !== null && $sort->hasSort()) {
            $meta['sorting'] = $sort->toArray();
        }

        if ($filters !== null && $filters->hasFilters()) {
            $meta['filters'] = $filters->toArray();
        }

        return empty($meta) ? null : $meta;
    }
}
