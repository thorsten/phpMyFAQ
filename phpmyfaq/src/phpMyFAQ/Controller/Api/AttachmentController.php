<?php

declare(strict_types=1);

/**
 * The Attachment Controller for the REST API
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
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AttachmentController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException('API is not enabled');
        }
    }

    #[OA\Get(
        path: '/api/v3.1/attachments/{faqId}',
        operationId: 'getAttachments',
        description: 'Returns a list of attachments for a given FAQ record ID.',
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
    #[OA\Response(response: 200, description: 'If the FAQ has at least one attached file.', content: new OA\JsonContent(
        example: '
        [
            {
                "filename": "attachment-1.pdf",
                "url": "https://www.example.org/index.php?action=attachment&id=1"
            },
            {
                "filename": "attachment-2.pdf",
                "url": "https://www.example.org/index.php?action=attachment&id=2"
            }
        ]',
    ))]
    #[OA\Response(
        response: 404,
        description: 'If the FAQ has no attachments.',
        content: new OA\JsonContent(example: []),
    )]
    public function list(Request $request): JsonResponse
    {
        $recordId = Filter::filterVar($request->get('recordId'), FILTER_VALIDATE_INT);
        $result = [];

        try {
            $attachments = AttachmentFactory::fetchByRecordId($this->configuration, $recordId);
        } catch (AttachmentException) {
            return $this->json($result, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        foreach ($attachments as $attachment) {
            $result[] = [
                'filename' => $attachment->getFilename(),
                'url' => $this->configuration->getDefaultUrl() . $attachment->buildUrl(),
            ];
        }

        if ($result === []) {
            return $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
