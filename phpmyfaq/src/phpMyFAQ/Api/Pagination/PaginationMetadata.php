<?php

/**
 * Pagination Metadata Generator
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

namespace phpMyFAQ\Api\Pagination;

/**
 * Class PaginationMetadata
 *
 * Generates pagination metadata for API responses including page counts, links, and status flags.
 */
class PaginationMetadata
{
    private int $total;

    private int $count;

    private int $perPage;

    private int $currentPage;

    private int $totalPages;

    private int $offset;

    private bool $hasMore;

    private bool $hasPrevious;

    private array $links;

    /**
     * Constructor
     *
     * @param int $total Total number of items across all pages
     * @param PaginationRequest $request The pagination request
     * @param string $baseUrl Base URL for generating pagination links
     * @param int $actualCount Actual count of items in the current response
     */
    public function __construct(int $total, PaginationRequest $request, string $baseUrl, int $actualCount = 0)
    {
        $this->total = max(0, $total);
        $this->perPage = $request->perPage;
        $this->currentPage = $request->page;
        $this->offset = $request->offset;
        $this->count = $actualCount > 0 ? $actualCount : min($this->perPage, max(0, $this->total - $this->offset));

        // Calculate total pages (minimum 1 even for empty results)
        $this->totalPages = $this->perPage > 0 ? max(1, (int) ceil($this->total / $this->perPage)) : 1;

        // Calculate navigation flags
        $this->hasMore = $this->currentPage < $this->totalPages;
        $this->hasPrevious = $this->currentPage > 1;

        // Generate pagination links
        $this->links = $this->generateLinks($baseUrl, $request);
    }

    /**
     * Generates pagination links (first, last, prev, next)
     *
     * @param string $baseUrl Base URL for links
     * @param PaginationRequest $request The pagination request
     * @return array Links array
     */
    private function generateLinks(string $baseUrl, PaginationRequest $request): array
    {
        // Parse existing query parameters from base URL
        $urlParts = parse_url($baseUrl);
        $basePath = $urlParts['path'] ?? '';
        $queryParams = [];

        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        }

        // Remove pagination parameters to rebuild them
        unset($queryParams['page'], $queryParams['per_page'], $queryParams['limit'], $queryParams['offset']);

        $links = [
            'first' => null,
            'last' => null,
            'prev' => null,
            'next' => null,
        ];

        // Determine pagination style for links
        if ($request->isPageBased) {
            // Page-based links
            $queryParams['per_page'] = $this->perPage;

            // First page link
            if ($this->totalPages > 0) {
                $firstParams = array_merge($queryParams, ['page' => 1]);
                $links['first'] = $basePath . '?' . http_build_query($firstParams);
            }

            // Last page link
            if ($this->totalPages > 0) {
                $lastParams = array_merge($queryParams, ['page' => $this->totalPages]);
                $links['last'] = $basePath . '?' . http_build_query($lastParams);
            }

            // Previous page link
            if ($this->hasPrevious) {
                $prevParams = array_merge($queryParams, ['page' => $this->currentPage - 1]);
                $links['prev'] = $basePath . '?' . http_build_query($prevParams);
            }

            // Next page link
            if ($this->hasMore) {
                $nextParams = array_merge($queryParams, ['page' => $this->currentPage + 1]);
                $links['next'] = $basePath . '?' . http_build_query($nextParams);
            }
        } else {
            // Offset-based links
            $queryParams['limit'] = $this->perPage;

            // First page link
            if ($this->totalPages > 0) {
                $firstParams = array_merge($queryParams, ['offset' => 0]);
                $links['first'] = $basePath . '?' . http_build_query($firstParams);
            }

            // Last page link
            if ($this->totalPages > 0) {
                $lastOffset = max(0, ($this->totalPages - 1) * $this->perPage);
                $lastParams = array_merge($queryParams, ['offset' => $lastOffset]);
                $links['last'] = $basePath . '?' . http_build_query($lastParams);
            }

            // Previous page link
            if ($this->hasPrevious) {
                $prevOffset = max(0, $this->offset - $this->perPage);
                $prevParams = array_merge($queryParams, ['offset' => $prevOffset]);
                $links['prev'] = $basePath . '?' . http_build_query($prevParams);
            }

            // Next page link
            if ($this->hasMore) {
                $nextOffset = $this->offset + $this->perPage;
                $nextParams = array_merge($queryParams, ['offset' => $nextOffset]);
                $links['next'] = $basePath . '?' . http_build_query($nextParams);
            }
        }

        return $links;
    }

    /**
     * Converts metadata to array format for API response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'count' => $this->count,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'total_pages' => $this->totalPages,
            'offset' => $this->offset,
            'has_more' => $this->hasMore,
            'has_previous' => $this->hasPrevious,
            'links' => $this->links,
        ];
    }
}
