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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
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
use Symfony\Component\Routing\Attribute\Route;

final class AutoCompleteController extends AbstractController
{
    public function __construct(
        private readonly Permission $faqPermission,
        private readonly Search $faqSearch,
        private readonly SearchHelper $faqSearchHelper,
        private readonly Plurals $plurals,
    ) {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'autocomplete', name: 'api.private.autocomplete', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $searchString = Filter::filterVar($request->query->get(key: 'search'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (is_null($searchString) || $searchString === '' || $searchString === '0') {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentGroups);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->transform(categoryId: 0);
        $category->buildCategoryTree();

        $searchResultSet = new SearchResultSet($this->currentUser, $this->faqPermission, $this->configuration);

        $this->faqSearch->setCategory($category);

        $searchResult = $this->faqSearch->autoComplete($searchString);

        $searchResultSet->reviewResultSet($searchResult);

        $this->faqSearchHelper->setSearchTerm($searchString);
        $this->faqSearchHelper->setCategory($category);
        $this->faqSearchHelper->setPlurals($this->plurals);

        return $this->json($this->faqSearchHelper->createAutoCompleteResult($searchResultSet), Response::HTTP_OK);
    }
}
