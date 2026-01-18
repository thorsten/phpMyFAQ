<?php

/**
 * The Glossary Controller for the REST API
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
 * @since     2026-01-03
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use Exception;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GlossaryController extends AbstractApiController
{
    /**
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/v3.2/glossary',
        operationId: 'getGlossary',
        description: 'Returns paginated glossary items.',
        tags: ['Public Endpoints'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the glossary items.',
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
        schema: new OA\Schema(type: 'string', default: 'item', enum: ['id', 'item', 'definition']),
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc']),
    )]
    #[OA\Response(response: 200, description: 'Returns paginated glossary items.', content: new OA\JsonContent(
        example: '{
            "success": true,
            "data": [
                {"id": 1, "language": "en", "item": "API", "definition": "Application Programming Interface"},
                {"id": 2, "language": "en", "item": "FAQ", "definition": "Frequently Asked Questions"}
            ],
            "meta": {
                "pagination": {
                    "total": 50,
                    "count": 25,
                    "per_page": 25,
                    "current_page": 1,
                    "total_pages": 2,
                    "links": {
                        "first": "/api/v3.2/glossary?page=1&per_page=25",
                        "last": "/api/v3.2/glossary?page=2&per_page=25",
                        "prev": null,
                        "next": "/api/v3.2/glossary?page=2&per_page=25"
                    }
                },
                "sorting": {
                    "field": "item",
                    "order": "asc"
                }
            }
        }',
    ))]
    public function list(Request $request): JsonResponse
    {
        $glossary = $this->container->get(id: 'phpmyfaq.glossary');
        $language = $this->container->get(id: 'phpmyfaq.language');
        $currentLanguage = $language->setLanguageByAcceptLanguage();

        if ($currentLanguage !== false) {
            $glossary->setLanguage($currentLanguage);
        }

        // Get pagination and sorting parameters
        $pagination = $this->getPaginationRequest();
        $sort = $this->getSortRequest(
            allowedFields: ['id', 'item', 'definition'],
            defaultField: 'item',
            defaultOrder: 'asc',
        );

        // Get all glossary items
        $allItems = $glossary->fetchAll();
        $total = is_countable($allItems) ? count($allItems) : 0;

        // Apply sorting if needed
        if ($sort->getField()) {
            usort($allItems, function ($a, $b) use ($sort) {
                $field = $sort->getField();
                $aVal = $a[$field] ?? '';
                $bVal = $b[$field] ?? '';
                $result = $aVal <=> $bVal;
                return $sort->getOrderSql() === 'DESC' ? -$result : $result;
            });
        }

        // Apply pagination
        $result = array_slice($allItems, $pagination->offset, $pagination->limit);

        return $this->paginatedResponse(
            data: array_values($result),
            total: $total,
            pagination: $pagination,
            sort: $sort,
        );
    }
}
