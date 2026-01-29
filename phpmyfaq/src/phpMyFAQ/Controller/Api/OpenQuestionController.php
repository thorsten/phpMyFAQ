<?php

/**
 * The Open Questions Controller for the REST API
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
use phpMyFAQ\Question;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class OpenQuestionController extends AbstractApiController
{
    /**
     * @throws \Exception
     */
    #[OA\Get(
        path: '/api/v3.2/open-questions',
        operationId: 'getOpenQuestions',
        description: 'Returns paginated open questions.',
        tags: ['Public Endpoints'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the open questions.',
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
    #[OA\Parameter(name: 'sort', description: 'Field to sort by', in: 'query', required: false, schema: new OA\Schema(
        type: 'string',
        default: 'id',
        enum: ['id', 'username', 'created', 'categoryId'],
    ))]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc']),
    )]
    #[OA\Response(response: 200, description: 'Returns paginated open questions.', content: new OA\JsonContent(
        example: '{
            "success": true,
            "data": [
                {
                    "id": 1,
                    "lang": "en",
                    "username": "phpMyFAQ User",
                    "email": "user@example.org",
                    "categoryId": 3,
                    "question": "Foo? Bar? Baz?",
                    "created": "20190106180429",
                    "answerId": 0,
                    "isVisible": "N"
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
                        "first": "/api/v3.2/open-questions?page=1&per_page=25",
                        "last": "/api/v3.2/open-questions?page=2&per_page=25",
                        "prev": null,
                        "next": "/api/v3.2/open-questions?page=2&per_page=25"
                    }
                },
                "sorting": {
                    "field": "id",
                    "order": "asc"
                }
            }
        }',
    ))]
    #[Route('/api/v3.2/open-questions', name: 'api_open_questions', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var Question $question */
        $question = $this->container?->get(id: 'phpmyfaq.question');

        $onlyPublic = (bool) $this->configuration->get('api.onlyPublicQuestions');

        // Get pagination and sorting parameters
        $pagination = $this->getPaginationRequest();
        $sort = $this->getSortRequest(
            allowedFields: ['id', 'username', 'created', 'categoryId'],
            defaultField: 'id',
            defaultOrder: 'asc',
        );

        // Get all open questions
        $allQuestions = $question->getAll($onlyPublic);
        $total = is_countable($allQuestions) ? count($allQuestions) : 0;

        // Apply sorting if needed
        if ($sort->getField()) {
            usort($allQuestions, static function ($a, $b) use ($sort) {
                $field = $sort->getField();
                $aVal = $a[$field] ?? '';
                $bVal = $b[$field] ?? '';
                $result = $aVal <=> $bVal;
                return $sort->getOrderSql() === 'DESC' ? -$result : $result;
            });
        }

        // Apply pagination
        $result = array_slice($allQuestions, $pagination->offset, $pagination->limit);

        return $this->paginatedResponse(
            data: array_values($result),
            total: $total,
            pagination: $pagination,
            sort: $sort,
        );
    }
}
