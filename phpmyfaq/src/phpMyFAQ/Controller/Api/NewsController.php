<?php

/**
 * The News Controller for the REST API
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

use OpenApi\Attributes as OA;
use phpMyFAQ\News;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class NewsController extends AbstractApiController
{
    #[OA\Get(path: '/api/v3.2/news', operationId: 'getNews', tags: ['Public Endpoints'])]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the news.',
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
        default: 'datum',
        enum: ['id', 'datum', 'header', 'author_name'],
    ))]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc']),
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns paginated news for the given language provided by "Accept-Language".',
        content: new OA\JsonContent(example: '{
            "success": true,
            "data": [
                {
                    "id": 1,
                    "lang": "en",
                    "date": "2019-08-23T20:43:00+0200",
                    "header": "Hallo, World!",
                    "content": "Hello, phpMyFAQ!",
                    "authorName": "phpMyFAQ User",
                    "authorEmail": "user@example.org",
                    "active": true,
                    "allowComments": true,
                    "link": "",
                    "linkTitle": "",
                    "target": "",
                    "url": "https://www.example.org/news/1/de/hallo-phpmyfaq.html"
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
                        "first": "/api/v3.2/news?page=1&per_page=25",
                        "last": "/api/v3.2/news?page=2&per_page=25",
                        "prev": null,
                        "next": "/api/v3.2/news?page=2&per_page=25"
                    }
                },
                "sorting": {
                    "field": "datum",
                    "order": "desc"
                }
            }
        }'),
    )]
    #[Route('/api/v3.2/news', name: 'api_news_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Get pagination and sorting parameters
        $pagination = $this->getPaginationRequest();
        $sort = $this->getSortRequest(
            allowedFields: ['id', 'datum', 'header', 'author_name'],
            defaultField: 'datum',
            defaultOrder: 'desc',
        );

        $news = new News($this->configuration);

        // Get paginated news data
        $data = $news->getLatestDataPaginated(
            active: true,
            limit: $pagination->limit,
            offset: $pagination->offset,
            sortField: $sort->getField() ?? 'datum',
            sortOrder: $sort->getOrderSql(),
        );

        // Get total count
        $total = $news->countLatestData();

        return $this->paginatedResponse(data: $data, total: $total, pagination: $pagination, sort: $sort);
    }
}
