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

use OpenApi\Attributes as OA;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController
{
    /**
     * @throws \Exception
     */
    #[OA\Get(
        path: '/api/v3.0/search',
        operationId: 'getSearch'
    )]

    #[OA\Response(
        response: 404,
        description: 'If the search returns no results',
        content: new OA\JsonContent(example: []),
    )]
    public function search(Request $request): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($faqConfig);

        $search = new Search($faqConfig);
        $search->setCategory(new Category($faqConfig));

        $faqPermission = new FaqPermission($faqConfig);
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

            $jsonResponse->setData($result);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        return $jsonResponse;
    }

    public function popular(): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $search = new Search($faqConfig);
        $result = $search->getMostPopularSearches(7, true);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $jsonResponse->setData($result);

        return $jsonResponse;
    }
}
