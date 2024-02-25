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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Configuration;
use phpMyFAQ\Tags;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TagController
{
    #[OA\Get(
        path: '/api/v3.0/tags',
        operationId: 'getTags',
        tags: ['Public Endpoints']
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the tags for the given language provided by "Accept-Language".',
        content: new OA\JsonContent(example: '[
            {"tagId": 4, "tagName": "phpMyFAQ", "tagFrequency": 3 },
            {"tagId": 1, "tagName": "PHP 8", "tagFrequency": 2 }
        ]'),
    )]
    #[OA\Response(
        response: 404,
        description: 'If no tags are stored.',
        content: new OA\JsonContent(example: []),
    )]
    public function list(): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $tags = new Tags($faqConfig);
        $result = $tags->getPopularTagsAsArray(16);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $jsonResponse->setData($result);

        return $jsonResponse;
    }
}
