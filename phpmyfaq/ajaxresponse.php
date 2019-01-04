<?php

/**
 * The Ajax driven response page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-27
 */

use phpMyFAQ\Category;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Strings;
use phpMyFAQ\Search;
use phpMyFAQ\Search\Exception;
use phpMyFAQ\Search\Resultset;
use phpMyFAQ\User\CurrentUser;

define('IS_VALID_PHPMYFAQ', null);

//
// Prepend and start the PHP session
//
require 'src/Bootstrap.php';

$searchString = Filter::filterInput(INPUT_GET, 'search', FILTER_SANITIZE_STRIPPED);
$ajaxLanguage = Filter::filterInput(INPUT_POST, 'ajaxlanguage', FILTER_SANITIZE_STRING, 'en');
$categoryId = Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');

$language = new Language($faqConfig);
$languageCode = $language->setLanguage(
    $faqConfig->get('main.languageDetection'),
    $faqConfig->get('main.language')
);
$faqConfig->setLanguage($language);

require_once 'lang/language_en.php';

if (Language::isASupportedLanguage($ajaxLanguage)) {
    $languageCode = trim($ajaxLanguage);
    require_once 'lang/language_'.$languageCode.'.php';
} else {
    $languageCode = 'en';
    require_once 'lang/language_en.php';
}

//Load plurals support for selected language
$plr = new Plurals($PMF_LANG);

//
// Initializing static string wrapper
//
Strings::init($languageCode);

//
// Get current user and group id - default: -1
//
$user = CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof CurrentUser) {
    $user = CurrentUser::getFromSession($faqConfig);
}
if (isset($user) && is_object($user)) {
    $current_user = $user->getUserId();
    if ($user->perm instanceof MediumPermission) {
        $current_groups = $user->perm->getUserGroups($current_user);
    } else {
        $current_groups = array(-1);
    }
    if (0 == count($current_groups)) {
        $current_groups = array(-1);
    }
} else {
    $user = new CurrentUser($faqConfig);
    $current_user = -1;
    $current_groups = array(-1);
}

$category = new Category($faqConfig, $current_groups);
$category->setUser($current_user);
$category->setGroups($current_groups);
$category->transform(0);
$category->buildTree();

$faq = new Faq($faqConfig);
$faqSearch = new Search($faqConfig);
$faqSearchResult = new Resultset($user, $faq, $faqConfig);

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

    try {
        $searchResult = $faqSearch->autoComplete($searchString);
        $faqSearchResult->reviewResultset($searchResult);
    } catch (Exception $e) {
        $http->sendWithHeaders($e->getMessage());
    }

    $faqSearchHelper = new SearchHelper($faqConfig);
    $faqSearchHelper->setSearchterm($searchString);
    $faqSearchHelper->setCategory($category);
    $faqSearchHelper->setPlurals($plr);

    $http->sendWithHeaders($faqSearchHelper->renderInstantResponseResult($faqSearchResult));
}
