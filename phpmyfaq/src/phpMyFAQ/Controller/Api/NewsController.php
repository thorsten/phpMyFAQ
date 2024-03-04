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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-30
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\News;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NewsController extends AbstractController
{
    #[OA\Get(
        path: '/api/v3.0/news',
        operationId: 'getNews',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the open questions.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the news for the given language provided by "Accept-Language".',
        content: new OA\JsonContent(example: '
        [
            {
                "id": 1,
                "lang": "en",
                "date": "2019-08-23T20:43:00+0200",
                "header": "Hallo, World!",
                "content": "Hello, phpMyFAQ!",
                "authorName": "phpMyFAQ User",
                "authorEmail": "user@example.org",
                "dateStart": "0",
                "dateEnd": "99991231235959",
                "active": true,
                "allowComments": true,
                "link": "",
                "linkTitle": "",
                "target": "",
                "url": "https://www.example.org/?action=news&newsid=1&newslang=de"
              }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If no news are stored.',
        content: new OA\JsonContent(example: []),
    )]
    public function list(): JsonResponse
    {
        $configuration = Configuration::getConfigurationInstance();

        $news = new News($configuration);
        $result = $news->getLatestData(false, true, true);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
