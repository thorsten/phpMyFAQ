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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-30
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController
{
    #[OA\Get(
        path: '/api/v3.0/attachments/{faqId}',
        operationId: 'getAttachments',
        description: 'Returns a list of attachments for a given FAQ record ID.',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the attachment.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'faqId',
        description: 'The FAQ record ID.',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'If the FAQ has at least one attached file.',
        content: new OA\JsonContent(example: '
        [
            {
                "filename": "attachment-1.pdf",
                "url": "https://www.example.org/index.php?action=attachment&amp;id=1"
            },
            {
                "filename": "attachment-2.pdf",
                "url": "https://www.example.org/index.php?action=attachment&amp;id=2"
            }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If the FAQ has no attachments.',
        content: new OA\JsonContent(example: []),
    )]
    public function list(Request $request): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $recordId = Filter::filterVar($request->get('recordId'), FILTER_VALIDATE_INT);
        $attachments = [];
        $result = [];
        try {
            $attachments = AttachmentFactory::fetchByRecordId($faqConfig, $recordId);
        } catch (AttachmentException) {
            $result = [];
        }

        foreach ($attachments as $attachment) {
            $result[] = [
                'filename' => $attachment->getFilename(),
                'url' => $faqConfig->getDefaultUrl() . $attachment->buildUrl(),
            ];
        }

        if ($result === []) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $jsonResponse->setData($result);

        return $jsonResponse;
    }
}
