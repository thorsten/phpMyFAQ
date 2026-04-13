<?php

/**
 * The Tags Controller for the REST API
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
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TagController extends AbstractApiController
{
    public function __construct(
        private readonly Tags $tags,
    ) {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    #[OA\Get(
        path: '/api/v4.0/tags',
        operationId: 'getTags',
        description: 'Returns paginated tags.',
        tags: ['Public Endpoints'],
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
        schema: new OA\Schema(type: 'string', default: 'tagFrequency', enum: ['tagId', 'tagName', 'tagFrequency']),
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc']),
    )]
    #[OA\Response(response: 200, description: 'Returns paginated tags.', content: new OA\JsonContent(example: [
        'success' => true,
        'data' => [
            ['tagId' => 4, 'tagName' => 'phpMyFAQ', 'tagFrequency' => 3],
            ['tagId' => 1, 'tagName' => 'PHP 8', 'tagFrequency' => 2],
        ],
        'meta' => [
            'pagination' => [
                'total' => 50,
                'count' => 25,
                'per_page' => 25,
                'current_page' => 1,
                'total_pages' => 2,
                'links' => [
                    'first' => '/api/v4.0/tags?page=1&per_page=25',
                    'last' => '/api/v4.0/tags?page=2&per_page=25',
                    'prev' => null,
                    'next' => '/api/v4.0/tags?page=2&per_page=25',
                ],
            ],
            'sorting' => [
                'field' => 'tagFrequency',
                'order' => 'desc',
            ],
        ],
    ]))]
    #[Route('/api/v4.0/tags', name: 'api.tags.list', methods: ['GET'])]
    public function list(?Request $request = null): JsonResponse
    {
        $request ??= Request::createFromGlobals();

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);
        $this->tags->setUser($currentUser);
        $this->tags->setGroups($currentGroups);

        // Get pagination and sorting parameters
        $pagination = $this->getPaginationRequest($request);
        $sort = $this->getSortRequest(
            $request,
            allowedFields: ['tagId', 'tagName', 'tagFrequency'],
            defaultField: 'tagFrequency',
            defaultOrder: 'desc',
        );

        // Get all tags (we'll use a high limit to get all tags)
        $allTags = $this->tags->getPopularTagsAsArray(limit: 1000);
        $total = is_countable($allTags) ? count($allTags) : 0;

        // Apply sorting if needed
        if ($sort->getField()) {
            usort($allTags, static function ($a, $b) use ($sort) {
                $field = $sort->getField();
                $aVal = $a[$field] ?? '';
                $bVal = $b[$field] ?? '';
                $result = $aVal <=> $bVal;
                return $sort->getOrderSql() === 'DESC' ? -$result : $result;
            });
        }

        // Apply pagination
        $result = array_slice($allTags, $pagination->offset, $pagination->limit);

        return $this->paginatedResponse(
            $request,
            data: array_values($result),
            total: $total,
            pagination: $pagination,
            options: new PaginatedResponseOptions(sort: $sort),
        );
    }
}
