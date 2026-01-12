<?php

/**
 * The Group Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Permission\MediumPermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class GroupController extends AbstractApiController
{
    #[OA\Get(
        path: '/api/v3.2/groups',
        operationId: 'getGroups',
        description: 'Returns paginated list of group IDs.',
        tags: ['Endpoints with Authentication'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'page',
        description: 'Page number for pagination (page-based)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 1),
    )]
    #[OA\Parameter(
        name: 'per_page',
        description: 'Items per page (page-based, max 100)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 25),
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Number of items to return (offset-based, max 100)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 25),
    )]
    #[OA\Parameter(
        name: 'offset',
        description: 'Starting offset (offset-based)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 0),
    )]
    #[OA\Parameter(
        name: 'sort',
        description: 'Field to sort by',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'group-id', enum: ['group-id']),
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc']),
    )]
    #[OA\Response(response: 200, description: 'Returns paginated list of group IDs.', content: new OA\JsonContent(
        example: '{
            "success": true,
            "data": [
                {"group-id": 1},
                {"group-id": 2}
            ],
            "meta": {
                "pagination": {
                    "total": 50,
                    "count": 25,
                    "per_page": 25,
                    "current_page": 1,
                    "total_pages": 2,
                    "links": {
                        "first": "/api/v3.2/groups?page=1&per_page=25",
                        "last": "/api/v3.2/groups?page=2&per_page=25",
                        "prev": null,
                        "next": "/api/v3.2/groups?page=2&per_page=25"
                    }
                },
                "sorting": {
                    "field": "group-id",
                    "order": "asc"
                }
            }
        }',
    ))]
    #[OA\Response(
        response: 200,
        description: 'If no groups are found, returns empty data array.',
        content: new OA\JsonContent(example: '{"success": true, "data": []}'),
    )]
    #[OA\Response(response: 401, description: 'If the user is not authenticated.')]
    public function list(): JsonResponse
    {
        $this->userIsAuthenticated();

        $mediumPermission = new MediumPermission($this->configuration);
        $allGroups = $mediumPermission->getAllGroups($this->currentUser);

        // Get pagination and sorting parameters
        $pagination = $this->getPaginationRequest();
        $sort = $this->getSortRequest(allowedFields: ['group-id'], defaultField: 'group-id', defaultOrder: 'asc');

        $total = is_countable($allGroups) ? count($allGroups) : 0;

        // Apply sorting if needed
        if ($sort->getOrderSql() === 'DESC') {
            usort($allGroups, function ($a, $b) {
                return ($b['group-id'] ?? 0) <=> ($a['group-id'] ?? 0);
            });
        }

        // Apply pagination
        $result = array_slice($allGroups, $pagination->offset, $pagination->limit);

        return $this->paginatedResponse(
            data: array_values($result),
            total: $total,
            pagination: $pagination,
            sort: $sort,
        );
    }
}
