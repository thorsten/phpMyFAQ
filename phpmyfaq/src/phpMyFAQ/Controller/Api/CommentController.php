<?php

declare(strict_types=1);

/**
 * The Comment Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-30
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Comments;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CommentController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException('API is not enabled');
        }
    }

    #[OA\Get(
        path: '/api/v3.1/comments/{faqId}',
        operationId: 'getComments',
        description: 'Returns a list of comments for a given FAQ record ID.',
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
    #[OA\Response(response: 200, description: 'If the FAQ has at least one comment.', content: new OA\JsonContent(
        example: '
        [
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
        ]',
    ))]
    #[OA\Response(response: 404, description: 'If the FAQ has no comments.', content: new OA\JsonContent(example: []))]
    public function list(Request $request): JsonResponse
    {
        $recordId = Filter::filterVar($request->get('recordId'), FILTER_VALIDATE_INT);

        $comments = new Comments($this->configuration);
        $result = $comments->getCommentsData($recordId, CommentType::FAQ);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
