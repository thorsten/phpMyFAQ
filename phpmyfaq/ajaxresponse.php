<?php

/**
 * The Ajax driven response page.
 *
 * PHP Version 5.5
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

define('IS_VALID_PHPMYFAQ', null);

//
// Prepend and start the PHP session
//
require 'inc/Bootstrap.php';

$searchString = PMF_Filter::filterInput(INPUT_GET, 'search', FILTER_SANITIZE_STRIPPED);
$ajaxLanguage = PMF_Filter::filterInput(INPUT_POST, 'ajaxlanguage', FILTER_SANITIZE_STRING, 'en');
$categoryId = PMF_Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');

$language = new PMF_Language($faqConfig);
$languageCode = $language->setLanguage(
    $faqConfig->get('main.languageDetection'),
    $faqConfig->get('main.language')
);
$faqConfig->setLanguage($language);

require_once 'lang/language_en.php';

if (PMF_Language::isASupportedLanguage($ajaxLanguage)) {
    $languageCode = trim($ajaxLanguage);
    require_once 'lang/language_'.$languageCode.'.php';
} else {
    $languageCode = 'en';
    require_once 'lang/language_en.php';
}

//Load plurals support for selected language
$plr = new PMF_Language_Plurals($PMF_LANG);

//
// Initalizing static string wrapper
//
PMF_String::init($languageCode);

//
// Get current user and group id - default: -1
//
$user = PMF_User_CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof PMF_User_CurrentUser) {
    $user = PMF_User_CurrentUser::getFromSession($faqConfig);
}
if (isset($user) && is_object($user)) {
    $current_user = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_Medium) {
        $current_groups = $user->perm->getUserGroups($current_user);
    } else {
        $current_groups = array(-1);
    }
    if (0 == count($current_groups)) {
        $current_groups = array(-1);
    }
} else {
    $user = new PMF_User_CurrentUser($faqConfig);
    $current_user = -1;
    $current_groups = array(-1);
}

$category = new PMF_Category($faqConfig, $current_groups);
$category->setUser($current_user);
$category->setGroups($current_groups);
$category->transform(0);
$category->buildTree();

$faq = new PMF_Faq($faqConfig);
$faqSearch = new PMF_Search($faqConfig);
$faqSearchResult = new PMF_Search_Resultset($user, $faq, $faqConfig);

//
// Send headers
//
$http = new PMF_Helper_Http();
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
    } catch (PMF_Search_Exception $e) {
        // @todo handle the exception
    }

    $faqSearchHelper = new PMF_Helper_Search($faqConfig);
    $faqSearchHelper->setSearchterm($searchString);
    $faqSearchHelper->setCategory($category);
    $faqSearchHelper->setPlurals($plr);

    echo $faqSearchHelper->renderInstantResponseResult($faqSearchResult);
}
