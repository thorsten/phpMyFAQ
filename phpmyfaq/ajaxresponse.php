<?php
/**
 * The Ajax driven response page.
 *
 * PHP Version 5.2
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   Ajax 
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-27
 */

define('IS_VALID_PHPMYFAQ', null);

//
// Prepend and start the PHP session
//
require_once 'inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$searchString = PMF_Filter::filterInput(INPUT_POST, 'search', FILTER_SANITIZE_STRIPPED);
$ajaxLanguage = PMF_Filter::filterInput(INPUT_POST, 'ajaxlanguage', FILTER_SANITIZE_STRING, 'en');
$categoryId   = PMF_Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');

$language     = new PMF_Language();
$languageCode = $language->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));
require_once 'lang/language_en.php';

if (PMF_Language::isASupportedLanguage($ajaxLanguage)) {
    $languageCode = trim($ajaxLanguage);
    require_once 'lang/language_' . $languageCode . '.php';
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
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('security.ipCheck'));
if (isset($user) && is_object($user)) {
    $current_user = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_PermMedium) {
        $current_groups = $user->perm->getUserGroups($current_user);
    } else {
        $current_groups = array(-1);
    }
    if (0 == count($current_groups)) {
        $current_groups = array(-1);
    }
} else {
    $user           = new PMF_User_CurrentUser();
    $current_user   = -1;
    $current_groups = array(-1);
}

$category = new PMF_Category($current_user, $current_groups);
$category->transform(0);
$category->buildTree();

$faq             = new PMF_Faq();
$faqSearch       = new PMF_Search($db, $language);
$faqSearchResult = new PMF_Search_Resultset($user, $faq);

//
// Handle the search requests
//
if (!is_null($searchString)) {
    $faqSearch->setCategory($categoryId);
    $searchResult = $faqSearch->search($searchString, false);
    
    $faqSearchResult->reviewResultset($searchResult);
    
    $faqSearchHelper = PMF_Helper_Search::getInstance();
    $faqSearchHelper->setSearchterm($searchString);
    $faqSearchHelper->setCategory($category);
    $faqSearchHelper->setPlurals($plr);
    
    print $faqSearchHelper->renderInstantResponseResult($faqSearchResult);
}
