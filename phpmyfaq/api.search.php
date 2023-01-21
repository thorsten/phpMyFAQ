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
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

const IS_VALID_PHPMYFAQ = null;

//
// Prepend and start the PHP session
//
require 'src/Bootstrap.php';

$searchString = Filter::filterInput(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$ajaxLanguage = Filter::filterInput(INPUT_POST, 'ajaxlanguage', FILTER_UNSAFE_RAW, 'en');
$categoryId = Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');

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
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Load plurals support for selected language
//
$plr = new Plurals();

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

//
// Get current user and group id - default: -1
//
$user = CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof CurrentUser) {
    $user = CurrentUser::getFromSession($faqConfig);
}
if (isset($user) && is_object($user)) {
    $currentUser = $user->getUserId();
    if ($user->perm instanceof MediumPermission) {
        $currentGroups = $user->perm->getUserGroups($currentUser);
    } else {
        $currentGroups = [-1];
    }
    if (0 == count($currentGroups)) {
        $currentGroups = [-1];
    }
} else {
    $user = new CurrentUser($faqConfig);
    $currentUser = -1;
    $currentGroups = [-1];
}

$category = new Category($faqConfig, $currentGroups);
$category->setUser($currentUser);
$category->setGroups($currentGroups);
$category->transform(0);
$category->buildCategoryTree();

$faqPermission = new FaqPermission($faqConfig);
$faqSearch = new Search($faqConfig);
$faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);

//
// Send headers
//
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

//
// Handle the search requests
//
if (!is_null($searchString)) {
    $faqSearch->setCategory($category);

    $searchResult = $faqSearch->autoComplete($searchString);
    $faqSearchResult->reviewResultSet($searchResult);

    $faqSearchHelper = new SearchHelper($faqConfig);
    $faqSearchHelper->setSearchTerm($searchString);
    $faqSearchHelper->setCategory($category);
    $faqSearchHelper->setPlurals($plr);

    try {
        $http->sendWithHeaders($faqSearchHelper->renderInstantResponseResult($faqSearchResult));
    } catch (JsonException $e) {
    }
}
