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
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\News;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class NewsController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException(challenge: 'API is not enabled');
        }
    }

    #[OA\Get(path: '/api/v3.1/news', operationId: 'getNews', tags: ['Public Endpoints'])]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the open questions.',
        schema: new OA\Schema(type: 'string'),
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
        ]'),
    )]
    #[OA\Response(response: 404, description: 'If no news are stored.', content: new OA\JsonContent(example: []))]
    public function list(): JsonResponse
    {
        $news = new News($this->configuration);
        $result = $news->getLatestData(showArchive: false, active: true, forceConfLimit: true);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
