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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Controller\Api;

use Exception;
use OpenApi\Attributes as OA;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/v3.0/search',
        operationId: 'getSearch',
        tags: ['Public Endpoints']
    )]
    #[OA\Parameter(
        name: 'q',
        description: 'The search term',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the results for the query string search term.',
        content: new OA\JsonContent(example: '
        [
            {
                "id": "1",
                "lang": "en",
                "category_id": "15",
                "question": "Why are you using phpMyFAQ?",
                "answer": "Because it is cool!",
                "link": "https://www.example.org/index.php?action=faq&cat=15&id=1&artlang=en"
            }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If the search returns no results',
        content: new OA\JsonContent(example: []),
    )]
    public function search(Request $request): JsonResponse
    {
        $faqConfig = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($faqConfig);

        $search = new Search($faqConfig);
        $search->setCategory(new Category($faqConfig));

        $faqPermission = new Permission($faqConfig);
        $searchResultSet = new SearchResultSet($user, $faqPermission, $faqConfig);

        $searchString = Filter::filterVar($request->get('q'), FILTER_SANITIZE_SPECIAL_CHARS);
        $searchResults = $search->search($searchString, false);
        $searchResultSet->reviewResultSet($searchResults);

        if ($searchResultSet->getNumberOfResults() > 0) {
            $url = $faqConfig->getDefaultUrl() . 'index.php?action=faq&cat=%d&id=%d&artlang=%s';
            $result = [];
            foreach ($searchResultSet->getResultSet() as $data) {
                $data->answer = html_entity_decode(strip_tags((string) $data->answer), ENT_COMPAT, 'utf-8');
                $data->answer = Utils::makeShorterText($data->answer, 12);
                $data->link = sprintf($url, $data->category_id, $data->id, $data->lang);
                $result[] = $data;
            }

            return $this->json($result, Response::HTTP_OK);
        } else {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }
    }

    #[OA\Get(
        path: '/api/v3.0/searches/popular',
        operationId: 'getPopularSearch',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string')
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
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If the popular search returns no results.',
        content: new OA\JsonContent(example: []),
    )]
    public function popular(): JsonResponse
    {
        $configuration = Configuration::getConfigurationInstance();

        $search = new Search($configuration);
        $result = $search->getMostPopularSearches(7, true);

        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        } else {
            return $this->json($result, Response::HTTP_OK);
        }
    }
}
