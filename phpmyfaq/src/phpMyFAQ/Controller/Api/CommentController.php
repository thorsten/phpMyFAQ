<?php

/**
 * The Comment Controller for the REST API
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
 * @since     2023-07-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use OpenApi\Attributes as OA;
use phpMyFAQ\Comments;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractApiController
{
    /**
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/v3.2/comments/{faqId}',
        operationId: 'getComments',
        description: 'Returns a paginated list of comments for a given FAQ record ID.',
        tags: ['Public Endpoints'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'faqId',
        description: 'The FAQ record ID.',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer'),
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
    #[OA\Parameter(name: 'sort', description: 'Field to sort by', in: 'query', required: false, schema: new OA\Schema(
        type: 'string',
        default: 'id_comment',
        enum: ['id_comment', 'id', 'usr', 'email', 'datum'],
    ))]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc']),
    )]
    #[OA\Response(response: 200, description: 'Returns paginated comments for the FAQ.', content: new OA\JsonContent(
        example: '{
            "success": true,
            "data": [
                {
                    "id": 2,
                    "recordId": 142,
                    "categoryId": null,
                    "type": "faq",
                    "username": "phpMyFAQ User",
                    "email": "user@example.org",
                    "comment": "Foo! Bar?",
                    "date": "2019-12-24T12:24:57+0100",
                    "helped": null
                }
            ],
            "meta": {
                "pagination": {
                    "total": 50,
                    "count": 25,
                    "per_page": 25,
                    "current_page": 1,
                    "total_pages": 2,
                    "links": {
                        "first": "/api/v3.2/comments/142?page=1&per_page=25",
                        "last": "/api/v3.2/comments/142?page=2&per_page=25",
                        "prev": null,
                        "next": "/api/v3.2/comments/142?page=2&per_page=25"
                    }
                },
                "sorting": {
                    "field": "id_comment",
                    "order": "asc"
                }
            }
        }',
    ))]
    #[Route(path: 'v3.2/comments/{recordId}', name: 'api.comments', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $recordId = (int) Filter::filterVar($request->attributes->get(key: 'recordId'), FILTER_VALIDATE_INT);

        /** @var Comments $comments */
        $comments = $this->container->get(id: 'phpmyfaq.comments');

        // Get pagination and sorting parameters
        $pagination = $this->getPaginationRequest();
        $sort = $this->getSortRequest(
            allowedFields: ['id_comment', 'id', 'usr', 'email', 'datum'],
            defaultField: 'id_comment',
            defaultOrder: 'asc',
        );

        // Get paginated comments
        $result = $comments->getCommentsDataPaginated(
            referenceId: $recordId,
            type: CommentType::FAQ,
            limit: $pagination->limit,
            offset: $pagination->offset,
            sortField: $sort->getField() ?? 'id_comment',
            sortOrder: $sort->getOrderSql(),
        );

        // Get total count
        $total = $comments->countComments($recordId, CommentType::FAQ);

        return $this->paginatedResponse(data: $result, total: $total, pagination: $pagination, sort: $sort);
    }
}
