<?php

/**
 * The Autocomplete Search API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-27
 */

use phpMyFAQ\Category;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

const IS_VALID_PHPMYFAQ = null;

//
// Prepend and start the PHP session
//
require 'src/Bootstrap.php';

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$searchString = Filter::filterVar($request->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);
$categoryId = Filter::filterVar($request->get('searchcategory'), FILTER_VALIDATE_INT, '%');
$ajaxLanguage = Filter::filterVar($request->request->get('ajaxlanguage'), FILTER_SANITIZE_SPECIAL_CHARS, 'en');

//
// Get language (default: english)
//
$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($faqLangCode);
} catch (Exception $e) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    $response->setData(['error' => $e->getMessage()]);
}

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

//
// Get current user and group id - default: -1
//
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

//
// Handle the search requests
//
if (!is_null($searchString)) {
    $faqSearch->setCategory($category);

    try {
        $searchResult = $faqSearch->autoComplete($searchString);

        $faqSearchResult->reviewResultSet($searchResult);

        $faqSearchHelper = new SearchHelper($faqConfig);
        $faqSearchHelper->setSearchTerm($searchString);
        $faqSearchHelper->setCategory($category);
        $faqSearchHelper->setPlurals(new Plurals());
        $response->setData(Response::HTTP_OK);
        $response->setData($faqSearchHelper->createAutoCompleteResult($faqSearchResult));
    } catch (Exception $e) {
        $response->setData(Response::HTTP_BAD_REQUEST);
        $faqConfig->getLogger()->error('Search exception: ' . $e->getMessage());
    }

    $response->send();
}
