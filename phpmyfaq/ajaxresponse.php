<?php
/**
 * The Ajax driven response page.
 *
 * PHP Version 5.2.0
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
 * @copyright 2007-2009 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-27
 */

//
// Prepend and start the PHP session
//
require_once 'inc/Init.php';
define('IS_VALID_PHPMYFAQ', null);
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$searchString = PMF_Filter::filterInput(INPUT_POST, 'search', FILTER_SANITIZE_STRIPPED);
$ajaxLanguage = PMF_Filter::filterInput(INPUT_POST, 'ajaxlanguage', FILTER_SANITIZE_STRING, 'en');

if (PMF_Language::isASupportedLanguage($ajaxLanguage)) {
    $LANGCODE = trim($ajaxLanguage);
    require_once 'lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
    require_once 'lang/language_en.php';
}

//Load plurals support for selected language
$plr = new PMF_Language_Plurals($PMF_LANG);

//
// Initalizing static string wrapper
//
PMF_String::init($LANGCODE);

//
// Get current user and group id - default: -1
//
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));
if (isset($user) && is_object($user)) {
    $currentUser = $user->getUserId();
    if ($user->perm instanceof PMF_Perm_PermMedium) {
        $currentGroups = $user->perm->getUserGroups($currentUser);
    } else {
        $currentGroups = array(-1);
    }
    if (0 == count($currentGroups)) {
        $currentGroups = array(-1);
    }
} else {
    $currentUser   = -1;
    $currentGroups = array(-1);
}

$category = new PMF_Category($currentUser, $currentGroups);
$category->transform(0);
$category->buildTree();

$faq = new PMF_Faq();

//
// Handle the search requests
//
if (!is_null($searchString)) {
    $result = searchEngine($db->escape_string($searchString), '%', false, true, true);
    if (strtolower($PMF_LANG['metaCharset']) != 'utf-8') {
        print utf8_encode($result);
    } else {
        print $result;
    }
}
