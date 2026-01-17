<?php

/**
 * The Search Controller for the REST API
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

use Exception;
use OpenApi\Attributes as OA;
use phpMyFAQ\Category;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SearchController extends AbstractApiController
{
    /**
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/v3.2/search',
        operationId: 'getSearch',
        description: 'Returns paginated search results.',
        tags: ['Public Endpoints'],
    )]
    #[OA\Parameter(
        name: 'q',
        description: 'The search term',
        in: 'query',
        required: true,
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
        schema: new OA\Schema(type: 'string', default: 'id', enum: ['id', 'question', 'category_id']),
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc']),
    )]
    #[OA\Response(response: 200, description: 'Returns paginated search results.', content: new OA\JsonContent(
        example: '{
            "success": true,
            "data": [
                {
                    "id": "1",
                    "lang": "en",
                    "category_id": "15",
                    "question": "Why are you using phpMyFAQ?",
                    "answer": "Because it is cool!",
                    "link": "https://www.example.org/content/15/1/en/why-are-you-using-phpmyfaq.html"
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
                        "first": "/api/v3.2/search?q=test&page=1&per_page=25",
                        "last": "/api/v3.2/search?q=test&page=2&per_page=25",
                        "prev": null,
                        "next": "/api/v3.2/search?q=test&page=2&per_page=25"
                    }
                },
                "sorting": {
                    "field": "id",
                    "order": "asc"
                }
            }
        }',
    ))]
    public function search(Request $request): JsonResponse
    {
        $search = $this->container->get(id: 'phpmyfaq.search');
        $search->setCategory(new Category($this->configuration));

        $faqPermission = new Permission($this->configuration);
        $searchResultSet = new SearchResultSet($this->currentUser, $faqPermission, $this->configuration);

        $searchString = Filter::filterVar($request->query->get(key: 'q'), FILTER_SANITIZE_SPECIAL_CHARS);
        $searchResults = $search->search(searchTerm: $searchString, allLanguages: false);
        $searchResultSet->reviewResultSet($searchResults);

        // Get pagination and sorting parameters
        $pagination = $this->getPaginationRequest();
        $sort = $this->getSortRequest(
            allowedFields: ['id', 'question', 'category_id'],
            defaultField: 'id',
            defaultOrder: 'asc',
        );

        if ($searchResultSet->getNumberOfResults() > 0) {
            $allResults = [];
            foreach ($searchResultSet->getResultSet() as $data) {
                $data->answer = html_entity_decode(strip_tags((string) $data->answer), ENT_COMPAT, encoding: 'utf-8');
                $data->answer = Utils::makeShorterText(string: $data->answer, characters: 12);
                $data->link = sprintf(
                    '%sfaq/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $data->category_id,
                    $data->id,
                    $data->lang,
                    TitleSlugifier::slug($data->question),
                );
                $allResults[] = $data;
            }

            $total = count($allResults);

            // Apply sorting if needed
            if ($sort->getField()) {
                usort($allResults, function ($a, $b) use ($sort) {
                    $field = $sort->getField();
                    $aVal = $a->{$field} ?? '';
                    $bVal = $b->{$field} ?? '';
                    $result = $aVal <=> $bVal;
                    return $sort->getOrderSql() === 'DESC' ? -$result : $result;
                });
            }

            // Apply pagination
            $result = array_slice($allResults, $pagination->offset, $pagination->limit);

            return $this->paginatedResponse(
                data: array_values($result),
                total: $total,
                pagination: $pagination,
                sort: $sort,
            );
        }

        return $this->paginatedResponse(data: [], total: 0, pagination: $pagination, sort: $sort);
    }

    /**
     * @throws Exception
     */
    #[OA\Get(path: '/api/v3.2/searches/popular', operationId: 'getPopularSearch', tags: ['Public Endpoints'])]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the popular search terms for the given language provided by "Accept-Language"',
        content: new OA\JsonContent(example: '
        [
            {
                "id": 3,
                "searchterm": "mac",
                "number": "18",
                "lang": "en"
            },
            {
                "id": 7,
                "searchterm": "test",
                "number": 9,
                "lang": "en"
            }
        ]'),
    )]
    #[OA\Response(
        response: 404,
        description: 'If the popular search returns no results.',
        content: new OA\JsonContent(example: []),
    )]
    public function popular(): JsonResponse
    {
        $result = $this->container->get(id: 'phpmyfaq.search')->getMostPopularSearches(numResults: 7, withLang: true);

        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
