<?php

/**
 * The Attachment Controller for the REST API
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
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends AbstractApiController
{
    #[OA\Get(
        path: '/api/v3.2/attachments/{faqId}',
        operationId: 'getAttachments',
        description: 'Returns a paginated list of attachments for a given FAQ record ID with optional sorting.',
        tags: ['Public Endpoints'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the attachment.',
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
        description: 'Page number for pagination (1-indexed)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 1, minimum: 1),
    )]
    #[OA\Parameter(
        name: 'per_page',
        description: 'Number of items per page',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 25, maximum: 100, minimum: 1),
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Alternative to per_page for offset-based pagination',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 25, maximum: 100, minimum: 1),
    )]
    #[OA\Parameter(
        name: 'offset',
        description: 'Offset for pagination (overrides page parameter)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 0, minimum: 0),
    )]
    #[OA\Parameter(
        name: 'sort',
        description: 'Field to sort by',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['id', 'filename', 'mime_type', 'filesize', 'created']),
    )]
    #[OA\Parameter(
        name: 'order',
        description: 'Sort order',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'asc', enum: ['asc', 'desc']),
    )]
    #[OA\Response(
        response: 200,
        description: 'Paginated list of attachments with metadata.',
        content: new OA\JsonContent(example: '{
            "success": true,
            "data": [
                {
                    "filename": "attachment-1.pdf",
                    "url": "https://www.example.org/attachment/1"
                },
                {
                    "filename": "attachment-2.pdf",
                    "url": "https://www.example.org/attachment/2"
                }
            ],
            "meta": {
                "pagination": {
                    "total": 2,
                    "count": 2,
                    "per_page": 25,
                    "current_page": 1,
                    "total_pages": 1,
                    "offset": 0,
                    "has_more": false,
                    "has_previous": false,
                    "links": {
                        "first": "/api/v3.2/attachments/1?page=1&per_page=25",
                        "last": "/api/v3.2/attachments/1?page=1&per_page=25",
                        "prev": null,
                        "next": null
                    }
                },
                "sorting": {
                    "field": "filename",
                    "order": "asc"
                }
            }
        }'),
    )]
    #[OA\Response(
        response: 404,
        description: 'If the FAQ has no attachments.',
        content: new OA\JsonContent(example: '{"success": true, "data": [], "meta": {"pagination": {"total": 0}}}'),
    )]
    public function list(Request $request): JsonResponse
    {
        $recordId = (int) Filter::filterVar($request->attributes->get(key: 'faqId'), FILTER_VALIDATE_INT);

        // Get pagination and sorting parameters
        $pagination = $this->getPaginationRequest();
        $sort = $this->getSortRequest(
            allowedFields: ['id', 'filename', 'mime_type', 'filesize', 'created'],
            defaultField: 'id',
            defaultOrder: 'asc',
        );

        try {
            // Fetch paginated attachments using property access (PHP 8.4 property hooks)
            $attachments = AttachmentFactory::fetchByRecordIdPaginated(
                configuration: $this->configuration,
                recordId: $recordId,
                limit: $pagination->limit,
                offset: $pagination->offset,
                sortField: $sort->getField() ?? 'id',
                sortOrder: $sort->getOrderSql(),
            );

            // Get total count for pagination metadata
            $total = AttachmentFactory::countByRecordId($this->configuration, $recordId);

            // Return paginated response with envelope
            return $this->paginatedResponse(data: $attachments, total: $total, pagination: $pagination, sort: $sort);
        } catch (AttachmentException) {
            return $this->errorResponse(
                message: 'Failed to fetch attachments',
                code: 'ATTACHMENT_ERROR',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
