<?php

/**
 * The Autocomplete Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoCompleteController
{
    public function search(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();

        $searchString = Filter::filterVar($request->query->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);

        $user = CurrentUser::getCurrentUser($faqConfig);
        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $category = new Category($faqConfig, $currentGroups);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->transform(0);
        $category->buildCategoryTree();

        $faqPermission = new FaqPermission($faqConfig);
        $faqSearch = new Search($faqConfig);
        $faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);

        if (!is_null($searchString)) {
            $faqSearch->setCategory($category);

            $searchResult = $faqSearch->autoComplete($searchString);

            $faqSearchResult->reviewResultSet($searchResult);

            $faqSearchHelper = new SearchHelper($faqConfig);
            $faqSearchHelper->setSearchTerm($searchString);
            $faqSearchHelper->setCategory($category);
            $faqSearchHelper->setPlurals(new Plurals());
            $response->setData(Response::HTTP_OK);
            $response->setData($faqSearchHelper->createAutoCompleteResult($faqSearchResult));
        }

        return $response;
    }
}
