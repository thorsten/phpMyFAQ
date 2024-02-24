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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Configuration;
use phpMyFAQ\Question;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class OpenQuestionController
{
    #[OA\Get(
        path: '/api/v3.0/open-questions',
        operationId: 'getOpenQuestions'
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the open questions.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the open questions for the given language provided by "Accept-Language".',
        content: new OA\JsonContent(example: '
        [
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
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If no open questions are stored.',
        content: new OA\JsonContent(example: []),
    )]
    public function list(): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $question = new Question($faqConfig);
        $result = $question->getAllOpenQuestions();
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $jsonResponse->setData($result);

        return $jsonResponse;
    }
}
