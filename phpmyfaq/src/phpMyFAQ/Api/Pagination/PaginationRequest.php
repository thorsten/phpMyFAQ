<?php

/**
 * Pagination Request Parser
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

use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PaginationRequest
 *
 * Parses and validates pagination query parameters from HTTP requests.
 * Supports both page-based (page + per_page) and offset-based (limit + offset) pagination.
 */
class PaginationRequest
{
    public int $limit {
        get {
            return $this->limit;
        }
    }

    public int $offset {
        get {
            return $this->offset;
        }
    }

    public int $page {
        get {
            return $this->page;
        }
    }

    public int $perPage {
        get {
            return $this->perPage;
        }
    }

    public bool $isPageBased {
        get {
            return $this->isPageBased;
        }
    }

    public bool $isOffsetBased {
        get {
            return $this->isOffsetBased;
        }
    }

    /**
     * Constructor
     *
     * @param int $limit Items per page
     * @param int $offset Starting offset
     * @param int $page Current page number
     * @param int $perPage Items per page (alias for limit)
     * @param bool $isPageBased Whether page-based pagination was used
     * @param bool $isOffsetBased Whether offset-based pagination was used
     */
    private function __construct(
        int $limit,
        int $offset,
        int $page,
        int $perPage,
        bool $isPageBased,
        bool $isOffsetBased,
    ) {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->isPageBased = $isPageBased;
        $this->isOffsetBased = $isOffsetBased;
    }

    /**
     * Creates a PaginationRequest from a Symfony Request object
     *
     * @param Request $request The HTTP request
     * @param int $defaultPerPage Default items per page
     * @param int $maxPerPage Maximum allowed items per page
     * @return self
     */
    public static function fromRequest(Request $request, int $defaultPerPage = 25, int $maxPerPage = 100): self
    {
        // Parse query parameters
        $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, default: 1);
        $perPage = Filter::filterVar($request->query->get('per_page'), FILTER_VALIDATE_INT, default: null);
        $limit = Filter::filterVar($request->query->get('limit'), FILTER_VALIDATE_INT, default: null);
        $offset = Filter::filterVar($request->query->get('offset'), FILTER_VALIDATE_INT, default: null);

        // Validate page number
        if ($page < 1) {
            $page = 1;
        }

        // Determine pagination style and values
        $isOffsetBased = false;
        $isPageBased = false;

        // Priority: explicit offset > page-based > defaults
        if ($offset !== null) {
            // Offset-based pagination
            $isOffsetBased = true;
            $offset = max(0, $offset); // Ensure non-negative
            $limit ??= $perPage ?? $defaultPerPage;
            $limit = self::validateLimit($limit, $maxPerPage);
            $perPage = $limit;
            // Calculate page from offset
            $page = (int) floor($offset / $limit) + 1;
        } else {
            // Page-based pagination
            $isPageBased = true;
            $perPage ??= $limit ?? $defaultPerPage;
            $perPage = self::validateLimit($perPage, $maxPerPage);
            $limit = $perPage;
            // Calculate offset from page
            $offset = ($page - 1) * $perPage;
        }

        return new self($limit, $offset, $page, $perPage, $isPageBased, $isOffsetBased);
    }

    /**
     * Validates and constrains the limit value
     *
     * @param int $limit The limit to validate
     * @param int $maxPerPage Maximum allowed limit
     * @return int Validated limit
     */
    private static function validateLimit(int $limit, int $maxPerPage): int
    {
        // Ensure the limit is at least 1
        $limit = max(1, $limit);

        // Ensure limit doesn't exceed maximum
        return min($limit, $maxPerPage);
    }
}
