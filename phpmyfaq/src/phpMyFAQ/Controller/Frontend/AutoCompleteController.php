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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AutoCompleteController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('api/autocomplete')]
    public function search(Request $request): JsonResponse
    {
        $searchString = Filter::filterVar($request->query->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);

        $user = CurrentUser::getCurrentUser($this->configuration);
        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $category = new Category($this->configuration, $currentGroups);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->transform(0);
        $category->buildCategoryTree();

        $faqPermission = new Permission($this->configuration);
        $faqSearch = new Search($this->configuration);
        $searchResultSet = new SearchResultSet($user, $faqPermission, $this->configuration);

        if (!is_null($searchString)) {
            $faqSearch->setCategory($category);

            $searchResult = $faqSearch->autoComplete($searchString);

            $searchResultSet->reviewResultSet($searchResult);

            $faqSearchHelper = new SearchHelper($this->configuration);
            $faqSearchHelper->setSearchTerm($searchString);
            $faqSearchHelper->setCategory($category);
            $faqSearchHelper->setPlurals(new Plurals());

            return $this->json($faqSearchHelper->createAutoCompleteResult($searchResultSet), Response::HTTP_OK);
        }

        return $this->json([], Response::HTTP_NOT_FOUND);
    }
}
